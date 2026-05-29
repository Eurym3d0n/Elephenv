<?php
declare(strict_types=1);

namespace Elephenv\Contracts;

/**
 * Defines the contract for environment file loaders.
 *
 * Implementations parse one or more .env sources and populate the
 * associated repository with the resulting key-value pairs.
 */
interface LoaderInterface
{
    /**
     * Load and parse a .env file at the given path.
     *
     * @param string $path Absolute or relative path to the environment file.
     * @param array<string, mixed> $options Loader options (cast, interpolate, rules, …).
     * @return array<string, mixed> Parsed and optionally cast environment values.
     * @throws \Elephenv\Exception\FileNotFoundException When the file does not exist.
     * @throws \Elephenv\Exception\SecurityException When the file fails a security guard.
     * @throws \Elephenv\Exception\ValidationException When one or more validation rules fail.
     */
    public function load(string $path, array $options = []): array;

    /**
     * Load and parse a .env file only when it exists at the given path.
     *
     * Returns an empty array silently when the file is absent.
     *
     * @param string $path Absolute or relative path to the environment file.
     * @param array<string, mixed> $options Loader options.
     * @return array<string, mixed> Parsed environment values, or an empty array.
     */
    public function loadIfExists(string $path, array $options = []): array;

    /**
     * Load and merge multiple .env files in declaration order.
     *
     * Later files take precedence over earlier ones for duplicate keys.
     *
     * @param array<int, string> $paths Ordered list of environment file paths.
     * @param array<string, mixed> $options Loader options (skipMissing defaults to true).
     * @return array<string, mixed> Merged environment values from all files.
     */
    public function loadMany(array $paths, array $options = []): array;

    /**
     * Parse environment definitions directly from a raw string.
     *
     * @param string $content Raw .env-formatted content.
     * @param array<string, mixed> $options Loader options.
     * @return array<string, mixed> Parsed environment values.
     */
    public function loadString(string $content, array $options = []): array;

    /**
     * Return the repository used by this loader instance.
     *
     * @return \Elephenv\Contracts\RepositoryInterface The underlying environment repository.
     */
    public function repository(): RepositoryInterface;
}
