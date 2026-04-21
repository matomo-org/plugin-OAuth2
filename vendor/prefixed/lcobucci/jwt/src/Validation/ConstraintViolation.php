<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Exception;
use RuntimeException;
final class ConstraintViolation extends RuntimeException implements Exception
{
    /**
     * @var class-string<Constraint>|null
     * @readonly
     */
    public $constraint;
    /** @param class-string<Constraint>|null $constraint */
    public function __construct(string $message = '', ?string $constraint = null)
    {
        $this->constraint = $constraint;
        parent::__construct($message);
    }
    /** @param non-empty-string $message */
    public static function error(string $message, Constraint $constraint) : self
    {
        return new self($message, get_class($constraint));
    }
}
