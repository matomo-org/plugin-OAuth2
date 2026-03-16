<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\Event;

use Matomo\Dependencies\Oauth2\Psr\EventDispatcher\EventDispatcherInterface;
interface EventDispatchingListenerRegistry extends ListenerRegistry, EventDispatcherInterface
{
}
