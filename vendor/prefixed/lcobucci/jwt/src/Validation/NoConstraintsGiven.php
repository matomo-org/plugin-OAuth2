<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation;

use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Exception;
use RuntimeException;
final class NoConstraintsGiven extends RuntimeException implements Exception
{
}
