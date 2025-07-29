<?php

namespace JobMetric\StateMachine\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use JobMetric\StateMachine\HasStateMachine;

/**
 * @method static create(string[] $array)
 */
class Order extends Model
{
    use HasStateMachine;

    public $timestamps = false;
}
