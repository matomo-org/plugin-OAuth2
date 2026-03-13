<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Exception;
use RuntimeException;
final class NoConstraintsGiven extends RuntimeException implements Exception
{
}
