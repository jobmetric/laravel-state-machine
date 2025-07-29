<?php

namespace JobMetric\StateMachine\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use JobMetric\StateMachine\Contracts\StateMachineContract;
use JobMetric\StateMachine\HasStateMachine;

class Article extends Model implements StateMachineContract
{
    use HasStateMachine;

    public $timestamps = false;
    protected $fillable = [
        'status'
    ];
    protected $casts = [
        'status' => 'string',
    ];

    public function stateMachineAllowTransition(): void
    {
        $this->allowTransition('status', 'draft', 'published');
        $this->allowTransition('status', 'published', 'archived', fn ($model) => $model->isAllowedToArchive());
    }

    public function isAllowedToArchive(): bool
    {
        return true;
    }
}
