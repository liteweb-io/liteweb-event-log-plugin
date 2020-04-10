<?php

namespace Liteweb\EventLogPlugin\Entity;

use Liteweb\EventLogPlugin\Model\ActorContext;

/**
 * Class EventLog
 * @package Litweb\EventLogPlugin\Entity
 */
class EventLog
{
    /**
     * @var
     */
    private $id;

    /**
     * @var
     */
    private $occurred_at;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var mixed|string
     */
    private $actor;

    /**
     * @var mixed|string|null
     */
    private $user_context;

    /**
     * @var int|mixed|null
     */
    private $user_context_id;

    /**
     * @var string
     */
    private $entity_id;

    /**
     * @var string
     */
    private $entity_type;

    /**
     * @var string|null
     */
    private $url;

    /**
     * EventLog constructor.
     * @param array $payload
     * @param ActorContext $actor
     * @param string $entityType
     * @param string $entityID
     * @param string|null $url
     */
    public function __construct(
        array $payload,
        ActorContext $actor,
        string $entityType,
        string $entityID,
        ?string $url = null
    ) {
        $this->payload = $payload;
        $this->actor = $actor->getActor();
        $this->user_context = $actor->getUserContext();
        $this->user_context_id = $actor->getUserId();
        $this->url = $url;
        $this->occurred_at = new \DateTime();
        $this->entity_id = $entityID;
        $this->entity_type = $entityType;
    }

    /**
     * @param array $payload
     * @param ActorContext $actorContext
     * @param string $entityType
     * @param string $entityID
     * @param string|null $url
     * @return EventLog
     */
    public static function log(
        array $payload,
        ActorContext $actorContext,
        string $entityType,
        string $entityID,
        ?string $url = null
    ) : EventLog {
        return new self($payload, $actorContext, $entityType, $entityID, $url);
    }

}