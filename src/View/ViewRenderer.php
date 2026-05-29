<?php
declare(strict_types=1);

namespace Elephenv\View;

use Elephenv\Support\HtmlEscaper;
use RuntimeException;
use Stringable;
use Throwable;

/**
 * Minimal PHP template renderer with layout support and HTML escaping.
 *
 * Templates are plain PHP files. The renderer instance is exposed
 * inside templates through the `$renderer` variable, providing access
 * to escaping and partial rendering helpers.
 *
 * Rendering flow:
 *  1. Render the target template into a content buffer.
 *  2. Inject the rendered content into the layout template.
 *  3. Return the final rendered HTML string.
 *
 * Global variables are merged into every rendering operation.
 */
final readonly class ViewRenderer
{
    /**
     * @param string $viewsPath Absolute path to the templates directory.
     * @param \Elephenv\Support\HtmlEscaper $escaper HTML escaping utility.
     * @param string $layout Default layout template path.
     * @param array<string, mixed> $globals Global template variables.
     */
    public function __construct(
        private string $viewsPath,
        private HtmlEscaper $escaper = new HtmlEscaper(),
        private string $layout = 'layouts/default.php',
        private array $globals = [],
    ) {
    }

    /**
     * Render a template wrapped in the configured layout.
     *
     * @param string $template Template relative path.
     * @param array<string, mixed> $data Template variables.
     */
    public function render(string $template, array $data = []): string
    {
        $content = $this->fetch($template, $data);

        return $this->fetch($this->layout, [
            ...$this->globals,
            ...$data,
            'content' => $content,
        ]);
    }

    /**
     * Render a partial template without layout wrapping.
     *
     * @param string $template Partial template path.
     * @param array<string, mixed> $data Partial variables.
     */
    public function partial(string $template, array $data = []): string
    {
        return $this->fetch($template, $data);
    }

    /**
     * Escape a value for safe HTML output.
     *
     * @param bool|float|int|null|string|Stringable $value
     */
    public function e(bool|float|int|null|string|Stringable $value): string
    {
        return $this->escaper->escape($value);
    }

    /**
     * Render a template file into a string.
     *
     * Rendering occurs inside an isolated static scope to avoid
     * leaking internal renderer variables into template scope.
     *
     * @param string $template Relative template path.
     * @param array<string, mixed> $data Template variables.
     *
     * @throws \RuntimeException
     */
    private function fetch(string $template, array $data = []): string
    {
        $file = $this->resolve($template);

        $variables = [
            ...$this->globals,
            ...$data,
        ];

        ob_start();

        try {
            (static function (
                string $__file__,
                array $__variables__,
                ViewRenderer $__renderer__,
            ): void {
                /**
                 * Expose renderer instance to templates.
                 *
                 * This variable is intentionally local to the rendering scope
                 * and cannot be overridden by extracted template variables.
                 */
                $renderer = $__renderer__;

                extract($__variables__, EXTR_SKIP);

                require $__file__;
            })($file, $variables, $this);

            $output = ob_get_clean();

            if ($output === false) {
                throw new RuntimeException(
                    sprintf(
                        'Failed to retrieve output buffer for template "%s".',
                        $template,
                    ),
                );
            }

            return $output;
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw new RuntimeException(
                sprintf(
                    'Failed to render template "%s".',
                    $template,
                ),
                previous: $throwable,
            );
        }
    }

    /**
     * Resolve a template path to an absolute filesystem path.
     *
     * @param string $template Relative template path.
     *
     * @throws \RuntimeException
     */
    private function resolve(string $template): string
    {
        $base = rtrim($this->viewsPath, DIRECTORY_SEPARATOR);

        $path = $base
            . DIRECTORY_SEPARATOR
            . ltrim($template, DIRECTORY_SEPARATOR);

        $real = realpath($path);

        if ($real === false || !is_file($real)) {
            throw new RuntimeException(
                sprintf(
                    'View template "%s" was not found.',
                    $template,
                ),
            );
        }

        /**
         * Prevent path traversal outside the views directory.
         */
        if (!str_starts_with($real, realpath($base) ?: $base)) {
            throw new RuntimeException(
                sprintf(
                    'Template "%s" is outside the allowed views directory.',
                    $template,
                ),
            );
        }

        return $real;
    }
}
