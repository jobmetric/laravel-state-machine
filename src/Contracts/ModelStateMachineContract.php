<?php

namespace JobMetric\StateMachine\Contracts;

interface ModelStateMachineContract
{
    public function stateMachineAllowTransition(): void;
}
