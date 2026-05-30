<?php
declare(strict_types=1);

namespace Elephenv;

use Elephenv\Elephenv;
use Elephenv\Value\EnvValue;

if (!function_exists('env')) {
    /**
     * Retrieve an environment variable value.
     *
     * This helper resolves the value using the following precedence order:
     * 1. Elephenv internal repository
     * 2. $_ENV superglobal
     * 3. $_SERVER superglobal
     * 4. Operating system environment variables via getenv()
     * 5. The provided default value (if none of the above contain the key)
     *
     * The returned value may be automatically cast depending on its content
     * (e.g., "true", "false", "null", numeric strings, etc.).
     *
     * @param string $name The name of the environment variable to retrieve.
     * @param mixed $default Optional fallback value returned if the variable is not found.
     * @return mixed The resolved and optionally cast value, or the default if unresolved.
     */
    function env(string $name, mixed $default = null): mixed
    {
        return Elephenv::get($name, $default);
    }
}

if (!function_exists('env_has')) {
    /**
     * Determine whether an environment variable exists.
     *
     * This method checks for existence using the same resolution order:
     * 1. Elephenv internal repository
     * 2. $_ENV superglobal
     * 3. $_SERVER superglobal
     * 4. Operating system environment variables via getenv()
     *
     * Note that this method strictly checks for presence, not value validity.
     *
     * @param string $name The name of the environment variable.
     * @return bool True if the variable exists in any source, false otherwise.
     */
    function env_has(string $name): bool
    {
        return Elephenv::has($name);
    }
}

if (!function_exists('env_value')) {
    /**
     * Retrieve an environment variable as an EnvValue object.
     *
     * This provides a fluent, object-oriented interface for working with
     * environment variables, allowing advanced transformations, casting,
     * and chaining operations.
     *
     * Resolution order:
     * 1. Elephenv internal repository
     * 2. $_ENV superglobal
     * 3. $_SERVER superglobal
     * 4. Operating system environment variables via getenv()
     * 5. The provided default value
     *
     * @param string $name The name of the environment variable.
     * @param mixed $default Optional fallback value if the variable is not found.
     * @return \Elephenv\Value\EnvValue An EnvValue wrapper instance.
     */
    function env_value(string $name, mixed $default = null): EnvValue
    {
        return Elephenv::value($name, $default);
    }
}

if (!function_exists('env_set')) {
    /**
     * Set or override an environment variable in the Elephenv internal repository.
     *
     * This does not modify system-level environment variables, but instead stores
     * the value within Elephenv's internal container, which has the highest priority
     * during resolution.
     *
     * @param string $name The name of the environment variable.
     * @param mixed $value The value to assign.
     * @return void
     */
    function env_set(string $name, mixed $value): void
    {
        Elephenv::set($name, $value);
    }
}

if (!function_exists('env_forget')) {
    /**
     * Remove an environment variable from the Elephenv internal repository.
     *
     * This only affects values previously set via Elephenv::set() or env_set().
     * It does not remove values from $_ENV, $_SERVER, or system-level environment variables.
     *
     * @param string $name The name of the environment variable to remove.
     * @return void
     */
    function env_forget(string $name): void
    {
        Elephenv::forget($name);
    }
}

if (!function_exists('env_all')) {
    /**
     * Retrieve all environment variables from the Elephenv internal repository.
     *
     * This method returns only the variables explicitly stored within Elephenv,
     * not those from $_ENV, $_SERVER, or the system environment.
     *
     * @return array<string, mixed> An associative array of all stored environment variables.
     */
    function env_all(): array
    {
        return Elephenv::all();
    }
}
