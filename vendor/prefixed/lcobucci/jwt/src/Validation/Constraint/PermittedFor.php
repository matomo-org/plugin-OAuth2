<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation\Constraint;

use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Token;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation\Constraint;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation\ConstraintViolation;
final class PermittedFor implements Constraint
{
    /** @param non-empty-string $audience */
    public function __construct(private readonly string $audience)
    {
    }
    public function assert(Token $token) : void
    {
        if (!$token->isPermittedFor($this->audience)) {
            throw ConstraintViolation::error('The token is not allowed to be used by this audience', $this);
        }
    }
}
