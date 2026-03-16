<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Constraint;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Token;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Constraint;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\ConstraintViolation;
final class RelatedTo implements Constraint
{
    /** @param non-empty-string $subject */
    public function __construct(private readonly string $subject)
    {
    }
    public function assert(Token $token) : void
    {
        if (!$token->isRelatedTo($this->subject)) {
            throw ConstraintViolation::error('The token is not related to the expected subject', $this);
        }
    }
}
