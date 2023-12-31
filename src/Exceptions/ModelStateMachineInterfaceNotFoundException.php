<?php

namespace JobMetric\StateMachine\Exceptions;

use Exception;
use Throwable;

class ModelStateMachineInterfaceNotFoundException extends Exception
{
    public function __construct(string $model, int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct("model $model Not Implements JobMetric\StateMachine\Contracts\StateMachineContract Interface!", $code, $previous);
    }
}
