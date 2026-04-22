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

use Matomo\Dependencies\OAuth2\Deprecated;
use Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriException;
use Matomo\Dependencies\OAuth2\League\Uri\Contracts\UriInterface;
use Matomo\Dependencies\OAuth2\League\Uri\Exceptions\MissingFeature;
use Matomo\Dependencies\OAuth2\League\Uri\Exceptions\SyntaxError;
use Matomo\Dependencies\OAuth2\League\Uri\UriTemplate\Template;
use Matomo\Dependencies\OAuth2\League\Uri\UriTemplate\TemplateCanNotBeExpanded;
use Matomo\Dependencies\OAuth2\League\Uri\UriTemplate\VariableBag;
use Matomo\Dependencies\OAuth2\Psr\Http\Message\UriFactoryInterface;
use Matomo\Dependencies\OAuth2\Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;
use Uri\InvalidUriException;
use Uri\Rfc3986\Uri as Rfc3986Uri;
use Uri\WhatWg\InvalidUrlException;
use Uri\WhatWg\Url as WhatWgUrl;
use function array_fill_keys;
use function array_key_exists;
use function class_exists;
/**
 * Defines the URI Template syntax and the process for expanding a URI Template into a URI reference.
 *
 * @link    https://tools.ietf.org/html/rfc6570
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   6.1.0
 *
 * @phpstan-import-type InputValue from VariableBag
 */
