<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Validation;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Exception;
use RuntimeException;
use function array_map;
use function implode;
final class RequiredConstraintsViolated extends RuntimeException implements Exception
{
    /**
     * @var ConstraintViolation[]
     * @readonly
     */
    public $violations = [];
    /** @param ConstraintViolation[] $violations */
    public function __construct(string $message = '', array $violations = [])
    {
        $this->violations = $violations;
        parent::__construct($message);
    }
    public static function fromViolations(ConstraintViolation ...$violations) : self
    {
        return new self(self::buildMessage($violations), $violations);
    }
    /** @param ConstraintViolation[] $violations */
    private static function buildMessage(array $violations) : string
    {
        $violations = array_map(static function (ConstraintViolation $violation) : string {
            return '- ' . $violation->getMessage();
        }, $violations);
        $message = "The token violates some mandatory constraints, details:\n";
        $message .= implode("\n", $violations);
        return $message;
    }
    /** @return ConstraintViolation[] */
    public function violations() : array
    {
        return $this->violations;
    }
}
