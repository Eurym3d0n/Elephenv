<?php
declare(strict_types=1);

namespace Elephenv\Validation;

use Elephenv\Contracts\ValidatorInterface;
use Elephenv\Exception\ValidationException;
use Elephenv\Validation\Rule\AllowEmptyRule;
use Elephenv\Validation\Rule\CallbackRule;
use Elephenv\Validation\Rule\IsRequiredRule;
use Elephenv\Validation\Rule\MatchRule;
use Elephenv\Validation\Rule\NotEmptyStringRule;

/**
 * Fluent builder for composing ordered sets of validation rules.
 *
 * Rules are evaluated in the order they are registered. All violations
 * are collected before throwing so the caller receives a complete picture.
 * The AllowEmptyRule acts as a modifier: when present, NotEmptyStringRule
 * is skipped regardless of its position in the chain.
 *
 * Example:
 *   $rules = RuleSet::make()
 *       ->isRequired()
 *       ->notEmptyString()
 *       ->match('/^https?:\/\//');
 *
 *   Elephenv::load('.env', ['rules' => ['APP_URL' => $rules]]);
 */
final class RuleSet
{
    /**
     * Cached flag set when allowEmpty() is called, avoiding an O(n) scan
     * at the start of every run() call.
     *
     * @var boolean
     */
    private bool $allowsEmpty = false;

    /**
     * @var array<int, ValidatorInterface> The ordered list of registered rules.
     */
    private array $rules = [];

    /**
     * Create a new, empty RuleSet instance.
     *
     * @return self A fresh RuleSet ready for rule registration.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Add the IsRequiredRule: the variable must not be null.
     *
     * @return self The current instance for method chaining.
     */
    public function isRequired(): self
    {
        $this->rules[] = new IsRequiredRule();

        return $this;
    }

    /**
     * Add the AllowEmptyRule: empty strings are acceptable for this variable.
     *
     * When this rule is present, NotEmptyStringRule is skipped at runtime
     * even if it has been registered in the same set.
     *
     * @return self The current instance for method chaining.
     */
    public function allowEmpty(): self
    {
        $this->allowsEmpty = true;
        $this->rules[] = new AllowEmptyRule();

        return $this;
    }

    /**
     * Add the NotEmptyStringRule: the variable must be a non-empty string.
     *
     * This rule is silently skipped when AllowEmptyRule is also present.
     *
     * @return self The current instance for method chaining.
     */
    public function notEmptyString(): self
    {
        $this->rules[] = new NotEmptyStringRule();

        return $this;
    }

    /**
     * Add the MatchRule: the variable must match the given PCRE pattern.
     *
     * @param string $pattern A valid PCRE pattern including delimiters.
     * @return self The current instance for method chaining.
     */
    public function match(string $pattern): self
    {
        $this->rules[] = new MatchRule($pattern);

        return $this;
    }

    /**
     * Add the CallbackRule: the variable is validated by a user-supplied callable.
     *
     * The callable receives (string $name, mixed $value) and must return
     * true on success, or a non-empty string message on failure.
     *
     * @param callable $callback The validation callback.
     * @return self The current instance for method chaining.
     */
    public function callback(callable $callback): self
    {
        $this->rules[] = new CallbackRule($callback);

        return $this;
    }

    /**
     * Register a custom validator implementing ValidatorInterface.
     *
     * @param \Elephenv\Contracts\ValidatorInterface $rule The rule to append to this set.
     * @return self The current instance for method chaining.
     */
    public function add(ValidatorInterface $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * Execute all registered rules against the given variable value.
     *
     * Violations from every rule are accumulated before throwing so that
     * the resulting exception describes every constraint that was violated.
     *
     * @param string $name The environment variable name passed to each rule.
     * @param mixed $value The resolved value to validate.
     *
     * @throws ValidationException When at least one rule produces a violation.
     */
    public function run(string $name, mixed $value): void
    {
        $violations = [];
        $allowsEmpty = $this->allowsEmpty;

        foreach ($this->rules as $rule) {
            if ($allowsEmpty && $rule instanceof NotEmptyStringRule) {
                continue;
            }

            try {
                $rule->validate($name, $value);
            } catch (ValidationException $exception) {
                array_push($violations, ...$exception->violations());
            }
        }

        if ($violations !== []) {
            throw new ValidationException($violations);
        }
    }

    /**
     * Return the ordered list of registered rule instances.
     *
     * @return array<int, ValidatorInterface> All rules in this set.
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Merge all rules from another RuleSet into this instance.
     *
     * Rules are appended in the order they were registered in $other. If $other
     * contains an AllowEmptyRule, the allowsEmpty flag on this instance is set
     * accordingly so that the O(1) check in run() remains accurate.
     *
     * @param self $other The rule set whose rules are appended to this instance.
     * @return self The current instance for method chaining.
     */
    public function merge(self $other): self
    {
        foreach ($other->rules() as $rule) {
            $this->rules[] = $rule;

            if ($rule instanceof AllowEmptyRule) {
                $this->allowsEmpty = true;
            }
        }

        return $this;
    }
}
