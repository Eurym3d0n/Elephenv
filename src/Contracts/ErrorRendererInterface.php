<?php
declare(strict_types=1);

namespace Elephenv\Contracts;

use Throwable;

/**
 * Defines the contract for terminal exception renderers.
 *
 * Implementations are responsible for rendering a human-readable
 * representation of a Throwable and terminating the current execution
 * flow afterward.
 *
 * Renderers must never allow execution to continue normally.
 */
interface ErrorRendererInterface
{
    /**
     * Render the given throwable and terminate execution.
     *
     * This method must never return normally.
     *
     * @param \Throwable $exception The throwable to render.
     * @return never
     */
    public function render(Throwable $exception): never;
}
