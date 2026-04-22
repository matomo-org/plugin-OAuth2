<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT;

use Closure;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Encoding\ChainedFormatter;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Encoding\JoseEncoder;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer\Key;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validation\Constraint;
/**
 * Configuration container for the JWT Builder and Parser
 *
 * Serves like a small DI container to simplify the creation and usage
 * of the objects.
 */
final class Configuration
{
    /**
     * @readonly
     * @var \Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer
     */
    private $signer;
    /**
     * @readonly
     * @var \Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer\Key
     */
    private $signingKey;
    /**
     * @readonly
     * @var \Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer\Key
     */
    private $verificationKey;
    /**
     * @readonly
     * @var \Matomo\Dependencies\OAuth2\Lcobucci\JWT\Encoder
     */
    private $encoder;
    /**
     * @readonly
     * @var \Matomo\Dependencies\OAuth2\Lcobucci\JWT\Decoder
     */
    private $decoder;
    /**
     * @var \Matomo\Dependencies\OAuth2\Lcobucci\JWT\Parser
     */
    private $parser;
    /**
     * @var \Matomo\Dependencies\OAuth2\Lcobucci\JWT\Validator
     */
    private $validator;
    /** @var Closure(ClaimsFormatter $claimFormatter): Builder */
    private $builderFactory;
    /** @var Constraint[] */
    private $validationConstraints;
    /** @param Closure(ClaimsFormatter $claimFormatter): Builder|null $builderFactory */
    private function __construct(Signer $signer, Key $signingKey, Key $verificationKey, Encoder $encoder, Decoder $decoder, ?Parser $parser, ?Validator $validator, ?Closure $builderFactory, Constraint ...$validationConstraints)
    {
        $this->signer = $signer;
        $this->signingKey = $signingKey;
        $this->verificationKey = $verificationKey;
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->parser = $parser ?? new Token\Parser($decoder);
        $this->validator = $validator ?? new Validation\Validator();
        $this->builderFactory = $builderFactory ?? static function (ClaimsFormatter $claimFormatter) use($encoder) : Builder {
            return Token\Builder::new($encoder, $claimFormatter);
        };
        $this->validationConstraints = $validationConstraints;
    }
    public static function forAsymmetricSigner(Signer $signer, Key $signingKey, Key $verificationKey, Encoder $encoder = null, Decoder $decoder = null) : self
    {
        $encoder = $encoder ?? new JoseEncoder();
        $decoder = $decoder ?? new JoseEncoder();
        return new self($signer, $signingKey, $verificationKey, $encoder, $decoder, null, null, null);
    }
    public static function forSymmetricSigner(Signer $signer, Key $key, Encoder $encoder = null, Decoder $decoder = null) : self
    {
        $encoder = $encoder ?? new JoseEncoder();
        $decoder = $decoder ?? new JoseEncoder();
        return new self($signer, $key, $key, $encoder, $decoder, null, null, null);
    }
    /**
     * @deprecated Deprecated since v5.5, please use {@see self::withBuilderFactory()} instead
     *
     * @param callable(ClaimsFormatter): Builder $builderFactory
     */
    public function setBuilderFactory(callable $builderFactory) : void
    {
        $this->builderFactory = \Closure::fromCallable($builderFactory);
    }
    /** @param callable(ClaimsFormatter): Builder $builderFactory */
    public function withBuilderFactory(callable $builderFactory) : self
    {
        return new self($this->signer, $this->signingKey, $this->verificationKey, $this->encoder, $this->decoder, $this->parser, $this->validator, \Closure::fromCallable($builderFactory), ...$this->validationConstraints);
    }
    public function builder(?ClaimsFormatter $claimFormatter = null) : Builder
    {
        return ($this->builderFactory)($claimFormatter ?? ChainedFormatter::default());
    }
    public function parser() : Parser
    {
        return $this->parser;
    }
    /** @deprecated Deprecated since v5.5, please use {@see self::withParser()} instead */
    public function setParser(Parser $parser) : void
    {
        $this->parser = $parser;
    }
    public function withParser(Parser $parser) : self
    {
        return new self($this->signer, $this->signingKey, $this->verificationKey, $this->encoder, $this->decoder, $parser, $this->validator, $this->builderFactory, ...$this->validationConstraints);
    }
    public function signer() : Signer
    {
        return $this->signer;
    }
    public function signingKey() : Key
    {
        return $this->signingKey;
    }
    public function verificationKey() : Key
    {
        return $this->verificationKey;
    }
    public function validator() : Validator
    {
        return $this->validator;
    }
    /** @deprecated Deprecated since v5.5, please use {@see self::withValidator()} instead */
    public function setValidator(Validator $validator) : void
    {
        $this->validator = $validator;
    }
    public function withValidator(Validator $validator) : self
    {
        return new self($this->signer, $this->signingKey, $this->verificationKey, $this->encoder, $this->decoder, $this->parser, $validator, $this->builderFactory, ...$this->validationConstraints);
    }
    /** @return Constraint[] */
    public function validationConstraints() : array
    {
        return $this->validationConstraints;
    }
    /** @deprecated Deprecated since v5.5, please use {@see self::withValidationConstraints()} instead */
    public function setValidationConstraints(Constraint ...$validationConstraints) : void
    {
        $this->validationConstraints = $validationConstraints;
    }
    public function withValidationConstraints(Constraint ...$validationConstraints) : self
    {
        return new self($this->signer, $this->signingKey, $this->verificationKey, $this->encoder, $this->decoder, $this->parser, $this->validator, $this->builderFactory, ...$validationConstraints);
    }
}
