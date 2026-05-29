<?php
declare(strict_types=1);

namespace Elephenv\Support;

use Elephenv\Contracts\CasterInterface;
use Elephenv\Enum\CastType;

/**
 * Detects and casts raw environment string values to their native PHP types.
 *
 * String representations of common scalar sentinels are mapped as follows:
 *   - Booleans  : true, 1, yes, on (true)  |  false, 0, no, off (false)
 *   - Null      : null, nil, none
 *   - Empty     : the literal string "empty" is cast to an empty string ('')
 *   - Integers  : any string matching /^-?\d+$/
 *   - Floats    : any string matching /^-?\d*\.\d+([eE][+-]?\d+)?$/
 *   - Strings   : everything else
 *
 * The is* methods are intentionally static because they are consumed as
 * lightweight type inspection shortcuts by the Elephenv facade and do not
 * belong to any injectable contract.
 */
final class Inferrer implements CasterInterface
{
    /**
     * String representations that map to boolean true.
     *
     * @var array<int, string>
     */
    private const array TRUTHY = ['true', '1', 'yes', 'on'];

    /**
     * String representations that map to boolean false.
     *
     * @var array<int, string>
     */
    private const array FALSY = ['false', '0', 'no', 'off'];

    /**
     * String representations that map to null.
     *
     * @var array<int, string>
     */
    private const array NULLISH = ['null', 'nil', 'none'];

    /**
     * The literal value that is cast to an empty string.
     */
    private const string EMPTY_SENTINEL = 'empty';

    /**
     * Determine whether the given value is a string.
     *
     * @param mixed $value The value to inspect.
     * @return bool True when the value is of type string.
     */
    public static function isString(mixed $value): bool
    {
        return is_string($value);
    }

    /**
     * Determine whether the given value is a boolean.
     *
     * @param mixed $value The value to inspect.
     * @return bool True when the value is of type bool.
     */
    public static function isBool(mixed $value): bool
    {
        return is_bool($value);
    }

    /**
     * Determine whether the given value is an array.
     *
     * @param mixed $value The value to inspect.
     * @return bool True when the value is of type array.
     */
    public static function isArray(mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Determine whether the given value is an integer.
     *
     * @param mixed $value The value to inspect.
     * @return bool True when the value is of type int.
     */
    public static function isInt(mixed $value): bool
    {
        return is_int($value);
    }

    /**
     * Determine whether the given value is a float.
     *
     * @param mixed $value The value to inspect.
     * @return bool True when the value is of type float.
     */
    public static function isFloat(mixed $value): bool
    {
        return is_float($value);
    }

    /**
     * Determine whether the given value is null.
     *
     * @param mixed $value The value to inspect.
     * @return bool True when the value is null.
     */
    public static function isNull(mixed $value): bool
    {
        return $value === null;
    }

    /**
     * Determine whether the given value is numeric.
     *
     * @param mixed $value The value to inspect.
     * @return bool True when the value is numeric (int, float, or numeric string).
     */
    public static function isNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * @inheritdoc
     */
    public function detect(string $raw): CastType
    {
        $normalised = strtolower(trim($raw));

        if (in_array($normalised, self::NULLISH, true)) {
            return CastType::Null;
        }

        if (
            in_array($normalised, self::TRUTHY, true)
            || in_array($normalised, self::FALSY, true)
        ) {
            return CastType::Bool;
        }

        if ($normalised === self::EMPTY_SENTINEL) {
            return CastType::String;
        }

        if (preg_match('/^-?\d+$/', $raw)) {
            return CastType::Integer;
        }

        if (preg_match('/^-?\d*\.\d+([eE][+-]?\d+)?$/', $raw)) {
            return CastType::Float;
        }

        return CastType::String;
    }

    /**
     * @inheritdoc
     */
    public function cast(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $normalised = strtolower(trim($value));

        return match (true) {
            in_array($normalised, self::TRUTHY, true) => true,
            in_array($normalised, self::FALSY, true) => false,
            in_array($normalised, self::NULLISH, true) => null,
            $normalised === self::EMPTY_SENTINEL => '',
            default => $this->castNumeric($value),
        };
    }

    /**
     * Attempt to cast a string to int or float, falling back to string.
     *
     * @param string $value The raw string to cast.
     * @return int|float|string The cast numeric value, or the original string.
     */
    private function castNumeric(string $value): int|float|string
    {
        if (preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }

        if (preg_match('/^-?\d*\.\d+([eE][+-]?\d+)?$/', $value)) {
            return (float) $value;
        }

        return $value;
    }
}
