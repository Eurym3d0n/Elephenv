<?php
declare(strict_types=1);

namespace Elephenv\Loader;

use Elephenv\Contracts\ArrayFlattenerInterface;
use Elephenv\Contracts\CasterInterface;
use Elephenv\Contracts\LoaderInterface;
use Elephenv\Contracts\RepositoryInterface;
use Elephenv\Exception\FileNotFoundException;
use Elephenv\Exception\ParseException;
use Elephenv\Exception\SecurityException;
use Elephenv\Exception\ValidationException;
use Elephenv\Parser\Interpolator;
use Elephenv\Parser\LineParser;
use Elephenv\Parser\ValueParser;
use Elephenv\Support\ArrayFlattener;
use Elephenv\Support\Inferrer;
use Elephenv\Validation\RuleSet;

/**
 * Reads, parses, and populates environment variables from .env sources.
 *
 * Responsibilities include reading files from disk (with size and permission
 * guards), delegating line-by-line parsing, performing value interpolation
 * and type casting, executing validation rules, and inflating array-notation
 * keys into nested PHP arrays.
 *
 * Supported options (passed via the $options array):
 *   - cast (bool, default true)
 *      Automatically cast string values to their native PHP type.
 *   - interpolate (array<string, mixed>, default [])
 *      Custom key-value pairs that take precedence over the repository
 *      when resolving ${VAR} placeholders.
 *   - interpolateCallback (callable|null, default null)
 *      A callback invoked on each interpolated placeholder value before
 *      it is substituted into the parent string.
 *   - valueCallback (callable|null, default null)
 *      A callback invoked on the fully resolved value of every variable
 *      before it is stored in the repository.
 *   - rules (array<string, RuleSet>, default [])
 *      A map of variable name to RuleSet, applied after each variable is
 *      resolved. Violations are collected and thrown together at the end
 *      of the loading pass.
 *   - skipMissing (bool, default true)
 *      When loading multiple files, silently skip paths that do not exist.
 *      Applies only to loadMany().
 *   - strictPermissions (bool, default false)
 *      Promote insecure file permission warnings to a SecurityException.
 *      Applies only to load() and loadIfExists().
 */
final class EnvLoader implements LoaderInterface
{
    /**
     * Default maximum file size accepted by the loader (1 MiB).
     */
    private const int DEFAULT_MAX_BYTES = 1_048_576;

