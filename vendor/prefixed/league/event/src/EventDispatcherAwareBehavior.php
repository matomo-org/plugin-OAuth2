<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\Event;

trait EventDispatcherAwareBehavior
{
    /**
     * @var EventDispatcher|null
     */
    protected $dispatcher;
    public function useEventDispatcher(EventDispatcher $emitter) : void
    {
        $this->dispatcher = $emitter;
    }
    public function eventDispatcher() : EventDispatcher
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new EventDispatcher(new PrioritizedListenerRegistry());
        }
        return $this->dispatcher;
    }
}
