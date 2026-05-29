<?php
declare(strict_types=1);

namespace Elephenv\Exception;

/**
 * Thrown when the line parser encounters a syntactically invalid .env entry.
 *
 * The original raw line and its position are captured in the exception
 * context to assist with debugging.
 */
final class ParseException extends ElephenvException
{
    /**
     * @param string $message Human-readable description of the parse failure.
     * @param string $rawLine The raw line that triggered the failure.
     * @param int $lineNo The one-based line number within the source file, or 0 when unknown.
     */
    public function __construct(
        string $message,
        private readonly string $rawLine = '',
        private readonly int $lineNo = 0,
    ) {
        parent::__construct(
            message: $message,
            context: [
                'line' => $rawLine,
                'lineNumber' => $lineNo,
            ],
        );
    }

    /**
     * Get the raw line that triggered the parsing failure.
     */
    public function rawLine(): string
    {
        return $this->rawLine;
    }

    /**
     * Get the one-based line number within the source file (0 if unknown).
     */
    public function lineNo(): int
    {
        return $this->lineNo;
    }

    /**
     * HTTP status code associated with the exception.
     * Override in child classes when needed.
     */
    public function statusCode(): int
    {
        return 400;
    }
}
