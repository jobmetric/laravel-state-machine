<?php

namespace JobMetric\StateMachine\Events;

use Illuminate\Database\Eloquent\Model;

class StateTransitioned
{
    public function __construct(
        public Model  $model,
        public string $field,
        public mixed  $from,
        public mixed  $to,
    )
    {
    }
}

