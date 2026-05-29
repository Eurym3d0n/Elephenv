<?php
declare(strict_types=1);

namespace Elephenv\Support;

use Elephenv\Contracts\ArrayFlattenerInterface;

/**
 * Converts a flat array of .env-style array-notation keys into a nested PHP array.
 *
 * Keys using bracket notation such as DB[host], DB[port], or MATRIX[0][1]
 * are exploded into their corresponding nested array structure. Plain keys
 * without brackets are passed through unchanged.
 *
 * Example:
 *   Input:  ['DB[host]' => 'localhost', 'DB[port]' => 5432, 'APP_NAME' => 'Acme']
 *   Output: ['DB' => ['host' => 'localhost', 'port' => 5432], 'APP_NAME' => 'Acme']
 */
final class ArrayFlattener implements ArrayFlattenerInterface
{
    /**
     * @inheritdoc
     */
    public function inflate(array $entries): array
    {
        $result = [];

        foreach ($entries as $key => $value) {
            $key = (string) $key;

            if (!str_contains($key, '[')) {
                $result[$key] = $value;
                continue;
            }

            preg_match_all('/([^\[]+)|\[([^\]]*)\]/', $key, $matches);

            $segments = array_map(
                static fn(string $part, string $bracketed): string => $part !== '' ? $part : $bracketed,
                $matches[1],
                $matches[2],
            );

            $this->setNested($result, $segments, $value);
        }

        return $result;
    }

    /**
     * Recursively assign a value into a nested array using a segment path.
     *
     * @param array<string, mixed> $target The array being built, passed by reference.
     * @param array<int, string> $segments Remaining path segments to traverse.
     * @param mixed $value The value to assign at the final segment.
     */
    private function setNested(array &$target, array $segments, mixed $value): void
    {
        $key = array_shift($segments);

        if ($segments === []) {
            $target[$key] = $value;
            return;
        }

        if (!isset($target[$key]) || !is_array($target[$key])) {
            $target[$key] = [];
        }

        $this->setNested($target[$key], $segments, $value);
    }
}
