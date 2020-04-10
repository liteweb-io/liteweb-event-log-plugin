<?php

namespace Liteweb\EventLogPlugin\Resolver;

use Liteweb\EventLogPlugin\Model\ActorContext;

/**
 * Class ActorContextResolver
 */
interface ActorContextResolverInterface
{
    /**
     * @return ActorContext
     */
    public function resolve(): ActorContext;
}