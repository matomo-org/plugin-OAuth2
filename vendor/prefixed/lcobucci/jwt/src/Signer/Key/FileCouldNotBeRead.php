<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer\Key;

use InvalidArgumentException;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Exception;
use Throwable;
final class FileCouldNotBeRead extends InvalidArgumentException implements Exception
{
    /** @param non-empty-string $path */
    public static function onPath(string $path, ?Throwable $cause = null) : self
    {
        return new self('The path "' . $path . '" does not contain a valid key file', 0, $cause);
    }
}
