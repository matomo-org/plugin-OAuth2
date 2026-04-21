<?php

/**
 * OAuth 2.0 Response Type Interface.
 *
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\OAuth2\Server\ResponseTypes;

use Matomo\Dependencies\Oauth2\Defuse\Crypto\Key;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use Matomo\Dependencies\Oauth2\Psr\Http\Message\ResponseInterface;
interface ResponseTypeInterface
{
    public function setAccessToken(AccessTokenEntityInterface $accessToken) : void;
    public function setRefreshToken(RefreshTokenEntityInterface $refreshToken) : void;
    public function generateHttpResponse(ResponseInterface $response) : ResponseInterface;
    /**
     * @param \Matomo\Dependencies\Oauth2\Defuse\Crypto\Key|string|null $key
     */
    public function setEncryptionKey($key = null) : void;
}
