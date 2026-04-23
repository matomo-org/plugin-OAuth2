<?php

/**
 * OAuth 2.0 Abstract Response Type.
 *
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\OAuth2\Server\ResponseTypes;

use Matomo\Dependencies\OAuth2\League\OAuth2\Server\CryptKeyInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\CryptTrait;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use SensitiveParameter;
abstract class AbstractResponseType implements ResponseTypeInterface
{
    use CryptTrait;
    protected AccessTokenEntityInterface $accessToken;
    protected RefreshTokenEntityInterface $refreshToken;
    protected CryptKeyInterface $privateKey;
    public function setAccessToken(#[SensitiveParameter] AccessTokenEntityInterface $accessToken) : void
    {
        $this->accessToken = $accessToken;
    }
    public function setRefreshToken(#[SensitiveParameter] RefreshTokenEntityInterface $refreshToken) : void
    {
        $this->refreshToken = $refreshToken;
    }
    public function setPrivateKey(#[SensitiveParameter] CryptKeyInterface $key) : void
    {
        $this->privateKey = $key;
    }
}
