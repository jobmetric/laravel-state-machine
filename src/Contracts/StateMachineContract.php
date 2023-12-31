<?php

namespace JobMetric\StateMachine\Contracts;

interface StateMachineContract
{
    public function stateMachineAllowTransition(): void;
}
