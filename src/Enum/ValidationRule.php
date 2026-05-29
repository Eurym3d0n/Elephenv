<?php
declare(strict_types=1);

namespace Elephenv\Enum;

/**
 * Enumerates the built-in validation rule identifiers.
 *
 * Each constant holds the machine-readable discriminator embedded in every
 * violation array produced by the corresponding rule class. Consumers can
 * compare the 'rule' field of a violation against these constants to identify
 * which constraint was violated without parsing human-readable messages.
 *
 * These identifiers are intentionally defined as string constants rather than
 * backed enum cases so that they can be used directly as array values and
 * compared with strict equality (===) without calling ->value.
 */
enum ValidationRule: string
{
    const IS_REQUIRED = 'is_required';
    const ALLOW_EMPTY = 'allow_empty';
    const NOT_EMPTY_STRING = 'not_empty_string';
    const MATCH = 'match';
    const CALLBACK = 'callback';
}