final class UriTemplate
{
    /**
     * @readonly
     * @var \Matomo\Dependencies\OAuth2\League\Uri\UriTemplate\Template
     */
    private $template;
    /**
     * @readonly
     * @var \Matomo\Dependencies\OAuth2\League\Uri\UriTemplate\VariableBag
     */
    private $defaultVariables;
    /**
     * @throws SyntaxError if the template syntax is invalid
     * @throws TemplateCanNotBeExpanded if the template or the variables are invalid
     * @param \Stringable|string $template
     */
    public function __construct($template, iterable $defaultVariables = [])
    {
        $this->template = $template instanceof Template ? $template : Template::new($template);
        $this->defaultVariables = $this->filterVariables($defaultVariables);
    }
    private function filterVariables(iterable $variables) : VariableBag
    {
        if (!$variables instanceof VariableBag) {
            $variables = new VariableBag($variables);
        }
        return $variables->filter(function ($value, $name) {
            return array_key_exists($name, array_fill_keys($this->template->variableNames, 1));
        });
    }
    /**
     * Returns the string representation of the UriTemplate.
     */
    public function __toString() : string
    {
        return $this->template->value;
    }
    /**
     * Returns the distinct variables placeholders used in the template.
     *
     * @return array<string>
     */
    public function getVariableNames() : array
    {
        return $this->template->variableNames;
    }
    /**
     * @return array<string, InputValue>
     */
    public function getDefaultVariables() : array
    {
        return iterator_to_array($this->defaultVariables);
    }
    /**
     * Returns a new instance with the updated default variables.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified default variables.
     *
     * If present, variables whose name is not part of the current template
     * possible variable names are removed.
     *
     * @throws TemplateCanNotBeExpanded if the variables are invalid
     */
    public function withDefaultVariables(iterable $defaultVariables) : self
    {
        $defaultVariables = $this->filterVariables($defaultVariables);
        if ($this->defaultVariables->equals($defaultVariables)) {
            return $this;
        }
        return new self($this->template, $defaultVariables);
    }
    private function templateExpanded(iterable $variables = []) : string
    {
        return $this->template->expand($this->filterVariables($variables)->replace($this->defaultVariables));
    }
    private function templateExpandedOrFail(iterable $variables = []) : string
    {
        return $this->template->expandOrFail($this->filterVariables($variables)->replace($this->defaultVariables));
    }
    /**
     * @throws TemplateCanNotBeExpanded if the variables are invalid
     * @throws UriException if the resulting expansion cannot be converted to a UriInterface instance
     * @param Rfc3986Uri|WhatWgUrl|\Stringable|string|null $baseUri
     */
    public function expand(iterable $variables = [], $baseUri = null) : UriInterface
    {
        $expanded = $this->templateExpanded($variables);
        return null === $baseUri ? Uri::new($expanded) : Uri::parse($expanded, $baseUri) ?? throw new SyntaxError('Unable to expand URI');
    }
    /**
     * @throws MissingFeature if no Uri\Rfc3986\Uri class is found
     * @throws TemplateCanNotBeExpanded if the variables are invalid
     * @throws InvalidUriException if the base URI cannot be converted to a Uri\Rfc3986\Uri instance
     * @throws InvalidUriException if the resulting expansion cannot be converted to a Uri\Rfc3986\Uri instance
     * @param Rfc3986Uri|WhatWgUrl|\Stringable|string|null $baseUri
     */
    public function expandToUri(iterable $variables = [], $baseUri = null) : Rfc3986Uri
    {
        class_exists(Rfc3986Uri::class) || throw new MissingFeature('Support for ' . Rfc3986Uri::class . ' requires PHP8.5+ or a polyfill. Run "composer require league/uri-polyfill" or use you owm polyfill.');
        return new Rfc3986Uri($this->templateExpanded($variables), $this->newRfc3986Uri($baseUri));
    }
    /**
     * @throws MissingFeature if no Uri\Whatwg\Url class is found
     * @throws TemplateCanNotBeExpanded if the variables are invalid
     * @throws InvalidUrlException if the base URI cannot be converted to a Uri\Whatwg\Url instance
     * @throws InvalidUrlException if the resulting expansion cannot be converted to a Uri\Whatwg\Url instance
     * @param Rfc3986Uri|WhatWgUrl|\Stringable|string|null $baseUrl
     */
    public function expandToUrl(iterable $variables = [], $baseUrl = null) : WhatWgUrl
    {
        class_exists(WhatWgUrl::class) || throw new MissingFeature('Support for ' . WhatWgUrl::class . ' requires PHP8.5+ or a polyfill. Run "composer require league/uri-polyfill" or use you owm polyfill.');
        return new WhatWgUrl($this->templateExpanded($variables), $this->newWhatWgUrl($baseUrl));
    }
    /**
     * @throws TemplateCanNotBeExpanded if the variables are invalid
     * @throws UriException if the resulting expansion cannot be converted to a UriInterface instance
     * @param Rfc3986Uri|WhatWgUrl|\Stringable|string|null $baseUrl
     */
    public function expandToPsr7Uri(iterable $variables = [], $baseUrl = null, UriFactoryInterface $uriFactory = null) : Psr7UriInterface
    {
        $uriFactory = $uriFactory ?? new HttpFactory();
        $uriString = $this->templateExpandedOrFail($variables);
        return $uriFactory->createUri(null === $baseUrl ? $uriString : UriString::resolve($uriString, (function () use ($baseUrl) {
            switch (\true) {
                case $baseUrl instanceof Rfc3986Uri:
                    return $baseUrl->toRawString();
                case $baseUrl instanceof WhatWgUrl:
                    return $baseUrl->toUnicodeString();
                default:
                    return $baseUrl;
            }
        })()));
    }
    /**
     * @throws TemplateCanNotBeExpanded if the variables are invalid or missing
     * @throws UriException if the resulting expansion cannot be converted to a UriInterface instance
     * @param Rfc3986Uri|WhatWgUrl|\Stringable|string|null $baseUri
     */
    public function expandOrFail(iterable $variables = [], $baseUri = null) : UriInterface
    {
        $expanded = $this->templateExpandedOrFail($variables);
        return null === $baseUri ? Uri::new($expanded) : Uri::parse($expanded, $baseUri) ?? throw new SyntaxError('Unable to expand URI');
    }
    /**
     * @throws MissingFeature if no Uri\Rfc3986\Uri class is found
     * @throws TemplateCanNotBeExpanded if the variables are invalid
     * @throws InvalidUriException if the base URI cannot be converted to a Uri\Rfc3986\Uri instance
     * @throws InvalidUriException if the resulting expansion cannot be converted to a Uri\Rfc3986\Uri instance
     * @param Rfc3986Uri|WhatWgUrl|\Stringable|string|null $baseUri
     */
    public function expandToUriOrFail(iterable $variables = [], $baseUri = null) : Rfc3986Uri
    {
        class_exists(Rfc3986Uri::class) || throw new MissingFeature('Support for ' . Rfc3986Uri::class . ' requires PHP8.5+ or a polyfill. Run "composer require league/uri-polyfill" or use you owm polyfill.');
        return new Rfc3986Uri($this->templateExpandedOrFail($variables), $this->newRfc3986Uri($baseUri));
    }
    /**
     * @throws MissingFeature if no Uri\Whatwg\Url class is found
     * @throws TemplateCanNotBeExpanded if the variables are invalid
     * @throws InvalidUrlException if the base URI cannot be converted to a Uri\Whatwg\Url instance
     * @throws InvalidUrlException if the resulting expansion cannot be converted to a Uri\Whatwg\Url instance
     * @param Rfc3986Uri|WhatWgUrl|\Stringable|string|null $baseUrl
     */
    public function expandToUrlOrFail(iterable $variables = [], $baseUrl = null) : WhatWgUrl
    {
        class_exists(WhatWgUrl::class) || throw new MissingFeature('Support for ' . WhatWgUrl::class . ' requires PHP8.5+ or a polyfill. Run "composer require league/uri-polyfill" or use you owm polyfill.');
        return new WhatWgUrl($this->templateExpandedOrFail($variables), $this->newWhatWgUrl($baseUrl));
    }
    /**
     * @throws TemplateCanNotBeExpanded if the variables are invalid
     * @throws UriException if the resulting expansion cannot be converted to a UriInterface instance
     * @param Rfc3986Uri|WhatWgUrl|\Stringable|string|null $baseUrl
     */
    public function expandToPsr7UriOrFail(iterable $variables = [], $baseUrl = null, UriFactoryInterface $uriFactory = null) : Psr7UriInterface
    {
        $uriFactory = $uriFactory ?? new HttpFactory();
        $uriString = $this->templateExpandedOrFail($variables);
        return $uriFactory->createUri(null === $baseUrl ? $uriString : UriString::resolve($uriString, (function () use ($baseUrl) {
            switch (\true) {
                case $baseUrl instanceof Rfc3986Uri:
                    return $baseUrl->toRawString();
                case $baseUrl instanceof WhatWgUrl:
                    return $baseUrl->toUnicodeString();
                default:
                    return $baseUrl;
            }
        })()));
    }
    /**
     * @throws InvalidUrlException
     * @param Rfc3986Uri|WhatWgUrl|\Stringable|string|null $url
     */
    private function newWhatWgUrl($url = null) : ?WhatWgUrl
    {
        switch (\true) {
            case null === $url:
                return null;
            case $url instanceof WhatWgUrl:
                return $url;
            case $url instanceof Rfc3986Uri:
                return new WhatWgUrl($url->toRawString());
            default:
                return new WhatWgUrl((string) $url);
        }
    }
    /**
     * @throws InvalidUriException
     * @param Rfc3986Uri|WhatWgUrl|\Stringable|string|null $uri
     */
    private function newRfc3986Uri($uri = null) : ?Rfc3986Uri
    {
        switch (\true) {
            case null === $uri:
                return null;
            case $uri instanceof Rfc3986Uri:
                return $uri;
            case $uri instanceof WhatWgUrl:
                return new Rfc3986Uri($uri->toAsciiString());
            default:
                return new Rfc3986Uri((string) $uri);
        }
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.6.0
     * @codeCoverageIgnore
     * @see UriTemplate::toString()
     *
     * Create a new instance from the environment.
     */
    #[Deprecated(message: 'use League\\Uri\\UriTemplate::__toString() instead', since: 'league/uri:7.6.0')]
    public function getTemplate() : string
    {
        return $this->__toString();
    }
}
