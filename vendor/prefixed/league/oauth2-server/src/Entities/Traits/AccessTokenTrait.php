<?php

/**
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\Traits;

use DateTimeImmutable;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Configuration;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer\Key\InMemory;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer\Rsa\Sha256;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Token;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\CryptKeyInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\ClientEntityInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\ScopeEntityInterface;
use RuntimeException;
use SensitiveParameter;
trait AccessTokenTrait
{
    /**
     * @var \Matomo\Dependencies\OAuth2\League\OAuth2\Server\CryptKeyInterface
     */
    private $privateKey;
    /**
     * @var \Matomo\Dependencies\OAuth2\Lcobucci\JWT\Configuration
     */
    private $jwtConfiguration;
    /**
     * Set the private key used to encrypt this access token.
     */
    public function setPrivateKey(#[SensitiveParameter] CryptKeyInterface $privateKey) : void
    {
        $this->privateKey = $privateKey;
    }
    /**
     * Initialise the JWT Configuration.
     */
    public function initJwtConfiguration() : void
    {
        $privateKeyContents = $this->privateKey->getKeyContents();
        if ($privateKeyContents === '') {
            throw new RuntimeException('Private key is empty');
        }
        $this->jwtConfiguration = Configuration::forAsymmetricSigner(new Sha256(), InMemory::plainText($privateKeyContents, $this->privateKey->getPassPhrase() ?? ''), InMemory::plainText('empty', 'empty'));
    }
    /**
     * Generate a JWT from the access token
     */
    private function convertToJWT() : Token
    {
        $this->initJwtConfiguration();
        return $this->jwtConfiguration->builder()->permittedFor($this->getClient()->getIdentifier())->identifiedBy($this->getIdentifier())->issuedAt(new DateTimeImmutable())->canOnlyBeUsedAfter(new DateTimeImmutable())->expiresAt($this->getExpiryDateTime())->relatedTo($this->getSubjectIdentifier())->withClaim('scopes', $this->getScopes())->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
    }
    /**
     * Generate a string representation from the access token
     */
    public function toString() : string
    {
        return $this->convertToJWT()->toString();
    }
    public abstract function getClient() : ClientEntityInterface;
    public abstract function getExpiryDateTime() : DateTimeImmutable;
    /**
     * @return non-empty-string|null
     */
    public abstract function getUserIdentifier() : ?string;
    /**
     * @return ScopeEntityInterface[]
     */
    public abstract function getScopes() : array;
    /**
     * @return non-empty-string
     */
    public abstract function getIdentifier() : string;
    /**
     * @return non-empty-string
     */
    private function getSubjectIdentifier() : string
    {
        return $this->getUserIdentifier() ?? $this->getClient()->getIdentifier();
    }
}
