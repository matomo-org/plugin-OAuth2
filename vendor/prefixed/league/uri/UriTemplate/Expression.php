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
namespace Matomo\Dependencies\OAuth2\League\Uri\UriTemplate;

use Matomo\Dependencies\OAuth2\Deprecated;
use Matomo\Dependencies\OAuth2\League\Uri\Exceptions\SyntaxError;
use Stringable;
use function array_filter;
use function array_map;
use function array_unique;
use function explode;
use function implode;
/**
 * @internal The class exposes the internal representation of an Expression and its usage
 * @link https://www.rfc-editor.org/rfc/rfc6570#section-2.2
 */
final class Expression
{
    /**
     * @readonly
     * @var \Matomo\Dependencies\OAuth2\League\Uri\UriTemplate\Operator
     */
    public $operator;
    /** @var array<VarSpecifier>
     * @readonly */
    private $varSpecifiers;
    /** @var array<string>
     * @readonly */
    public $variableNames;
    /**
     * @readonly
     * @var string
     */
    public $value;
    /**
     * @param \Matomo\Dependencies\OAuth2\League\Uri\UriTemplate\Operator::* $operator
     */
    private function __construct(string $operator, VarSpecifier ...$varSpecifiers)
    {
        $this->operator = $operator;
        $this->varSpecifiers = $varSpecifiers;
        $this->variableNames = array_unique(array_map(static function (VarSpecifier $varSpecifier) : string {
            return $varSpecifier->name;
        }, $varSpecifiers));
        $this->value = '{' . $operator->value . implode(',', array_map(static function (VarSpecifier $varSpecifier) : string {
            return $varSpecifier->toString();
        }, $varSpecifiers)) . '}';
    }
    /**
     * @throws SyntaxError if the expression is invalid
     * @param \Stringable|string $expression
     */
    public static function new($expression) : self
    {
        $parts = Operator::parseExpression($expression);
        return new Expression($parts['operator'], ...array_map(static function (string $varSpec) : VarSpecifier {
            return VarSpecifier::new($varSpec);
        }, explode(',', $parts['variables'])));
    }
    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @throws SyntaxError if the expression is invalid
     * @see Expression::new()
     *
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @param \Stringable|string $expression
     */
    #[Deprecated(message: 'use League\\Uri\\UriTemplate\\Exppression::new() instead', since: 'league/uri:7.0.0')]
    public static function createFromString($expression) : self
    {
        return self::new($expression);
    }
    public function expand(VariableBag $variables) : string
    {
        $expanded = implode($this->operator->separator(), array_filter(array_map(function (VarSpecifier $varSpecifier) use ($variables) : string {
            return $this->operator->expand($varSpecifier, $variables);
        }, $this->varSpecifiers), static function ($value) : bool {
            return '' !== $value;
        }));
        switch ('') {
            case $expanded:
                return '';
            default:
                return $this->operator->first() . $expanded;
        }
    }
}
