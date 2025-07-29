<?php

namespace JobMetric\StateMachine;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JobMetric\StateMachine\Contracts\StateMachine;
use JobMetric\StateMachine\Contracts\StateMachineContract;
use JobMetric\StateMachine\Events\StateTransitioned;
use JobMetric\StateMachine\Exceptions\InvalidStateMachineClassException;
use JobMetric\StateMachine\Exceptions\ModelStateMachineInterfaceNotFoundException;
use JobMetric\StateMachine\Exceptions\StateMachineNotAllowTransitionException;
use Throwable;

/**
 * Trait HasStateMachine
 *
 * Adds state machine logic to Eloquent models. It enables defining valid state transitions,
 * and executes pre,post transition hooks via optional StateMachine classes.
 *
 * Requirements:
 * - Model must implement StateMachineContract
 * - Define transitions using allowTransition() inside stateMachineAllowTransition() method
 */
trait HasStateMachine
{
    /**
     * Stores allowed transitions per field.
     *
     * Format:
     * [
     *   'field_name' => [
     *     [
     *       'from' => 'draft',
     *       'to' => 'published',
     *       'name' => 'DraftToPublished',
     *       'condition' => Closure|null,
     *       'class' => 'App\StateMachines\...StateMachine' (optional)
     *     ],
     *     ...
     *   ],
     *   ...
     * ]
     *
     * @var array<string, array<int, array{from: mixed, to: mixed, name: string, condition: ?Closure, class?: string}>>
     */
    protected array $stateMachines = [];

    /**
     * Whether the state transitions have been initialized.
     *
     * @var bool
     */
    private bool $transitionsInitialized = false;

    /**
     * Boot the HasStateMachine trait.
     *
     * Verifies model implements required interface.
     *
     * @return void
     * @throws ModelStateMachineInterfaceNotFoundException
     */
    public static function bootHasStateMachine(): void
    {
        if (!is_subclass_of(static::class, StateMachineContract::class)) {
            throw new ModelStateMachineInterfaceNotFoundException(static::class);
        }
    }

    /**
     * Resolve the namespace for locating StateMachine classes.
     *
     * Can be overridden in the model to change lookup location.
     *
     * @return string
     */
    public function resolveStateMachineNamespace(): string
    {
        $appNamespace = trim(appNamespace(), "\\");

        return "$appNamespace\\StateMachines";
    }

    /**
     * Register a valid state transition for a field.
     *
     * Automatically detects if a specific StateMachine class exists and stores it.
     *
     * @param string $field
     * @param mixed $from
     * @param mixed $to
     * @param Closure|null $condition Optional condition closure that returns boolean.
     *
     * @return void
     */
    public function allowTransition(string $field, mixed $from, mixed $to, ?Closure $condition = null): void
    {
        $selfClass = class_basename(static::class);
        $baseNamespace = $this->resolveStateMachineNamespace();

        $stateName = Str::studly($from) . 'To' . Str::studly($to);
        $className = "$baseNamespace\\$selfClass\\" . $selfClass . Str::studly($field) . $stateName . "StateMachine";

        $transition = [
            'from' => $from,
            'to' => $to,
            'name' => $stateName,
            'condition' => $condition
        ];

        if (class_exists($className)) {
            $transition['class'] = $className;
        }

        $this->stateMachines[$field][] = $transition;
    }

    /**
     * Initializes transitions only once per instance lifecycle.
     *
     * @return void
     */
    protected function initializeStateMachineTransitions(): void
    {
        if ($this->transitionsInitialized) {
            return;
        }

        if (method_exists($this, 'stateMachineAllowTransition')) {
            $this->stateMachineAllowTransition();
        }

        $this->transitionsInitialized = true;
    }

