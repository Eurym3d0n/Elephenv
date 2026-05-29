<?php
declare(strict_types=1);

namespace Elephenv\Integrity;

use Elephenv\Contracts\IntegrityCheckerInterface;
use Elephenv\Contracts\RepositoryInterface;
use Elephenv\Exception\FileNotFoundException;
use Elephenv\Exception\IntegrityException;
use Elephenv\Parser\LineParser;

/**
 * Verifies that all variables declared in a reference example file
 * are present in the active environment repository.
 *
 * The typical workflow is to commit a .env.example file to version control
 * listing every required variable name (with or without values) and then
 * call check() at application bootstrap to detect missing configuration
 * before the application attempts to use it.
 *
 * Array-notation keys (e.g. DB[host]) are resolved against the inflated
 * nested structure stored in the repository rather than against the raw
 * bracket-notation key, which is removed during the inflation pass.
 */
final class IntegrityChecker implements IntegrityCheckerInterface
{
    /**
     * @inheritdoc
     */
    public function check(RepositoryInterface $repository, string $examplePath): void
    {
        if (!is_file($examplePath)) {
            throw new FileNotFoundException($examplePath);
        }

        $missing = array_values(array_filter(
            $this->extractNames($examplePath),
            fn(string $name): bool => !$this->isPresent($repository, $name),
        ));

        if ($missing !== []) {
            throw new IntegrityException($missing, $examplePath);
        }
    }

    /**
     * @inheritdoc
     */
    public function listRequired(string $examplePath): array
    {
        return is_file($examplePath) ? $this->extractNames($examplePath) : [];
    }

    /**
     * Parse a .env file and return every unique variable name it declares.
     *
     * @param string $path Path to the .env or .env.example file.
     * @return array<int, string> Unique variable names in declaration order.
     */
    public function extractNames(string $path): array
    {
        $parser  = new LineParser();
        $content = file_get_contents($path);

        if ($content === false) {
            return [];
        }

        $lines = preg_split('/\R/', $content) ?: [];
        $names = [];

        foreach ($lines as $line) {
            $parsed = $parser->parse($line);

            if ($parsed !== null) {
                $names[] = $parsed['name'];
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Determine whether the given variable name is resolvable in the repository.
     *
     * Plain keys are checked directly via has(). Bracket-notation keys
     * (e.g. DB[host], MATRIX[0][1]) are resolved by traversing the inflated
     * nested array returned by the repository, since the loader removes flat
     * bracket-notation keys after inflation and stores only the nested form.
     *
     * @param \Elephenv\Contracts\RepositoryInterface $repository The active repository.
     * @param string $name The raw variable name, with or without bracket notation.
     * @return bool True when the key (or its nested equivalent) is present.
     */
    private function isPresent(RepositoryInterface $repository, string $name): bool
    {
        if (!str_contains($name, '[')) {
            return $repository->has($name);
        }

        // Parse the bracket-notation key into an ordered list of path segments.
        preg_match_all('/([^\[]+)|\[([^\]]+)\]/', $name, $matches);

        $segments = array_map(
            static fn(string $part, string $bracketed): string => $part !== '' ? $part : $bracketed,
            $matches[1],
            $matches[2],
        );

        $topLevel = array_shift($segments);

        if (!$repository->has($topLevel)) {
            return false;
        }

        // Traverse the nested array structure one segment at a time.
        $current = $repository->get($topLevel);

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }

            $current = $current[$segment];
        }

        return true;
    }
}
