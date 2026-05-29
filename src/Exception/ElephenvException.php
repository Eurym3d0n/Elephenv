<?php
declare(strict_types=1);

namespace Elephenv\Exception;

use RuntimeException;
use Throwable;

/**
 * Base exception for all Elephenv domain failures.
 *
 * Provides:
 * - structured machine-readable context
 * - optional previous exception chaining
 * - HTTP status metadata
 *
 * The context payload is intended for renderers, loggers,
 * and debugging tooling.
 */
abstract class ElephenvException extends RuntimeException
{
    /**
     * @param string $message Human-readable description of the error.
     * @param array<string, mixed> $context Structured diagnostic payload.
     * @param \Throwable|null $previous Previous throwable in the exception chain.
     */
    public function __construct(
        string $message,
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Return structured exception metadata.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * Return the HTTP status code associated with the exception.
     *
     * Child exceptions may override this method.
     */
    public function statusCode(): int
    {
        return 500;
    }
}
