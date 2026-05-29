<?php
declare(strict_types=1);

namespace Elephenv\Validation\Rule;

use Elephenv\Contracts\ValidatorInterface;
use Elephenv\Enum\ValidationRule;
use Elephenv\Exception\ValidationException;

/**
 * Delegates validation to a user-supplied callback.
 *
 * The callback receives the variable name and its resolved value.
 * It must return true to indicate a passing validation, or return a
 * non-empty string to signal failure with a custom message. Any other
 * return value causes a generic failure message to be generated.
 *
 * Example:
 *   RuleSet::make()->callback(function (string $name, mixed $value): bool|string {
 *       return filter_var($value, FILTER_VALIDATE_URL) !== false
 *           ? true
 *           : sprintf('"%s" must be a valid URL.', $name);
 *   });
 */
final class CallbackRule implements ValidatorInterface
{
    /**
     * @param callable $callback The validation callback invoked with (string $name, mixed $value).
     */
    public function __construct(private readonly mixed $callback)
    {
    }

    /**
     * @inheritdoc
     */
    public function validate(string $name, mixed $value): void
    {
        $result = ($this->callback)($name, $value);

        if ($result === true) {
            return;
        }

        $message = is_string($result) && $result !== ''
            ? $result
            : sprintf('"%s" failed custom validation.', $name);

        throw new ValidationException([[
            'variable' => $name,
            'rule' => ValidationRule::CALLBACK,
            'message' => $message,
        ]]);
    }
}
