<?php
declare(strict_types=1);

namespace Elephenv\Renderer;

use Elephenv\Exception\ElephenvException;
use ReflectionClass;
use Throwable;

/**
 * Builds a typed view-model array for a given ElephenvException.
 *
 * The resulting array contains all data required by the HTML error templates:
 * shared fields present for every exception type, plus type-specific payload
 * fields that the corresponding template consumes. A `template` key indicates
 * which PHP template file the HttpErrorRenderer should render.
 *
 * Shared fields present in every view model:
 *   - title (string) - Short document title.
 *   - pageTitle (string) - Full page title for the browser tab.
 *   - exceptionClass (string) - Short name of the exception class.
 *   - message (string) - Human-readable exception message.
 *   - template (string) - Relative path to the inner error template.
 *   - statusCode (int) - HTTP status code to send with the response.
 *   - version (string) - Elephenv package version string.
 *   - phpVersion (string) - Active PHP version string.
 */
final readonly class ErrorViewModelFactory
{
    /**
     * @param \Elephenv\Renderer\ExceptionViewRegistry $registry
     * @param string $version The Elephenv version string shown in the rendered page footer.
     */
    public function __construct(
        private ExceptionViewRegistry $registry,
        private bool $debug = false,
        private string $version = '1.0.0'
    ) {
    }

    /**
     * Build a complete view-model array for the given exception.
     *
     * @param \Throwable $exception The exception to describe.
     * @return array<string, mixed> The view-model data map.
     */
    public function make(Throwable $exception): array
    {
        return [
            ...$this->resolveContext($exception),
            'title' => 'Elephenv Error',
            'pageTitle' => 'Elephenv - Environment Error',
            'exceptionClass' => (new ReflectionClass($exception))->getShortName(),
            'message' => $this->sanitizeMessage(
                $exception->getMessage(),
            ),
            'template' => $this->registry->resolve($exception),
            'statusCode' => $this->resolveStatusCode($exception),
            'version' => $this->version,
            'phpVersion' => PHP_VERSION,
            'trace' => $this->debug
                ? $exception->getTrace()
                : [],
            'file' => $this->debug
                ? $exception->getFile()
                : null,
            'line' => $this->debug
                ? $exception->getLine()
                : null,
        ];
    }

    /**
     * Resolve the HTTP status code for the given exception.
     *
     * Delegates to ElephenvException::statusCode() for exceptions originating
     * from within the package. Falls back to 500 for any other Throwable that
     * reaches the renderer through an unexpected code path.
     *
     * @param \Throwable $exception The exception being rendered.
     * @return int The HTTP status code to send with the error response.
     */
    private function resolveStatusCode(Throwable $exception): int
    {
        return $exception instanceof ElephenvException
            ? $exception->statusCode()
            : 500;
    }

    /**
     * Extract the structured context payload from the given exception.
     *
     * Returns the machine-readable context array attached to ElephenvException
     * instances. For generic Throwable instances that carry no context, an
     * empty array is returned so the view-model remains consistent.
     *
     * @param \Throwable $exception The exception being rendered.
     * @return array<string, mixed> The context payload, or an empty array for non-Elephenv exceptions.
     */
    private function resolveContext(Throwable $exception): array
    {
        if (!$exception instanceof ElephenvException) {
            return [];
        }

        return $this->sanitizeContext(
            $exception->context(),
        );
    }

    /**
     * Remove sensitive values from messages.
     *
     * @param string $message
     * @return string
     */
    private function sanitizeMessage(
        string $message,
    ): string {
        if ($this->debug) {
            return $message;
        }

        return preg_replace('/(password|secret|token|key)=([^\s]+)/i', '$1=********', $message) ?? $message;
    }

    /**
     * Recursively sanitize sensitive context values.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(
        array $context,
    ): array {
        if ($this->debug) {
            return $context;
        }

        array_walk_recursive(
            $context,
            static function (&$value, $key): void {
                if (!is_string($value)) {
                    return;
                }

                if (preg_match('/password|secret|token|key/i', (string) $key)) {
                    $value = '********';
                }
            }
        );

        return $context;
    }
}
