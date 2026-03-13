<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Constraint;

use InvalidArgumentException;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Exception;
final class LeewayCannotBeNegative extends InvalidArgumentException implements Exception
{
    public static function create() : self
    {
        return new self('Leeway cannot be negative');
    }
}
