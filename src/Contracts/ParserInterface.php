<?php
declare(strict_types=1);

namespace Elephenv\Contracts;

/**
 * Defines the contract for line-level .env parsers.
 *
 * Implementations receive a single raw line from a .env file and return
 * either a structured token array or null when the line carries no
 * meaningful data (blank lines, comments).
 */
interface ParserInterface
{
    /**
     * Parse a single raw .env line into a name/value token pair.
     *
     * Returns null for empty lines and comment lines. Throws on malformed
     * input that cannot be recovered from (missing separator, invalid name).
     *
     * @param string $line A single raw line from a .env file.
     * @return array{name: string, value: string}|null The token pair, or null when the line is not parseable.
     * @throws \Elephenv\Exception\ParseException When the line is syntactically invalid.
     */
    public function parse(string $line): ?array;
}
