<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\OAuth2\Server\EventEmitting;

use Matomo\Dependencies\Oauth2\League\Event\ListenerRegistry;
use Matomo\Dependencies\Oauth2\Psr\EventDispatcher\EventDispatcherInterface;
trait EmitterAwarePolyfill
{
    /**
     * @var \Matomo\Dependencies\Oauth2\League\OAuth2\Server\EventEmitting\EventEmitter
     */
    private $emitter;
    public function getEmitter() : EventEmitter
    {
        return $this->emitter = $this->emitter ?? new EventEmitter();
    }
    public function setEmitter(EventEmitter $emitter) : self
    {
        $this->emitter = $emitter;
        return $this;
    }
    public function getEventDispatcher() : EventDispatcherInterface
    {
        return $this->getEmitter();
    }
    public function getListenerRegistry() : ListenerRegistry
    {
        return $this->getEmitter();
    }
}
