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
        if($args->getObject() instanceof EventLog) {
            return;
        }

        if($this->isOnBlacklist($args->getObject())) {
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
        $occuredAt = (new \DateTime())->format('Y-m-d H:i:s');

        $entityID = $reflectedProperty->getValue($currentEntity);

        $reflectedProperty->setAccessible(false);

        $connection = $this->entityManager->getConnection();
        $sql = "INSERT INTO liteweb_event_log_new 
            ('occurred_at', 'entity_type', 'payload', 'actor', 'user_context', 'user_context_id', 'url') VALUES
            (':occured_at', ':entity_type', ':payload', ':actor', ':user_context', ':user_context_id', ':url')
        ";

        $statement = $connection->prepare($sql);
        $statement->bindParam('entity_type', get_class($currentEntity));
        $statement->bindParam('occured_at', $occuredAt);
        $statement->bindParam('payload', $changes);
        $statement->bindParam('actor', $entityID ?? 'unknown');
        $statement->bindParam('user_context', $actorContext);
        $statement->bindParam('url', $requestUri);

        $statement->execute();

    }

    private function isOnBlacklist($object) : bool
    {
        return in_array(get_class($object), $this->blackListEntities);
    }

}