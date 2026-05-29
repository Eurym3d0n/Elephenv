<?php
declare(strict_types=1);

namespace Elephenv\Value;

use Elephenv\Contracts\RepositoryInterface;
use Elephenv\Validation\RuleSet;

/**
 * Fluent wrapper around a single resolved environment variable value.
 *
 * Provides chainable validation, transformation, and type-casting methods.
 * Validation methods throw ValidationException on failure, allowing them to
 * be composed in a fail-fast chain without intermediate conditionals.
 *
 * Example:
 *   Elephenv::value('DATABASE_URL')
 *       ->required()
 *       ->notEmptyString()
 *       ->match('/^postgres:\/\//')
 *       ->toString();
 */
final class EnvValue
{
    /**
     * @param mixed $value The resolved variable value.
     * @param string $name The variable name, used in validation messages.
     * @param \Elephenv\Contracts\RepositoryInterface $repository The active repository for side-effect writes.
     * @param array<string, mixed> $context Additional variables written via assign().
     */
    public function __construct(
        private mixed $value,
        private readonly string $name,
        private readonly RepositoryInterface $repository,
        private array $context = [],
    ) {
    }

    /**
     * Return the raw resolved value without any casting or transformation.
     *
     * @return mixed The value as resolved by the repository.
     */
    public function raw(): mixed
    {
        return $this->value;
    }

    /**
     * Assert that the value is not null.
     *
     * @return self The current instance for method chaining.
     *
     * @throws \Elephenv\Exception\ValidationException When the value is null.
     */
    public function required(): self
    {
        RuleSet::make()->isRequired()->run($this->name, $this->value);

        return $this;
    }

    /**
     * Assert that the value is a non-empty string.
     *
     * @return self The current instance for method chaining.
     *
     * @throws \Elephenv\Exception\ValidationException When the value is not a non-empty string.
     */
    public function notEmptyString(): self
    {
        RuleSet::make()->notEmptyString()->run($this->name, $this->value);

        return $this;
    }

    /**
     * Assert that the value matches the given PCRE pattern.
     *
     * @param string $pattern A valid PCRE pattern including delimiters.
     * @return self The current instance for method chaining.
     *
     * @throws \Elephenv\Exception\ValidationException When the value does not match.
     */
    public function match(string $pattern): self
    {
        RuleSet::make()->match($pattern)->run($this->name, $this->value);

        return $this;
    }

    /**
     * Assert that the value passes the given validation callback.
     *
     * @param callable $callback Receives (string $name, mixed $value) and returns true or an error message.
     * @return self The current instance for method chaining.
     *
     * @throws \Elephenv\Exception\ValidationException When the callback does not return true.
     */
    public function validate(callable $callback): self
    {
        RuleSet::make()->callback($callback)->run($this->name, $this->value);

        return $this;
    }

    /**
     * Apply a complete RuleSet to this value.
     *
     * @param RuleSet $rules The rule set to execute.
     * @return self The current instance for method chaining.
     *
     * @throws \Elephenv\Exception\ValidationException When any rule in the set fails.
     */
    public function applyRules(RuleSet $rules): self
    {
        $rules->run($this->name, $this->value);

        return $this;
    }

    /**
     * Replace the current value with the given default when the value is null or an empty string.
     *
     * @param mixed $default The fallback value to use.
     * @return self The current instance for method chaining.
     */
    public function defaults(mixed $default): self
    {
        if ($this->value === null || $this->value === '') {
            $this->value = $default;
        }

        return $this;
    }

    /**
     * Pass the current value through a transformation callback and store the result.
     *
     * @param callable $callback Receives the current value and returns the transformed value.
     * @return self The current instance for method chaining.
     */
    public function transform(callable $callback): self
    {
        $this->value = $callback($this->value);

        return $this;
    }

    /**
     * Cast the current value to string.
     *
     * @return string The value coerced to string.
     */
    public function toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Cast the current value to bool.
     *
     * Uses PHP's FILTER_VALIDATE_BOOLEAN filter for accurate string coercion.
     *
     * @return bool The value coerced to bool.
     */
    public function toBool(): bool
    {
        return is_bool($this->value)
            ? $this->value
            : (bool) filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Cast the current value to an array.
     *
     * Scalar values are wrapped in a single-element array.
     *
     * @return array<int|string, mixed> The value as an array.
     */
    public function toArray(): array
    {
        return is_array($this->value) ? $this->value : [$this->value];
    }

    /**
     * Cast the current value to int.
     *
     * @return int The value coerced to int.
     */
    public function toInt(): int
    {
        return (int) $this->value;
    }

    /**
     * Cast the current value to float.
     *
     * @return float The value coerced to float.
     */
    public function toFloat(): float
    {
        return (float) $this->value;
    }

    /**
     * Determine whether the current value is a string.
     *
     * @return bool True when the value is of type string.
     */
    public function isString(): bool
    {
        return is_string($this->value);
    }

    /**
     * Determine whether the current value is a boolean.
     *
     * @return bool True when the value is of type bool.
     */
    public function isBool(): bool
    {
        return is_bool($this->value);
    }

    /**
     * Determine whether the current value is an array.
     *
     * @return bool True when the value is of type array.
     */
    public function isArray(): bool
    {
        return is_array($this->value);
    }

    /**
     * Determine whether the current value is an integer.
     *
     * @return bool True when the value is of type int.
     */
    public function isInt(): bool
    {
        return is_int($this->value);
    }

    /**
     * Determine whether the current value is a float.
     *
     * @return bool True when the value is of type float.
     */
    public function isFloat(): bool
    {
        return is_float($this->value);
    }

    /**
     * Determine whether the current value is null.
     *
     * @return bool True when the value is null.
     */
    public function isNull(): bool
    {
        return $this->value === null;
    }

    /**
     * Write an additional variable to the repository and superglobals as a side effect.
     *
     * @param string $name The variable name to write.
     * @param mixed $value The value to assign.
     * @return self The current instance for method chaining.
     */
    public function assign(string $name, mixed $value): self
    {
        $this->context[$name] = $value;
        $this->repository->set($name, $value);

        return $this;
    }

    /**
     * Write multiple variables to the repository and superglobals as a side effect.
     *
     * @param array<string, mixed> $values Variable name to value pairs to write.
     * @return self The current instance for method chaining.
     */
    public function assignMany(array $values): self
    {
        foreach ($values as $name => $value) {
            $this->assign((string) $name, $value);
        }

        return $this;
    }

    /**
     * Return all variables written to the repository during this value chain.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
