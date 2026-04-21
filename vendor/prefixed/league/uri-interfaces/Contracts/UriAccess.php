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
namespace Matomo\Dependencies\Oauth2\League\Uri\Contracts;

use Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface as Psr7UriInterface;
/**
 * @deprecated since version 7.6.0
 */
interface UriAccess
{
    /**
     * @return \Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface|Psr7UriInterface
     */
    public function getUri();
    /**
     * Returns the RFC3986 string representation of the complete URI.
     */
    public function getUriString() : string;
}
