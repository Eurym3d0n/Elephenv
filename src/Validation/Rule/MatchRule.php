<?php
declare(strict_types=1);

namespace Elephenv\Validation\Rule;

use Elephenv\Contracts\ValidatorInterface;
use Elephenv\Enum\ValidationRule;
use Elephenv\Exception\ValidationException;

/**
 * Validates that an environment variable value matches a PCRE regular expression.
 *
 * Non-scalar values are coerced to an empty string before matching, so the
 * rule will fail for arrays, objects, and null unless the pattern explicitly
 * accommodates an empty string.
 *
 * Example:
 *   RuleSet::make()->match('/^\d{4}-\d{2}-\d{2}$/');
 */
final class MatchRule implements ValidatorInterface
{
    /**
     * @param string $pattern A valid PCRE pattern including delimiters (e.g. '/^[a-z]+$/i').
     */
    public function __construct(private readonly string $pattern)
    {
    }

    /**
     * @inheritdoc
     */
    public function validate(string $name, mixed $value): void
    {
        $subject = is_scalar($value) ? (string) $value : '';

        if (!preg_match($this->pattern, $subject)) {
            throw new ValidationException([[
                'variable' => $name,
                'rule' => ValidationRule::MATCH,
                'message' => sprintf(
                    '"%s" with value "%s" does not match the required pattern %s.',
                    $name,
                    $subject,
                    $this->pattern,
                ),
            ]]);
        }
    }
}
