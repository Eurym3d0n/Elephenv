<?php
declare(strict_types=1);

namespace Elephenv\Contracts;

/**
 * Defines the contract for inflating flat array-notation key-value pairs into
 * nested PHP arrays.
 *
 * Implementations receive a flat map whose keys may use bracket notation
 * (e.g. DB[host], MATRIX[0][1]) and return a nested array reflecting the
 * declared structure. Plain keys without brackets are passed through unchanged.
 */
interface ArrayFlattenerInterface
{
    /**
     * Inflate a flat key-value map into a nested array structure.
     *
     * @param array<string, mixed> $entries The flat key-value pairs to process.
     * @return array<string, mixed> The inflated, potentially nested array.
     */
    public function inflate(array $entries): array;
}
