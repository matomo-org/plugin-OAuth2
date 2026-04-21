<?php

/**
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\OAuth2\Server\Middleware;

use Matomo\Dependencies\Oauth2\League\OAuth2\Server\AuthorizationServer;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Exception\OAuthServerException;
use Matomo\Dependencies\Oauth2\Psr\Http\Message\ResponseInterface;
use Matomo\Dependencies\Oauth2\Psr\Http\Message\ServerRequestInterface;
class AuthorizationServerMiddleware
{
    /**
     * @var \Matomo\Dependencies\Oauth2\League\OAuth2\Server\AuthorizationServer
     */
    private $server;
    public function __construct(AuthorizationServer $server)
    {
        $this->server = $server;
    }
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) : ResponseInterface
    {
        try {
            $response = $this->server->respondToAccessTokenRequest($request, $response);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        }
        // Pass the request and response on to the next responder in the chain
        return $next($request, $response);
    }
}
