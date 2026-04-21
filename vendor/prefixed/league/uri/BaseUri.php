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
use Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriAccess;
use Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface;
use Matomo\Dependencies\Oauth2\League\Uri\Exceptions\MissingFeature;
use Matomo\Dependencies\Oauth2\League\Uri\Idna\Converter as IdnaConverter;
use Matomo\Dependencies\Oauth2\League\Uri\IPv4\Converter as IPv4Converter;
use Matomo\Dependencies\Oauth2\League\Uri\IPv6\Converter as IPv6Converter;
use Matomo\Dependencies\Oauth2\Psr\Http\Message\UriFactoryInterface;
use Matomo\Dependencies\Oauth2\Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;
use function array_pop;
use function array_reduce;
use function count;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function rawurldecode;
use function str_repeat;
use function str_replace;
use function strpos;
use function substr;
/**
 * @phpstan-import-type ComponentMap from UriInterface
 * @deprecated since version 7.6.0
 *
 * @see Modifier
 * @see Uri
 */
class BaseUri implements JsonSerializable, UriAccess
{
    /**
     * @readonly
     * @var Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface
     */
    protected $uri;
    /**
     * @var UriFactoryInterface|null
     * @readonly
     */
    protected $uriFactory;
    /** @var array<string,int> */
    protected const WHATWG_SPECIAL_SCHEMES = ['ftp' => 1, 'http' => 1, 'https' => 1, 'ws' => 1, 'wss' => 1];
    /** @var array<string,int> */
    protected const DOT_SEGMENTS = ['.' => 1, '..' => 1];
    /**
     * @readonly
     * @var Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface|null
     */
    protected $origin;
    /**
     * @readonly
     * @var string|null
     */
    protected $nullValue;
    /**
     * @param UriFactoryInterface|null $uriFactory Deprecated, will be removed in the next major release
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $uri
     */
    protected final function __construct($uri, ?UriFactoryInterface $uriFactory)
    {
        $this->uri = $uri;
        $this->uriFactory = $uriFactory;
        $this->nullValue = $this->uri instanceof Psr7UriInterface ? '' : null;
        $this->origin = $this->computeOrigin($this->uri, $this->nullValue);
    }
    /**
     * @param \Stringable|string $uri
     * @return static
     */
    public static function from($uri, ?UriFactoryInterface $uriFactory = null)
    {
        $uri = static::formatHost(static::filterUri($uri, $uriFactory));
        return new static($uri, $uriFactory);
    }
    /**
     * @return static
     */
    public function withUriFactory(UriFactoryInterface $uriFactory)
    {
        return new static($this->uri, $uriFactory);
    }
    /**
     * @return static
     */
    public function withoutUriFactory()
    {
        return new static($this->uri, null);
    }
    /**
     * @return Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface
     */
    public function getUri()
    {
        return $this->uri;
    }
    public function getUriString() : string
    {
        return $this->uri->__toString();
    }
    public function jsonSerialize() : string
    {
        return $this->uri->__toString();
    }
    public function __toString() : string
    {
        return $this->uri->__toString();
    }
    public function origin() : ?self
    {
        switch (null) {
            case $this->origin:
                return null;
            default:
                return new self($this->origin, $this->uriFactory);
        }
    }
    /**
     * Returns the Unix filesystem path.
     *
     * The method will return null if a scheme is present and is not the `file` scheme
     */
    public function unixPath() : ?string
    {
        switch ($this->uri->getScheme()) {
            case 'file':
            case $this->nullValue:
                return rawurldecode($this->uri->getPath());
            default:
                return null;
        }
    }
    /**
     * Returns the Windows filesystem path.
     *
     * The method will return null if a scheme is present and is not the `file` scheme
     */
    public function windowsPath() : ?string
    {
        static $regexpWindowsPath = ',^(?<root>[a-zA-Z]:),';
        if (!in_array($this->uri->getScheme(), ['file', $this->nullValue], \true)) {
            return null;
        }
        $originalPath = $this->uri->getPath();
        $path = $originalPath;
        if ('/' === ($path[0] ?? '')) {
            $path = substr($path, 1);
        }
        if (1 === preg_match($regexpWindowsPath, $path, $matches)) {
            $root = $matches['root'];
            $path = substr($path, strlen($root));
            return $root . str_replace('/', '\\', rawurldecode($path));
        }
        $host = $this->uri->getHost();
        switch ($this->nullValue) {
            case $host:
                return str_replace('/', '\\', rawurldecode($originalPath));
            default:
                return '\\\\' . $host . '\\' . str_replace('/', '\\', rawurldecode($path));
        }
    }
    /**
     * Returns a string representation of a File URI according to RFC8089.
     *
     * The method will return null if the URI scheme is not the `file` scheme
     */
    public function toRfc8089() : ?string
    {
        $path = $this->uri->getPath();
        switch (\true) {
            case 'file' !== $this->uri->getScheme():
                return null;
            case in_array($this->uri->getAuthority(), ['', null, 'localhost'], \true):
                switch (\true) {
                    case '' === $path:
                    case '/' === $path[0]:
                        return $path;
                    default:
                        return '/' . $path;
                }
            default:
                return (string) $this->uri;
        }
    }
    /**
     * Tells whether the `file` scheme base URI represents a local file.
     */
    public function isLocalFile() : bool
    {
        switch (\true) {
            case 'file' !== $this->uri->getScheme():
                return \false;
            case in_array($this->uri->getAuthority(), ['', null, 'localhost'], \true):
                return \true;
            default:
                return \false;
        }
    }
    /**
     * Tells whether the URI is opaque or not.
     *
     * A URI is opaque if and only if it is absolute
     * and does not have an authority path.
     */
    public function isOpaque() : bool
    {
        return $this->nullValue === $this->uri->getAuthority() && $this->isAbsolute();
    }
    /**
     * Tells whether two URI do not share the same origin.
     * @param \Stringable|string $uri
     */
    public function isCrossOrigin($uri) : bool
    {
        if (null === $this->origin) {
            return \true;
        }
        $uri = static::filterUri($uri);
        $uriOrigin = $this->computeOrigin($uri, $uri instanceof Psr7UriInterface ? '' : null);
        switch (\true) {
            case null === $uriOrigin:
            case $uriOrigin->__toString() !== $this->origin->__toString():
                return \true;
            default:
                return \false;
        }
    }
    /**
     * Tells whether the URI is absolute.
     */
    public function isAbsolute() : bool
    {
        return $this->nullValue !== $this->uri->getScheme();
    }
    /**
     * Tells whether the URI is a network path.
     */
    public function isNetworkPath() : bool
    {
        return $this->nullValue === $this->uri->getScheme() && $this->nullValue !== $this->uri->getAuthority();
    }
    /**
     * Tells whether the URI is an absolute path.
     */
    public function isAbsolutePath() : bool
    {
        return $this->nullValue === $this->uri->getScheme() && $this->nullValue === $this->uri->getAuthority() && '/' === ($this->uri->getPath()[0] ?? '');
    }
    /**
     * Tells whether the URI is a relative path.
     */
    public function isRelativePath() : bool
    {
        return $this->nullValue === $this->uri->getScheme() && $this->nullValue === $this->uri->getAuthority() && '/' !== ($this->uri->getPath()[0] ?? '');
    }
    /**
     * Tells whether both URI refers to the same document.
     * @param \Stringable|string $uri
     */
    public function isSameDocument($uri) : bool
    {
        return self::normalizedUri($this->uri)->isSameDocument(self::normalizedUri($uri));
    }
    /**
     * @param \Stringable|string $uri
     */
    private static function normalizedUri($uri) : Uri
    {
        $uri = $uri instanceof Uri ? $uri : Uri::new($uri);
        $host = $uri->getHost();
        if (null === $host || Ipv4Converter::fromEnvironment()->isIpv4($host) || IPv6Converter::isIpv6($host)) {
            return $uri;
        }
        /** @var Uri $uri */
        $uri = $uri->withHost(IdnaConverter::toUnicode((string) Ipv6Converter::compress($host))->domain());
        return $uri;
    }
    /**
     * Tells whether the URI contains an Internationalized Domain Name (IDN).
     */
    public function hasIdn() : bool
    {
        return IdnaConverter::isIdn($this->uri->getHost());
    }
    /**
     * Tells whether the URI contains an IPv4 regardless if it is mapped or native.
     */
    public function hasIPv4() : bool
    {
        return IPv4Converter::fromEnvironment()->isIpv4($this->uri->getHost());
    }
    /**
     * Resolves a URI against a base URI using RFC3986 rules.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * a URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter or silence them apart from validating its own parameters.
     * @param \Stringable|string $uri
     * @return static
     */
    public function resolve($uri)
    {
        $resolved = UriString::resolve($uri, $this->uri->__toString());
        return new static((function () use ($resolved) {
            switch ($this->uriFactory) {
                case null:
                    return Uri::new($resolved);
                default:
                    return $this->uriFactory->createUri($resolved);
            }
        })(), $this->uriFactory);
    }
    /**
     * Relativize a URI according to a base URI.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * a URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter of silence them apart from validating its own parameters.
     * @param \Stringable|string $uri
     * @return static
     */
    public function relativize($uri)
    {
        $uri = static::formatHost(static::filterUri($uri, $this->uriFactory));
        if ($this->canNotBeRelativize($uri)) {
            return new static($uri, $this->uriFactory);
        }
        $null = $uri instanceof Psr7UriInterface ? '' : null;
        $uri = $uri->withScheme($null)->withPort(null)->withUserInfo($null)->withHost($null);
        $targetPath = $uri->getPath();
        $basePath = $this->uri->getPath();
        return new static((function () use ($targetPath, $basePath, $uri, $null) {
            switch (\true) {
                case $targetPath !== $basePath:
                    return $uri->withPath(static::relativizePath($targetPath, $basePath));
                case static::componentEquals('query', $uri):
                    return $uri->withPath('')->withQuery($null);
                case $null === $uri->getQuery():
                    return $uri->withPath(static::formatPathWithEmptyBaseQuery($targetPath));
                default:
                    return $uri->withPath('');
            }
        })(), $this->uriFactory);
    }
    /**
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $uri
     * @return Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface|null
     */
    protected final function computeOrigin($uri, ?string $nullValue)
    {
        if ($uri instanceof Uri) {
            $origin = $uri->getOrigin();
            if (null === $origin) {
                return null;
            }
            return Uri::tryNew($origin);
        }
        $origin = ($nullsafeVariable1 = Uri::tryNew($uri)) ? $nullsafeVariable1->getOrigin() : null;
        if (null === $origin) {
            return null;
        }
        $components = UriString::parse($origin);
        return $uri->withFragment($nullValue)->withQuery($nullValue)->withPath('')->withScheme('localhost')->withHost((string) $components['host'])->withPort($components['port'])->withScheme((string) $components['scheme'])->withUserInfo($nullValue);
    }
    /**
     * Input URI normalization to allow Stringable and string URI.
     * @param \Stringable|string $uri
     * @return Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface
     */
    protected static final function filterUri($uri, ?\Matomo\Dependencies\Oauth2\Psr\Http\Message\UriFactoryInterface $uriFactory = null)
    {
        switch (\true) {
            case $uri instanceof UriAccess:
                return $uri->getUri();
            case $uri instanceof Psr7UriInterface:
            case $uri instanceof UriInterface:
                return $uri;
            case $uriFactory instanceof UriFactoryInterface:
                return $uriFactory->createUri((string) $uri);
            default:
                return Uri::new($uri);
        }
    }
    /**
     * Tells whether the component value from both URI object equals.
     *
     * @pqram 'query'|'authority'|'scheme' $property
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $uri
     */
    protected final function componentEquals(string $property, $uri) : bool
    {
        $getComponent = function (string $property, $uri) : ?string {
            switch ($property) {
                case 'query':
                    $component = $uri->getQuery();
                    break;
                case 'authority':
                    $component = $uri->getAuthority();
                    break;
                default:
                    $component = $uri->getScheme();
                    break;
            }
            switch (\true) {
                case $uri instanceof UriInterface:
                case '' !== $component:
                    return $component;
                default:
                    return null;
            }
        };
        return $getComponent($property, $uri) === $getComponent($property, $this->uri);
    }
    /**
     * Filter the URI object.
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $uri
     * @return Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface
     */
    protected static final function formatHost($uri)
    {
        $host = $uri->getHost();
        try {
            $converted = IPv4Converter::fromEnvironment()->toDecimal($host);
        } catch (MissingFeature $exception) {
            $converted = null;
        }
        if (\false === filter_var($converted, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            $converted = IPv6Converter::compress($host);
        }
        switch (\true) {
            case null !== $converted:
                return $uri->withHost($converted);
            case '' === $host:
            case $uri instanceof UriInterface:
                return $uri;
            default:
                return $uri->withHost((string) Uri::fromComponents(['host' => $host])->getHost());
        }
    }
    /**
     * Tells whether the submitted URI object can be relativized.
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $uri
     */
    protected final function canNotBeRelativize($uri) : bool
    {
        return !static::componentEquals('scheme', $uri) || !static::componentEquals('authority', $uri) || static::from($uri)->isRelativePath();
    }
    /**
     * Relatives the URI for an authority-less target URI.
     */
    protected static final function relativizePath(string $path, string $basePath) : string
    {
        $baseSegments = static::getSegments($basePath);
        $targetSegments = static::getSegments($path);
        $targetBasename = array_pop($targetSegments);
        array_pop($baseSegments);
        foreach ($baseSegments as $offset => $segment) {
            if (!isset($targetSegments[$offset]) || $segment !== $targetSegments[$offset]) {
                break;
            }
            unset($baseSegments[$offset], $targetSegments[$offset]);
        }
        $targetSegments[] = $targetBasename;
        return static::formatPath(str_repeat('../', count($baseSegments)) . implode('/', $targetSegments), $basePath);
    }
    /**
     * returns the path segments.
     *
     * @return string[]
     */
    protected static final function getSegments(string $path) : array
    {
        return explode('/', (function () use ($path) {
            switch (\true) {
                case '' === $path:
                case '/' !== $path[0]:
                    return $path;
                default:
                    return substr($path, 1);
            }
        })());
    }
    /**
     * Formatting the path to keep a valid URI.
     */
    protected static final function formatPath(string $path, string $basePath) : string
    {
        $colonPosition = strpos($path, ':');
        $slashPosition = strpos($path, '/');
        switch (\true) {
            case '' === $path:
                switch (\true) {
                    case '' === $basePath:
                    case '/' === $basePath:
                        return $basePath;
                    default:
                        return './';
                }
            case \false === $colonPosition:
                return $path;
            case \false === $slashPosition:
            case $colonPosition < $slashPosition:
                return "./{$path}";
            default:
                return $path;
        }
    }
    /**
     * Formatting the path to keep a resolvable URI.
     */
    protected static final function formatPathWithEmptyBaseQuery(string $path) : string
    {
        $targetSegments = static::getSegments($path);
        $basename = $targetSegments[array_key_last($targetSegments)];
        return '' === $basename ? './' : $basename;
    }
    /**
     * Normalizes a URI for comparison; this URI string representation is not suitable for usage as per RFC guidelines.
     *
     * @deprecated since version 7.6.0
     *
     * @codeCoverageIgnore
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $uri
     */
    #[Deprecated(message: 'no longer used by the isSameDocument method', since: 'league/uri-interfaces:7.6.0')]
    protected final function normalize($uri) : string
    {
        $newUri = $uri->withScheme($uri instanceof Psr7UriInterface ? '' : null);
        if ('' === $newUri->__toString()) {
            return '';
        }
        return UriString::normalize($newUri);
    }
    /**
     * Remove dot segments from the URI path as per RFC specification.
     *
     * @deprecated since version 7.6.0
     *
     * @codeCoverageIgnore
     */
    #[Deprecated(message: 'no longer used by the isSameDocument method', since: 'league/uri-interfaces:7.6.0')]
    protected final function removeDotSegments(string $path) : string
    {
        if (strpos($path, '.') === false) {
            return $path;
        }
        $reducer = function (array $carry, string $segment) : array {
            if ('..' === $segment) {
                array_pop($carry);
                return $carry;
            }
            if (!isset(static::DOT_SEGMENTS[$segment])) {
                $carry[] = $segment;
            }
            return $carry;
        };
        $oldSegments = explode('/', $path);
        $newPath = implode('/', array_reduce($oldSegments, \Closure::fromCallable($reducer), []));
        if (isset(static::DOT_SEGMENTS[$oldSegments[array_key_last($oldSegments)]])) {
            $newPath .= '/';
        }
        // @codeCoverageIgnoreStart
        // added because some PSR-7 implementations do not respect RFC3986
        if (strncmp($path, '/', strlen('/')) === 0 && strncmp($newPath, '/', strlen('/')) !== 0) {
            return '/' . $newPath;
        }
        // @codeCoverageIgnoreEnd
        return $newPath;
    }
    /**
     * Resolves an URI path and query component.
     *
     * @return array{0:string, 1:string|null}
     *
     * @deprecated since version 7.6.0
     *
     * @codeCoverageIgnore
     * @param Psr7UriInterface|\Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface $uri
     */
    #[Deprecated(message: 'no longer used by the isSameDocument method', since: 'league/uri-interfaces:7.6.0')]
    protected final function resolvePathAndQuery($uri) : array
    {
        $targetPath = $uri->getPath();
        $null = $uri instanceof Psr7UriInterface ? '' : null;
        if (strncmp($targetPath, '/', strlen('/')) === 0) {
            return [$targetPath, $uri->getQuery()];
        }
        if ('' === $targetPath) {
            $targetQuery = $uri->getQuery();
            if ($null === $targetQuery) {
                $targetQuery = $this->uri->getQuery();
            }
            $targetPath = $this->uri->getPath();
            //@codeCoverageIgnoreStart
            //because some PSR-7 Uri implementations allow this RFC3986 forbidden construction
            if (null !== $this->uri->getAuthority() && strncmp($targetPath, '/', strlen('/')) !== 0) {
                $targetPath = '/' . $targetPath;
            }
            //@codeCoverageIgnoreEnd
            return [$targetPath, $targetQuery];
        }
        $basePath = $this->uri->getPath();
        if (null !== $this->uri->getAuthority() && '' === $basePath) {
            $targetPath = '/' . $targetPath;
        }
        if ('' !== $basePath) {
            $segments = explode('/', $basePath);
            array_pop($segments);
            if ([] !== $segments) {
                $targetPath = implode('/', $segments) . '/' . $targetPath;
            }
        }
        return [$targetPath, $uri->getQuery()];
    }
}
