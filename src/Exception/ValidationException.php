<?php
declare(strict_types=1);

namespace Elephenv\Exception;

/**
 * Thrown when one or more validation rules fail during environment loading.
 *
 * All violations accumulated during a single loading pass are collected and
 * reported together so the consumer receives a complete picture of every
 * failing constraint in a single exception rather than one at a time.
 */
final class ValidationException extends ElephenvException
{
    /**
     * @param array<int, array{variable: string, rule: \Elephenv\Enum\ValidationRule|string, message: string}> $violations
     *   Structured list of rule violations, each describing the affected variable,
     *   the rule that was violated, and a human-readable message.
     */
    public function __construct(private readonly array $violations)
    {
        $summary = implode('; ', array_map(
            static fn(array $violation): string => sprintf(
                '[%s] %s',
                $violation['variable'],
                $violation['message'],
            ),
            $violations,
        ));

        parent::__construct(
            message: sprintf('Environment validation failed: %s', $summary),
            context: ['violations' => $violations],
        );
    }

    /**
     * Return all structured violations collected during the loading pass.
     *
     * @return array<int, array{variable: string, rule: \Elephenv\Enum\ValidationRule|string, message: string}>
     */
    public function violations(): array
    {
        return $this->violations;
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
