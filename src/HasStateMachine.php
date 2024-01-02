<?php

namespace JobMetric\StateMachine;

use Illuminate\Support\Str;
use JobMetric\StateMachine\Contracts\StateMachine;
use JobMetric\StateMachine\Exceptions\ModelStateMachineInterfaceNotFoundException;
use JobMetric\StateMachine\Exceptions\StateMachineNotAllowTransitionException;
use Throwable;

/**
 * @method stateMachineAllowTransition()
 */
trait HasStateMachine
{
    protected array $stateMachines = [];

    /**
     * boot has state machine
     *
     * @return void
     * @throws ModelStateMachineInterfaceNotFoundException
     */
    public static function bootHasStateMachine(): void
    {
        if (!in_array("JobMetric\StateMachine\Contracts\StateMachineContract", class_implements(self::class))) {
            throw new ModelStateMachineInterfaceNotFoundException(self::class);
        }
    }

    /**
     * allow transition field
     *
     * @param string $field field name
     * @param mixed $from from state
     * @param mixed $to to state
     *
     * @return void
     */
    public function allowTransition(string $field, mixed $from, mixed $to): void
    {
        $appNamespace = trim(appNamespace(), "\\");

        $classPart = explode('\\', self::class);
        $selfClass = end($classPart);

        $stateName = Str::studly($from) . 'To' . Str::studly($to);

        $className = "\\$appNamespace\\StateMachines\\$selfClass\\" . $selfClass . Str::studly($field) . $stateName . "StateMachine";
        if (class_exists($className)) {
            $this->stateMachines[$field][] = [$from, $to, $stateName];
        } else {
            $this->stateMachines[$field][] = [$from, $to];
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
        $common_object = null;
        $object = null;

        $className = "\\$appNamespace\\StateMachines\\$selfClass\\" . $selfClass . Str::studly($field) . $checkTransitions . "StateMachine";
        if (class_exists($className)) {
            $object = new $className($this);
        }

        $className = "\\$appNamespace\\StateMachines\\$selfClass\\" . $selfClass . Str::studly($field) . "CommonStateMachine";
        if (class_exists($className)) {
            $common_object = new $className($this);
        }

        if (!is_null($common_object)) {
            $common_object->before($this, $currentState, $to);
        }

        if (!is_null($object)) {
            $object->before($this, $currentState, $to);
        }

        $this->{$field} = $to;
        $this->save();

        if (!is_null($object)) {
            $object->after($this, $currentState, $to);
        }
        if (!is_null($common_object)) {
            $common_object->after($this, $currentState, $to);
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
                return $transition[2] ?? 'Common';
            }
        }

        return false;
    }
}
