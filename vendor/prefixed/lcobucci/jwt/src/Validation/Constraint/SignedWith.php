<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Constraint;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Token;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\UnencryptedToken;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\ConstraintViolation;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\SignedWith as SignedWithInterface;
final class SignedWith implements SignedWithInterface
{
    /**
     * @readonly
     * @var \Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer
     */
    private $signer;
    /**
     * @readonly
     * @var \Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer\Key
     */
    private $key;
    public function __construct(Signer $signer, Signer\Key $key)
    {
        $this->signer = $signer;
        $this->key = $key;
    }
    public function assert(Token $token) : void
    {
        if (!$token instanceof UnencryptedToken) {
            throw ConstraintViolation::error('You should pass a plain token', $this);
        }
        if ($token->headers()->get('alg') !== $this->signer->algorithmId()) {
            throw ConstraintViolation::error('Token signer mismatch', $this);
        }
        if (!$this->signer->verify($token->signature()->hash(), $token->payload(), $this->key)) {
            throw ConstraintViolation::error('Token signature mismatch', $this);
        }
    }
}
