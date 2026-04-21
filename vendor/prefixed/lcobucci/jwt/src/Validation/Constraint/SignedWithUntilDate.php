<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Constraint;

use DateTimeImmutable;
use DateTimeInterface;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Token;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\ConstraintViolation;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\SignedWith as SignedWithInterface;
use Matomo\Dependencies\Oauth2\Psr\Clock\ClockInterface;
final class SignedWithUntilDate implements SignedWithInterface
{
    /**
     * @readonly
     * @var \DateTimeImmutable
     */
    private $validUntil;
    /**
     * @readonly
     * @var \Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Constraint\SignedWith
     */
    private $verifySignature;
    /**
     * @readonly
     * @var \Matomo\Dependencies\Oauth2\Psr\Clock\ClockInterface
     */
    private $clock;
    public function __construct(Signer $signer, Signer\Key $key, DateTimeImmutable $validUntil, ?ClockInterface $clock = null)
    {
        $this->validUntil = $validUntil;
        $this->verifySignature = new SignedWith($signer, $key);
        $this->clock = $clock ?? new class implements ClockInterface
        {
            public function now() : DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };
    }
    public function assert(Token $token) : void
    {
        if ($this->validUntil < $this->clock->now()) {
            throw ConstraintViolation::error('This constraint was only usable until ' . $this->validUntil->format(DateTimeInterface::RFC3339), $this);
        }
        $this->verifySignature->assert($token);
    }
}
