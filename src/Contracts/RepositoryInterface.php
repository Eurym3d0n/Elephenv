<?php
declare(strict_types=1);

namespace Elephenv\Contracts;

use InvalidArgumentException;

/**
 * Defines a mutable environment variable repository.
 *
 * Implementations maintain an internal key-value store and optionally
 * synchronize values with the active PHP runtime environment.
 *
 * Resolution order SHOULD follow this priority chain:
 *  1. Internal repository storage.
 *  2. The global $_ENV array.
 *  3. The global $_SERVER array.
 *  4. The operating system environment via getenv().
 *
 * Implementations MAY propagate mutations to:
 *  - $_ENV;
 *  - $_SERVER;
 *  - putenv();
 *
 * depending on runtime capabilities and configuration.
 *
 * Implementations MAY also support placeholder interpolation for string values:
 *  - $VAR
 *  - ${VAR}
 *  - FOO[BAR]  (resolved as FOO_BAR)
 *
 * Because process environment variables are string-based, non-scalar values
 * cannot be exported natively via putenv(). Implementations MAY therefore
 * adopt one of the following strategies:
 *  - skip export for complex values;
 *  - serialize complex values before export, for example as JSON.
 */
interface RepositoryInterface
{
    /**
     * Store or overwrite an environment variable.
     *
     * Implementations SHOULD synchronize the value across all configured
     * runtime propagation targets. If interpolation is supported, string
     * values MAY be resolved before propagation.
     *
     * @param string $name The environment variable name.
     * @param mixed $value The value to store.
     * @throws \InvalidArgumentException When the variable name or value is invalid.
     */
    public function set(string $name, mixed $value): void;

    /**
     * Retrieve an environment variable.
     *
     * Implementations SHOULD resolve values using the configured lookup
     * priority chain.
     *
     * @param string $name The variable name.
     * @param mixed $default The fallback value returned when the variable
     *                      cannot be resolved.
     *
     * @return mixed The resolved value or the provided default.
     */
    public function get(string $name, mixed $default = null): mixed;

    /**
     * Determine whether a variable exists.
     *
     * This method MUST distinguish between a missing variable and a variable
     * explicitly set to null.
     *
     * @param string $name The variable name.
     * @return bool True when the variable exists in any resolution tier.
     */
    public function has(string $name): bool;

    /**
     * Remove a variable from the repository and all synchronized runtime
     * environments.
     *
     * @param string $name The variable name.
     */
    public function forget(string $name): void;

    /**
     * Remove all variables managed by this repository instance.
     *
     * Implementations SHOULD clear synchronized runtime targets only for
     * variables owned by the repository itself.
     */
    public function clear(): void;

    /**
     * Return all variables stored in the internal repository layer.
     *
     * This method DOES NOT include values resolved dynamically from external
     * runtime sources.
     *
     * @return array<string, mixed> A snapshot of the internal repository state.
     */
    public function all(): array;

    /**
     * Return the configured export strategy for complex values.
     *
     * Implementations MAY support multiple strategies, for example:
     *  - "skip" : do not export arrays/objects to putenv();
     *  - "json" : JSON-encode arrays/objects before export.
     *
     * @return string The export mode identifier.
     */
    public function complexExportMode(): string;
}
