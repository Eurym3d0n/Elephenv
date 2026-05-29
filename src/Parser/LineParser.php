<?php
declare(strict_types=1);

namespace Elephenv\Parser;

use Elephenv\Contracts\ParserInterface;
use Elephenv\Exception\ParseException;

/**
 * Parses a single raw line from a .env file into a name/value token pair.
 *
 * Handles the following syntax:
 *  - Blank lines and lines beginning with # are silently skipped.
 *  - Optional `export ` prefix is stripped before tokenisation.
 *  - Array-notation variable names (e.g. DB[host]) are accepted.
 *  - Inline comments (unquoted # after the value) are stripped.
 *  - Quoted values retain internal whitespace; unquoted values are left-trimmed.
 */
final class LineParser implements ParserInterface
{
    /**
     * Accepted variable name pattern, including optional array-notation segments.
     */
    private const string NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(?:\[[A-Za-z0-9_]+\])*$/';

    /**
     * @inheritdoc
     */
    public function parse(string $line): ?array
    {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            return null;
        }

        // Strip the optional `export` shell directive.
        $trimmed = preg_replace('/^export\s+/', '', $trimmed) ?? $trimmed;

        if (!str_contains($trimmed, '=')) {
            throw new ParseException(
                sprintf('Missing "=" separator on line: %s', $line),
                $line,
            );
        }

        [$name, $value] = explode('=', $trimmed, 2);

        $name = trim($name);
        $value = ltrim($value);

        if ($name === '') {
            throw new ParseException(
                sprintf('Variable name cannot be empty on line: %s', $line),
                $line,
            );
        }

        if (!preg_match(self::NAME_PATTERN, $name)) {
            throw new ParseException(
                sprintf(
                    'Invalid variable name "%s" on line: %s',
                    $name,
                    $line,
                ),
                $line,
            );
        }

        return [
            'name' => $name,
            'value' => $this->stripInlineComment($value),
        ];
    }

    /**
     * Remove a trailing inline comment from an unquoted value.
     *
     * The parser tracks open/closed quote state character by character.
     * A # outside of any quoted region is treated as the start of a comment
     * and everything from that point onward is discarded.
     *
     * @param string $value The raw value portion of a .env line.
     * @return string The value with any trailing inline comment removed.
     */
    private function stripInlineComment(string $value): string
    {
        $inQuote = null;
        $escaped = false;
        $buffer = '';

        foreach (mb_str_split($value, 1, 'UTF-8') as $char) {
            if ($escaped) {
                $buffer .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $buffer .= $char;
                $escaped = true;
                continue;
            }

            if ($char === '"' || $char === "'") {
                $inQuote = $inQuote === null
                    ? $char
                    : ($inQuote === $char ? null : $inQuote);

                $buffer .= $char;
                continue;
            }

            if ($char === '#' && $inQuote === null) {
                break;
            }

            $buffer .= $char;
        }

        return rtrim($buffer);
    }
}
