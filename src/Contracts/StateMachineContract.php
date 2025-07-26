<?php

namespace JobMetric\StateMachine\Contracts;

interface StateMachineContract
{
    /**
     * Register all allowed state transitions for the model.
     *
     * This method should be implemented in the model to define all transitions
     * using the `allowTransition()` method. You may also define conditional
     * transitions or logic-based flows here.
     *
     * Example:
     * ```php
     * public function stateMachineAllowTransition(): void
     * {
     *     $this->allowTransition('status', 'pending', 'processing');
     *     $this->allowTransition('status', 'processing', 'completed');
     *
     *     if ($this->hasSpecialPermission()) {
     *         $this->allowTransition('status', 'cancelled', 'processing');
     *     }
     * }
     * ```
     *
     * @return void
     */
    public function stateMachineAllowTransition(): void;
}
