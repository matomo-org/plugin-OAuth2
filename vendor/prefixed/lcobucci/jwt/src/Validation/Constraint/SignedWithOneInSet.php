<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation\Constraint;

use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Token;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation\ConstraintViolation;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation\SignedWith as SignedWithInterface;
use const PHP_EOL;
final class SignedWithOneInSet implements SignedWithInterface
{
    /** @var array<SignedWithUntilDate>
     * @readonly */
    private $constraints;
    public function __construct(SignedWithUntilDate ...$constraints)
    {
        $this->constraints = $constraints;
    }
    public function assert(Token $token) : void
    {
        $errorMessage = 'It was not possible to verify the signature of the token, reasons:';
        foreach ($this->constraints as $constraint) {
            try {
                $constraint->assert($token);
                return;
            } catch (ConstraintViolation $violation) {
                $errorMessage .= PHP_EOL . '- ' . $violation->getMessage();
            }
        }
        throw ConstraintViolation::error($errorMessage, $this);
    }
}
