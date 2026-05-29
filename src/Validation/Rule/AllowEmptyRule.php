<?php
declare(strict_types=1);

namespace Elephenv\Validation\Rule;

use Elephenv\Contracts\ValidatorInterface;

/**
 * Marker rule that permits empty string values for a variable.
 *
 * This rule performs no validation itself. Its presence in a RuleSet
 * signals to the RuleSet runner that the NotEmptyStringRule should be
 * skipped, effectively allowing empty strings even when that rule is
 * also registered in the same set.
 *
 * Usage:
 *   RuleSet::make()->isRequired()->allowEmpty()->notEmptyString();
 *   // notEmptyString is skipped because allowEmpty is present.
 */
final class AllowEmptyRule implements ValidatorInterface
{
    /**
     * No-op: this rule acts as a marker only.
     *
     * The RuleSet runner inspects the rule collection for the presence of
     * this class and adjusts its execution accordingly. No exception is
     * ever thrown from this method.
     *
     * @param string $name The variable name (unused).
     * @param mixed $value The resolved value (unused).
     */
    public function validate(string $name, mixed $value): void
    {
        // Intentionally empty - this rule is a marker, not a constraint.
    }
}
