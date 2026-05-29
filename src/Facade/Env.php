<?php
declare(strict_types=1);

namespace Elephenv\Facade;

use Elephenv\Elephenv;
use Elephenv\Value\EnvValue;

/**
 * Static facade providing convenient access to the Elephenv singleton.
 *
 * This class delegates every call to the underlying Elephenv singleton,
 * making it suitable as a drop-in import alias in application code that
 * prefers a named facade over the main Elephenv class directly.
 *
 * @see \Elephenv\Elephenv
 */
final class Env
{
    /**
     * Load and parse a .env file at the given path.
     *
     * @param string $path Absolute or relative path to the environment file.
     * @param array<string, mixed> $options Loader options.
     * @return array<string, mixed> Parsed environment values.
     */
    public static function load(string $path, array $options = []): array
    {
        return Elephenv::load($path, $options);
    }

    /**
     * Load and parse a .env file only when it exists.
     *
     * @param string $path Absolute or relative path to the environment file.
     * @param array<string, mixed> $options Loader options.
     * @return array<string, mixed> Parsed environment values, or an empty array when absent.
     */
    public static function loadIfExists(string $path, array $options = []): array
    {
        return Elephenv::loadIfExists($path, $options);
    }

    /**
     * Load and merge multiple .env files in declaration order.
     *
     * @param array<int, string> $paths Ordered list of environment file paths.
     * @param array<string, mixed> $options Loader options.
     * @return array<string, mixed> Merged environment values from all files.
     */
    public static function loadMany(array $paths, array $options = []): array
    {
        return Elephenv::loadMany($paths, $options);
    }

    /**
     * Parse environment definitions directly from a raw string.
     *
     * @param string $content Raw .env-formatted content.
     * @param array<string, mixed> $options Loader options.
     * @return array<string, mixed> Parsed environment values.
     */
    public static function loadString(string $content, array $options = []): array
    {
        return Elephenv::loadString($content, $options);
    }

    /**
     * Retrieve a variable value from the repository, with automatic type casting.
     *
     * @param string $name The environment variable name.
     * @param mixed $default The default value returned when the variable is absent.
     * @return mixed The resolved and cast value, or $default.
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return Elephenv::get($name, $default);
    }

    /**
     * Return a fluent EnvValue wrapper around the resolved variable value.
     *
     * @param string $name The environment variable name.
     * @param mixed $default The default value used when the variable is absent.
     * @return \Elephenv\Value\EnvValue A fluent wrapper supporting chained validation and casting.
     */
    public static function value(string $name, mixed $default = null): EnvValue
    {
        return Elephenv::value($name, $default);
    }

    /**
     * Determine whether a variable is present in the repository.
     *
     * @param string $name The environment variable name.
     * @return bool True when the variable can be resolved.
     */
    public static function has(string $name): bool
    {
        return Elephenv::has($name);
    }

    /**
     * Return a snapshot of all variables stored in the active repository.
     *
     * @return array<string, mixed> All tracked environment variables.
     */
    public static function all(): array
    {
        return Elephenv::all();
    }

    /**
     * Write a variable directly to the active repository.
     *
     * @param string $name The variable name.
     * @param mixed $value The value to store.
     */
    public static function set(string $name, mixed $value): void
    {
        Elephenv::set($name, $value);
    }

    /**
     * Remove a variable from the repository and all propagation targets.
     *
     * @param string $name The variable name to remove.
     */
    public static function forget(string $name): void
    {
        Elephenv::forget($name);
    }

    /**
     * Remove all variables managed by the active repository instance.
     */
    public static function clear(): void
    {
        Elephenv::clear();
    }
}
