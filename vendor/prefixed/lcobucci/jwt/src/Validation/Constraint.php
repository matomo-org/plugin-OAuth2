<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation;

use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Token;
interface Constraint
{
    /** @throws ConstraintViolation */
    public function assert(Token $token) : void;
}
