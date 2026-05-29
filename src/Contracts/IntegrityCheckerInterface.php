<?php
declare(strict_types=1);

namespace Elephenv\Contracts;

/**
 * Defines the contract for environment integrity checkers.
 *
 * Implementations compare variable names declared in a reference example file
 * against the active repository and report any that are absent.
 */
interface IntegrityCheckerInterface
{
    /**
     * Assert that every variable declared in the example file exists in the repository.
     *
     * @param \Elephenv\Contracts\RepositoryInterface $repository The active environment repository.
     * @param string $examplePath Path to the .env.example reference file.
     * @throws \Elephenv\Exception\FileNotFoundException When the example file does not exist.
     * @throws \Elephenv\Exception\IntegrityException When one or more required variables are absent.
     */
    public function check(RepositoryInterface $repository, string $examplePath): void;

    /**
     * Return the list of variable names declared in the given example file.
     *
     * Returns an empty array silently when the file does not exist.
     *
     * @param string $examplePath Path to the .env.example reference file.
     * @return array<int, string> Unique variable names found in the file.
     */
    public function listRequired(string $examplePath): array;
}
