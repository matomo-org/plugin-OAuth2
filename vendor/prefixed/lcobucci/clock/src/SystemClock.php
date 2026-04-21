<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\Clock;

use DateTimeImmutable;
use DateTimeZone;
use function date_default_timezone_get;
/** @immutable */
final class SystemClock implements Clock
{
    /**
     * @readonly
     * @var \DateTimeZone
     */
    private $timezone;
    public function __construct(DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }
    public static function fromUTC() : self
    {
        return new self(new DateTimeZone('UTC'));
    }
    public static function fromSystemTimezone() : self
    {
        return new self(new DateTimeZone(date_default_timezone_get()));
    }
    public function now() : DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }
}
