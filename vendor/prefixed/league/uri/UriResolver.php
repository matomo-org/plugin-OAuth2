<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\Uri;

use Matomo\Dependencies\Oauth2\Deprecated;
use Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface;
use Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface as Psr7UriInterface;
/**
 * @deprecated since version 7.0.0
 * @codeCoverageIgnore
 * @see BaseUri
 */
final class UriResolver
{
    /**
     * Resolves a URI against a base URI using RFC3986 rules.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * a URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter or silence them apart from validating its own parameters.
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $uri
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $baseUri
     * @return Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface
     */
    #[Deprecated(message: 'use League\\Uri\\BaseUri::resolve() instead', since: 'league/uri:7.0.0')]
    public static function resolve($uri, $baseUri)
    {
        return BaseUri::from($baseUri)->resolve($uri)->getUri();
    }
    /**
     * Relativizes a URI according to a base URI.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * a URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter or silence them apart from validating its own parameters.
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $uri
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $baseUri
     * @return Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface
     */
    #[Deprecated(message: 'use League\\Uri\\BaseUri::relativize() instead', since: 'league/uri:7.0.0')]
    public static function relativize($uri, $baseUri)
    {
        return BaseUri::from($baseUri)->relativize($uri)->getUri();
    }
}
