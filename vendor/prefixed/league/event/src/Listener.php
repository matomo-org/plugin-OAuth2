<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\Event;

interface Listener
{
    public function __invoke(object $event) : void;
}
