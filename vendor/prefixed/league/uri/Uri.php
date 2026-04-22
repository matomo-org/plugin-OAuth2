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
namespace Matomo\Dependencies\OAuth2\League\Uri;

use Closure;
use Matomo\Dependencies\OAuth2\Deprecated;
use finfo;
use Matomo\Dependencies\OAuth2\League\Uri\Contracts\Conditionable;
use Matomo\Dependencies\OAuth2\League\Uri\Contracts\FragmentDirective;
use Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriComponentInterface;
use Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriException;
use Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriInterface;
use Matomo\Dependencies\OAuth2\League\Uri\Exceptions\ConversionFailed;
use Matomo\Dependencies\OAuth2\League\Uri\Exceptions\MissingFeature;
use Matomo\Dependencies\OAuth2\League\Uri\Exceptions\SyntaxError;
use Matomo\Dependencies\OAuth2\League\Uri\Idna\Converter as IdnaConverter;
use Matomo\Dependencies\OAuth2\League\Uri\IPv4\Converter as IPv4Converter;
use Matomo\Dependencies\OAuth2\League\Uri\IPv6\Converter as IPv6Converter;
use Matomo\Dependencies\OAuth2\League\Uri\UriTemplate\TemplateCanNotBeExpanded;
use Matomo\Dependencies\OAuth2\Psr\Http\Message\UriInterface as Psr7UriInterface;
use RuntimeException;
use SensitiveParameter;
use SplFileInfo;
use SplFileObject;
use Stringable;
use Throwable;
use TypeError;
use Uri\Rfc3986\Uri as Rfc3986Uri;
use Uri\WhatWg\Url as WhatWgUrl;
use function array_filter;
use function array_key_last;
use function array_map;
use function array_pop;
use function base64_decode;
use function base64_encode;
use function basename;
use function count;
use function dirname;
use function explode;
use function feof;
use function file_get_contents;
use function filter_var;
use function fread;
use function implode;
use function in_array;
use function inet_pton;
use function is_bool;
use function is_string;
use function preg_match;
use function preg_replace_callback;
use function rawurldecode;
use function rawurlencode;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_contains;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function strspn;
use function strtolower;
use function substr;
use function trim;
use const FILEINFO_MIME;
use const FILEINFO_MIME_TYPE;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_IP;
/**
 * @phpstan-import-type ComponentMap from UriString
 * @phpstan-import-type InputComponentMap from UriString
 */
