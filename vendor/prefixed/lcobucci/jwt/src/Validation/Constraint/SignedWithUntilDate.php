<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation\Constraint;

use DateTimeImmutable;
use DateTimeInterface;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Token;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation\ConstraintViolation;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation\SignedWith as SignedWithInterface;
use Matomo\Dependencies\OAuth2\Psr\Clock\ClockInterface;
final class SignedWithUntilDate implements SignedWithInterface
{
    private readonly SignedWith $verifySignature;
    private readonly ClockInterface $clock;
    public function __construct(Signer $signer, Signer\Key $key, private readonly DateTimeImmutable $validUntil, ?ClockInterface $clock = null)
    {
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
