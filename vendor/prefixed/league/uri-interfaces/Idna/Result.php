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
namespace Matomo\Dependencies\Oauth2\League\Uri\Idna;

/**
 * @see https://unicode-org.github.io/icu-docs/apidoc/released/icu4c/uidna_8h.html
 */
final class Result
{
    /**
     * @readonly
     * @var string
     */
    private $domain;
    /**
     * @readonly
     * @var bool
     */
    private $isTransitionalDifferent;
    /**
     * @readonly
     * @var mixed[]
     */
    private $errors;
    private function __construct(string $domain, bool $isTransitionalDifferent, array $errors)
    {
        $this->domain = $domain;
        $this->isTransitionalDifferent = $isTransitionalDifferent;
        /** @var array<Error> */
        $this->errors = $errors;
    }
    /**
     * @param array{result:string, isTransitionalDifferent:bool, errors:int} $infos
     */
    public static function fromIntl(array $infos) : self
    {
        return new self($infos['result'], $infos['isTransitionalDifferent'], Error::filterByErrorBytes($infos['errors']));
    }
    public function domain() : string
    {
        return $this->domain;
    }
    public function isTransitionalDifferent() : bool
    {
        return $this->isTransitionalDifferent;
    }
    /**
     * @return array<Error>
     */
    public function errors() : array
    {
        return $this->errors;
    }
    public function hasErrors() : bool
    {
        return [] !== $this->errors;
    }
    /**
     * @param \Matomo\Dependencies\Oauth2\League\Uri\Idna\Error::* $error
     */
    public function hasError(int $error) : bool
    {
        return in_array($error, $this->errors, \true);
    }
}
