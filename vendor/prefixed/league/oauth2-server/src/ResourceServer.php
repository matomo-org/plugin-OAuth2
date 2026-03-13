<?php

/**
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\OAuth2\Server;

use Matomo\Dependencies\Oauth2\League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Exception\OAuthServerException;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Matomo\Dependencies\Oauth2\Psr\Http\Message\ServerRequestInterface;
class ResourceServer
{
    private CryptKeyInterface $publicKey;
    public function __construct(private AccessTokenRepositoryInterface $accessTokenRepository, CryptKeyInterface|string $publicKey, private ?AuthorizationValidatorInterface $authorizationValidator = null)
    {
        if ($publicKey instanceof CryptKeyInterface === \false) {
            $publicKey = new CryptKey($publicKey);
        }
        $this->publicKey = $publicKey;
    }
    protected function getAuthorizationValidator() : AuthorizationValidatorInterface
    {
        if ($this->authorizationValidator instanceof AuthorizationValidatorInterface === \false) {
            $this->authorizationValidator = new BearerTokenValidator($this->accessTokenRepository);
        }
        if ($this->authorizationValidator instanceof BearerTokenValidator === \true) {
            $this->authorizationValidator->setPublicKey($this->publicKey);
        }
        return $this->authorizationValidator;
    }
    /**
     * Determine the access token validity.
     *
     * @throws OAuthServerException
     */
    public function validateAuthenticatedRequest(ServerRequestInterface $request) : ServerRequestInterface
    {
        return $this->getAuthorizationValidator()->validateAuthorization($request);
    }
}
