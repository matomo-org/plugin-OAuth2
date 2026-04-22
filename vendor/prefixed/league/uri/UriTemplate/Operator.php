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

use Matomo\Dependencies\OAuth2\League\Uri\Encoder;
use Matomo\Dependencies\OAuth2\League\Uri\Exceptions\SyntaxError;
use Stringable;
use function implode;
use function is_array;
use function preg_match;
use function rawurlencode;
use function str_contains;
use function substr;
class Operator
{
    /**
     * Expression regular expression pattern.
     *
     * @link https://tools.ietf.org/html/rfc6570#section-2.2
     */
    private const REGEXP_EXPRESSION = '/^\\{(?:(?<operator>[\\.\\/;\\?&\\=,\\!@\\|\\+#])?(?<variables>[^\\}]*))\\}$/';
    /**
     * Reserved Operator characters.
     *
     * @link https://tools.ietf.org/html/rfc6570#section-2.2
     */
    private const RESERVED_OPERATOR = '=,!@|';
    public const None = '';
    public const ReservedChars = '+';
    public const Label = '.';
    public const Path = '/';
    public const PathParam = ';';
    public const Query = '?';
    public const QueryPair = '&';
    public const Fragment = '#';
    public function first() : string
    {
        switch ($this) {
            case self::None:
            case self::ReservedChars:
                return '';
            default:
                return $this->value;
        }
    }
    public function separator() : string
    {
        switch ($this) {
            case self::None:
            case self::ReservedChars:
            case self::Fragment:
                return ',';
            case self::Query:
            case self::QueryPair:
                return '&';
            default:
                return $this->value;
        }
    }
    public function isNamed() : bool
    {
        switch ($this) {
            case self::Query:
            case self::PathParam:
            case self::QueryPair:
                return \true;
            default:
                return \false;
        }
    }
    /**
     * Removes percent encoding on reserved characters (used with + and # modifiers).
     */
    public function decode(string $var) : string
    {
        switch ($this) {
            case Operator::ReservedChars:
            case Operator::Fragment:
                return (string) Encoder::encodeQueryOrFragment($var);
            default:
                return rawurlencode($var);
        }
    }
    /**
     * @throws SyntaxError if the expression is invalid
     * @throws SyntaxError if the operator used in the expression is invalid
     * @throws SyntaxError if the contained variable specifiers are invalid
     *
     * @return array{operator:Operator, variables:string}
     * @param \Stringable|string $expression
     */
    public static function parseExpression($expression) : array
    {
        $expression = (string) $expression;
        if (1 !== preg_match(self::REGEXP_EXPRESSION, $expression, $parts)) {
            throw new SyntaxError('The expression "' . $expression . '" is invalid.');
        }
        /** @var array{operator:string, variables:string} $parts */
        $parts = $parts + ['operator' => ''];
        if ('' !== $parts['operator'] && strpos(self::RESERVED_OPERATOR, $parts['operator']) !== false) {
            throw new SyntaxError('The operator used in the expression "' . $expression . '" is reserved.');
        }
        return ['operator' => self::from($parts['operator']), 'variables' => $parts['variables']];
    }
    /**
     * Replaces an expression with the given variables.
     *
     * @throws TemplateCanNotBeExpanded if the variables is an array and a ":" modifier needs to be applied
     * @throws TemplateCanNotBeExpanded if the variables contains nested array values
     */
    public function expand(VarSpecifier $varSpecifier, VariableBag $variables) : string
    {
        $value = $variables->fetch($varSpecifier->name);
        if (null === $value) {
            return '';
        }
        [$expanded, $actualQuery] = $this->inject($value, $varSpecifier);
        if (!$actualQuery) {
            return $expanded;
        }
        if ('&' !== $this->separator() && '' === $expanded) {
            return $varSpecifier->name;
        }
        return $varSpecifier->name . '=' . $expanded;
    }
    /**
     * @param string|array<string> $value
     *
     * @return array{0:string, 1:bool}
     */
    private function inject($value, VarSpecifier $varSpec) : array
    {
        if (is_array($value)) {
            return $this->replaceList($value, $varSpec);
        }
        if (':' === $varSpec->modifier) {
            $value = substr($value, 0, $varSpec->position);
        }
        return [$this->decode($value), $this->isNamed()];
    }
    /**
     * Expands an expression using a list of values.
     *
     * @param array<string> $value
     *
     * @throws TemplateCanNotBeExpanded if the variables is an array and a ":" modifier needs to be applied
     *
     * @return array{0:string, 1:bool}
     */
    private function replaceList(array $value, VarSpecifier $varSpec) : array
    {
        if (':' === $varSpec->modifier) {
            throw TemplateCanNotBeExpanded::dueToUnableToProcessValueListWithPrefix($varSpec->name);
        }
        if ([] === $value) {
            return ['', \false];
        }
        $pairs = [];
        $arrayIsListFunction = function (array $array) : bool {
            if (function_exists('array_is_list')) {
                return array_is_list($array);
            }
            if ($array === []) {
                return true;
            }
            $current_key = 0;
            foreach ($array as $key => $noop) {
                if ($key !== $current_key) {
                    return false;
                }
                ++$current_key;
            }
            return true;
        };
        $isList = $arrayIsListFunction($value);
        $useQuery = $this->isNamed();
        foreach ($value as $key => $var) {
            if (!$isList) {
                $key = rawurlencode((string) $key);
            }
            $var = $this->decode($var);
            if ('*' === $varSpec->modifier) {
                if (!$isList) {
                    $var = $key . '=' . $var;
                } elseif ($key > 0 && $useQuery) {
                    $var = $varSpec->name . '=' . $var;
                }
            }
            $pairs[$key] = $var;
        }
        if ('*' === $varSpec->modifier) {
            if (!$isList) {
                // Don't prepend the value name when using the `explode` modifier with an associative array.
                $useQuery = \false;
            }
            return [implode($this->separator(), $pairs), $useQuery];
        }
        if (!$isList) {
            // When an associative array is encountered and the `explode` modifier is not set, then
            // the result must be a comma separated list of keys followed by their respective values.
            $retVal = [];
            foreach ($pairs as $offset => $data) {
                $retVal[$offset] = $offset . ',' . $data;
            }
            $pairs = $retVal;
        }
        return [implode(',', $pairs), $useQuery];
    }
}
