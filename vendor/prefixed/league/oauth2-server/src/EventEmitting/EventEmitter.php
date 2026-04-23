<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\OAuth2\Server\EventEmitting;

use Matomo\Dependencies\OAuth2\League\Event\EventDispatcher;
use Matomo\Dependencies\OAuth2\League\Event\ListenerPriority;
final class EventEmitter extends EventDispatcher
{
    public function addListener(string $event, callable $listener, int $priority = ListenerPriority::NORMAL) : self
    {
        $this->subscribeTo($event, $listener, $priority);
        return $this;
    }
    public function emit(object $event) : object
    {
        return $this->dispatch($event);
    }
}
