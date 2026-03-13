<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Constraint;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\NoConstraintsGiven;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\RequiredConstraintsViolated;
interface Validator
{
    /**
     * @throws RequiredConstraintsViolated
     * @throws NoConstraintsGiven
     */
    public function assert(Token $token, Constraint ...$constraints) : void;
    /** @throws NoConstraintsGiven */
    public function validate(Token $token, Constraint ...$constraints) : bool;
}
