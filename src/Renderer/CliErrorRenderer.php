<?php
declare(strict_types=1);

namespace Elephenv\Renderer;

use Throwable;

/**
 * Renders exception output to the standard error stream for CLI environments.
 *
 * Writes the exception class name, its message, and the full stack trace to
 * STDERR in a plain-text format suitable for terminal output. This class is
 * responsible only for formatting and output; it never calls exit() or throws.
 * Termination is delegated to the orchestrating ErrorRenderer.
 */
final class CliErrorRenderer
{
    /**
     * Write the exception details to STDERR.
     *
     * @param \Throwable $exception The exception to render.
     */
    public function render(Throwable $exception): void
    {
        fwrite(STDERR, sprintf(
            "[%s]\n%s\n\n%s\n",
            $exception::class,
            $exception->getMessage(),
            $exception->getTraceAsString(),
        ));
    }
}
