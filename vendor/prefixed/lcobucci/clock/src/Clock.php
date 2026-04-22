<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\Clock;

use DateTimeImmutable;
use Matomo\Dependencies\OAuth2\Psr\Clock\ClockInterface;
interface Clock extends ClockInterface
{
    public function now() : DateTimeImmutable;
}
