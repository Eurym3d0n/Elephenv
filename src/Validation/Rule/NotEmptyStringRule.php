<?php
declare(strict_types=1);

namespace Elephenv\Validation\Rule;

use Elephenv\Contracts\ValidatorInterface;
use Elephenv\Enum\ValidationRule;
use Elephenv\Exception\ValidationException;

/**
 * Enforces that an environment variable resolves to a non-empty string.
 *
 * Fails when the value is not a string, or when it is a string that is
 * empty after casting. This rule is automatically skipped by the RuleSet
 * runner when an AllowEmptyRule is also present in the same set.
 *
 * Combine with IsRequiredRule to reject both null and empty string:
 *   RuleSet::make()->isRequired()->notEmptyString();
 */
final class NotEmptyStringRule implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate(string $name, mixed $value): void
    {
        if (!is_string($value) || $value === '') {
            throw new ValidationException([[
                'variable' => $name,
                'rule' => ValidationRule::NOT_EMPTY_STRING,
                'message' => sprintf('"%s" must be a non-empty string.', $name),
            ]]);
        }
    }
}
