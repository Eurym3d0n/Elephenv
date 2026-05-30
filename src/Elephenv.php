<?php

declare(strict_types=1);

namespace Elephenv;

use Elephenv\Contracts\CasterInterface;
use Elephenv\Contracts\ErrorRendererInterface;
use Elephenv\Contracts\IntegrityCheckerInterface;
use Elephenv\Contracts\LoaderInterface;
use Elephenv\Contracts\RepositoryInterface;
use Elephenv\Integrity\IntegrityChecker;
use Elephenv\Loader\EnvLoader;
use Elephenv\Renderer\ErrorRendererFactory;
use Elephenv\Repository\EnvironmentRepository;
use Elephenv\Support\Inferrer;
use Elephenv\Value\EnvValue;
use Throwable;

/**
 * Main Elephenv facade.
 *
 * Provides a static high-level API for environment loading, repository access,
 * typed value retrieval, integrity checking, and error rendering. Internally
 * backed by a singleton runtime container that holds all service dependencies.
 *
 * Services can be replaced at runtime via swap() to support custom
 * implementations and isolated test environments.
 *
 * Complex values (e.g. arrays) are always stored in the internal repository
 * layer. Their export to the process environment via putenv() is controlled
 * by the configured complex export mode:
 *  - "skip" : complex values are not exported to putenv();
 *  - "json" : complex values are JSON-encoded before export.
 *
 * The export mode can be changed at runtime using setComplexExportMode() or
 * enableComplexJsonExport(). Changing the mode replaces the internal repository
 * and loader while preserving the current repository contents.
 */
final class Elephenv
{
    /**
     * Singleton runtime container.
     */
    private static ?self $instance = null;

    /**
     * Global error renderer, lazily initialised on first use.
     */
    private static ?ErrorRendererInterface $errorRenderer = null;

    /**
     * Create the facade instance.
     *
     * @param LoaderInterface $loader The loader used for all load* operations.
     * @param RepositoryInterface $repository The active variable store.
     * @param IntegrityCheckerInterface $integrityChecker Checker used by checkIntegrity().
     * @param CasterInterface $caster Caster applied to OS environment variable values in get().
     */
    private function __construct(
        private readonly LoaderInterface $loader,
        private readonly RepositoryInterface $repository,
        private readonly IntegrityCheckerInterface $integrityChecker,
        private readonly CasterInterface $caster,
    ) {
    }

    /**
     * Return the singleton instance, creating it with default services on first call.
     *
     * The default repository uses "skip" as the complex export mode, meaning
     * arrays and objects are never exported to the process environment via
     * putenv().
     */
    public static function instance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $repository = new EnvironmentRepository(
            seed: [],
            complexExportMode: 'skip',
        );

        self::$instance = new self(
            new EnvLoader($repository),
            $repository,
            new IntegrityChecker(),
            new Inferrer(),
        );

