<?php

namespace JobMetric\StateMachine\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JobMetric\StateMachine\StateMachineService
 */
class JStateMachine extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'StateMachineService';
    }
}