    /**
     * @param \Elephenv\Repository\EnvironmentRepository $repository The repository populated during loading.
     * @param \Elephenv\Parser\LineParser $lineParser Parser for individual .env lines.
     * @param \Elephenv\Parser\ValueParser $valueParser Parser that strips quotes and processes escape sequences.
     * @param \Elephenv\Parser\Interpolator $interpolator Resolver for ${VAR} placeholder tokens.
     * @param int $maxBytes Maximum file size in bytes accepted before throwing SecurityException.
     */
    public function __construct(
        private readonly RepositoryInterface $repository,
        private readonly LineParser $lineParser = new LineParser(),
        private readonly ValueParser $valueParser = new ValueParser(),
        private readonly Interpolator $interpolator = new Interpolator(),
        private readonly ArrayFlattenerInterface $flattener = new ArrayFlattener(),
        private readonly CasterInterface $caster = new Inferrer(),
        private readonly int $maxBytes = self::DEFAULT_MAX_BYTES,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function load(string $path, array $options = []): array
    {
        if (!file_exists($path)) {
            throw new FileNotFoundException($path);
        }

        $this->guardSize($path);
        $this->guardPermissions($path, (bool) ($options['strictPermissions'] ?? false));

        return $this->processContent((string) file_get_contents($path), $options);
    }

    /**
     * @inheritdoc
     */
    public function loadIfExists(string $path, array $options = []): array
    {
        return is_file($path) ? $this->load($path, $options) : [];
    }

    /**
     * @inheritdoc
     */
    public function loadMany(array $paths, array $options = []): array
    {
        $merged = [];

        foreach ($paths as $path) {
            $path = (string) $path;

            if (!is_file($path)) {
                if ($options['skipMissing'] ?? true) {
                    continue;
                }

                throw new FileNotFoundException($path);
            }

            $merged = array_replace($merged, $this->load($path, $options));
        }

        return $merged;
    }

    /**
     * @inheritdoc
     */
    public function loadString(string $content, array $options = []): array
    {
        return $this->processContent($content, $options);
    }

    /**
     * @inheritdoc
     */
    public function repository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Parse raw .env content and populate the repository.
     *
     * Iterates over each line, delegates parsing and interpolation,
     * applies optional callbacks and validation rules, and finally inflates
     * array-notation keys into nested structures.
     *
     * @param string $content Raw .env-formatted string.
     * @param array<string, mixed> $options Loader options as described in the class docblock.
     * @return array<string, mixed> Inflated environment values after all processing steps.
     * @throws \Elephenv\Exception\ValidationException When at least one validation rule violation is collected.
     */
    private function processContent(string $content, array $options): array
    {
        $flat = [];
        $violations = [];

        foreach ($this->lines($content) as $lineIndex => $line) {
            try {
                $parsed = $this->lineParser->parse($line);
            } catch (ParseException $exception) {
                throw new ParseException(
                    $exception->getMessage(),
                    $line,
                    $lineIndex + 1,
                );
            }

            if ($parsed === null) {
                continue;
            }

            [$name, $value] = $this->resolveEntry($parsed, $options);

            $flat[$name] = $value;

            // Write immediately so that subsequent lines can resolve this variable
            // during interpolation. The bracket-notation key will be replaced by
            // its inflated counterpart in inflateAndStore().
            $this->repository->set($name, $value);

            $this->validateEntry($name, $value, $options, $violations);
        }

        if ($violations !== []) {
            throw new ValidationException($violations);
        }

        return $this->inflateAndStore($flat);
    }

    /**
     * Split raw .env content into individual lines.
     *
     * Uses a universal newline pattern so that files with CR+LF, LF, or CR
     * line endings are handled uniformly across platforms.
     *
     * @param string $content Raw .env-formatted string.
     * @return array<int, string> Ordered array of raw lines.
     */
    private function lines(string $content): array
    {
        return preg_split('/\R/', $content) ?: [];
    }

    /**
     * Resolve and transform a parsed entry.
     *
     * @param array{name:string, value:string} $parsed
     * @param array<string, mixed> $options
     * @return array{0:string, 1:mixed}
     */
    private function resolveEntry(array $parsed, array $options): array
    {
        $name = $parsed['name'];

        $value = $this->valueParser->parse($parsed['value']);

        $value = $this->interpolator->interpolate(
            $value,
            $this->repository,
            (array) ($options['interpolate'] ?? []),
            is_callable($options['interpolateCallback'] ?? null)
                ? $options['interpolateCallback']
                : null,
        );

        if (($options['cast'] ?? true) && is_string($value)) {
            $value = $this->caster->cast($value);
        }

        if (is_callable($options['valueCallback'] ?? null)) {
            $value = $options['valueCallback']($name, $value);
        }

        return [$name, $value];
    }

    /**
     * @param array<string, mixed> $options
     * @param array<int, mixed> $violations
     */
    private function validateEntry(
        string $name,
        mixed $value,
        array $options,
        array &$violations,
    ): void {
        $ruleSets = (array) ($options['rules'] ?? []);

        if (
            !isset($ruleSets[$name]) ||
            !$ruleSets[$name] instanceof RuleSet
        ) {
            return;
        }

        try {
            $ruleSets[$name]->run($name, $value);
        } catch (ValidationException $exception) {
            array_push($violations, ...$exception->violations());
        }
    }

    /**
     * Inflate array-notation keys and write the final values to the repository.
     *
     * Bracket-notation keys written during the parsing pass (e.g. DB[host]) are
     * removed from the repository before the inflated keys (e.g. DB as array)
     * are written, preventing both forms from coexisting simultaneously.
     *
     * @param array<string, mixed> $flat
     * @return array<string, mixed>
     */
    private function inflateAndStore(array $flat): array
    {
        $inflated = $this->flattener->inflate($flat);

        foreach (array_keys($flat) as $name) {
            if (str_contains((string) $name, '[')) {
                $this->repository->forget($name);
            }
        }

        foreach ($inflated as $name => $value) {
            $this->repository->set($name, $value);
        }

        return $inflated;
    }

    /**
     * Reject files that exceed the configured maximum size.
     *
     * @param string $path Path to the file being loaded.
     * @throws \Elephenv\Exception\SecurityException When the file size exceeds $maxBytes.
     */
    private function guardSize(string $path): void
    {
        $size = filesize($path);

        if ($size !== false && $size > $this->maxBytes) {
            throw new SecurityException(
                sprintf(
                    'File "%s" exceeds the maximum allowed size of %d bytes (actual size: %d bytes).',
                    $path,
                    $this->maxBytes,
                    $size,
                ),
                [
                    'path' => $path,
                    'size' => $size,
                    'maxBytes' => $this->maxBytes,
                ],
            );
        }
    }

    /**
     * Warn or throw when a .env file is readable by the group or by others.
     *
     * On Windows, POSIX permissions do not exist and this guard is skipped.
     *
     * @param string $path Path to the file being loaded.
     * @param bool $strict When true, insecure permissions throw a SecurityException instead of triggering a warning.
     * @throws \Elephenv\Exception\SecurityException When $strict is true and the file has insecure permissions.
     */
    private function guardPermissions(string $path, bool $strict): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $perms = fileperms($path);

        if ($perms === false) {
            return;
        }

        $isGroupReadable = ($perms & 0o040) !== 0;
        $isWorldReadable = ($perms & 0o004) !== 0;

        if (!$isGroupReadable && !$isWorldReadable) {
            return;
        }

        $octal = decoct($perms & 0o777);
        $message = sprintf(
            'File "%s" has insecure permissions (0%s) - it should not be group- or world-readable.',
            $path,
            $octal,
        );

        if ($strict) {
            throw new SecurityException($message, [
                'path' => $path,
                'permissions' => '0' . $octal,
            ]);
        }

        trigger_error($message, E_USER_WARNING);
    }
}
