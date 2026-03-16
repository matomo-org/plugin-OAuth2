<?php

declare(strict_types=1);

namespace Piwik\Plugins\ActivityLog\Activity;

if (!class_exists(Activity::class)) {
    abstract class Activity
    {
        public const USER_ANONYMOUS = 'anonymous';
        public const USER_SYSTEM = 'system';
    }
}
