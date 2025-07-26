<?php

namespace JobMetric\StateMachine\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Abstract base class for defining state machine hooks for model transitions.
 *
 * Extend this class to implement specific logic that should run
 * before and after a model's state is changed using a state machine.
 *
 * The implementing class should be tied to a specific model and
 * state transition (or common transition logic).
 *
 * Example usage:
 *   - Validate business rules before state change
 *   - Send notifications after transition
 *   - Log audit trails for state changes
 *
 * Usage pattern:
 *   $machine = new OrderStatusApprovedStateMachine($model);
 *   $machine->before($model, 'pending', 'approved');
 *   // update state
 *   $machine->after($model, 'pending', 'approved');
 */
abstract class StateMachine
{
    /**
     * The Eloquent model instance that is being transitioned.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Instantiate the state machine with the given model.
     *
     * @param Model $model The Eloquent model undergoing state transition.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Hook executed *before* the model transitions from one state to another.
     *
     * Use this to perform any validation, permission checks,
     * data preparation, or business logic that should run
     * before the actual state change is committed.
     *
     * If this method throws an exception, the transition will be aborted.
     *
     * @param Model $model The model instance being transitioned.
     * @param mixed $from  The current state value before transition.
     * @param mixed $to    The target state value after transition.
     *
     * @return void
     */
    abstract public function before(Model $model, mixed $from, mixed $to): void;

    /**
     * Hook executed *after* the model has successfully transitioned.
     *
     * Use this to trigger side effects such as notifications, logging,
     * cache clearing, or syncing with external systems.
     *
     * This method is only called if the transition was successful.
     *
     * @param Model $model The model instance that was transitioned.
     * @param mixed $from  The previous state value before transition.
     * @param mixed $to    The new state value after transition.
     *
     * @return void
     */
    abstract public function after(Model $model, mixed $from, mixed $to): void;
}
