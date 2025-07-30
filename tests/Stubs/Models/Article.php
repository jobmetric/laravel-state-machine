<?php

namespace JobMetric\StateMachine\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use JobMetric\StateMachine\Contracts\StateMachineContract;
use JobMetric\StateMachine\HasStateMachine;

/**
 * @property int $id
 * @property string $title
 * @property string $status
 *
 * @method static create(string[] $array)
 */
class Article extends Model implements StateMachineContract
{
    use HasStateMachine;

    public $timestamps = false;
    protected $fillable = [
        'title',
        'status'
    ];
    protected $casts = [
        'title' => 'string',
        'status' => 'string',
    ];

    public function stateMachineAllowTransition(): void
    {
        $this->allowTransition('status', 'draft', 'published');
        $this->allowTransition('status', 'published', 'archived', fn($model) => $model->isPublishedToArchive());
        $this->allowTransition('status', 'archived', 'draft', fn($model) => $model->isArchiveToDraft());
    }

    public function resolveStateMachineNamespace(): string
    {
        return "JobMetric\\StateMachine\\Tests\\Stubs\\StateMachines";
    }

    public function isPublishedToArchive(): bool
    {
        return true;
    }

    public function isArchiveToDraft(): bool
    {
        return false;
    }
}
