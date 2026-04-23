<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\Event;

use Matomo\Dependencies\OAuth2\Psr\EventDispatcher\EventDispatcherInterface;
interface EventDispatchingListenerRegistry extends ListenerRegistry, EventDispatcherInterface
{
}
