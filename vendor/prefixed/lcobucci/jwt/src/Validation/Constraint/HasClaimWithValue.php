<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Constraint;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Token;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\UnencryptedToken;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Constraint;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\ConstraintViolation;
use function in_array;
final class HasClaimWithValue implements Constraint
{
    /**
     * @var non-empty-string
     * @readonly
     */
    private $claim;
    /**
     * @readonly
     * @var mixed
     */
    private $expectedValue;
    /** @param non-empty-string $claim
     * @param mixed $expectedValue */
    public function __construct(string $claim, $expectedValue)
    {
        $this->claim = $claim;
        $this->expectedValue = $expectedValue;
        if (in_array($claim, Token\RegisteredClaims::ALL, \true)) {
            throw CannotValidateARegisteredClaim::create($claim);
        }
    }
    public function assert(Token $token) : void
    {
        if (!$token instanceof UnencryptedToken) {
            throw ConstraintViolation::error('You should pass a plain token', $this);
        }
        $claims = $token->claims();
        if (!$claims->has($this->claim)) {
            throw ConstraintViolation::error('The token does not have the claim "' . $this->claim . '"', $this);
        }
        if ($claims->get($this->claim) !== $this->expectedValue) {
            throw ConstraintViolation::error('The claim "' . $this->claim . '" does not have the expected value', $this);
        }
    }
}
