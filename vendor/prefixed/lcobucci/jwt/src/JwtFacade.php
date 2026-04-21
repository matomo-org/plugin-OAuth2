<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT;

use Closure;
use DateTimeImmutable;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Encoding\ChainedFormatter;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Encoding\JoseEncoder;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer\Key;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Constraint;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\SignedWith;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\ValidAt;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation\Validator;
use Matomo\Dependencies\Oauth2\Psr\Clock\ClockInterface as Clock;
use function assert;
final class JwtFacade
{
    /**
     * @readonly
     * @var \Matomo\Dependencies\Oauth2\Lcobucci\JWT\Parser
     */
    private $parser;
    /**
     * @readonly
     * @var Clock
     */
    private $clock;
    public function __construct(Parser $parser = null, ?Clock $clock = null)
    {
        $parser = $parser ?? new Token\Parser(new JoseEncoder());
        $this->parser = $parser;
        $this->clock = $clock ?? new class implements Clock
        {
            public function now() : DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };
    }
    /** @param Closure(Builder, DateTimeImmutable):Builder $customiseBuilder */
    public function issue(Signer $signer, Key $signingKey, Closure $customiseBuilder) : UnencryptedToken
    {
        $builder = Token\Builder::new(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates());
        $now = $this->clock->now();
        $builder = $builder->issuedAt($now)->canOnlyBeUsedAfter($now)->expiresAt($now->modify('+5 minutes'));
        return $customiseBuilder($builder, $now)->getToken($signer, $signingKey);
    }
    /** @param non-empty-string $jwt */
    public function parse(string $jwt, SignedWith $signedWith, ValidAt $validAt, Constraint ...$constraints) : UnencryptedToken
    {
        $token = $this->parser->parse($jwt);
        assert($token instanceof UnencryptedToken);
        (new Validator())->assert($token, $signedWith, $validAt, ...$constraints);
        return $token;
    }
}
