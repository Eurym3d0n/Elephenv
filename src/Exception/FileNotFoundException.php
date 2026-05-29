<?php
declare(strict_types=1);

namespace Elephenv\Exception;

/**
 * Thrown when a .env file path cannot be resolved to an existing file.
 */
final class FileNotFoundException extends ElephenvException
{
    /**
     * @param string $path The path that could not be found.
     */
    public function __construct(private readonly string $path)
    {
        parent::__construct(
            message: sprintf('Environment file not found: "%s".', $path),
            context: ['path' => $path],
        );
    }

    /**
     * Returns the missing file path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * HTTP status code associated with the exception.
     * Override in child classes when needed.
     */
    public function statusCode(): int
    {
        return 404;
    }
}
