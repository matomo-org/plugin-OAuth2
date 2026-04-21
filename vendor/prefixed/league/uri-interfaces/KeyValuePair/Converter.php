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
namespace Matomo\Dependencies\Oauth2\League\Uri\KeyValuePair;

use Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriComponentInterface;
use Matomo\Dependencies\Oauth2\League\Uri\Exceptions\SyntaxError;
use Stringable;
use function array_combine;
use function explode;
use function implode;
use function is_float;
use function is_int;
use function is_string;
use function json_encode;
use function preg_match;
use function str_replace;
use const JSON_PRESERVE_ZERO_FRACTION;
use const PHP_QUERY_RFC1738;
use const PHP_QUERY_RFC3986;
final class Converter
{
    /**
     * @var non-empty-string
     * @readonly
     */
    private $separator;
    /**
     * @var array<string>
     * @readonly
     */
    private $fromRfc3986 = [];
    /**
     * @var array<string>
     * @readonly
     */
    private $toEncoding = [];
    private const REGEXP_INVALID_CHARS = '/[\\x00-\\x1f\\x7f]/';
    /**
     * @param non-empty-string $separator the query string separator
     * @param array<string> $fromRfc3986 contains all the RFC3986 encoded characters to be converted
     * @param array<string> $toEncoding contains all the expected encoded characters
     */
    private function __construct(string $separator, array $fromRfc3986 = [], array $toEncoding = [])
    {
        $this->separator = $separator;
        $this->fromRfc3986 = $fromRfc3986;
        $this->toEncoding = $toEncoding;
        if ('' === $this->separator) {
            throw new SyntaxError('The separator character must be a non empty string.');
        }
    }
    /**
     * @param non-empty-string $separator
     */
    public static function new(string $separator) : self
    {
        return new self($separator);
    }
    /**
     * @param non-empty-string $separator
     */
    public static function fromRFC3986(string $separator = '&') : self
    {
        return self::new($separator);
    }
    /**
     * @param non-empty-string $separator
     */
    public static function fromRFC1738(string $separator = '&') : self
    {
        return self::new($separator)->withEncodingMap(['%20' => '+']);
    }
    /**
     * @param non-empty-string $separator
     *
     * @see https://url.spec.whatwg.org/#application/x-www-form-urlencoded
     */
    public static function fromFormData(string $separator = '&') : self
    {
        return self::new($separator)->withEncodingMap(['%20' => '+', '%2A' => '*']);
    }
    public static function fromEncodingType(int $encType) : self
    {
        switch ($encType) {
            case PHP_QUERY_RFC3986:
                return self::fromRFC3986();
            case PHP_QUERY_RFC1738:
                return self::fromRFC1738();
            default:
                throw new SyntaxError('Unknown or Unsupported encoding.');
        }
    }
    /**
     * @return non-empty-string
     */
    public function separator() : string
    {
        return $this->separator;
    }
    /**
     * @return array<string, string>
     */
    public function encodingMap() : array
    {
        return array_combine($this->fromRfc3986, $this->toEncoding);
    }
    /**
     * @return array<non-empty-list<string|null>>
     * @param \Stringable|string|int|float|bool|null $value
     */
    public function toPairs($value) : array
    {
        switch (\true) {
            case $value instanceof UriComponentInterface:
                $value = $value->value();
                break;
            case $value instanceof Stringable:
            case is_int($value):
                $value = (string) $value;
                break;
            case \false === $value:
                $value = '0';
                break;
            case \true === $value:
                $value = '1';
                break;
            default:
                $value = $value;
                break;
        }
        if (null === $value) {
            return [];
        }
        switch (1) {
            case preg_match(self::REGEXP_INVALID_CHARS, (string) $value):
                throw new SyntaxError('Invalid query string: `' . $value . '`.');
            default:
                $value = str_replace($this->toEncoding, $this->fromRfc3986, (string) $value);
                break;
        }
        return array_map(function (string $pair) : array {
            return explode('=', $pair, 2) + [1 => null];
        }, explode($this->separator, $value));
    }
    /**
     * @param \Stringable|string|bool|int|float|null $value
     */
    private static function vString($value) : ?string
    {
        switch (\true) {
            case $value:
                return '1';
            case \false === $value:
                return '0';
            case null === $value:
                return null;
            case is_float($value):
                return (string) json_encode($value, JSON_PRESERVE_ZERO_FRACTION);
            default:
                return (string) $value;
        }
    }
    /**
     * @param iterable<array{0:string|null, 1:Stringable|string|bool|int|float|null}> $pairs
     */
    public function toValue(iterable $pairs) : ?string
    {
        $filteredPairs = [];
        foreach ($pairs as $pair) {
            switch (\true) {
                case !is_string($pair[0]):
                    throw new SyntaxError('the pair key MUST be a string;, `' . gettype($pair[0]) . '` given.');
                case null === $pair[1]:
                    $filteredPairs[] = self::vString($pair[0]);
                    break;
                default:
                    $filteredPairs[] = self::vString($pair[0]) . '=' . self::vString($pair[1]);
                    break;
            }
        }
        switch ([]) {
            case $filteredPairs:
                return null;
            default:
                return str_replace($this->fromRfc3986, $this->toEncoding, implode($this->separator, $filteredPairs));
        }
    }
    /**
     * @param non-empty-string $separator
     */
    public function withSeparator(string $separator) : self
    {
        switch ($this->separator) {
            case $separator:
                return $this;
            default:
                return new self($separator, $this->fromRfc3986, $this->toEncoding);
        }
    }
    /**
     * Sets the conversion map.
     *
     * Each key from the iterable structure represents the RFC3986 encoded characters as string,
     * while each value represents the expected output encoded characters
     */
    public function withEncodingMap(iterable $encodingMap) : self
    {
        $fromRfc3986 = [];
        $toEncoding = [];
        foreach ($encodingMap as $from => $to) {
            switch (\true) {
                case !is_string($from):
                    throw new SyntaxError('The encoding output must be a string; `' . gettype($from) . '` given.');
                case $to instanceof Stringable:
                case is_string($to):
                    [$fromRfc3986[], $toEncoding[]] = [$from, (string) $to];
                    break;
                default:
                    throw new SyntaxError('The encoding output must be a string; `' . gettype($to) . '` given.');
            }
        }
        switch (\true) {
            case $fromRfc3986 !== $this->fromRfc3986:
            case $toEncoding !== $this->toEncoding:
                return new self($this->separator, $fromRfc3986, $toEncoding);
            default:
                return $this;
        }
    }
}
