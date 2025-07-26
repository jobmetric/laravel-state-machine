<?php

namespace JobMetric\StateMachine\Exceptions;

use Exception;
use Throwable;

class InvalidStateMachineClassException extends Exception
{
    public function __construct(string $className, int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct("Invalid state machine class: {$className}. It must implement the JobMetric\StateMachine\Contracts\StateMachine interface.", $code, $previous);
    }
}
