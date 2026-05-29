<?php
declare(strict_types=1);

namespace Elephenv\Contracts;

use Elephenv\Enum\CastType;

/**
 * Defines the contract for raw string value detection and type casting.
 *
 * Implementations inspect a raw string representation from a .env source,
 * determine its most appropriate native PHP type, and perform the conversion.
 */
interface CasterInterface
{
    /**
     * Detect the most appropriate CastType for the given raw string.
     *
     * @param string $raw The raw string representation from the .env file.
     * @return \Elephenv\Enum\CastType The detected target type.
     */
    public function detect(string $raw): CastType;

    /**
     * Cast a raw string value to its inferred native PHP type.
     *
     * Non-string values are returned unchanged so this method is safe to call
     * on already-cast values.
     *
     * @param mixed $value The value to cast.
     * @return mixed The cast value, or the original when no cast applies.
     */
    public function cast(mixed $value): mixed;
}
