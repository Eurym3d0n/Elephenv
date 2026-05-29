<?php
declare(strict_types=1);

namespace Elephenv\Enum;

/**
 * Enumerates the scalar type targets supported by automatic value casting.
 *
 * Used by the Inferrer to communicate which PHP type a raw string
 * representation should be converted to before it is stored.
 */
enum CastType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Float = 'float';
    case Bool = 'bool';
    case Null = 'null';
}
