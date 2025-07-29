<?php

namespace JobMetric\StateMachine\Tests\Stubs\StateMachines\Article;

use Illuminate\Database\Eloquent\Model;
use JobMetric\StateMachine\Contracts\StateMachine;

class ArticleStatusDraftToPublishedStateMachine extends StateMachine
{

    public function before(Model $model, mixed $from, mixed $to): void
    {
        $array_title = json_decode($model->title, true);

        if (is_array($array_title)) {
            $array_title = array_merge($array_title, [
                'draft_to_publish_before' => true
            ]);

            $model->title = json_encode($array_title);
            $model->save();
        }
    }

    public function after(Model $model, mixed $from, mixed $to): void
    {
        $array_title = json_decode($model->title, true);

        if (is_array($array_title)) {
            $array_title = array_merge($array_title, [
                'draft_to_publish_after' => true
            ]);

            $model->title = json_encode($array_title);
            $model->save();
        }
    }
}
