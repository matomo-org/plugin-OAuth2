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

use Closure;
use JsonSerializable;
use Matomo\Dependencies\Oauth2\League\Uri\Contracts\Conditionable;
use Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriComponentInterface;
use Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriInterface;
use Matomo\Dependencies\Oauth2\League\Uri\Exceptions\SyntaxError;
use Matomo\Dependencies\Oauth2\League\Uri\UriTemplate\Template;
use Stringable;
use Uri\Rfc3986\Uri as Rfc3986Uri;
use Uri\WhatWg\Url as WhatWgUrl;
use function is_bool;
use function preg_match;
use function str_replace;
use function strtolower;
/**
 * @phpstan-type UrnSerialize array{0: array{urn: non-empty-string}, 1: array{}}
 * @phpstan-import-type InputComponentMap from UriString
 * @phpstan-type UrnMap array{
 *      scheme: 'urn',
 *      nid: string,
 *      nss: string,
 *      r_component: ?string,
 *      q_component: ?string,
 *      f_component: ?string,
 *  }
 */
final class Urn implements Conditionable, JsonSerializable
{
    /**
     * RFC8141 regular expression URN splitter.
     *
     * The regexp does not perform any look-ahead.
     * Not all invalid URN are caught. Some
     * post-regexp-validation checks
     * are mandatory.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc8141#section-2
     *
     * @var string
     */
    private const REGEXP_URN_PARTS = '/^
        urn:
        (?<nid>[a-z0-9](?:[a-z0-9-]{0,30}[a-z0-9])?): # NID
        (?<nss>.*?)                                   # NSS
        (?<frc>\\?\\+(?<rcomponent>.*?))?               # r-component
        (?<fqc>\\?\\=(?<qcomponent>.*?))?               # q-component
        (?:\\#(?<fcomponent>.*))?                      # f-component
    $/xi';
    /**
     * RFC8141 namespace identifier regular expression.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc8141#section-2
     *
     * @var string
     */
    private const REGEX_NID_SEQUENCE = '/^[a-z0-9]([a-z0-9-]{0,30})[a-z0-9]$/xi';
    /** @var non-empty-string
     * @readonly */
    private $uriString;
    /** @var non-empty-string
     * @readonly */
    private $nid;
    /** @var non-empty-string
     * @readonly */
    private $nss;
    /** @var non-empty-string|null
     * @readonly */
    private $rComponent;
    /** @var non-empty-string|null
     * @readonly */
    private $qComponent;
    /** @var non-empty-string|null
     * @readonly */
    private $fComponent;
    /**
     * @param Rfc3986Uri|WhatWgUrl|Stringable|string $urn the percent-encoded URN
     */
    public static function parse($urn) : ?Urn
    {
        try {
            return self::fromString($urn);
        } catch (SyntaxError $exception) {
            return null;
        }
    }
    /**
     * @param Rfc3986Uri|WhatWgUrl|Stringable|string $urn the percent-encoded URN
     * @see self::fromString()
     *
     * @throws SyntaxError if the URN is invalid
     */
    public static function new($urn) : self
    {
        return self::fromString($urn);
    }
    /**
     * @param Rfc3986Uri|WhatWgUrl|Stringable|string $urn the percent-encoded URN
     *
     * @throws SyntaxError if the URN is invalid
     */
    public static function fromString($urn) : self
    {
        switch (\true) {
            case $urn instanceof Rfc3986Uri:
                $urn = $urn->toRawString();
                break;
            case $urn instanceof WhatWgUrl:
                $urn = $urn->toAsciiString();
                break;
            default:
                $urn = (string) $urn;
                break;
        }
        UriString::containsRfc3986Chars($urn) || throw new SyntaxError('The URN is malformed, it contains invalid characters.');
        1 === preg_match(self::REGEXP_URN_PARTS, $urn, $matches) || throw new SyntaxError('The URN string is invalid.');
        return new self($matches['nid'], $matches['nss'], isset($matches['frc']) && '' !== $matches['frc'] ? $matches['rcomponent'] : null, isset($matches['fqc']) && '' !== $matches['fqc'] ? $matches['qcomponent'] : null, $matches['fcomponent'] ?? null);
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
        return self::fromString(UriString::build($components));
    }
    /**
     * @param Stringable|string $nss the percent-encoded NSS
     *
     * @throws SyntaxError if the URN is invalid
     * @param \Stringable|string $nid
     */
    public static function fromRfc2141($nid, $nss) : self
    {
        return new self((string) $nid, (string) $nss);
    }
    /**
     * @param string $nss the percent-encoded NSS
     * @param ?string $rComponent the percent-encoded r-component
     * @param ?string $qComponent the percent-encoded q-component
     * @param ?string $fComponent the percent-encoded f-component
     *
     * @throws SyntaxError if one of the URN part is invalid
     */
    private function __construct(string $nid, string $nss, ?string $rComponent = null, ?string $qComponent = null, ?string $fComponent = null)
    {
        '' !== $nid && 1 === preg_match(self::REGEX_NID_SEQUENCE, $nid) || throw new SyntaxError('The URN is malformed, the NID is invalid.');
        '' !== $nss && Encoder::isPathEncoded($nss) || throw new SyntaxError('The URN is malformed, the NSS is invalid.');
        /** @param Closure(string): ?non-empty-string $closure */
        $validateComponent = static function (?string $value, Closure $closure, string $name) : ?string {
            switch (\true) {
                case null === $value:
                case '' !== $value && 1 !== preg_match('/[#?]/', $value) && $closure($value):
                    return $value;
                default:
                    throw new SyntaxError('The URN is malformed, the `' . $name . '` component is invalid.');
            }
        };
        $this->nid = $nid;
        $this->nss = $nss;
        $this->rComponent = $validateComponent($rComponent, \Closure::fromCallable([Encoder::class, 'isPathEncoded']), 'r-component');
        $this->qComponent = $validateComponent($qComponent, \Closure::fromCallable([Encoder::class, 'isQueryEncoded']), 'q-component');
        $this->fComponent = $validateComponent($fComponent, \Closure::fromCallable([Encoder::class, 'isFragmentEncoded']), 'f-component');
        $this->uriString = $this->setUriString();
    }
    /**
     * @return non-empty-string
     */
    private function setUriString() : string
    {
        $str = $this->toRfc2141();
        if (null !== $this->rComponent) {
            $str .= '?+' . $this->rComponent;
        }
        if (null !== $this->qComponent) {
            $str .= '?=' . $this->qComponent;
        }
        if (null !== $this->fComponent) {
            $str .= '#' . $this->fComponent;
        }
        return $str;
    }
    /**
     * Returns the NID.
     *
     * @return non-empty-string
     */
    public function getNid() : string
    {
        return $this->nid;
    }
    /**
     * Returns the percent-encoded NSS.
     *
     * @return non-empty-string
     */
    public function getNss() : string
    {
        return $this->nss;
    }
    /**
     * Returns the percent-encoded r-component string or null if it is not set.
     *
     * @return ?non-empty-string
     */
    public function getRComponent() : ?string
    {
        return $this->rComponent;
    }
    /**
     * Returns the percent-encoded q-component string or null if it is not set.
     *
     * @return ?non-empty-string
     */
    public function getQComponent() : ?string
    {
        return $this->qComponent;
    }
    /**
     * Returns the percent-encoded f-component string or null if it is not set.
     *
     * @return ?non-empty-string
     */
    public function getFComponent() : ?string
    {
        return $this->fComponent;
    }
    /**
     * Returns the RFC8141 URN string representation.
     *
     * @return non-empty-string
     */
    public function toString() : string
    {
        return $this->uriString;
    }
    /**
     * Returns the RFC2141 URN string representation.
     *
     * @return non-empty-string
     */
    public function toRfc2141() : string
    {
        return 'urn:' . $this->nid . ':' . $this->nss;
    }
    /**
     * Returns the human-readable string representation of the URN as an IRI.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3987
     */
    public function toDisplayString() : string
    {
        return UriString::toIriString($this->uriString);
    }
    /**
     * Returns the RFC8141 URN string representation.
     *
     * @see self::toString()
     *
     * @return non-empty-string
     */
    public function __toString() : string
    {
        return $this->toString();
    }
    /**
     * Returns the RFC8141 URN string representation.
     * @see self::toString()
     *
     * @return non-empty-string
     */
    public function jsonSerialize() : string
    {
        return $this->toString();
    }
    /**
     * Returns the RFC3986 representation of the current URN.
     *
     * If a template URI is used the following variables as present
     * {nid} for the namespace identifier
     * {nss} for the namespace specific string
     * {r_component} for the r-component without its delimiter
     * {q_component} for the q-component without its delimiter
     * {f_component} for the f-component without its delimiter
     * @param \Matomo\Dependencies\Oauth2\League\Uri\UriTemplate|\Matomo\Dependencies\Oauth2\League\Uri\UriTemplate\Template|string|null $template
     */
    public function resolve($template = null) : UriInterface
    {
        return null !== $template ? Uri::fromTemplate($template, $this->toComponents()) : Uri::new($this->uriString);
    }
    public function hasRComponent() : bool
    {
        return null !== $this->rComponent;
    }
    public function hasQComponent() : bool
    {
        return null !== $this->qComponent;
    }
    public function hasFComponent() : bool
    {
        return null !== $this->fComponent;
    }
    public function hasOptionalComponent() : bool
    {
        return null !== $this->rComponent || null !== $this->qComponent || null !== $this->fComponent;
    }
    /**
     * Return an instance with the specified NID.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified NID.
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in an object in invalid state.
     * @param \Stringable|string $nid
     */
    public function withNid($nid) : self
    {
        $nid = (string) $nid;
        return $this->nid === $nid ? $this : new self($nid, $this->nss, $this->rComponent, $this->qComponent, $this->fComponent);
    }
    /**
     * Return an instance with the specified NSS.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified NSS.
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in an object in invalid state.
     * @param \Stringable|string $nss
     */
    public function withNss($nss) : self
    {
        $nss = Encoder::encodePath($nss);
        return $this->nss === $nss ? $this : new self($this->nid, $nss, $this->rComponent, $this->qComponent, $this->fComponent);
    }
    /**
     * Return an instance with the specified r-component.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified r-component.
     *
     * The component is removed if the value is null.
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in an object in invalid state.
     * @param \Stringable|string|null $component
     */
    public function withRComponent($component) : self
    {
        if ($component instanceof UriComponentInterface) {
            $component = $component->value();
        }
        if (null !== $component) {
            $component = self::formatComponent(Encoder::encodePath($component));
        }
        return $this->rComponent === $component ? $this : new self($this->nid, $this->nss, $component, $this->qComponent, $this->fComponent);
    }
    private static function formatComponent(?string $component) : ?string
    {
        return null === $component ? null : str_replace(['?', '#'], ['%3F', '%23'], $component);
    }
    /**
     * Return an instance with the specified q-component.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified q-component.
     *
     * The component is removed if the value is null.
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in an object in invalid state.
     * @param \Stringable|string|null $component
     */
    public function withQComponent($component) : self
    {
        if ($component instanceof UriComponentInterface) {
            $component = $component->value();
        }
        $component = self::formatComponent(Encoder::encodeQueryOrFragment($component));
        return $this->qComponent === $component ? $this : new self($this->nid, $this->nss, $this->rComponent, $component, $this->fComponent);
    }
    /**
     * Return an instance with the specified f-component.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified f-component.
     *
     * The component is removed if the value is null.
     *
     * @throws SyntaxError for invalid component or transformations
     *                     that would result in an object in invalid state.
     * @param \Stringable|string|null $component
     */
    public function withFComponent($component) : self
    {
        if ($component instanceof UriComponentInterface) {
            $component = $component->value();
        }
        $component = self::formatComponent(Encoder::encodeQueryOrFragment($component));
        return $this->fComponent === $component ? $this : new self($this->nid, $this->nss, $this->rComponent, $this->qComponent, $component);
    }
    public function normalize() : self
    {
        $copy = new self(strtolower($this->nid), (string) Encoder::normalizePath($this->nss), null === $this->rComponent ? $this->rComponent : Encoder::normalizePath($this->rComponent), Encoder::normalizeQuery($this->qComponent), Encoder::normalizeFragment($this->fComponent));
        return $copy->uriString === $this->uriString ? $this : $copy;
    }
    /**
     * @param \Matomo\Dependencies\Oauth2\League\Uri\Urn|Rfc3986Uri|WhatWgUrl|\Stringable|string $other
     * @param \Matomo\Dependencies\Oauth2\League\Uri\UrnComparisonMode::* $urnComparisonMode
     */
    public function equals($other, string $urnComparisonMode = UrnComparisonMode::ExcludeComponents) : bool
    {
        if (!$other instanceof Urn) {
            $other = self::parse($other);
        }
        switch ($urnComparisonMode) {
            case UrnComparisonMode::ExcludeComponents:
                return $other->normalize()->toRfc2141() === $this->normalize()->toRfc2141();
            case UrnComparisonMode::IncludeComponents:
                return $other->normalize()->toString() === $this->normalize()->toString();
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
     * @return UrnSerialize
     */
    public function __serialize() : array
    {
        return [['urn' => $this->toString()], []];
    }
    /**
     * @param UrnSerialize $data
     *
     * @throws SyntaxError
     */
    public function __unserialize(array $data) : void
    {
        [$properties] = $data;
        if ($properties['urn'] === null) {
            throw new SyntaxError('The `urn` property is missing from the serialized object.');
        }
        $uri = self::fromString($properties['urn']);
        $this->nid = $uri->nid;
        $this->nss = $uri->nss;
        $this->rComponent = $uri->rComponent;
        $this->qComponent = $uri->qComponent;
        $this->fComponent = $uri->fComponent;
        $this->uriString = $uri->uriString;
    }
    /**
     * @return UrnMap
     */
    public function toComponents() : array
    {
        return ['scheme' => 'urn', 'nid' => $this->nid, 'nss' => $this->nss, 'r_component' => $this->rComponent, 'q_component' => $this->qComponent, 'f_component' => $this->fComponent];
    }
    /**
     * @return UrnMap
     */
    public function __debugInfo() : array
    {
        return $this->toComponents();
    }
}
