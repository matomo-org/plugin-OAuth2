<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Encoding;

use JsonException;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Exception;
use RuntimeException;
final class CannotEncodeContent extends RuntimeException implements Exception
{
    public static function jsonIssues(JsonException $previous) : self
    {
        return new self('Error while encoding to JSON', 0, $previous);
    }
}
