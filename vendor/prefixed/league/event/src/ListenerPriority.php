<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\Event;

final class ListenerPriority
{
    /**
     * High priority.
     *
     * @const int
     */
    public const HIGH = 100;
    /**
     * Normal priority.
     *
     * @const int
     */
    public const NORMAL = 0;
    /**
     * Low priority.
     *
     * @const int
     */
    public const LOW = -100;
}
