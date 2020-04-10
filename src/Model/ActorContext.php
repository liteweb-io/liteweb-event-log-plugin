<?php

namespace Liteweb\EventLogPlugin\Model;


final class ActorContext
{

    private $actor;

    private $user_context;

    private $user_id;

    /**
     * ActorContext constructor.
     * @param $actor
     * @param $user_context
     * @param $user_id
     */
    public function __construct(string $actor, ?string $user_context = null, ?int $user_id = null)
    {
        $this->actor = $actor;
        $this->user_context = $user_context;
        $this->user_id = $user_id;
    }

    /**
     * @return mixed
     */
    public function getActor() : string
    {
        return $this->actor;
    }

    /**
     * @return mixed
     */
    public function getUserContext() :?string
    {
        return $this->user_context;
    }

    /**
     * @return mixed
     */
    public function getUserId() :?int
    {
        return $this->user_id;
    }
}