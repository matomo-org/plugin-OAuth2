<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\Event;

interface ListenerSubscriber
{
    public function subscribeListeners(ListenerRegistry $acceptor) : void;
}
