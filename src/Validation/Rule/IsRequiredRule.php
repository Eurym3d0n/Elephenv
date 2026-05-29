<?php
declare(strict_types=1);

namespace Elephenv\Validation\Rule;

use Elephenv\Contracts\ValidatorInterface;
use Elephenv\Enum\ValidationRule;
use Elephenv\Exception\ValidationException;

/**
 * Enforces that an environment variable is present and not null.
 *
 * A variable resolves to null when it is absent from the environment entirely
 * or when its value is one of the nullish sentinels (null, nil, none) and
 * automatic casting is enabled. Empty strings pass this rule; use
 * NotEmptyStringRule in addition when an empty string is also unacceptable.
 */
final class IsRequiredRule implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate(string $name, mixed $value): void
    {
        if ($value === null) {
            throw new ValidationException([[
                'variable' => $name,
                'rule' => ValidationRule::IS_REQUIRED,
                'message' => sprintf('"%s" is required but is missing or null.', $name),
            ]]);
        }
    }
}
