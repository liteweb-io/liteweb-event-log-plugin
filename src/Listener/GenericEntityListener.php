<?php

namespace Liteweb\EventLogPlugin\Listener;


use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Liteweb\EventLogPlugin\Entity\EventLog;
use Liteweb\EventLogPlugin\Resolver\ActorContextResolverInterface;
use ReflectionObject;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GenericEntityListener
 */
final class GenericEntityListener
{

    /**
     * @var array
     */
    private $whitelistedEntities;

    /**
     * @var ActorContextResolverInterface
     */
    private $actorContextResolver;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * GenericEntityListener constructor.
     * @param array $whitelistedEntities
     * @param ActorContextResolverInterface $actorContextResolver
     * @param EntityManagerInterface $entityManager
     * @param RequestStack $requestStack
     */
    public function __construct(
        array $whitelistedEntities,
        ActorContextResolverInterface $actorContextResolver,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->whitelistedEntities = $whitelistedEntities;
        $this->actorContextResolver = $actorContextResolver;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \ReflectionException
     */
    public function postRemove(LifecycleEventArgs $args) : void
    {
        $this->process($args);
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \ReflectionException
     */
    public function postUpdate(LifecycleEventArgs $args) : void
    {
        $this->process($args);
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \ReflectionException
     */
    public function postPersist(LifecycleEventArgs $args) : void
    {
        $this->process($args);
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \ReflectionException
     */
    private function process(LifecycleEventArgs $args) : void
    {
        if(!$this->isOnWhiteList($args->getObject())) {
            return;
        }

        $currentEntity = $args->getObject();
        $actorContext = $this->actorContextResolver->resolve();
        $uow = $this->entityManager->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($currentEntity);
        $requestUri = null;

        /*
         *  Try to log requested URI if context of execution was in web
         */
        if($this->requestStack->getCurrentRequest() !== null) {
            $requestUri = $this->requestStack->getCurrentRequest()->getRequestUri();
        }

        $reflectedEntity  = new ReflectionObject($currentEntity);
        $reflectedProperty = $reflectedEntity->getProperty('id');
        $reflectedProperty->setAccessible(true);

        $entityID = $reflectedProperty->getValue($currentEntity);

        $reflectedProperty->setAccessible(false);

        $eventLog = EventLog::log($changes, $actorContext, get_class($currentEntity), $entityID, $requestUri);
        $this->entityManager->persist($eventLog);
        $this->entityManager->flush();
    }

    /**
     * @param $object
     * @return bool
     */
    private function isOnWhiteList($object) : bool
    {
        return in_array(get_class($object), $this->whitelistedEntities, true);
    }
}