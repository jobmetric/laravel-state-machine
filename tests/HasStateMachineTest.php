<?php

namespace JobMetric\StateMachine\Tests;

use Illuminate\Support\Facades\Event;
use JobMetric\StateMachine\Events\StateTransitioned;
use JobMetric\StateMachine\Exceptions\InvalidStateMachineClassException;
use JobMetric\StateMachine\Exceptions\ModelStateMachineInterfaceNotFoundException;
use JobMetric\StateMachine\Exceptions\StateMachineNotAllowTransitionException;
use JobMetric\StateMachine\Tests\Stubs\Models\Article;
use JobMetric\StateMachine\Tests\Stubs\Models\Order;
use Throwable;

class HasStateMachineTest extends TestCase
{
    private function makeDraftArticle(): Article
    {
        return Article::create([
            'status' => 'draft'
        ]);
    }

    public function test_boot()
    {
        $this->expectException(ModelStateMachineInterfaceNotFoundException::class);

        // Trying to boot a model that does not implement StateMachineContract
        $order = new Order;
    }

    public function test_resolve_state_machine_namespace()
    {
        $article = $this->makeDraftArticle();

        $appNamespace = trim(appNamespace(), "\\");

        $this->assertEquals("JobMetric\\StateMachine\\Tests\\Stubs\\StateMachines", $article->resolveStateMachineNamespace());
    }

    public function test_allow_transition()
    {
        $article = $this->makeDraftArticle();

        // Allow transition from 'draft' to 'published'
        $article->allowTransition('status', 'draft', 'scheduled');

        // Check if the transition is registered
        $this->assertTrue($article->canTransitionTo('scheduled'));
        $this->assertTrue($article->canTransitionTo('published'));
        $this->assertFalse($article->canTransitionTo('archived')); // Not allowed yet
    }

    /**
     * @throws Throwable
     * @throws InvalidStateMachineClassException
     */
    public function test_transition_to()
    {
        $article = $this->makeDraftArticle();

        Event::fake();

        try {
            // Attempting to transition to an invalid state
            $article->transitionTo('archived'); // This should throw an exception
        } catch (Throwable $exception) {
            $this->assertInstanceOf(StateMachineNotAllowTransitionException::class, $exception);
        }

        // If the exception is not thrown, the test will fail
        $this->assertEquals('draft', $article->status); // Should still be in 'draft' state~

        $article->title = '[]';
        $article->save();

        // Now transition to a valid state
        $state = $article->transitionTo('published');

        $this->assertTrue($state);

        // Check if the event was dispatched
        Event::assertDispatched(StateTransitioned::class);

        // Should now be in 'published' state
        $this->assertEquals('published', $article->status);

        // Assuming title is a JSON string
        $this->assertJson($article->title);

        // Should be able to transition to 'archived'
        $this->assertTrue($article->canTransitionTo('archived'));

        // Should not be able to transition back to 'draft'
        $this->assertFalse($article->canTransitionTo('draft'));

        // Assuming 'common_before' is a key in the JSON title
        $this->assertArrayHasKey('common_before', json_decode($article->title, true));

        // Assuming 'common_after' is a key in the JSON title
        $this->assertArrayHasKey('common_after', json_decode($article->title, true));

        // Assuming 'draft_to_publish_before' is a key in the JSON title
        $this->assertArrayHasKey('draft_to_publish_before', json_decode($article->title, true));

        // Assuming 'draft_to_publish_after' is a key in the JSON title
        $this->assertArrayHasKey('draft_to_publish_after', json_decode($article->title, true));

        // Check if the transition class was called
        $this->assertTrue(class_exists('JobMetric\StateMachine\Tests\Stubs\StateMachines\Article\ArticleStatusDraftToPublishedStateMachine'));
        $this->assertTrue(class_exists('JobMetric\StateMachine\Tests\Stubs\StateMachines\Article\ArticleStatusCommonStateMachine'));

        // now transition to 'archived'
        $state = $article->transitionTo('archived');

        $this->assertTrue($state);

        // Should now be in 'archived' state
        $this->assertEquals('archived', $article->status);

        // now transition to 'draft'
        $this->expectException(StateMachineNotAllowTransitionException::class);

        $article->transitionTo('draft'); // This should throw an exception

        // Should still be in 'archived' state
        $this->assertEquals('archived', $article->status);
    }

    public function test_can_transition()
    {
        $article = $this->makeDraftArticle();

        // valid state
        $this->assertTrue($article->canTransitionTo('published'));
        // field not in attributes
        $this->assertFalse($article->canTransitionTo('published', 'fake_field'));
        // field in attributes but not in state machine
        $this->assertFalse($article->canTransitionTo('published', 'title'));
        // invalid state
        $this->assertFalse($article->canTransitionTo('archived'));
    }

    /**
     * @throws InvalidStateMachineClassException
     * @throws Throwable
     * @throws StateMachineNotAllowTransitionException
     */
    public function test_find_transitions_to_new_state()
    {
        $article = new Article(['status' => 'draft']);
        $article->transitionTo('published');

        $this->assertEquals('published', $article->status);
    }
}