final class Uri implements Conditionable, UriInterface
{
    /**
     * RFC3986 invalid characters.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.2
     *
     * @var string
     */
    private const REGEXP_INVALID_CHARS = '/[\\x00-\\x1f\\x7f]/';
    /**
     * RFC3986 host identified by a registered name regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @var string
     */
    private const REGEXP_HOST_REGNAME = '/^(
        (?<unreserved>[a-z\\d_~\\-\\.])|
        (?<sub_delims>[!$&\'()*+,;=])|
        (?<encoded>%[A-F\\d]{2})
    )+$/x';
    /**
     * RFC3986 delimiters of the generic URI components regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-2.2
     *
     * @var string
     */
    private const REGEXP_HOST_GEN_DELIMS = '/[:\\/?#\\[\\]@ ]/';
    // Also includes space.
    /**
     * RFC3986 IPvFuture regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @var string
     */
    private const REGEXP_HOST_IP_FUTURE = '/^
        v(?<version>[A-F\\d])+\\.
        (?:
            (?<unreserved>[a-z\\d_~\\-\\.])|
            (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
        )+
    $/ix';
    /**
     * RFC3986 IPvFuture host and port component.
     *
     * @var string
     */
    private const REGEXP_HOST_PORT = ',^(?<host>(\\[.*]|[^:])*)(:(?<port>[^/?#]*))?$,x';
    /**
     * Significant 10 bits of IP to detect Zone ID regular expression pattern.
     *
     * @var string
     */
    private const HOST_ADDRESS_BLOCK = "\xfe\x80";
    /**
     * Regular expression pattern to for file URI.
     * <volume> contains the volume but not the volume separator.
     * The volume separator may be URL-encoded (`|` as `%7C`) by formatPath(),
     * so we account for that here.
     *
     * @var string
     */
    private const REGEXP_FILE_PATH = ',^(?<delim>/)?(?<volume>[a-zA-Z])(?:[:|\\|]|%7C)(?<rest>.*)?,';
    /**
     * Mimetype regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc2397
     *
     * @var string
     */
    private const REGEXP_MIMETYPE = ',^\\w+/[-.\\w]+(?:\\+[-.\\w]+)?$,';
    /**
     * Base64 content regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc2397
     *
     * @var string
     */
    private const REGEXP_BINARY = ',(;|^)base64$,';
    /**
     * Windows filepath regular expression pattern.
     * <root> contains both the volume and volume separator.
     *
     * @var string
     */
    private const REGEXP_WINDOW_PATH = ',^(?<root>[a-zA-Z][:|\\|]),';
    /**
     * Maximum number of cached items.
     *
     * @var int
     */
    private const MAXIMUM_CACHED_ITEMS = 100;
    /**
     * All ASCII letters sorted by typical frequency of occurrence.
     *
     * @var string
     */
    private const ASCII = " eiasntrolud][cmp'\ng|hv.fb,:=-q10C2*yx)(L9AS/P\"EjMIk3>5T<D4}B{8FwR67UGN;JzV#HOW_&!K?XQ%Y\\\tZ+~^\$@`\x00\x01\x02\x03\x04\x05\x06\x07\x08\v\f\r\x0e\x0f\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f";
    /**
     * @readonly
     * @var string|null
     */
    private $scheme;
    /**
     * @readonly
     * @var string|null
     */
    private $user;
    /**
     * @readonly
     * @var string|null
     */
    private $pass;
    /**
     * @readonly
     * @var string|null
     */
    private $userInfo;
    /**
     * @readonly
     * @var string|null
     */
    private $host;
    /**
     * @readonly
     * @var int|null
     */
    private $port;
    /**
     * @readonly
     * @var string|null
     */
    private $authority;
    /**
     * @readonly
     * @var string
     */
    private $path;
    /**
     * @readonly
     * @var string|null
     */
    private $query;
    /**
     * @readonly
     * @var string|null
     */
    private $fragment;
    /**
     * @readonly
     * @var string
     */
    private $uriAsciiString;
    /**
     * @readonly
     * @var string
     */
    private $uriUnicodeString;
    /**
     * @readonly
     * @var string|null
     */
    private $origin;
    private function __construct(?string $scheme, ?string $user, #[SensitiveParameter] ?string $pass, ?string $host, ?int $port, string $path, ?string $query, ?string $fragment)
    {
        $this->scheme = $this->formatScheme($scheme);
        $this->user = Encoder::encodeUser($user);
        $this->pass = Encoder::encodePassword($pass);
        $this->host = $this->formatHost($host);
        $this->port = $this->formatPort($port);
        $this->path = $this->formatPath($path);
        $this->query = Encoder::encodeQueryOrFragment($query);
        $this->fragment = Encoder::encodeQueryOrFragment($fragment);
        $this->userInfo = null !== $this->pass ? $this->user . ':' . $this->pass : $this->user;
        $this->authority = UriString::buildAuthority($this->toComponents());
        $this->uriAsciiString = UriString::buildUri($this->scheme, $this->authority, $this->path, $this->query, $this->fragment);
        $this->assertValidRfc3986Uri();
        $this->assertValidState();
        $this->origin = $this->setOrigin();
        $host = $this->getUnicodeHost();
        $this->uriUnicodeString = $host === $this->host ? $this->uriAsciiString : UriString::buildUri($this->scheme, UriString::buildAuthority(array_merge($this->toComponents(), ['host' => $host])), $this->path, $this->query, $this->fragment);
    }
    /**
     * Format the Scheme and Host component.
     *
     * @throws SyntaxError if the scheme is invalid
     */
    private function formatScheme(?string $scheme) : ?string
    {
        if (null === $scheme) {
            return null;
        }
        $formattedScheme = strtolower($scheme);
        static $cache = [];
        if (isset($cache[$formattedScheme])) {
            return $formattedScheme;
        }
        null !== UriScheme::tryFrom($formattedScheme) || UriString::isValidScheme($formattedScheme) || throw new SyntaxError('The scheme `' . $scheme . '` is invalid.');
        $cache[$formattedScheme] = 1;
        if (self::MAXIMUM_CACHED_ITEMS < count($cache)) {
            array_shift($cache);
        }
        return $formattedScheme;
    }
    /**
     * Validate and Format the Host component.
     */
    private function formatHost(?string $host) : ?string
    {
        if (null === $host || '' === $host) {
            return $host;
        }
        static $cache = [];
        if (isset($cache[$host])) {
            return $cache[$host];
        }
        $formattedHost = '[' === $host[0] ? $this->formatIp($host) : $this->formatRegisteredName($host);
        $cache[$host] = $formattedHost;
        if (self::MAXIMUM_CACHED_ITEMS < count($cache)) {
            array_shift($cache);
        }
        return $formattedHost;
    }
    /**
     * Validate and format a registered name.
     *
     * The host is converted to its ascii representation if needed
     *
     * @throws MissingFeature if the submitted host required missing or misconfigured IDN support
     * @throws SyntaxError if the submitted host is not a valid registered name
     * @throws ConversionFailed if the submitted IDN host cannot be converted to a valid ascii form
     */
    private function formatRegisteredName(string $host) : string
    {
        $formattedHost = rawurldecode($host);
        if ($formattedHost === $host) {
            switch (1) {
                case preg_match(self::REGEXP_HOST_REGNAME, $formattedHost):
                    return $formattedHost;
                case preg_match(self::REGEXP_HOST_GEN_DELIMS, $formattedHost):
                    throw new SyntaxError('The host `' . $host . '` is invalid : a registered name cannot contain URI delimiters or spaces.');
                default:
                    return IdnaConverter::toAsciiOrFail($host);
            }
        }
        if (IdnaConverter::toAscii($formattedHost)->hasErrors()) {
            throw new SyntaxError('The host `' . $host . '` is invalid : the registered name contains invalid characters.');
        }
        return (string) Encoder::normalizeHost($host);
    }
    /**
     * Validate and Format the IPv6/IPvfuture host.
     *
     * @throws SyntaxError if the submitted host is not a valid IP host
     */
    private function formatIp(string $host) : string
    {
        $ip = substr($host, 1, -1);
        if (\false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $host;
        }
        if (1 === preg_match(self::REGEXP_HOST_IP_FUTURE, $ip, $matches) && !in_array($matches['version'], ['4', '6'], \true)) {
            return $host;
        }
        $pos = strpos($ip, '%');
        if (\false === $pos) {
            throw new SyntaxError('The host `' . $host . '` is invalid : the IP host is malformed.');
        }
        if (1 === preg_match(self::REGEXP_HOST_GEN_DELIMS, rawurldecode(substr($ip, $pos)))) {
            throw new SyntaxError('The host `' . $host . '` is invalid : the IP host is malformed.');
        }
        $ip = substr($ip, 0, $pos);
        if (\false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new SyntaxError('The host `' . $host . '` is invalid : the IP host is malformed.');
        }
        //Only the address block fe80::/10 can have a Zone ID attach to
        //let's detect the link local significant 10 bits
        if (strncmp((string) inet_pton($ip), self::HOST_ADDRESS_BLOCK, strlen(self::HOST_ADDRESS_BLOCK)) === 0) {
            return $host;
        }
        throw new SyntaxError('The host `' . $host . '` is invalid : the IP host is malformed.');
    }
    /**
     * Format the Port component.
     *
     * @throws SyntaxError
     */
    private function formatPort(?int $port = null) : ?int
    {
        $defaultPort = null !== $this->scheme ? ($nullsafeVariable1 = UriScheme::tryFrom($this->scheme)) ? $nullsafeVariable1->port() : null : null;
        switch (\true) {
            case null === $port:
            case $defaultPort === $port:
                return null;
            case 0 > $port:
                throw new SyntaxError('The port `' . $port . '` is invalid.');
            default:
                return $port;
        }
    }
    /**
     * Create a new instance from a string or a stringable structure or returns null on failure.
     * @param Rfc3986Uri|WhatWgUrl|\Matomo\Dependencies\OAuth2\League\Uri\Urn|\Stringable|string $uri
     */
    public static function tryNew($uri = '') : ?self
    {
        try {
            return self::new($uri);
        } catch (Throwable $exception) {
            return null;
        }
    }
    /**
     * Create a new instance from a string.
     * @param Rfc3986Uri|WhatWgUrl|\Matomo\Dependencies\OAuth2\League\Uri\Urn|\Stringable|string $uri
     */
    public static function new($uri = '') : self
    {
        if ($uri instanceof Rfc3986Uri) {
            return new self($uri->getRawScheme(), $uri->getRawUsername(), $uri->getRawPassword(), $uri->getRawHost(), $uri->getPort(), $uri->getRawPath(), $uri->getRawQuery(), $uri->getRawFragment());
        }
        if ($uri instanceof WhatWgUrl) {
            return new self($uri->getScheme(), $uri->getUsername(), $uri->getPassword(), $uri->getAsciiHost(), $uri->getPort(), $uri->getPath(), $uri->getQuery(), $uri->getFragment());
        }
        $uri = (string) $uri;
        trim($uri) === $uri || throw new SyntaxError(sprintf('The uri `%s` contains invalid characters', $uri));
        return new self(...UriString::parse(str_replace(' ', '%20', $uri)));
    }
    /**
     * Returns a new instance from a URI and a Base URI.or null on failure.
     *
     * The returned URI must be absolute if a base URI is provided
     * @param Rfc3986Uri|WhatWgUrl|\Matomo\Dependencies\OAuth2\League\Uri\Urn|\Stringable|string $uri
     * @param Rfc3986Uri|WhatWgUrl|\Matomo\Dependencies\OAuth2\League\Uri\Urn|\Stringable|string|null $baseUri
     */
    public static function parse($uri, $baseUri = null) : ?self
    {
        try {
            if (null === $baseUri) {
                return self::new($uri);
            }
            if ($uri instanceof Rfc3986Uri) {
                $uri = $uri->toRawString();
            }
            if ($uri instanceof WhatWgUrl) {
                $uri = $uri->toAsciiString();
            }
            if ($baseUri instanceof Rfc3986Uri) {
                $baseUri = $baseUri->toRawString();
            }
            if ($baseUri instanceof WhatWgUrl) {
                $baseUri = $baseUri->toAsciiString();
            }
            return self::new(UriString::resolve($uri, $baseUri));
        } catch (Throwable $exception) {
            return null;
        }
    }
    /**
     * Creates a new instance from a template.
     *
     * @throws TemplateCanNotBeExpanded if the variables are invalid or missing
     * @throws UriException if the resulting expansion cannot be converted to a UriInterface instance
     * @param \Matomo\Dependencies\OAuth2\League\Uri\UriTemplate|\Stringable|string $template
     */
    public static function fromTemplate($template, iterable $variables = []) : self
    {
        switch (\true) {
            case $template instanceof UriTemplate:
                return self::new($template->expand($variables));
            case $template instanceof UriTemplate\Template:
                return self::new($template->expand($variables));
            default:
                return self::new(UriTemplate\Template::new($template)->expand($variables));
        }
    }
    /**
     * Create a new instance from a hash representation of the URI similar
     * to PHP parse_url function result.
     *
     * @param InputComponentMap $components a hash representation of the URI similar to PHP parse_url function result
     */
    public static function fromComponents(array $components = []) : self
    {
        $components += ['scheme' => null, 'user' => null, 'pass' => null, 'host' => null, 'port' => null, 'path' => '', 'query' => null, 'fragment' => null];
        if (null === $components['path']) {
            $components['path'] = '';
        }
        return new self($components['scheme'], $components['user'], $components['pass'], $components['host'], $components['port'], $components['path'], $components['query'], $components['fragment']);
    }
    /**
     * Create a new instance from a data file path.
     *
     * @param mixed $path
     * @param ?resource $context
     *
     * @throws MissingFeature If ext/fileinfo is not installed
     * @throws SyntaxError If the file does not exist or is not readable
     */
    public static function fromFileContents($path, $context = null) : self
    {
        FeatureDetection::supportsFileDetection();
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $bufferSize = 8192;
        /** @var Closure(SplFileobject): array{0:string, 1:string} $fromFileObject */
        $fromFileObject = function (SplFileObject $path) use($finfo, $bufferSize) : array {
            $raw = $path->fread($bufferSize);
            \false !== $raw || throw new SyntaxError('The file `' . $path . '` does not exist or is not readable.');
            $mimetype = (string) $finfo->buffer($raw);
            while (!$path->eof()) {
                $raw .= $path->fread($bufferSize);
            }
            return [$mimetype, $raw];
        };
        /** @var Closure(resource): array{0:string, 1:string} $fromResource */
        $fromResource = function ($stream) use($finfo, $path, $bufferSize) : array {
            set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
                return \true;
            });
            $raw = fread($stream, $bufferSize);
            \false !== $raw || throw new SyntaxError('The file `' . $path . '` does not exist or is not readable.');
            $mimetype = (string) $finfo->buffer($raw);
            while (!feof($stream)) {
                $raw .= fread($stream, $bufferSize);
            }
            restore_error_handler();
            return [$mimetype, $raw];
        };
        /** @var Closure(Stringable|string, resource|null): array{0:string, 1:string} $fromPath */
        $fromPath = function ($path, $context) use($finfo) : array {
            $path = (string) $path;
            set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
                return \true;
            });
            $raw = file_get_contents($path, false, $context);
            restore_error_handler();
            \false !== $raw || throw new SyntaxError('The file `' . $path . '` does not exist or is not readable.');
            $mimetype = (string) $finfo->file($path, FILEINFO_MIME, $context);
            return [$mimetype, $raw];
        };
        switch (\true) {
            case $path instanceof SplFileObject:
                [$mimetype, $raw] = $fromFileObject($path);
                break;
            case $path instanceof SplFileInfo:
                [$mimetype, $raw] = $fromFileObject($path->openFile('rb', false, $context));
                break;
            case is_resource($path):
                [$mimetype, $raw] = $fromResource($path);
                break;
            case $path instanceof Stringable:
            case is_string($path):
                [$mimetype, $raw] = $fromPath($path, $context);
                break;
            default:
                throw new TypeError('The path `' . $path . '` is not a valid resource.');
        }
        return Uri::fromComponents(['scheme' => 'data', 'path' => str_replace(' ', '', $mimetype . ';base64,' . base64_encode($raw))]);
    }
    /**
     * Create a new instance from a data URI string.
     *
     * @throws SyntaxError If the parameter syntax is invalid
     * @param \Stringable|string $data
     */
    public static function fromData($data, string $mimetype = '', string $parameters = '') : self
    {
        static $regexpMimetype = ',^\\w+/[-.\\w]+(?:\\+[-.\\w]+)?$,';
        switch (\true) {
            case '' === $mimetype:
                $mimetype = 'text/plain';
                break;
            case 1 === preg_match($regexpMimetype, $mimetype):
                $mimetype = $mimetype;
                break;
            default:
                throw new SyntaxError('Invalid mimeType, `' . $mimetype . '`.');
        }
        $data = (string) $data;
        if ('' === $parameters) {
            return self::fromComponents(['scheme' => 'data', 'path' => self::formatDataPath($mimetype . ',' . rawurlencode($data))]);
        }
        $isInvalidParameter = static function (string $parameter) : bool {
            $properties = explode('=', $parameter);
            return 2 !== count($properties) || 'base64' === strtolower($properties[0]);
        };
        if (strncmp($parameters, ';', strlen(';')) === 0) {
            $parameters = substr($parameters, 1);
        }
        switch ([]) {
            case array_filter(explode(';', $parameters), $isInvalidParameter):
                return self::fromComponents(['scheme' => 'data', 'path' => self::formatDataPath($mimetype . ';' . $parameters . ',' . rawurlencode($data))]);
            default:
                throw new SyntaxError(sprintf('Invalid mediatype parameters, `%s`.', $parameters));
        }
    }
    /**
     * Create a new instance from a Unix path string.
     * @param \Stringable|string $path
     */
    public static function fromUnixPath($path) : self
    {
        $path = implode('/', array_map(\Closure::fromCallable('rawurlencode'), explode('/', (string) $path)));
        return Uri::fromComponents((function () use ($path) {
            switch (\true) {
                case '/' !== ($path[0] ?? ''):
                    return ['path' => $path];
                default:
                    return ['path' => $path, 'scheme' => 'file', 'host' => ''];
            }
        })());
    }
    /**
     * Create a new instance from a local Windows path string.
     * @param \Stringable|string $path
     */
    public static function fromWindowsPath($path) : self
    {
        $root = '';
        $path = (string) $path;
        if (1 === preg_match(self::REGEXP_WINDOW_PATH, $path, $matches)) {
            $root = substr($matches['root'], 0, -1) . ':';
            $path = substr($path, strlen($root));
        }
        $path = str_replace('\\', '/', $path);
        $path = implode('/', array_map(\Closure::fromCallable('rawurlencode'), explode('/', $path)));
        //Local Windows absolute path
        if ('' !== $root) {
            return Uri::fromComponents(['path' => '/' . $root . $path, 'scheme' => 'file', 'host' => '']);
        }
        //UNC Windows Path
        if (strncmp($path, '//', strlen('//')) !== 0) {
            return Uri::fromComponents(['path' => $path]);
        }
        [$host, $path] = explode('/', substr($path, 2), 2) + [1 => ''];
        return Uri::fromComponents(['host' => $host, 'path' => '/' . $path, 'scheme' => 'file']);
    }
    /**
     * Creates a new instance from a RFC8089 compatible URI.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc8089
     * @param \Stringable|string $uri
     * @return static
     */
    public static function fromRfc8089($uri)
    {
        $fileUri = self::new((string) preg_replace(',^(file:/)([^/].*)$,i', 'file:///$2', (string) $uri));
        $scheme = $fileUri->getScheme();
        switch (\true) {
            case 'file' !== $scheme:
                throw new SyntaxError('As per RFC8089, the URI scheme must be `file`.');
            case 'localhost' === $fileUri->getAuthority():
                return $fileUri->withHost('');
            default:
                return $fileUri;
        }
    }
    /**
     * Create a new instance from the environment.
     */
    public static function fromServer(array $server) : self
    {
        $components = ['scheme' => self::fetchScheme($server)];
        [$components['user'], $components['pass']] = self::fetchUserInfo($server);
        [$components['host'], $components['port']] = self::fetchHostname($server);
        [$components['path'], $components['query']] = self::fetchRequestUri($server);
        return Uri::fromComponents($components);
    }
    /**
     * Returns the environment scheme.
     */
    private static function fetchScheme(array $server) : string
    {
        $server += ['HTTPS' => ''];
        switch (\true) {
            case \false !== filter_var($server['HTTPS'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE):
                return 'https';
            default:
                return 'http';
        }
    }
    /**
     * Returns the environment user info.
     *
     * @return non-empty-array {0: ?string, 1: ?string}
     */
    private static function fetchUserInfo(array $server) : array
    {
        $server += ['PHP_AUTH_USER' => null, 'PHP_AUTH_PW' => null, 'HTTP_AUTHORIZATION' => ''];
        $user = $server['PHP_AUTH_USER'];
        $pass = $server['PHP_AUTH_PW'];
        if (strncmp(strtolower($server['HTTP_AUTHORIZATION']), 'basic', strlen('basic')) === 0) {
            $userinfo = base64_decode(substr($server['HTTP_AUTHORIZATION'], 6), \true);
            \false !== $userinfo || throw new SyntaxError('The user info could not be detected');
            [$user, $pass] = explode(':', $userinfo, 2) + [1 => null];
        }
        if (null !== $user) {
            $user = rawurlencode($user);
        }
        if (null !== $pass) {
            $pass = rawurlencode($pass);
        }
        return [$user, $pass];
    }
    /**
     * Returns the environment host.
     *
     * @throws SyntaxError If the host cannot be detected
     *
     * @return array{0:string|null, 1:int|null}
     */
    private static function fetchHostname(array $server) : array
    {
        $server += ['SERVER_PORT' => null];
        if (null !== $server['SERVER_PORT']) {
            $server['SERVER_PORT'] = (int) $server['SERVER_PORT'];
        }
        if (isset($server['HTTP_HOST']) && 1 === preg_match(self::REGEXP_HOST_PORT, $server['HTTP_HOST'], $matches)) {
            $matches += ['host' => null, 'port' => null];
            if (null !== $matches['port']) {
                $matches['port'] = (int) $matches['port'];
            }
            return [$matches['host'], $matches['port'] ?? $server['SERVER_PORT']];
        }
        isset($server['SERVER_ADDR']) || throw new SyntaxError('The host could not be detected');
        if (\false === filter_var($server['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ['[' . $server['SERVER_ADDR'] . ']', $server['SERVER_PORT']];
        }
        return [$server['SERVER_ADDR'], $server['SERVER_PORT']];
    }
    /**
     * Returns the environment path.
     *
     * @return list<?string>
     */
    private static function fetchRequestUri(array $server) : array
    {
        $server += ['IIS_WasUrlRewritten' => null, 'UNENCODED_URL' => '', 'PHP_SELF' => '', 'QUERY_STRING' => null];
        if ('1' === $server['IIS_WasUrlRewritten'] && '' !== $server['UNENCODED_URL']) {
            return explode('?', $server['UNENCODED_URL'], 2) + [1 => null];
        }
        if (isset($server['REQUEST_URI'])) {
            [$path] = explode('?', $server['REQUEST_URI'], 2);
            $query = '' !== $server['QUERY_STRING'] ? $server['QUERY_STRING'] : null;
            return [$path, $query];
        }
        return [$server['PHP_SELF'], $server['QUERY_STRING']];
    }
    /**
     * Format the Path component.
     */
    private function formatPath(string $path) : string
    {
        switch ($this->scheme) {
            case 'data':
                return Encoder::encodePath(self::formatDataPath($path));
            case 'file':
                return self::formatFilePath(Encoder::encodePath($path));
            default:
                return Encoder::encodePath($path);
        }
    }
    /**
     * Filter the Path component.
     *
     * @link https://tools.ietf.org/html/rfc2397
     *
     * @throws SyntaxError If the path is not compliant with RFC2397
     */
    private static function formatDataPath(string $path) : string
    {
        if ('' == $path) {
            return 'text/plain;charset=us-ascii,';
        }
        if (strlen($path) !== strspn($path, self::ASCII) || strpos($path, ',') === false) {
            throw new SyntaxError('The path `' . $path . '` is invalid according to RFC2937.');
        }
        $parts = explode(',', $path, 2) + [1 => null];
        $mediatype = explode(';', (string) $parts[0], 2) + [1 => null];
        $data = (string) $parts[1];
        $mimetype = $mediatype[0];
        if (null === $mimetype || '' === $mimetype) {
            $mimetype = 'text/plain';
        }
        $parameters = $mediatype[1];
        if (null === $parameters || '' === $parameters) {
            $parameters = 'charset=us-ascii';
        }
        self::assertValidPath($mimetype, $parameters, $data);
        return $mimetype . ';' . $parameters . ',' . $data;
    }
    /**
     * Assert the path is a compliant with RFC2397.
     *
     * @link https://tools.ietf.org/html/rfc2397
     *
     * @throws SyntaxError If the mediatype or the data are not compliant with the RFC2397
     */
    private static function assertValidPath(string $mimetype, string $parameters, string $data) : void
    {
        1 === preg_match(self::REGEXP_MIMETYPE, $mimetype) || throw new SyntaxError('The path mimetype `' . $mimetype . '` is invalid.');
        $isBinary = 1 === preg_match(self::REGEXP_BINARY, $parameters, $matches);
        if ($isBinary) {
            $parameters = substr($parameters, 0, -strlen($matches[0]));
        }
        $res = array_filter(array_filter(explode(';', $parameters), \Closure::fromCallable([self::class, 'validateParameter'])));
        [] === $res || throw new SyntaxError('The path parameters `' . $parameters . '` is invalid.');
        if (!$isBinary) {
            return;
        }
        $res = base64_decode($data, \true);
        if (\false === $res || $data !== base64_encode($res)) {
            throw new SyntaxError('The path data `' . $data . '` is invalid.');
        }
    }
    /**
     * Validate mediatype parameter.
     */
    private static function validateParameter(string $parameter) : bool
    {
        $properties = explode('=', $parameter);
        return 2 != count($properties) || 'base64' === strtolower($properties[0]);
    }
    /**
     * Format the path component for the URI scheme file.
     */
    private static function formatFilePath(string $path) : string
    {
        return (string) preg_replace_callback(self::REGEXP_FILE_PATH, static function (array $matches) : string {
            return $matches['delim'] . $matches['volume'] . (isset($matches['rest']) ? ':' . $matches['rest'] : '');
        }, $path);
    }
    /**
     * assert the URI internal state is valid.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3
     * @link https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @throws SyntaxError if the URI is in an invalid state, according to RFC3986
     */
    private function assertValidRfc3986Uri() : void
    {
        if (null !== $this->authority && ('' !== $this->path && '/' !== $this->path[0])) {
            throw new SyntaxError('If an authority is present the path must be empty or start with a `/`.');
        }
        if (null === $this->authority && strncmp($this->path, '//', strlen('//')) === 0) {
            throw new SyntaxError('If there is no authority the path `' . $this->path . '` cannot start with a `//`.');
        }
        $pos = strpos($this->path, ':');
        if (null === $this->authority && null === $this->scheme && \false !== $pos && strpos(substr($this->path, 0, $pos), '/') === false) {
            throw new SyntaxError('In absence of a scheme and an authority the first path segment cannot contain a colon (":") character.');
        }
    }
    /**
     * assert the URI scheme is valid
     *
     * @link https://w3c.github.io/FileAPI/#url
     * @link https://datatracker.ietf.org/doc/html/rfc2397
     * @link https://tools.ietf.org/html/rfc3986#section-3
     * @link https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @throws SyntaxError if the URI is in an invalid state, according to scheme-specific rules
     */
    private function assertValidState() : void
    {
        $scheme = UriScheme::tryFrom((string) $this->scheme);
        if (null === $scheme) {
            return;
        }
        $schemeType = $scheme->type();
        switch ($scheme) {
            case UriScheme::Blob:
            case UriScheme::Mailto:
            case UriScheme::Data:
            case UriScheme::About:
            case UriScheme::Javascript:
            case UriScheme::File:
            case UriScheme::Ftp:
            case UriScheme::Gopher:
            case UriScheme::Afp:
            case UriScheme::Dict:
            case UriScheme::Msrps:
            case UriScheme::Msrp:
            case UriScheme::Mtqp:
            case UriScheme::Rsync:
            case UriScheme::Ssh:
            case UriScheme::Svn:
            case UriScheme::Snmp:
            case UriScheme::Https:
            case UriScheme::Http:
            case UriScheme::Ws:
            case UriScheme::Wss:
            case UriScheme::Ipp:
            case UriScheme::Ipps:
            case UriScheme::Ldap:
            case UriScheme::Ldaps:
            case UriScheme::Acap:
            case UriScheme::Imaps:
            case UriScheme::Imap:
            case UriScheme::Redis:
            case UriScheme::Prospero:
            case UriScheme::Urn:
            case UriScheme::Telnet:
            case UriScheme::Tn3270:
            case UriScheme::Vnc:
            default:
        }
    }
    private function isValidBlob() : bool
    {
        static $regexpUuidRfc4122 = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        if (!$this->isUriWithSchemeAndPathOnly() || '' === $this->path || strpos($this->path, '/') === false || substr_compare($this->path, '/', -strlen('/')) === 0 || 1 !== preg_match($regexpUuidRfc4122, basename($this->path))) {
            return \false;
        }
        $origin = dirname($this->path);
        if ('null' === $origin) {
            return \true;
        }
        try {
            $components = UriString::parse($origin);
            return '' === $components['path'] && null === $components['query'] && null === $components['fragment'] && \true === (($nullsafeVariable2 = UriScheme::tryFrom((string) $components['scheme'])) ? $nullsafeVariable2->isWhatWgSpecial() : null);
        } catch (UriException $exception) {
            return \false;
        }
    }
    private function isValidMailto() : bool
    {
        if (null !== $this->authority || null !== $this->fragment || strpos((string) $this->query, '?') !== false) {
            return \false;
        }
        static $mailHeaders = ['to', 'cc', 'bcc', 'reply-to', 'from', 'sender', 'resent-to', 'resent-cc', 'resent-bcc', 'resent-from', 'resent-sender', 'return-path', 'delivery-to', 'site-owner'];
        static $headerRegexp = '/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/D';
        $pairs = QueryString::parseFromValue($this->query);
        $hasTo = \false;
        foreach ($pairs as [$name, $value]) {
            $headerName = strtolower($name);
            if (in_array($headerName, $mailHeaders, \true)) {
                if (null === $value || !self::validateEmailList($value)) {
                    return \false;
                }
                if (!$hasTo && 'to' === $headerName) {
                    $hasTo = \true;
                }
                continue;
            }
            if (1 !== preg_match($headerRegexp, (string) Encoder::decodeAll($name))) {
                return \false;
            }
        }
        return '' === $this->path ? $hasTo : self::validateEmailList($this->path);
    }
    private static function validateEmailList(string $emails) : bool
    {
        foreach (explode(',', $emails) as $email) {
            if (\false === filter_var((string) Encoder::decodeAll($email), FILTER_VALIDATE_EMAIL)) {
                return \false;
            }
        }
        return '' !== $emails;
    }
    /**
     * Sets the URI origin.
     *
     * The origin read-only property of the URL interface returns a string containing
     * the Unicode serialization of the represented URL.
     */
    private function setOrigin() : ?string
    {
        try {
            if ('blob' !== $this->scheme) {
                if (!((($nullsafeVariable3 = UriScheme::tryFrom($this->scheme ?? '')) ? $nullsafeVariable3->isWhatWgSpecial() : null) ?? \false)) {
                    return null;
                }
                $host = $this->host;
                $converted = $host;
                if (null !== $converted) {
                    try {
                        $converted = IPv4Converter::fromEnvironment()->toDecimal($host);
                    } catch (MissingFeature $exception) {
                        $converted = null;
                    }
                    if (\false === filter_var($converted, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $converted = IPv6Converter::compress($host);
                    }
                    /** @var string $converted */
                    if ($converted !== $host) {
                        $converted = Idna\Converter::toAscii($converted)->domain();
                    }
                }
                return $this->withFragment(null)->withQuery(null)->withPath('')->withUserInfo(null)->withHost($converted)->toString();
            }
            $components = UriString::parse($this->path);
            $scheme = strtolower($components['scheme'] ?? '');
            if (!((($nullsafeVariable4 = UriScheme::tryFrom($scheme)) ? $nullsafeVariable4->isWhatWgSpecial() : null) ?? \false)) {
                return null;
            }
            return self::fromComponents($components)->origin;
        } catch (UriException $exception) {
            return null;
        }
    }
    /**
     * URI validation for URI schemes which allows only scheme and path components.
     */
    private function isUriWithSchemeAndPathOnly() : bool
    {
        return null === $this->authority && null === $this->query && null === $this->fragment;
    }
    /**
     * URI validation for URI schemes which allows only scheme, host and path components.
     */
    private function isUriWithSchemeHostAndPathOnly() : bool
    {
        return null === $this->userInfo && null === $this->port && null === $this->query && null === $this->fragment && !('' != $this->scheme && null === $this->host);
    }
    /**
     * URI validation for URI schemes which disallow the empty '' host.
     */
    private function isNonEmptyHostUri() : bool
    {
        return '' !== $this->host && !(null !== $this->scheme && null === $this->host);
    }
    /**
     * URI validation for URIs schemes which disallow the empty '' host
     * and forbids the fragment component.
     */
    private function isNonEmptyHostUriWithoutFragment() : bool
    {
        return $this->isNonEmptyHostUri() && null === $this->fragment;
    }
    /**
     * URI validation for URIs schemes which disallow the empty '' host
     * and forbids fragment and query components.
     */
    private function isNonEmptyHostUriWithoutFragmentAndQuery() : bool
    {
        return $this->isNonEmptyHostUri() && null === $this->fragment && null === $this->query;
    }
    public function __toString() : string
    {
        return $this->toString();
    }
    /**
     * Returns the string representation as a URI reference.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @see ::toString
     */
    public function jsonSerialize() : string
    {
        return $this->toString();
    }
    /**
     * Returns the string representation as a URI reference.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     */
    public function toString() : string
    {
        return $this->toAsciiString();
    }
    /**
     * Returns the string representation as a URI reference.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     */
    public function toAsciiString() : string
    {
        return $this->uriAsciiString;
    }
    /**
     * Returns the string representation as a URI reference.
     *
     * The host is converted to its UNICODE representation if available
     */
    public function toUnicodeString() : string
    {
        return $this->uriUnicodeString;
    }
    /**
     * Returns the human-readable string representation of the URI as an IRI.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3987
     */
    public function toDisplayString() : string
    {
        return UriString::toIriString($this->toString());
    }
    /**
     * Returns the Unix filesystem path.
     *
     * The method will return null if a scheme is present and is not the `file` scheme
     */
    public function toUnixPath() : ?string
    {
        switch ($this->scheme) {
            case 'file':
            case null:
                return rawurldecode($this->path);
            default:
                return null;
        }
    }
    /**
     * Returns the Windows filesystem path.
     *
     * The method will return null if a scheme is present and is not the `file` scheme
     */
    public function toWindowsPath() : ?string
    {
        static $regexpWindowsPath = ',^(?<root>[a-zA-Z]:),';
        if (!in_array($this->scheme, ['file', null], \true)) {
            return null;
        }
        $originalPath = $this->path;
        $path = $originalPath;
        if ('/' === ($path[0] ?? '')) {
            $path = substr($path, 1);
        }
        if (1 === preg_match($regexpWindowsPath, $path, $matches)) {
            $root = $matches['root'];
            $path = substr($path, strlen($root));
            return $root . str_replace('/', '\\', rawurldecode($path));
        }
        $host = $this->host;
        switch (null) {
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
     *
     * @see https://datatracker.ietf.org/doc/html/rfc8089
     */
    public function toRfc8089() : ?string
    {
        $path = $this->path;
        switch (\true) {
            case 'file' !== $this->scheme:
                return null;
            case in_array($this->authority, ['', null, 'localhost'], \true):
                switch (\true) {
                    case '' === $path:
                    case '/' === $path[0]:
                        return $path;
                    default:
                        return '/' . $path;
                }
            default:
                return $this->toString();
        }
    }
    /**
     * Save the data to a specific file.
     *
     * The method returns the number of bytes written to the file
     * or null for any other scheme except the data scheme
     *
     * @param mixed $destination
     * @param ?resource $context
     *
     * @throws RuntimeException if the content cannot be stored.
     */
    public function toFileContents($destination, $context = null) : ?int
    {
        if ('data' !== $this->scheme) {
            return null;
        }
        [$mediaType, $document] = explode(',', $this->path, 2) + [0 => '', 1 => null];
        null !== $document || throw new RuntimeException('Unable to extract the document part from the URI path.');
        switch (\true) {
            case substr_compare((string) $mediaType, ';base64', -strlen(';base64')) === 0:
                $data = (string) base64_decode($document, \true);
                break;
            default:
                $data = rawurldecode($document);
                break;
        }
        switch (\true) {
            case $destination instanceof SplFileObject:
                $res = $destination->fwrite($data);
                break;
            case $destination instanceof SplFileInfo:
                $res = $destination->openFile('wb', false, $context)->fwrite($data);
                break;
            case is_resource($destination):
                $res = fwrite($destination, $data);
                break;
            case $destination instanceof Stringable:
            case is_string($destination):
                $res = (function () use($destination, $data, $context) {
                    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
                        return \true;
                    });
                    $rsrc = fopen((string) $destination, 'wb', false, $context);
                    if (\false === $rsrc) {
                        restore_error_handler();
                        throw new RuntimeException('Unable to open the destination file: ' . $destination);
                    }
                    $bytes = fwrite($rsrc, $data);
                    fclose($rsrc);
                    restore_error_handler();
                    return $bytes;
                })();
                break;
            default:
                throw new TypeError('Unsupported destination type; expected SplFileObject, SplFileInfo, resource or a string; ' . (is_object($destination) ? get_class($destination) : gettype($destination)) . ' given.');
        }
        \false !== $res || throw new RuntimeException('Unable to write to the destination file.');
        return $res;
    }
    /**
     * Returns an associative array containing all the URI components.
     *
     * @return ComponentMap
     */
    public function toComponents() : array
    {
        return ['scheme' => $this->scheme, 'user' => $this->user, 'pass' => $this->pass, 'host' => $this->host, 'port' => $this->port, 'path' => $this->path, 'query' => $this->query, 'fragment' => $this->fragment];
    }
    public function getScheme() : ?string
    {
        return $this->scheme;
    }
    public function getAuthority() : ?string
    {
        return $this->authority;
    }
    /**
     * Returns the user component encoded value.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     */
    public function getUsername() : ?string
    {
        return $this->user;
    }
    public function getPassword() : ?string
    {
        return $this->pass;
    }
    public function getUserInfo() : ?string
    {
        return $this->userInfo;
    }
    public function getHost() : ?string
    {
        return $this->host;
    }
    public function getUnicodeHost() : ?string
    {
        if (null === $this->host) {
            return null;
        }
        $host = IdnaConverter::toUnicode($this->host)->domain();
        if ($host === $this->host) {
            return $this->host;
        }
        return $host;
    }
    public function getPort() : ?int
    {
        return $this->port;
    }
    public function getPath() : string
    {
        return $this->path;
    }
    public function getQuery() : ?string
    {
        return $this->query;
    }
    public function getFragment() : ?string
    {
        return $this->fragment;
    }
    public function getOrigin() : ?string
    {
        return $this->origin;
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
     * @param \Stringable|string|null $scheme
     * @return static
     */
    public function withScheme($scheme)
    {
        $scheme = $this->formatScheme($this->filterString($scheme));
        switch ($scheme) {
            case $this->scheme:
                return $this;
            default:
                return new self($scheme, $this->user, $this->pass, $this->host, $this->port, $this->path, $this->query, $this->fragment);
        }
    }
    /**
     * Filter a string.
     *
     * @throws SyntaxError if the submitted data cannot be converted to string
     * @param \Stringable|string|null $str
     */
    private function filterString($str) : ?string
    {
        switch (\true) {
            case $str instanceof UriComponentInterface:
                $str = $str->value();
                break;
            case null === $str:
                $str = null;
                break;
            default:
                $str = (string) $str;
                break;
        }
        switch (\true) {
            case null === $str:
                return null;
            case 1 === preg_match(self::REGEXP_INVALID_CHARS, $str):
                throw new SyntaxError('The component `' . $str . '` contains invalid characters.');
            default:
                return $str;
        }
    }
    /**
     * @param \Stringable|string|null $user
     * @param \Stringable|string|null $password
     * @return static
     */
    public function withUserInfo($user, #[SensitiveParameter] $password = null)
    {
        $user = Encoder::encodeUser($this->filterString($user));
        $pass = Encoder::encodePassword($this->filterString($password));
        $userInfo = $user;
        if (null !== $password) {
            $userInfo .= ':' . $pass;
        }
        switch ($userInfo) {
            case $this->userInfo:
                return $this;
            default:
                return new self($this->scheme, $user, $pass, $this->host, $this->port, $this->path, $this->query, $this->fragment);
        }
    }
    /**
     * @param \Stringable|string|null $user
     * @return static
     */
    public function withUsername($user)
    {
        return $this->withUserInfo($user, $this->pass);
    }
    /**
     * @param \Stringable|string|null $password
     * @return static
     */
    public function withPassword(#[SensitiveParameter] $password)
    {
        return $this->withUserInfo($this->user, $password);
    }
    /**
     * @param \Stringable|string|null $host
     * @return static
     */
    public function withHost($host)
    {
        $host = $this->formatHost($this->filterString($host));
        switch ($host) {
            case $this->host:
                return $this;
            default:
                return new self($this->scheme, $this->user, $this->pass, $host, $this->port, $this->path, $this->query, $this->fragment);
        }
    }
    /**
     * @return static
     */
    public function withPort(?int $port)
    {
        $port = $this->formatPort($port);
        switch ($port) {
            case $this->port:
                return $this;
            default:
                return new self($this->scheme, $this->user, $this->pass, $this->host, $port, $this->path, $this->query, $this->fragment);
        }
    }
    /**
     * @param \Stringable|string $path
     * @return static
     */
    public function withPath($path)
    {
        if ($this->filterString($path) === null) {
            throw new SyntaxError('The path component cannot be null.');
        }
        $path = $this->formatPath($this->filterString($path));
        switch ($path) {
            case $this->path:
                return $this;
            default:
                return new self($this->scheme, $this->user, $this->pass, $this->host, $this->port, $path, $this->query, $this->fragment);
        }
    }
    /**
     * @param \Stringable|string|null $query
     * @return static
     */
    public function withQuery($query)
    {
        $query = Encoder::encodeQueryOrFragment($this->filterString($query));
        switch ($query) {
            case $this->query:
                return $this;
            default:
                return new self($this->scheme, $this->user, $this->pass, $this->host, $this->port, $this->path, $query, $this->fragment);
        }
    }
    /**
     * @param \Stringable|string|null $fragment
     * @return static
     */
    public function withFragment($fragment)
    {
        if ($fragment instanceof FragmentDirective) {
            $fragment = ':~:' . $fragment->toString();
        }
        $fragment = Encoder::encodeQueryOrFragment($this->filterString($fragment));
        switch ($fragment) {
            case $this->fragment:
                return $this;
            default:
                return new self($this->scheme, $this->user, $this->pass, $this->host, $this->port, $this->path, $this->query, $fragment);
        }
    }
    /**
     * Tells whether the `file` scheme base URI represents a local file.
     */
    public function isLocalFile() : bool
    {
        switch (\true) {
            case 'file' !== $this->scheme:
                return \false;
            case in_array($this->authority, ['', null, 'localhost'], \true):
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
        return null === $this->authority && null !== $this->scheme;
    }
    /**
     * Tells whether two URI do not share the same origin.
     * @param Rfc3986Uri|WhatWgUrl|\Matomo\Dependencies\OAuth2\League\Uri\Urn|\Stringable|string $uri
     */
    public function isCrossOrigin($uri) : bool
    {
        if (null === $this->origin) {
            return \true;
        }
        $uri = self::tryNew($uri);
        if (null === $uri || null === ($origin = $uri->getOrigin())) {
            return \true;
        }
        return $this->origin !== $origin;
    }
    /**
     * @param Rfc3986Uri|WhatWgUrl|\Matomo\Dependencies\OAuth2\League\Uri\Urn|\Stringable|string $uri
     */
    public function isSameOrigin($uri) : bool
    {
        return !$this->isCrossOrigin($uri);
    }
    /**
     * Tells whether the URI is absolute.
     */
    public function isAbsolute() : bool
    {
        return null !== $this->scheme;
    }
    /**
     * Tells whether the URI is a network path.
     */
    public function isNetworkPath() : bool
    {
        return null === $this->scheme && null !== $this->authority;
    }
    /**
     * Tells whether the URI is an absolute path.
     */
    public function isAbsolutePath() : bool
    {
        return null === $this->scheme && null === $this->authority && '/' === ($this->path[0] ?? '');
    }
    /**
     * Tells whether the URI is a relative path.
     */
    public function isRelativePath() : bool
    {
        return null === $this->scheme && null === $this->authority && '/' !== ($this->path[0] ?? '');
    }
    /**
     * Tells whether both URIs refer to the same document.
     * @param Rfc3986Uri|WhatWgUrl|\Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriInterface|\Stringable|\Matomo\Dependencies\OAuth2\League\Uri\Urn|string $uri
     */
    public function isSameDocument($uri) : bool
    {
        return $this->equals($uri);
    }
    /**
     * @param Rfc3986Uri|WhatWgUrl|\Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriInterface|\Stringable|\Matomo\Dependencies\OAuth2\League\Uri\Urn|string $uri
     * @param \Matomo\Dependencies\OAuth2\League\Uri\UriComparisonMode::* $uriComparisonMode
     */
    public function equals($uri, $uriComparisonMode = UriComparisonMode::ExcludeFragment) : bool
    {
        if (!$uri instanceof UriInterface && !$uri instanceof Rfc3986Uri && !$uri instanceof WhatWgUrl) {
            $uri = self::tryNew($uri);
        }
        if (null === $uri) {
            return \false;
        }
        $baseUri = $this;
        if (UriComparisonMode::ExcludeFragment === $uriComparisonMode) {
            $uri = $uri->withFragment(null);
            $baseUri = $baseUri->withFragment(null);
        }
        switch (\true) {
            case $uri instanceof Rfc3986Uri:
                return $uri->toString();
            case $uri instanceof WhatWgUrl:
                return $uri->toAsciiString();
            default:
                return $uri->normalize()->toString();
        }
    }
    /**
     * Normalize a URI by applying non-destructive and destructive normalization
     * rules as defined in RFC3986 and RFC3987.
     * @return static
     */
    public function normalize()
    {
        $uriString = $this->toString();
        if ('' === $uriString) {
            return $this;
        }
        $normalizedUriString = UriString::normalize($uriString);
        $normalizedUri = self::new($normalizedUriString);
        if (null !== $normalizedUri->getAuthority() && ('' === $normalizedUri->getPath() && ((($nullsafeVariable5 = UriScheme::tryFrom($normalizedUri->getScheme() ?? '')) ? $nullsafeVariable5->isWhatWgSpecial() : null) ?? \false))) {
            $normalizedUri = $normalizedUri->withPath('/');
        }
        if ($normalizedUri->toString() === $uriString) {
            return $this;
        }
        return $normalizedUri;
    }
    /**
     * Resolves a URI against a base URI using RFC3986 rules.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * a URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with errors and exceptions.
     * It MUST not alter or silence them apart from validating its own parameters.
     * @param Rfc3986Uri|WhatWgUrl|\Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriInterface|\Stringable|\Matomo\Dependencies\OAuth2\League\Uri\Urn|string $uri
     * @return static
     */
    public function resolve($uri)
    {
        return self::new(UriString::resolve((function () use ($uri) {
            switch (\true) {
                case $uri instanceof UriInterface:
                case $uri instanceof Rfc3986Uri:
                    return $uri->toString();
                case $uri instanceof WhatWgUrl:
                    return $uri->toAsciiString();
                default:
                    return $uri;
            }
        })(), $this->toString()));
    }
    /**
     * Relativize a URI according to a base URI.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * a URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter of silence them apart from validating its own parameters.
     * @param Rfc3986Uri|WhatWgUrl|\Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriInterface|\Stringable|\Matomo\Dependencies\OAuth2\League\Uri\Urn|string $uri
     * @return static
     */
    public function relativize($uri)
    {
        $uri = self::new($uri);
        if ($this->scheme !== $uri->getScheme() || $this->authority !== $uri->getAuthority() || $uri->isRelativePath()) {
            return $uri;
        }
        $targetPath = $uri->getPath();
        $basePath = $this->path;
        $uri = $uri->withScheme(null)->withUserInfo(null)->withPort(null)->withHost(null);
        switch (\true) {
            case $targetPath !== $basePath:
                return $uri->withPath(self::relativizePath($targetPath, $basePath));
            case $this->query === $uri->getQuery():
                return $uri->withPath('')->withQuery(null);
            case null === $uri->getQuery():
                return $uri->withPath(self::formatPathWithEmptyBaseQuery($targetPath));
            default:
                return $uri->withPath('');
        }
    }
    /**
     * Formatting the path to keep a resolvable URI.
     */
    private static function formatPathWithEmptyBaseQuery(string $path) : string
    {
        $targetSegments = self::getSegments($path);
        $basename = $targetSegments[array_key_last($targetSegments)];
        return '' === $basename ? './' : $basename;
    }
    /**
     * Relatives the URI for an authority-less target URI.
     */
    private static function relativizePath(string $path, string $basePath) : string
    {
        $baseSegments = self::getSegments($basePath);
        $targetSegments = self::getSegments($path);
        $targetBasename = array_pop($targetSegments);
        array_pop($baseSegments);
        foreach ($baseSegments as $offset => $segment) {
            if (!isset($targetSegments[$offset]) || $segment !== $targetSegments[$offset]) {
                break;
            }
            unset($baseSegments[$offset], $targetSegments[$offset]);
        }
        $targetSegments[] = $targetBasename;
        return static::formatRelativePath(str_repeat('../', count($baseSegments)) . implode('/', $targetSegments), $basePath);
    }
    /**
     * Formatting the path to keep a valid URI.
     */
    private static function formatRelativePath(string $path, string $basePath) : string
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
     * returns the path segments.
     *
     * @return array<string>
     */
    private static function getSegments(string $path) : array
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
     * @return ComponentMap
     */
    public function __debugInfo() : array
    {
        return $this->toComponents();
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.6.0
     * @codeCoverageIgnore
     * @see Uri::parse()
     *
     * Creates a new instance from a URI and a Base URI.
     *
     * The returned URI must be absolute.
     * @param WhatWgUrl|Rfc3986Uri|\Stringable|string $uri
     * @param WhatWgUrl|Rfc3986Uri|\Stringable|string|null $baseUri
     */
    #[Deprecated(message: 'use League\\Uri\\Uri::parse() instead', since: 'league/uri:7.6.0')]
    public static function fromBaseUri($uri, $baseUri = null) : self
    {
        if ($uri instanceof Rfc3986Uri) {
            $uri = $uri->toRawString();
        }
        if ($uri instanceof WhatWgUrl) {
            $uri = $uri->toAsciiString();
        }
        if ($baseUri instanceof Rfc3986Uri) {
            $baseUri = $baseUri->toRawString();
        }
        if ($baseUri instanceof WhatWgUrl) {
            $baseUri = $baseUri->toAsciiString();
        }
        return self::new(UriString::resolve($uri, $baseUri));
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.5.0
     * @codeCoverageIgnore
     * @see Uri::toComponents()
     *
     * @return ComponentMap
     */
    #[Deprecated(message: 'use League\\Uri\\Uri::toComponents() instead', since: 'league/uri:7.5.0')]
    public function getComponents() : array
    {
        return $this->toComponents();
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Uri::new()
     * @param \Stringable|string $uri
     */
    #[Deprecated(message: 'use League\\Uri\\Uri::new() instead', since: 'league/uri:7.0.0')]
    public static function createFromString($uri = '') : self
    {
        return self::new($uri);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Uri::fromComponents()
     *
     * @param InputComponentMap $components a hash representation of the URI similar to PHP parse_url function result
     */
    #[Deprecated(message: 'use League\\Uri\\Uri::fromComponents() instead', since: 'league/uri:7.0.0')]
    public static function createFromComponents(array $components = []) : self
    {
        return self::fromComponents($components);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @param resource|null $context
     *
     * @throws MissingFeature If ext/fileinfo is not installed
     * @throws SyntaxError If the file does not exist or is not readable
     * @see Uri::fromFileContents()
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     */
    #[Deprecated(message: 'use League\\Uri\\Uri::fromDataPath() instead', since: 'league/uri:7.0.0')]
    public static function createFromDataPath(string $path, $context = null) : self
    {
        return self::fromFileContents($path, $context);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Uri::fromBaseUri()
     *
     * Creates a new instance from a URI and a Base URI.
     *
     * The returned URI must be absolute.
     * @param \Stringable|\Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriInterface|string $uri
     * @param \Stringable|\Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriInterface|string|null $baseUri
     * @return static
     */
    #[Deprecated(message: 'use League\\Uri\\Uri::fromBaseUri() instead', since: 'league/uri:7.0.0')]
    public static function createFromBaseUri($uri, $baseUri = null)
    {
        return self::fromBaseUri($uri, $baseUri);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Uri::fromUnixPath()
     *
     * Create a new instance from a Unix path string.
     */
    #[Deprecated(message: 'use League\\Uri\\Uri::fromUnixPath() instead', since: 'league/uri:7.0.0')]
    public static function createFromUnixPath(string $uri = '') : self
    {
        return self::fromUnixPath($uri);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Uri::fromWindowsPath()
     *
     * Create a new instance from a local Windows path string.
     */
    #[Deprecated(message: 'use League\\Uri\\Uri::fromWindowsPath() instead', since: 'league/uri:7.0.0')]
    public static function createFromWindowsPath(string $uri = '') : self
    {
        return self::fromWindowsPath($uri);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Uri::new()
     *
     * Create a new instance from a URI object.
     * @param Psr7UriInterface|\Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriInterface $uri
     */
    #[Deprecated(message: 'use League\\Uri\\Uri::new() instead', since: 'league/uri:7.0.0')]
    public static function createFromUri($uri) : self
    {
        return self::new($uri);
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Uri::fromServer()
     *
     * Create a new instance from the environment.
     */
    #[Deprecated(message: 'use League\\Uri\\Uri::fromServer() instead', since: 'league/uri:7.0.0')]
    public static function createFromServer(array $server) : self
    {
        return self::fromServer($server);
    }
}
