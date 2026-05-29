<?php
declare(strict_types=1);

namespace Elephenv\Renderer;

use Elephenv\Contracts\ErrorRendererInterface;
use Throwable;

/**
 * Orchestrates error rendering by delegating to the appropriate sub-renderer.
 *
 * Dispatches to CliErrorRenderer when running under the PHP CLI SAPI, and to
 * HttpErrorRenderer otherwise. After the sub-renderer has produced its output,
 * this class terminates the process with a non-zero exit code unless the $exit
 * flag has been disabled (e.g. during testing).
 *
 * This class does not implement ErrorRendererInterface directly because the
 * interface contract accepts ElephenvException, while this orchestrator must
 * handle any Throwable thrown during bootstrap — including exceptions that
 * originate outside the Elephenv package.
 */
final class ErrorRenderer implements ErrorRendererInterface
{
    /**
     * @param \Elephenv\Renderer\HttpErrorRenderer $http Renderer used for HTTP request contexts.
     * @param \Elephenv\Renderer\CliErrorRenderer $cli Renderer used for CLI execution contexts.
     */
    public function __construct(
        private HttpErrorRenderer $http,
        private CliErrorRenderer $cli,
    ) {
    }

    /**
     * Render the exception in the appropriate format for the current SAPI and terminate.
     *
     * @param \Throwable $exception The exception to render.
     * @return never
     */
    public function render(Throwable $exception): never
    {
        if ($this->isCliEnvironment()) {
            $this->cli->render($exception);
        } else {
            $this->http->render($exception);
        }

        exit(1);
    }

    /**
     * Determine whether the current runtime
     * environment is CLI-oriented.
     *
     * @return bool
     */
    private function isCliEnvironment(): bool
    {
        return in_array(
            PHP_SAPI,
            ['cli', 'phpdbg'],
            true,
        );
    }
}
