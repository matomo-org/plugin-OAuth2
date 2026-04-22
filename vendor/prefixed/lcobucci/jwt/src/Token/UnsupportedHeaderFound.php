<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Token;

use InvalidArgumentException;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Exception;
final class UnsupportedHeaderFound extends InvalidArgumentException implements Exception
{
    public static function encryption() : self
    {
        return new self('Encryption is not supported yet');
    }
}
