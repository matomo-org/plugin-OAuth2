<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\Clock;

use DateTimeImmutable;
use Matomo\Dependencies\Oauth2\Psr\Clock\ClockInterface;
interface Clock extends ClockInterface
{
    public function now() : DateTimeImmutable;
}
