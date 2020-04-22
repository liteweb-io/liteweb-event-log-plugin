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
    private $blackListEntities;

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
        array $blackListEntities,
        ActorContextResolverInterface $actorContextResolver,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->blackListEntities = $blackListEntities[0];
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
        if($args->getObject() instanceof EventLog || $args->getObject() instanceof \AppBundle\Entity\EventLog) {
            return;
        }

        if($this->isOnBlacklist($args->getObject())) {
            return;
        }

        $currentEntity = $args->getObject();
        $actorContext = $this->actorContextResolver->resolve();
        $uow = $this->entityManager->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($currentEntity);
        $changes = json_encode($changes);
        $requestUri = null;

        /*
         *  Try to log requested URI if context of execution was in web
         */
        if($this->requestStack->getCurrentRequest() !== null) {
            $requestUri = $this->requestStack->getCurrentRequest()->getRequestUri();
        }

        $occuredAt = (new \DateTime())->format('Y-m-d H:i:s');
        $entityType = get_class($currentEntity);
        $entityID = 'unknown';

        try {
            $reflectedEntity = new ReflectionObject($currentEntity);
            $reflectedProperty = $reflectedEntity->getProperty('id');
            $reflectedProperty->setAccessible(true);
            $entityID = $reflectedProperty->getValue($currentEntity);
            $reflectedProperty->setAccessible(false);

            $entityID = $entityID == null ? 'unknown' : $entityID;
        } catch (\Exception $exception) {
            //Let them pass. We are only try to obtain ID from reflection here
        }

        $connection = $this->entityManager->getConnection();
        $connection->insert('liteweb_event_log_new', [
            'entity_type' => $entityType,
            'occurred_at' => $occuredAt,
            'entity_id' => $entityID,
            'url' => $requestUri,
            'payload' => $changes,
            'actor' => $actorContext->getActor(),
            'user_context_id' => $actorContext->getUserContext(),
            'user_context' => $actorContext->getUserContext()
        ]);
    }

    private function isOnBlacklist($object) : bool
    {
        return in_array(get_class($object), $this->blackListEntities);
    }

}