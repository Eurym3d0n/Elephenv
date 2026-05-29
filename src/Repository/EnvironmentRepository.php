<?php
declare(strict_types=1);

namespace Elephenv\Repository;

use Elephenv\Contracts\RepositoryInterface;
use InvalidArgumentException;
use JsonException;

/**
 * Default mutable environment repository implementation.
 *
 * This repository maintains an internal in-memory storage layer while
 * synchronizing variables with the PHP runtime environment. It supports
 * optional variable interpolation in string values and optional JSON
 * encoding for complex values when exporting to the process environment.
 *
 * Synchronized targets:
 *  - the $_ENV superglobal;
 *  - the $_SERVER superglobal;
 *  - the process environment via putenv().
 *
 * Resolution order:
 *  1. Internal repository storage.
 *  2. $_ENV.
 *  3. $_SERVER.
 *  4. getenv().
 *
 * Supported interpolation syntax:
 *  - $VAR
 *  - ${VAR}
 *  - FOO[BAR]  (resolved as FOO_BAR)
 *
 * Complex values such as arrays cannot be stored natively in the process
 * environment because environment variables are string-based. This repository
 * can optionally JSON-encode complex values before sending them to putenv().
 * When this option is disabled, complex values are still stored internally
 * and mirrored to $_ENV / $_SERVER, but they are not exported to the process
 * environment.
 */
final class EnvironmentRepository implements RepositoryInterface
{
    /**
     * Internal repository storage.
     *
     * Only variables explicitly managed by this repository instance are stored
     * here. Values are preserved in their original PHP form.
     *
     * @var array<string, mixed>
     */
    private array $items = [];

    /**
     * Export strategy for complex values when propagating to putenv().
     *
     * Supported values:
     *  - "skip" : do not export arrays/objects to putenv();
     *  - "json" : JSON-encode arrays/objects before export.
     */
    private string $complexExportMode;

    /**
     * Create a new repository instance.
     *
     * Seeded values are automatically propagated to all configured runtime
     * synchronization targets.
     *
     * @param array<string, mixed> $seed Initial repository values.
     * @param string $complexExportMode Export strategy for complex values
     *                                  ("skip" or "json").
     * @throws \InvalidArgumentException When an unsupported export mode is provided.
     */
    public function __construct(
        array $seed = [],
        string $complexExportMode = 'skip'
    ) {
        if (!in_array($complexExportMode, ['skip', 'json'], true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid complex export mode "%s". Expected "skip" or "json".',
                $complexExportMode,
            ));
        }

        $this->complexExportMode = $complexExportMode;

