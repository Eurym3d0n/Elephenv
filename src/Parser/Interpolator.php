<?php
declare(strict_types=1);

namespace Elephenv\Parser;

use Elephenv\Contracts\InterpolatorInterface;
use Elephenv\Contracts\RepositoryInterface;

/**
 * Resolves ${VAR} and $VAR placeholder tokens within environment string values.
 *
 * Placeholders are matched against a custom key-value map first, then against
 * the active repository. An optional callback may post-process each resolved
 * value before substitution. Non-string and empty values are returned as-is.
 */
final readonly class Interpolator implements InterpolatorInterface
{
    /**
     * Regex pattern that matches both ${VAR_NAME} and $VAR_NAME placeholder forms.
     */
    private const string PLACEHOLDER_PATTERN =
        '/\$\{([A-Za-z_][A-Za-z0-9_]*)\}|\$([A-Za-z_][A-Za-z0-9_]*)/';

    /**
     * Default maximum resolution depth before recursion is halted.
     */
    private const int DEFAULT_MAX_DEPTH = 10;

    /**
     * @param int $maxDepth Maximum number of recursive resolution passes before halting.
     *                      Prevents infinite loops caused by circular placeholder references.
     */
    public function __construct(
        private int $maxDepth = self::DEFAULT_MAX_DEPTH,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function interpolate(
        mixed $value,
        RepositoryInterface $repository,
        array $custom = [],
        ?callable $callback = null,
    ): mixed {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        // Normalise custom keys to lowercase for case-insensitive lookup.
        $normalized = [];

        foreach ($custom as $key => $customValue) {
            $normalized[strtolower((string) $key)] = (string) ($customValue ?? '');
        }

        return $this->resolveRecursive(
            $value,
            $repository,
            $normalized,
            $callback,
            [],
            $this->maxDepth,
        );
    }

    /**
     * Recursively resolve all placeholders within a string value.
     *
     * Each placeholder is resolved from the custom map or the repository. If the
     * resolved string itself contains placeholders, this method is called again
     * with the current variable name added to $visited to detect and break cycles.
     *
     * @param string $value The string currently being resolved.
     * @param RepositoryInterface $repository The repository for variable lookup.
     * @param array<string, string> $normalized Custom variables keyed by lowercase name.
     * @param callable|null $callback Optional post-resolution callback.
     * @param array<int, string> $visited Variable names already on the resolution stack.
     * @param int $depth Remaining recursion budget; resolution halts when this reaches zero.
     * @return string The fully resolved string.
     */
    private function resolveRecursive(
        string $value,
        RepositoryInterface $repository,
        array $normalized,
        ?callable $callback,
        array $visited,
        int $depth,
    ): string {
        if ($depth === 0) {
            return $value;
        }

        return preg_replace_callback(
            self::PLACEHOLDER_PATTERN,
            function (array $matches) use (
                $repository,
                $normalized,
                $callback,
                $visited,
                $depth,
            ): string {
                // Group 1 captures ${VAR}, group 2 captures $VAR.
                $name = $matches[1] !== '' ? $matches[1] : $matches[2];

                // Cycle detected: this name is already being resolved upstream.
                if (in_array($name, $visited, true)) {
                    return '';
                }

                $resolved = $normalized[strtolower($name)]
                    ?? (string) ($repository->get($name) ?? '');

                // Recurse only when the resolved value may contain further placeholders.
                if (str_contains($resolved, '$')) {
                    $resolved = $this->resolveRecursive(
                        $resolved,
                        $repository,
                        $normalized,
                        $callback,
                        [...$visited, $name],
                        $depth - 1,
                    );
                }

                if ($callback !== null) {
                    $resolved = (string) ($callback($resolved) ?? $resolved);
                }

                return $resolved;
            },
            $value,
        ) ?? $value;
    }
}
