<?php

namespace JobMetric\StateMachine\Tests\Feature;

use JobMetric\StateMachine\Tests\Stubs\Models\Article;
use JobMetric\StateMachine\Tests\TestCase;

class StateMachineDebugConsoleTest extends TestCase
{
    private function makeDraftArticle(): Article
    {
        return Article::create([
            'status' => 'draft'
        ]);
    }

    public function test_run()
    {
        $article = $this->makeDraftArticle();

        $this->artisan('state-machine:debug', [
            'model_type' => Article::class,
            'model_id' => $article->id,
        ])
            ->assertExitCode(0);

        $this->assertEquals('draft', $article->fresh()->status);
    }
}