        foreach ($seed as $name => $value) {
            $this->set((string) $name, $value);
        }
    }

    /**
     * Set an environment variable value.
     *
     * The original value is stored internally. If the value is a string,
     * interpolation is resolved before propagation to runtime targets.
     *
     * @param string $name The environment variable name.
     * @param mixed $value The value to store.
     */
    public function set(string $name, mixed $value): void
    {
        $this->items[$name] = $value;

        $resolvedValue = $this->resolveValue($value);

        $this->propagate($name, $resolvedValue);
    }

    /**
     * Get an environment variable value.
     *
     * Lookup order:
     *  1. Internal repository storage.
     *  2. $_ENV.
     *  3. $_SERVER.
     *  4. getenv().
     *
     * @param string $name The environment variable name.
     * @param mixed $default Default value returned when the variable does not exist.
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, $this->items)) {
            return $this->items[$name];
        }

        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        }

        if (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        }

        $value = getenv($name);

        return $value !== false ? $value : $default;
    }

    /**
     * Determine whether a variable exists in any configured source.
     *
     * @param string $name The environment variable name.
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->items)
            || array_key_exists($name, $_ENV)
            || array_key_exists($name, $_SERVER)
            || getenv($name) !== false;
    }

    /**
     * Remove a variable from internal storage and all synchronized runtime targets.
     *
     * @param string $name The environment variable name.
     */
    public function forget(string $name): void
    {
        unset($this->items[$name]);

        $this->remove($name);
    }

    /**
     * Remove all variables managed by this repository instance.
     *
     * Only variables explicitly stored in the internal repository are cleared.
     * Existing external environment values not tracked in $items are left untouched.
     */
    public function clear(): void
    {
        foreach (array_keys($this->items) as $name) {
            $this->remove($name);
        }

        $this->items = [];
    }

    /**
     * Return all variables explicitly stored by this repository instance.
     *
     * Values are returned in their original PHP form.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Return the configured export strategy for complex values.
     *
     * @return string Export mode ("skip" or "json").
     */
    public function complexExportMode(): string
    {
        return $this->complexExportMode;
    }

    /**
     * Resolve interpolation placeholders inside a string value.
     *
     * Supported placeholders:
     *  - $VAR
     *  - ${VAR}
     *  - FOO[BAR] (resolved as FOO_BAR)
     *
     * Non-string values are returned unchanged. Missing references are
     * preserved as-is to avoid silently replacing unresolved placeholders
     * with empty strings.
     *
     * @param mixed $value The value to resolve.
     * @return mixed
     */
    private function resolveValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $value = preg_replace_callback(
            '/\$\{([A-Za-z_][A-Za-z0-9_]*)\}/',
            function (array $matches): string {
                return $this->resolveReference($matches[1], $matches[0]);
            },
            $value,
        );

        $value = preg_replace_callback(
            '/\$([A-Za-z_][A-Za-z0-9_]*)/',
            function (array $matches): string {
                return $this->resolveReference($matches[1], $matches[0]);
            },
            $value,
        );

        $value = preg_replace_callback(
            '/([A-Za-z_][A-Za-z0-9_]*)\[([A-Za-z_][A-Za-z0-9_]*)\]/',
            function (array $matches): string {
                return $this->resolveReference(
                    $matches[1] . '_' . $matches[2],
                    $matches[0],
                );
            },
            $value,
        );

        return $value;
    }

    /**
     * Resolve a single placeholder reference.
     *
     * If the referenced variable cannot be resolved, the original placeholder
     * is returned unchanged so the caller can still detect unresolved
     * placeholders.
     *
     * @param string $name The normalized variable name to resolve.
     * @param string $originalPlaceholder The original placeholder text.
     * @return string
     */
    private function resolveReference(string $name, string $originalPlaceholder): string
    {
        $value = $this->get($name);

        if ($value === null) {
            return $originalPlaceholder;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value) || is_object($value)) {
            $encoded = json_encode(
                $value,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            return $encoded !== false ? $encoded : $originalPlaceholder;
        }

        return $originalPlaceholder;
    }

    /**
     * Synchronize a variable across all runtime targets.
     *
     * Propagation rules:
     *  - $_ENV accepts the original resolved PHP value;
     *  - $_SERVER accepts the original resolved PHP value;
     *  - putenv() only accepts strings, therefore:
     *      - scalar values are string-cast and exported;
     *      - null removes the variable from the process environment;
     *      - complex values are either JSON-encoded or skipped,
     *        depending on configuration.
     *
     * @param string $name The environment variable name.
     * @param mixed $value The resolved value to propagate.
     * @throws \InvalidArgumentException When JSON encoding is enabled but fails.
     */
    private function propagate(string $name, mixed $value): void
    {
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;

        if ($value === null) {
            putenv($name);

            return;
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            putenv(sprintf('%s=%s', $name, (string) $value));

            return;
        }

        if ($this->complexExportMode === 'skip') {
            return;
        }

        if ($this->complexExportMode === 'json') {
            try {
                $encoded = json_encode(
                    $value,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                );

                putenv(sprintf('%s=%s', $name, $encoded));

                return;
            } catch (JsonException $exception) {
                throw new InvalidArgumentException(sprintf(
                    'Environment variable "%s" could not be JSON-encoded for process export: %s',
                    $name,
                    $exception->getMessage(),
                ), 0, $exception);
            }
        }
    }

    /**
     * Remove a variable from all runtime synchronization targets.
     *
     * @param string $name The environment variable name.
     */
    private function remove(string $name): void
    {
        unset($_ENV[$name], $_SERVER[$name]);

        putenv($name);
    }
}
