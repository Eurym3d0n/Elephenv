<?php
declare(strict_types=1);

namespace Elephenv\Contracts;

/**
 * Defines the contract for individual environment variable validators.
 *
 * Each implementation encapsulates a single validation rule and throws a
 * ValidationException when the supplied value does not satisfy its constraint.
 */
interface ValidatorInterface
{
    /**
     * Validate the given environment variable value against this rule.
     *
     * @param string $name The variable name, used in violation messages.
     * @param mixed $value The resolved value to validate.
     * @throws \Elephenv\Exception\ValidationException When the value fails this rule.
     */
    public function validate(string $name, mixed $value): void;
}
