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
use JsonSerializable;
use Matomo\Dependencies\Oauth2\League\Uri\Contracts\Conditionable;
use Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriException;
use Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface;
use Matomo\Dependencies\Oauth2\League\Uri\Exceptions\SyntaxError;
use Matomo\Dependencies\Oauth2\League\Uri\UriTemplate\TemplateCanNotBeExpanded;
use Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;
use Uri\Rfc3986\Uri as Rfc3986Uri;
use Uri\WhatWg\Url as WhatWgUrl;
use function is_bool;
use function ltrim;
/**
 * @phpstan-import-type InputComponentMap from UriString
 */
final class Http implements Psr7UriInterface, JsonSerializable, Conditionable
{
    /**
     * @readonly
     * @var \Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface
     */
    private $uri;
    private function __construct(UriInterface $uri)
    {
        if (null === $uri->getScheme() && '' === $uri->getHost()) {
            throw new SyntaxError('An URI without scheme cannot contain an empty host string according to PSR-7: ' . $uri);
        }
        $port = $uri->getPort();
        if (null !== $port && ($port < 0 || $port > 65535)) {
            throw new SyntaxError('The URI port is outside the established TCP and UDP port ranges: ' . $uri);
        }
        $this->uri = $this->normalizePsr7Uri($uri);
    }
    /**
     * PSR-7 UriInterface makes the following normalization.
     *
     * Safely stringify input when possible for League UriInterface compatibility.
     *
     * Query, Fragment and User Info when undefined are normalized to the empty string
     */
    private function normalizePsr7Uri(UriInterface $uri) : UriInterface
    {
        $components = [];
        if ('' === $uri->getFragment()) {
            $components['fragment'] = null;
        }
        if ('' === $uri->getQuery()) {
            $components['query'] = null;
        }
        if ('' === $uri->getUserInfo()) {
            $components['user'] = null;
            $components['pass'] = null;
        }
        switch ($components) {
            case []:
                return $uri;
            default:
                return Uri::fromComponents(array_merge($uri->toComponents(), is_array($components) ? $components : iterator_to_array($components)));
        }
    }
    /**
     * Create a new instance from a string or a stringable object.
     * @param Rfc3986Uri|WhatwgUrl|\Stringable|string $uri
     */
    public static function new($uri = '') : self
    {
        return new self(Uri::new($uri));
    }
    /**
     * Create a new instance from a string or a stringable structure or returns null on failure.
     * @param Rfc3986Uri|WhatwgUrl|\Stringable|string $uri
     */
    public static function tryNew($uri = '') : ?self
    {
        try {
            return self::new($uri);
        } catch (UriException $exception) {
            return null;
        }
    }
    /**
     * Create a new instance from a hash of parse_url parts.
     *
     * @param InputComponentMap $components a hash representation of the URI similar
     *                                      to PHP parse_url function result
     */
    public static function fromComponents(array $components) : self
    {
        $components += ['scheme' => null, 'user' => null, 'pass' => null, 'host' => null, 'port' => null, 'path' => '', 'query' => null, 'fragment' => null];
        if ('' === $components['user']) {
            $components['user'] = null;
        }
        if ('' === $components['pass']) {
            $components['pass'] = null;
        }
        if ('' === $components['query']) {
            $components['query'] = null;
        }
        if ('' === $components['fragment']) {
            $components['fragment'] = null;
        }
        return new self(Uri::fromComponents($components));
    }
    /**
     * Create a new instance from the environment.
     */
    public static function fromServer(array $server) : self
    {
        return new self(Uri::fromServer($server));
    }
    /**
     * Creates a new instance from a template.
     *
     * @throws TemplateCanNotBeExpanded if the variables are invalid or missing
     * @throws UriException if the variables are invalid or missing
     * @param \Stringable|string $template
     */
    public static function fromTemplate($template, iterable $variables = []) : self
    {
        return new self(Uri::fromTemplate($template, $variables));
    }
    /**
     * Returns a new instance from a URI and a Base URI.or null on failure.
     *
     * The returned URI must be absolute if a base URI is provided
     * @param WhatWgUrl|Rfc3986Uri|\Stringable|string $uri
     * @param WhatWgUrl|Rfc3986Uri|\Stringable|string|null $baseUri
     */
    public static function parse($uri, $baseUri = null) : ?self
    {
        return null !== ($uri = Uri::parse($uri, $baseUri)) ? new self($uri) : null;
    }
    public function getScheme() : string
    {
        return $this->uri->getScheme() ?? '';
    }
    public function getAuthority() : string
    {
        return $this->uri->getAuthority() ?? '';
    }
    public function getUserInfo() : string
    {
        return $this->uri->getUserInfo() ?? '';
    }
    public function getHost() : string
    {
        return $this->uri->getHost() ?? '';
    }
    public function getPort() : ?int
    {
        return $this->uri->getPort();
    }
    public function getPath() : string
    {
        $path = $this->uri->getPath();
        switch (\true) {
            case strncmp($path, '//', strlen('//')) === 0:
                return '/' . ltrim($path, '/');
            default:
                return $path;
        }
    }
    public function getQuery() : string
    {
        return $this->uri->getQuery() ?? '';
    }
    public function getFragment() : string
    {
        return $this->uri->getFragment() ?? '';
    }
    public function __toString() : string
    {
        return $this->uri->toString();
    }
    public function jsonSerialize() : string
    {
        return $this->uri->toString();
    }
    /**
     * Safely stringify input when possible for League UriInterface compatibility.
     */
    private function filterInput(string $str) : ?string
    {
        switch ('') {
            case $str:
                return null;
            default:
                return $str;
        }
    }
    private function newInstance(UriInterface $uri) : self
    {
        switch ($this->uri->toString()) {
            case $uri->toString():
                return $this;
            default:
                return new self($uri);
        }
    }
    /**
     * @param callable|bool $condition
     * @return static
     */
    public function when($condition, callable $onSuccess, ?callable $onFail = null)
    {
        if (!is_bool($condition)) {
            $condition = $condition($this);
        }
        switch (\true) {
            case $condition:
                return $onSuccess($this);
            case null !== $onFail:
                return $onFail($this);
            default:
                return $this;
        }
    }
    /**
     * @return $this
     */
    public function withScheme(string $scheme) : \Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface
    {
        return $this->newInstance($this->uri->withScheme($this->filterInput($scheme)));
    }
    /**
     * @return $this
     */
    public function withUserInfo(string $user, ?string $password = null) : \Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface
    {
        return $this->newInstance($this->uri->withUserInfo($this->filterInput($user), $password));
    }
    /**
     * @return $this
     */
    public function withHost(string $host) : \Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface
    {
        return $this->newInstance($this->uri->withHost($this->filterInput($host)));
    }
    /**
     * @return $this
     */
    public function withPort(?int $port) : \Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface
    {
        return $this->newInstance($this->uri->withPort($port));
    }
    /**
     * @return $this
     */
    public function withPath(string $path) : \Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface
    {
        return $this->newInstance($this->uri->withPath($path));
    }
    /**
     * @return $this
     */
    public function withQuery(string $query) : \Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface
    {
        return $this->newInstance($this->uri->withQuery($this->filterInput($query)));
    }
    /**
     * @return $this
     */
    public function withFragment(string $fragment) : \Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface
    {
        return $this->newInstance($this->uri->withFragment($this->filterInput($fragment)));
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.6.0
     * @codeCoverageIgnore
     * @see Http::parse()
     *
     * Create a new instance from a URI and a Base URI.
     *
     * The returned URI must be absolute.
     * @param Rfc3986Uri|WhatwgUrl|\Stringable|string $uri
     * @param Rfc3986Uri|WhatwgUrl|\Stringable|string|null $baseUri
     */
    #[Deprecated(message: 'use League\\Uri\\Http::parse() instead', since: 'league/uri:7.6.0')]
    public static function fromBaseUri($uri, $baseUri = null) : self
    {
        return new self(Uri::fromBaseUri($uri, $baseUri));
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Http::new()
     *
     * Create a new instance from a string.
     * @param \Stringable|string $uri
     */
    #[Deprecated(message: 'use League\\Uri\\Http::new() instead', since: 'league/uri:7.0.0')]
    public static function createFromString($uri = '') : self
    {
        return self::new($uri);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Http::fromComponents()
     *
     * Create a new instance from a hash of parse_url parts.
     *
     * @param InputComponentMap $components a hash representation of the URI similar
     *                                      to PHP parse_url function result
     */
    #[Deprecated(message: 'use League\\Uri\\Http::fromComponents() instead', since: 'league/uri:7.0.0')]
    public static function createFromComponents(array $components) : self
    {
        return self::fromComponents($components);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Http::fromServer()
     *
     * Create a new instance from the environment.
     */
    #[Deprecated(message: 'use League\\Uri\\Http::fromServer() instead', since: 'league/uri:7.0.0')]
    public static function createFromServer(array $server) : self
    {
        return self::fromServer($server);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Http::new()
     *
     * Create a new instance from a URI object.
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $uri
     */
    #[Deprecated(message: 'use League\\Uri\\Http::new() instead', since: 'league/uri:7.0.0')]
    public static function createFromUri($uri) : self
    {
        return self::new($uri);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Http::fromBaseUri()
     *
     * Create a new instance from a URI and a Base URI.
     *
     * The returned URI must be absolute.
     * @param \Stringable|string $uri
     * @param \Stringable|string|null $baseUri
     */
    #[Deprecated(message: 'use League\\Uri\\Http::fromBaseUri() instead', since: 'league/uri:7.0.0')]
    public static function createFromBaseUri($uri, $baseUri = null) : self
    {
        return self::fromBaseUri($uri, $baseUri);
    }
}
