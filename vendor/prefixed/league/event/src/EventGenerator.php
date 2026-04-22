<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\Event;

interface EventGenerator
{
    /**
     * Release all the added events.
     *
     * @return object[]
     */
    public function releaseEvents();
}
