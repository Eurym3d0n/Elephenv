<?php
declare(strict_types=1);

namespace Elephenv\Contracts;

/**
 * Defines the contract for variable interpolation within environment values.
 *
 * Implementations resolve placeholder tokens such as ${VAR} or $VAR found
 * inside string values, substituting them with values drawn from the
 * repository, a custom map, or a user-supplied callback.
 */
interface InterpolatorInterface
{
    /**
     * Resolve all variable placeholders found within the given value.
     *
     * Non-string values are returned unchanged. Placeholders are resolved
     * in the following priority order:
     *  1. The custom key-value map provided via $custom.
     *  2. The environment repository.
     *  3. The optional post-resolution callback.
     *
     * @param mixed $value The raw value potentially containing placeholders.
     * @param \Elephenv\Contracts\RepositoryInterface $repository The repository used for variable lookup.
     * @param array<string, mixed> $custom Additional key-value pairs that take precedence over the repository.
     * @param callable|null $callback An optional callback invoked on each resolved value before substitution.
     * @return mixed The interpolated value, or the original value when interpolation is not applicable.
     */
    public function interpolate(
        mixed $value,
        RepositoryInterface $repository,
        array $custom = [],
        ?callable $callback = null,
    ): mixed;
}
