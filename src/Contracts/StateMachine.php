<?php

namespace JobMetric\StateMachine\Contracts;

use Illuminate\Database\Eloquent\Model;

abstract class StateMachine
{
    public function __construct(protected Model $model)
    {
    }

    /**
     * before change state in current field model
     *
     * @param Model $model
     * @param mixed $from
     * @param mixed $to
     *
     * @return void
     */
    abstract function before(Model $model, mixed $from, mixed $to): void;


    /**
     * after change state in current field model
     *
     * @param Model $model
     * @param mixed $from
     * @param mixed $to
     *
     * @return void
     */
    abstract function after(Model $model, mixed $from, mixed $to): void;
}
