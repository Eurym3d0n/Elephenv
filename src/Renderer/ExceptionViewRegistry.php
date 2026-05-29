<?php
declare(strict_types=1);

namespace Elephenv\Renderer;

use Throwable;

/**
 * Maps exception class names to their corresponding error template paths.
 *
 * The registry is populated by calling register() with fully-qualified class
 * names as keys and relative template paths as values. Resolution is performed
 * via instanceof so that subclasses of registered exceptions are matched
 * automatically. When no registered class matches, the generic fallback
 * template is returned.
 */
final class ExceptionViewRegistry
{
    /**
     * @var array<class-string, string> Ordered map of exception class name to relative template path.
     */
    private array $templates = [];

    /**
     * Register an exception class and its associated error template.
     *
     * Entries are evaluated in registration order during resolve(), so more
     * specific exception classes should be registered before their parents.
     *
     * @param class-string $exception The fully-qualified exception class name to match.
     * @param string $template The relative template path to use when this class is matched.
     * @return self The current instance for method chaining.
     */
    public function register(string $exception, string $template): self
    {
        $this->templates[$exception] = $template;

        return $this;
    }

    /**
     * Resolve the template path for the given exception.
     *
     * Iterates the registered map in order and returns the template path of
     * the first registered class for which the exception is an instance.
     * Falls back to the generic error template when no match is found.
     *
     * @param \Throwable $exception The exception to resolve a template for.
     * @return string The relative path to the error template.
     */
    public function resolve(Throwable $exception): string
    {
        foreach ($this->templates as $class => $template) {
            if ($exception instanceof $class) {
                return $template;
            }
        }

        return 'errors/generic.php';
    }
}
