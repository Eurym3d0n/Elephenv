<?php
declare(strict_types=1);

namespace Elephenv\Renderer;

use Elephenv\View\ViewRenderer;
use Throwable;

/**
 * Renders an ElephenvException as a styled HTML page via the view layer.
 *
 * Sends a 500 Content-Type header when headers have not yet been emitted,
 * delegates view-model construction to ErrorViewModelFactory, and outputs
 * the rendered page by passing the model to ViewRenderer.
 *
 * This class is responsible for output only. Termination is handled by
 * the orchestrating ErrorRenderer.
 */
final readonly class HttpErrorRenderer
{
    /**
     * @param \Elephenv\View\ViewRenderer $views The view renderer used to produce the HTML output.
     * @param \Elephenv\Renderer\ErrorViewModelFactory $factory The factory that builds the view-model for each exception.
     */
    public function __construct(
        private ViewRenderer $views,
        private ErrorViewModelFactory $factory,
    ) {
    }

    /**
     * Render the exception as an HTML page and send it to the output buffer.
     *
     * @param \Elephenv\Exception\ElephenvException $exception The exception to render.
     */
    public function render(Throwable $exception): void
    {
        $model = $this->factory->make($exception);

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8', true, $model['statusCode']);
        }

        echo $this->views->render($model['template'], $model);
    }
}
