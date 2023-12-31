<?php

namespace JobMetric\StateMachine;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JobMetric\StateMachine\Contracts\StateMachine;
use JobMetric\StateMachine\Exceptions\ModelStateMachineInterfaceNotFoundException;
use JobMetric\StateMachine\Exceptions\StateMachineNotAllowTransitionException;
use Throwable;

/**
 * @method static retrieved(\Closure $param)
 * @method stateMachineAllowTransition()
 */
trait HasStateMachine
{
    protected array $stateMachines = [];

    /**
     * boot has state machine
     *
     * @return void
     */
    public static function bootHasStateMachine(): void
    {
        static::retrieved(function ($model) {
            if (!in_array("JobMetric\StateMachine\Contracts\StateMachineContract", class_implements($model))) {
                throw new ModelStateMachineInterfaceNotFoundException($model::class);
            }
        });
    }

    /**
     * allow transition field
     *
     * @param string $field field name
     * @param mixed $from from state
     * @param mixed $to to state
     * @param callable|string $callable callback function
     *
     * @return void
     */
    public function allowTransition(string $field, mixed $from, mixed $to, callable|string $callable = 'Default'): void
    {
        if ($callable == 'Default') {
            $this->stateMachines[$field][] = [$from, $to];
        } else {
            $this->stateMachines[$field][] = [$from, $to, $callable];
        }
    }

    /**
     * transition to state
     *
     * @param mixed $to
     * @param string $field
     *
     * @return bool
     * @throws Throwable
     */
    public function transitionTo(mixed $to, string $field = 'status'): bool
    {
        $appNamespace = trim(appNamespace(), "\\");

        /**
         * @var $this Model
         */
        $this->stateMachineAllowTransition();

        $currentState = $this->{$field};

        $checkTransitions = $this->validationTransitions($field, $currentState, $to);

        if ($checkTransitions === false) {
            throw new StateMachineNotAllowTransitionException(self::class, $field, $currentState, $to);
        }

        $classPart = explode('\\', self::class);
        $selfClass = end($classPart);

        /**
         * @var $object StateMachine
         */
        $default_object = null;
        $object = null;

        $className = "\\$appNamespace\\StateMachines\\$selfClass\\" . $selfClass . Str::studly($field) . $checkTransitions . "StateMachine";
        if (class_exists($className)) {
            $object = new $className($this);
        }

        $className = "\\$appNamespace\\StateMachines\\$selfClass\\" . $selfClass . Str::studly($field) . "DefaultStateMachine";
        if (class_exists($className)) {
            $default_object = new $className($this);
        }

        if (!is_null($default_object)) {
            $default_object->before($currentState, $to);
        }

        if (!is_null($object)) {
            $object->before($currentState, $to);
        }

        $this->{$field} = $to;
        $this->save();

        if (!is_null($object)) {
            $object->after($currentState, $to);
        }
        if (!is_null($default_object)) {
            $default_object->after($currentState, $to);
        }

        return true;
    }

    /**
     * check validation transition
     *
     * @param string $field
     * @param mixed $from
     * @param mixed $to
     *
     * @return bool|callable|string
     */
    private function validationTransitions(string $field, mixed $from, mixed $to): bool|callable|string
    {
        foreach ($this->stateMachines[$field] as $transition) {
            if ($transition[0] == $from && $transition[1] == $to) {
                return $transition[2] ?? 'Default';
            }
        }

        return false;
    }
}
