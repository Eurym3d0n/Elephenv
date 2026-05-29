<?php
declare(strict_types=1);

namespace Elephenv\Parser;

/**
 * Strips surrounding quotes from .env values and resolves escape sequences.
 *
 * Quoting rules:
 *  - Double-quoted values: surrounding quotes are removed and the recognised
 *    escape sequences (\n, \t, \r, \\, \", \$) are expanded.
 *  - Single-quoted values: surrounding quotes are removed and the content is
 *    returned verbatim without any escape processing.
 *  - Unquoted values: returned as-is.
 *  - Null or empty input: returned unchanged.
 */
final readonly class ValueParser
{
    /**
     * Escape sequences expanded inside double-quoted values.
     *
     * @var array<string, string>
     */
    private const array ESCAPE_MAP = [
        '\n' => "\n",
        '\t' => "\t",
        '\r' => "\r",
        '\\\\' => '\\',
        '\"' => '"',
        '\$' => '$',
    ];

    /**
     * Parse a raw .env value, stripping quotes and resolving escape sequences.
     *
     * @param string|null $raw The raw value token produced by the line parser.
     * @return string|null The processed value, or null when the input is null or empty.
     */
    public function parse(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $length = strlen($raw);

        // Values shorter than two characters cannot have balanced surrounding quotes.
        if ($length < 2) {
            return $raw;
        }

        $first = $raw[0];
        $last = $raw[$length - 1];

        // Only process values where the opening and closing characters match.
        if ($first !== $last) {
            return $raw;
        }

        $inner = substr($raw, 1, -1);

        return match ($first) {
            '"' => strtr($inner, self::ESCAPE_MAP),
            "'" => $inner,
            default => $raw,
        };
    }
}