    /**
     * Transition the given model field from current state to the desired state.
     *
     * It will:
     * - Check for valid transition in the defined state machine map.
     * - Resolve and call the common and specific transition classes.
     * - Call `before` hooks from both common and specific transition handlers.
     * - Update the field value and persist it.
     * - Call `after` hooks from both common and specific transition handlers.
     *
     * @param mixed $to The target state to transition to.
     * @param string $field The name of the field to transition (default: 'status').
     *
     * @return bool Returns true if the transition was successful.
     *
     * @throws StateMachineNotAllowTransitionException If transition is not allowed.
     * @throws InvalidStateMachineClassException If a resolved class is not a valid state machine.
     * @throws Throwable If save operation fails.
     */
    public function transitionTo(mixed $to, string $field = 'status'): bool
    {
        if (!array_key_exists($field, $this->getAttributes())) {
            throw new InvalidArgumentException("Field [$field] does not exist on model.");
        }

        $this->initializeStateMachineTransitions();

        $currentState = $this->{$field};
        $transitionData = $this->findTransition($field, $currentState, $to);

        if ($transitionData === null) {
            throw new StateMachineNotAllowTransitionException(static::class, $field, $currentState, $to);
        }

        $selfClass = class_basename(static::class);
        $baseNamespace = $this->resolveStateMachineNamespace();
        $commonClassName = "$baseNamespace\\$selfClass\\$selfClass" . Str::studly($field) . "CommonStateMachine";

        $transitionObject = null;
        if (isset($transitionData['class']) && class_exists($transitionData['class'])) {
            if (!is_subclass_of($transitionData['class'], StateMachine::class)) {
                throw new InvalidStateMachineClassException($transitionData['class']);
            }

            $transitionObject = new $transitionData['class']($this);
        }

        $commonObject = null;
        if (class_exists($commonClassName)) {
            if (!is_subclass_of($commonClassName, StateMachine::class)) {
                throw new InvalidStateMachineClassException($commonClassName);
            }

            $commonObject = new $commonClassName($this);
        }

        $commonObject?->before($this, $currentState, $to);
        $transitionObject?->before($this, $currentState, $to);

        $this->{$field} = $to;

        // If saving fails, it will throw exception and prevent running "after"
        $this->saveOrFail();

        event(new StateTransitioned($this, $field, $currentState, $to));

        $transitionObject?->after($this, $currentState, $to);
        $commonObject?->after($this, $currentState, $to);

        return true;
    }

    /**
     * Find a valid transition entry for the given field and states.
     *
     * @param string $field
     * @param mixed $from
     * @param mixed $to
     *
     * @return array|null
     */
    private function findTransition(string $field, mixed $from, mixed $to): ?array
    {
        if (!isset($this->stateMachines[$field])) {
            return null;
        }

        foreach ($this->stateMachines[$field] as $transition) {
            if (($transition['from'] ?? null) == $from && ($transition['to'] ?? null) == $to) {
                if (isset($transition['condition']) && $transition['condition'] instanceof Closure) {
                    if (!call_user_func($transition['condition'], $this)) {
                        return null;
                    }
                }

                return $transition;
            }
        }

        return null;
    }

    /**
     * Check if the model can transition from current state to $to on given $field.
     *
     * @param mixed $to Target state to check transition to.
     * @param string $field Field name holding the state, default 'status'.
     *
     * @return bool True if the transition is allowed and condition (if exists) passes, false otherwise.
     */
    public function canTransitionTo(mixed $to, string $field = 'status'): bool
    {
        if (!array_key_exists($field, $this->getAttributes())) {
            return false;
        }

        $this->initializeStateMachineTransitions();

        $currentState = $this->{$field};

        // Search for valid transition from current state to $to
        if (!isset($this->stateMachines[$field])) {
            return false;
        }

        foreach ($this->stateMachines[$field] as $transition) {
            if (($transition['from'] ?? null) == $currentState && ($transition['to'] ?? null) == $to) {
                if (isset($transition['condition']) && $transition['condition'] instanceof Closure) {
                    if (!call_user_func($transition['condition'], $this)) {
                        return false;
                    }
                }
                return true;
            }
        }

        return false;
    }
}
