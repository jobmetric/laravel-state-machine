<?php

namespace JobMetric\StateMachine\Events;

use Illuminate\Database\Eloquent\Model;
use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

readonly class StateTransitioned implements DomainEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Model  $model,
        public string $field,
        public mixed  $from,
        public mixed  $to,
    )
    {
    }

    /**
     * Returns the stable technical key for the domain event.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'state_machine.transitioned';
    }

    /**
     * Returns the full metadata definition for this domain event.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'state_machine::base.events.state_transitioned.group', 'state_machine::base.events.state_transitioned.title', 'state_machine::base.events.state_transitioned.description', 'fas fa-exchange-alt', [
            'state_machine',
            'transition',
            'state',
        ]);
    }
}

