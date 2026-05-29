<?php
declare(strict_types=1);

namespace Elephenv\Exception;

/**
 * Thrown when a security guard rejects a .env file.
 *
 * Security guards inspect properties such as file size and POSIX
 * permissions before the file content is processed. When strict mode
 * is enabled, violations are promoted from warnings to this exception.
 */
final class SecurityException extends ElephenvException
{
    /**
     * @param string $message Human-readable description of the security violation.
     * @param array<string, mixed> $context Machine-readable context for renderers and handlers.
     */
    public function __construct(string $message, array $context = [])
    {
        parent::__construct(message: $message, context: $context);
    }

    /**
     * HTTP status code associated with the exception.
     * Override in child classes when needed.
     */
    public function statusCode(): int
    {
        return 403;
    }
}
