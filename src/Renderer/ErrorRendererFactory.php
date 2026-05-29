<?php

declare(strict_types=1);

namespace Elephenv\Renderer;

use Elephenv\Exception\FileNotFoundException;
use Elephenv\Exception\IntegrityException;
use Elephenv\Exception\ParseException;
use Elephenv\Exception\SecurityException;
use Elephenv\Exception\ValidationException;
use Elephenv\View\ViewRenderer;

/**
 * Assembles the fully configured ErrorRenderer from its constituent parts.
 *
 * Wires together the ExceptionViewRegistry (exception-to-template mapping),
 * ViewRenderer (HTML template engine), ErrorViewModelFactory (view-model
 * builder), HttpErrorRenderer, and CliErrorRenderer into a ready-to-use
 * ErrorRenderer instance. Callers provide only the views directory path
 * and optional configuration flags.
 */
final class ErrorRendererFactory
{
    /**
     * Build and return a fully wired ErrorRenderer instance.
     *
     * @param string $viewsPath Absolute path to the directory containing error templates.
     * @param bool $debug Enable debug output (stack traces, extended context).
     * @param string $version The Elephenv version string shown in rendered error pages.
     * @return \Elephenv\Renderer\ErrorRenderer The configured renderer, ready for use.
     */
    public static function make(
        string $viewsPath,
        bool $debug = false,
        string $version = '1.0.0',
    ): ErrorRenderer {
        $registry = (new ExceptionViewRegistry())
            ->register(
                FileNotFoundException::class,
                'errors/file_not_found.php',
            )
            ->register(
                ValidationException::class,
                'errors/validation.php',
            )
            ->register(
                ParseException::class,
                'errors/parse.php',
            )
            ->register(
                SecurityException::class,
                'errors/security.php',
            )
            ->register(
                IntegrityException::class,
                'errors/integrity.php',
            );

        $factory = new ErrorViewModelFactory(
            registry: $registry,
            debug: $debug,
            version: $version,
        );

        return new ErrorRenderer(
            http: new HttpErrorRenderer(
                views: new ViewRenderer($viewsPath),
                factory: $factory,
            ),
            cli: new CliErrorRenderer(),
        );
    }
}