        return self::$instance;
    }

    /**
     * Replace one or more internal runtime services.
     *
     * Any parameter left null retains the value from the current instance.
     * Useful for injecting test doubles or custom implementations without
     * rebuilding the entire container.
     *
     * @param \Elephenv\Contracts\LoaderInterface|null $loader
     * @param \Elephenv\Contracts\RepositoryInterface|null $repository
     * @param \Elephenv\Contracts\IntegrityCheckerInterface|null $integrityChecker
     * @param \Elephenv\Contracts\CasterInterface|null $caster
     */
    public static function swap(
        ?LoaderInterface $loader = null,
        ?RepositoryInterface $repository = null,
        ?IntegrityCheckerInterface $integrityChecker = null,
        ?CasterInterface $caster = null,
    ): void {
        $current = self::instance();
        $repository = $repository ?? $current->repository;

        self::$instance = new self(
            $loader ?? new EnvLoader($repository),
            $repository,
            $integrityChecker ?? $current->integrityChecker,
            $caster ?? $current->caster,
        );
    }

    /**
     * Reset the singleton state.
     *
     * Clears both the runtime container and the error renderer. Intended for
     * test teardown to prevent state leaking between test cases.
     */
    public static function reset(): void
    {
        self::$instance = null;
        self::$errorRenderer = null;
    }

    /**
     * Register a custom global error renderer.
     *
     * @param \Elephenv\Contracts\ErrorRendererInterface $renderer
     */
    public static function setErrorRenderer(ErrorRendererInterface $renderer): void
    {
        self::$errorRenderer = $renderer;
    }

    /**
     * Return the active repository instance.
     *
     * @return \Elephenv\Contracts\RepositoryInterface
     */
    public static function repository(): RepositoryInterface
    {
        return self::instance()->repository;
    }

    /**
     * Configure how complex values (arrays, objects) are exported to the
     * process environment.
     *
     * Supported modes:
     *  - "skip" : complex values are stored internally and mirrored to
     *            $_ENV / $_SERVER, but are not exported to putenv();
     *  - "json" : complex values are JSON-encoded before being exported
     *            to putenv(), allowing getenv() to retrieve their JSON
     *            string representation.
     *
     * Changing the mode replaces the internal repository and loader while
     * preserving all currently stored variables.
     *
     * @param string $mode The complex export mode ("skip" or "json").
     * @throws \InvalidArgumentException When an unsupported mode is provided.
     */
    public static function setComplexExportMode(string $mode): void
    {
        if (!in_array($mode, ['skip', 'json'], true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid complex export mode "%s". Expected "skip" or "json".',
                $mode,
            ));
        }

        $current = self::instance();

        $repository = new EnvironmentRepository(
            seed: $current->repository->all(),
            complexExportMode: $mode,
        );

        self::swap(
            repository: $repository,
            loader: new EnvLoader($repository),
        );
    }

    /**
     * Enable or disable JSON export for complex values.
     *
     * This is a convenience wrapper around setComplexExportMode():
     *  - enableComplexJsonExport(true)  -> mode "json";
     *  - enableComplexJsonExport(false) -> mode "skip".
     *
     * @param bool $enabled True to enable JSON export for arrays and objects;
     *                      false to skip export.
     */
    public static function enableComplexJsonExport(bool $enabled = true): void
    {
        self::setComplexExportMode($enabled ? 'json' : 'skip');
    }

    /**
     * Load and parse a .env file.
     *
     * @param string $path Absolute or relative path to the environment file.
     * @param array<string, mixed> $options Loader options.
     * @return array<string, mixed>
     */
    public static function load(string $path, array $options = []): array
    {
        return self::execute(
            static fn(): array => self::instance()->loader->load($path, $options),
        );
    }

    /**
     * Load and parse a .env file only when it exists.
     *
     * @param string $path Absolute or relative path to the environment file.
     * @param array<string, mixed> $options Loader options.
     * @return array<string, mixed>
     */
    public static function loadIfExists(string $path, array $options = []): array
    {
        return self::execute(
            static fn(): array => self::instance()->loader->loadIfExists($path, $options),
        );
    }

    /**
     * Load and merge multiple .env files in declaration order.
     *
     * @param array<int, string> $paths Ordered list of environment file paths.
     * @param array<string, mixed> $options Loader options.
     * @return array<string, mixed>
     */
    public static function loadMany(array $paths, array $options = []): array
    {
        return self::execute(
            static fn(): array => self::instance()->loader->loadMany($paths, $options),
        );
    }

    /**
     * Parse environment definitions directly from a raw string.
     *
     * @param string $content Raw .env-formatted content.
     * @param array<string, mixed> $options Loader options.
     * @return array<string, mixed>
     */
    public static function loadString(string $content, array $options = []): array
    {
        return self::execute(
            static fn(): array => self::instance()->loader->loadString($content, $options),
        );
    }

    /**
     * Retrieve a variable from the repository with automatic type casting.
     *
     * Resolution order:
     *  1. Internal repository store.
     *  2. OS environment via getenv() when the repository returns the default.
     *
     * String values from either source are cast to their native PHP type by
     * the injected CasterInterface before being returned.
     *
     * @param string $name The variable name.
     * @param mixed $default The value returned when the variable is absent.
     * @return mixed The resolved and cast value, or $default.
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        $instance = self::instance();
        $value = $instance->repository->get($name, $default);

        return is_string($value)
            ? $instance->caster->cast($value)
            : $value;
    }

    /**
     * Return a fluent EnvValue wrapper around the resolved variable value.
     *
     * @param string $name The variable name.
     * @param mixed $default The default value used when the variable is absent.
     * @return EnvValue
     */
    public static function value(string $name, mixed $default = null): EnvValue
    {
        return new EnvValue(
            self::get($name, $default),
            $name,
            self::instance()->repository,
        );
    }

    /**
     * Determine whether a variable is present in the repository.
     *
     * @param string $name The variable name.
     *
     * @return bool
     */
    public static function has(string $name): bool
    {
        return self::instance()->repository->has($name);
    }

    /**
     * Write a variable directly to the active repository.
     *
     * @param string $name The variable name.
     * @param mixed $value The value to store.
     */
    public static function set(string $name, mixed $value): void
    {
        self::instance()->repository->set($name, $value);
    }

    /**
     * Remove a variable from the repository and all propagation targets.
     *
     * @param string $name The variable name to remove.
     */
    public static function forget(string $name): void
    {
        self::instance()->repository->forget($name);
    }

    /**
     * Remove all variables managed by the active repository instance.
     */
    public static function clear(): void
    {
        self::instance()->repository->clear();
    }

    /**
     * Return a snapshot of all variables stored in the active repository.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::instance()->repository->all();
    }

    /**
     * Validate repository integrity against a reference example file.
     *
     * @param string $examplePath Path to the .env.example reference file.
     * @throws \Elephenv\Exception\FileNotFoundException When the example file does not exist.
     * @throws \Elephenv\Exception\IntegrityException When required variables are absent.
     */
    public static function checkIntegrity(string $examplePath): void
    {
        self::execute(static function () use ($examplePath): void {
            $instance = self::instance();
            $instance->integrityChecker->check($instance->repository, $examplePath);
        });
    }

    /**
     * Determine whether the given value is a string.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isString(mixed $value): bool
    {
        return Inferrer::isString($value);
    }

    /**
     * Determine whether the given value is a boolean.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isBool(mixed $value): bool
    {
        return Inferrer::isBool($value);
    }

    /**
     * Determine whether the given value is an array.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isArray(mixed $value): bool
    {
        return Inferrer::isArray($value);
    }

    /**
     * Determine whether the given value is an integer.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isInt(mixed $value): bool
    {
        return Inferrer::isInt($value);
    }

    /**
     * Determine whether the given value is a float.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isFloat(mixed $value): bool
    {
        return Inferrer::isFloat($value);
    }

    /**
     * Determine whether the given value is null.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isNull(mixed $value): bool
    {
        return Inferrer::isNull($value);
    }

    /**
     * Determine whether the given value is numeric.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isNumeric(mixed $value): bool
    {
        return Inferrer::isNumeric($value);
    }

    /**
     * Lazily initialise the default error renderer.
     *
     * @return \Elephenv\Contracts\ErrorRendererInterface
     */
    private static function getErrorRenderer(): ErrorRendererInterface
    {
        if (self::$errorRenderer !== null) {
            return self::$errorRenderer;
        }

        // $projecViews = getcwd() . DIRECTORY_SEPARATOR . 'views/errors';
        $packageViews = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views';

        self::$errorRenderer = ErrorRendererFactory::make(
            viewsPath: $packageViews,
            version: '1.0.3',
        );

        return self::$errorRenderer;
    }

    /**
     * Execute an operation with centralised exception rendering.
     *
     * Invokes the error renderer before re-throwing so that a human-readable
     * error is produced even when the caller does not catch the exception.
     *
     * The throw statement after render() is technically unreachable because
     * the ErrorRendererInterface contract returns never. It is kept as a
     * safety net in case a custom renderer does not terminate execution.
     *
     * @param callable $callback The operation to protect.
     * @return mixed The return value of $callback.
     */
    private static function execute(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $throwable) {
            self::getErrorRenderer()->render($throwable);

            throw $throwable;
        }
    }
}
