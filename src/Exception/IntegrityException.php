<?php
declare(strict_types=1);

namespace Elephenv\Exception;

/**
 * Thrown when the integrity check detects variables declared in the example
 * file that are absent from the active environment repository.
 */
final class IntegrityException extends ElephenvException
{
    /**
     * @param array<int, string> $missing Variable names present in the example file but absent from the repository.
     * @param string $source Path to the example file that was used as the reference.
     */
    public function __construct(
        private readonly array $missing,
        private readonly string $source,
    ) {
        parent::__construct(
            message: sprintf(
                'Integrity check failed - %d variable(s) declared in "%s" are missing from the environment: %s.',
                count($missing),
                $source,
                implode(', ', $missing),
            ),
            context: [
                'missing' => $missing,
                'source' => $source,
            ],
        );
    }

    /**
     * Return the list of variable names that are missing from the repository.
     *
     * @return array<int, string> Missing variable names.
     */
    public function missing(): array
    {
        return $this->missing;
    }

    /**
     * Return the path to the example file used as the integrity reference.
     *
     * @return string Absolute or relative path to the example file.
     */
    public function source(): string
    {
        return $this->source;
    }

    /**
     * HTTP status code associated with the exception.
     * Override in child classes when needed.
     */
    public function statusCode(): int
    {
        return 422;
    }
}
