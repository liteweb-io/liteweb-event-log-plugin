<?php

namespace Liteweb\EventLogPlugin\Resolver;


use Liteweb\EventLogPlugin\Model\ActorContext;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class ActorContextResolver
 */
final class ActorContextResolver implements ActorContextResolverInterface
{

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     *
     */
    private const SYSTEM_CONTEXT = 'system';

    /**
     *
     */
    private const USER_CONTEXT = 'user';

    /**
     * ActorContextResolver constructor.
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return ActorContext
     */
    public function resolve() : ActorContext
    {
        $securityToken = $this->tokenStorage->getToken();

        if($securityToken === null) {
            return new ActorContext(self::SYSTEM_CONTEXT);
        }

        if(is_string($securityToken->getUser())) {
            return new ActorContext(self::SYSTEM_CONTEXT);
        }

        $user = $securityToken->getUser();
        return new ActorContext(self::USER_CONTEXT, get_class($user), $user->getId());
    }
}