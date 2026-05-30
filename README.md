# Elephenv

[![Latest Version](https://img.shields.io/packagist/v/eurym3d0n/elephenv.svg)](https://packagist.org/packages/eurym3d0n/elephenv)
[![PHP Version](https://img.shields.io/packagist/php-v/eurym3d0n/elephenv.svg)](https://packagist.org/packages/eurym3d0n/elephenv)
[![License](https://img.shields.io/packagist/l/eurym3d0n/elephenv.svg)](LICENSE)

> A modern, strictly-typed PHP 8.2+ environment loader with fluent validation,
> type inference, integrity checking, and structured error rendering.

---

## Table of Contents

- [Overview](#overview)
- [Why Elephenv](#why-elephenv)
- [Comparison Table](#comparison-table)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Global Helper Functions](#global-helper-functions)
- [`.env` File Syntax](#env-file-syntax)
- [Loading Sources](#loading-sources)
- [Type Casting](#type-casting)
- [Variable Interpolation](#variable-interpolation)
- [Array Notation](#array-notation)
- [Validation](#validation)
- [Fluent Value API](#fluent-value-api)
- [Integrity Checking](#integrity-checking)
- [Security Guards](#security-guards)
- [Error Rendering](#error-rendering)
- [Repository API](#repository-api)
- [Extending Elephenv](#extending-elephenv)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

Elephenv is a PHP library for loading, parsing, validating, and exposing
environment variables from `.env` files. It is built for **PHP 8.2+**, enforces
`declare(strict_types=1)` throughout, and is organized around explicit contracts
in `Elephenv\Contracts` so that every component can be replaced or extended
without touching the core.

Unlike most `.env` loaders, Elephenv treats environment configuration as a
first-class concern:

- Values are **automatically cast** to native PHP types (`bool`, `int`, `float`, `null`).
- Validation is **composable** and **collects all violations** before throwing.
- A **fluent `EnvValue` wrapper** exposes each value with chainable assertions
  and transformations.
- A **structured error renderer** produces readable output for both HTTP and CLI
  without additional configuration.

---

## Why Elephenv

Most PHP `.env` loaders solve a narrow problem: read a file and populate `$_ENV`.
Elephenv solves the broader problem of making environment configuration **safe,
correct, and expressive** at every stage of an application's lifecycle.

**Correctness by default.**
String values are automatically inferred and cast to their native PHP types
(`bool`, `int`, `float`, `null`) without opt-in. A variable whose raw value is
`"true"` is stored as `true`, not the string `"true"`. This eliminates an entire
class of bugs where application code must defensively re-parse strings from
`getenv()`.

**Fail loudly and completely.**
Validation collects every violation that occurs during a loading pass before
throwing. A single `ValidationException` describes every failing constraint,
giving you a complete picture instead of one-at-a-time failures to debug.

**Fluent, type-safe access.**
`Elephenv::value('KEY')` returns an `EnvValue` wrapper that chains validation,
transformation, and casting methods. No more `(int) env('PORT')` scattered across
your codebase.

**Security-aware.**
The loader enforces a configurable maximum file size and inspects POSIX
permissions on every `.env` file it reads. Files readable by group or world
trigger a warning by default and a hard exception in strict mode.

**Fully injectable.**
Every major component implements a contract defined in `Elephenv\Contracts`.
The runtime container (exposed via `Elephenv::swap()`) accepts custom
implementations for the loader, repository, caster, integrity checker, and error
renderer, making every part testable and replaceable in isolation.

**Structured error rendering.**
When an exception is raised during loading, Elephenv can render a styled HTML
page (HTTP) or a formatted CLI output without any additional setup. The
rendering pipeline is fully replaceable via `ErrorRendererInterface`.

---

## Comparison Table

The table below compares Elephenv against the three most widely used PHP
environment loaders as of 2026.

| Feature                               | **Elephenv** | vlucas/phpdotenv | symfony/dotenv | josegonzalez/dotenv |
|---------------------------------------|:------------:|:----------------:|:--------------:|:-------------------:|
| PHP version requirement               | 8.2+         | 7.4+             | 7.2+           | 5.4+                |
| Strict types throughout               | Yes          | No               | No             | No                  |
| Interface-driven contracts (full)     | Yes          | No               | No             | No                  |
| Swappable runtime services            | Yes          | No               | No             | No                  |
| Automatic type casting                | Yes (native) | No               | No             | No                  |
| Injectable caster (`CasterInterface`) | Yes          | No               | No             | No                  |
| Fluent value wrapper (`EnvValue`)     | Yes          | No               | No             | No                  |
| Composable validation (`RuleSet`)     | Yes          | Partial          | No             | No                  |
| All violations collected before throw | Yes          | No               | No             | No                  |
| Custom validation callback            | Yes          | No               | No             | No                  |
| PCRE pattern validation               | Yes          | No               | No             | No                  |
| `RuleSet` merge / composition         | Yes          | No               | No             | No                  |
| Array notation keys (`DB[host]`)      | Yes          | No               | No             | No                  |
| Injectable array flattener            | Yes          | No               | No             | No                  |
| Variable interpolation (`${VAR}`)     | Yes          | Yes              | Yes            | Partial             |
| Recursive multi-pass interpolation    | Yes          | No               | No             | No                  |
| Cycle detection in interpolation      | Yes          | No               | No             | No                  |
| Interpolation callback                | Yes          | No               | No             | No                  |
| Load from raw string                  | Yes          | No               | Yes            | No                  |
| Load multiple files                   | Yes          | Partial          | No             | No                  |
| Skip missing files (`loadIfExists`)   | Yes          | Yes              | Yes            | No                  |
| `set()` / `forget()` / `clear()`      | Yes          | No               | No             | No                  |
| Integrity checker (`.env.example`)    | Yes          | Yes              | No             | No                  |
| File size security guard              | Yes          | No               | No             | No                  |
| POSIX permission security guard       | Yes          | No               | No             | No                  |
| HTML error renderer                   | Yes          | No               | No             | No                  |
| CLI error renderer                    | Yes          | No               | No             | No                  |
| Replaceable error renderer            | Yes          | No               | No             | No                  |
| Singleton facade with `reset()`       | Yes          | No               | No             | No                  |
| Global `env()` helper                 | Yes          | No               | No             | No                  |

---

## Requirements

- PHP **8.2** or higher
- The `mbstring` extension (for multibyte-safe string handling)
- Composer

---

## Installation

```bash
composer require eurym3d0n/elephenv
```

---

## Quick Start

```php
use Elephenv\Elephenv;

// Load a .env file from disk.
Elephenv::load(__DIR__ . '/.env');

// Retrieve typed values.
$debug = Elephenv::get('APP_DEBUG');  // true (bool), not "true" (string)
$port  = Elephenv::get('DB_PORT');    // 5432 (int), not "5432" (string)
$name  = Elephenv::get('APP_NAME');   // "Acme" (string)
```

Using the `Env` facade alias:

```php
use Elephenv\Facade\Env;

Env::load(__DIR__ . '/.env');

$url = Env::get('DATABASE_URL', 'sqlite::memory:');
```

---

## Global Helper Functions

Elephenv automatically registers a set of global helper functions that expose
the most common facade operations without the `Elephenv::` prefix. They are
available immediately after installation with no additional configuration.

```bash
composer require eurym3d0n/elephenv
```

Each function is guarded by `function_exists()` to coexist safely with any
framework that already defines its own implementation. The first definition
loaded by the runtime wins.

### Available helpers

```php
// Typed variable retrieval — equivalent to Elephenv::get().
$port  = env('DB_PORT');              // 5432 (int)
$debug = env('APP_DEBUG');            // true (bool)
$name  = env('APP_NAME', 'default');  // with fallback

// Repository presence check — equivalent to Elephenv::has().
if (env_has('STRIPE_SECRET')) {
    // ...
}

// Fluent EnvValue wrapper — equivalent to Elephenv::value().
$dsn = env_value('DATABASE_URL')
    ->required()
    ->match('/^postgres:\/\//')
    ->toString();

// Runtime write — equivalent to Elephenv::set().
env_set('MAINTENANCE_MODE', true);

// Variable removal — equivalent to Elephenv::forget().
env_forget('MAINTENANCE_MODE');

// Full repository snapshot — equivalent to Elephenv::all().
$config = env_all();
```

### What is not exposed as a global helper

Bootstrap and infrastructure methods are intentionally excluded.
Keeping them on `Elephenv::` preserves their semantic weight at call
sites — seeing `Elephenv::checkIntegrity()` in a bootstrap file
immediately signals a critical startup operation in a way that a short
global alias would not.

Excluded methods include `load()`, `loadIfExists()`, `loadMany()`,
`loadString()`, `swap()`, `reset()`, `checkIntegrity()`,
`setErrorRenderer()`, and `setComplexExportMode()`.

---

## `.env` File Syntax

Elephenv supports a clean, standard `.env` file syntax.

### Basic assignment

```dotenv
APP_NAME=Acme
APP_ENV=production
```

### Quoted values

- Double-quoted values expand escape sequences (`\n`, `\t`, `\r`, `\\`, `\"`, `\$`).
- Single-quoted values are treated as literals — no escape processing.

```dotenv
GREETING="Hello\nWorld"   # Spans two lines in resolution
REGEX='^\d+$'             # Matches exactly the backslash and d
```

### Empty and null values

Variables defined without a value are resolved as `null`. Use the `empty` sentinel
for an explicit empty string.

```dotenv
EMPTY_VAR=            # Resolved as null
EXPLICIT_EMPTY=empty  # Resolved as '' (empty string)
```

### Comments

Comments can be placed on a line by themselves or inline after a value (if not
inside quotes).

```dotenv
# This is a full-line comment
APP_NAME=Acme       # This is an inline comment
APP_URL="https://example.com#anchor"  # The '#' character inside quotes is preserved
```

### Export prefix

The optional `export` keyword is silently stripped, allowing the same file to be
sourced by shell scripts:

```dotenv
export APP_ENV=production
```

---

## Loading Sources

### Single file

```php
Elephenv::load(__DIR__ . '/.env');
```

Throws `FileNotFoundException` when the file does not exist and
`SecurityException` when a security guard is violated.

### Single file, optional

```php
Elephenv::loadIfExists(__DIR__ . '/.env.local');
```

Returns an empty array silently when the file is absent.

### Multiple files

```php
Elephenv::loadMany([
    __DIR__ . '/.env',
    __DIR__ . '/.env.local',
]);
```

Files are merged in order. Later files override earlier ones for duplicate keys.
By default, missing paths are silently skipped. Pass `skipMissing: false` via
options to make every path required.

```php
Elephenv::loadMany(['.env', '.env.missing'], ['skipMissing' => false]);
```

### Raw string

```php
Elephenv::loadString("APP_NAME=Acme\nAPP_DEBUG=true");
```

Useful in test suites or when configuration is sourced from a remote store.

### Value callback

Transform every resolved value before it is stored in the repository:

```php
Elephenv::load('.env', [
    'valueCallback' => static fn(string $name, mixed $value): mixed => match ($name) {
        'APP_SECRET' => str_rot13($value),
        default      => $value,
    },
]);
```

---

## Type Casting

By default, every string value is inspected and cast to its native PHP type
before being stored. No configuration is required.

| Raw `.env` value          | PHP type | PHP value       |
|---------------------------|:--------:|:---------------:|
| `true`, `yes`, `on`, `1`  | `bool`   | `true`          |
| `false`, `no`, `off`, `0` | `bool`   | `false`         |
| `null`, `nil`, `none`     | `null`   | `null`          |
| `empty`                   | `string` | `''`            |
| `42`                      | `int`    | `42`            |
| `-7`                      | `int`    | `-7`            |
| `3.14`                    | `float`  | `3.14`          |
| `1.5e3`                   | `float`  | `1500.0`        |
| `"hello world"`           | `string` | `'hello world'` |

Casting can be disabled per load call:

```php
Elephenv::load('.env', ['cast' => false]);
```

> **Note**
> The string `"0"` is cast to `false` (bool), not `0` (int), because boolean
> detection takes precedence over integer detection. If you need the integer `0`,
> disable casting or cast the value explicitly using the fluent API:
> `Elephenv::value('MY_VAR')->toInt()`.

The casting strategy is injectable via `CasterInterface`. A custom caster can
be registered at bootstrap or swapped in for tests:

```php
use Elephenv\Contracts\CasterInterface;
use Elephenv\Enum\CastType;

final class StrictCaster implements CasterInterface
{
    public function detect(string $raw): CastType
    {
        return CastType::String; // Never infer — always keep raw strings.
    }

    public function cast(mixed $value): mixed
    {
        return $value; // No-op.
    }
}

Elephenv::swap(caster: new StrictCaster());
```

---

## Variable Interpolation

Placeholders in the form `${VAR}` or `$VAR` are resolved against previously
loaded variables. Resolution is **recursive**: if a resolved value itself
contains placeholders, they are expanded in subsequent passes. Circular
references (`A -> B -> A`) are detected and broken by returning an empty string.

```dotenv
SCHEME=postgres
DB_HOST=localhost
DB_PORT=5432
DB_DSN=${SCHEME}://${DB_HOST}:${DB_PORT}/mydb
```

```php
Elephenv::get('DB_DSN'); // "postgres://localhost:5432/mydb"
```

### Custom override map

Provide additional key-value pairs that take precedence over the repository
during placeholder resolution:

```php
Elephenv::load('.env', [
    'interpolate' => ['DB_HOST' => '10.0.0.1'],
]);
```

### Interpolation callback

Transform every resolved placeholder value before substitution:

```php
Elephenv::load('.env', [
    'interpolateCallback' => static fn(string $value): string => strtoupper($value),
]);
```

### Recursion depth

Interpolation is recursive up to **10 levels deep** by default. The depth limit
is configurable via the `Interpolator` constructor:

```php
use Elephenv\Parser\Interpolator;
use Elephenv\Loader\EnvLoader;

$interpolator = new Interpolator(maxDepth: 5);
$loader       = new EnvLoader($repository, interpolator: $interpolator);
```

### Cycle detection

Circular references are automatically detected and resolved to an empty string
to prevent infinite loops:

```dotenv
A=${B}
B=${A}
```

```php
Elephenv::get('A'); // '' — cycle broken
```

---

## Array Notation

Keys using bracket notation are automatically inflated into nested PHP arrays
after the loading pass. Flat bracket-notation keys are removed from the
repository after inflation so that only the nested form remains accessible.

```dotenv
DB[host]=localhost
DB[port]=5432
DB[name]=myapp
```

```php
$env = Elephenv::load('.env');

$env['DB']['host']; // "localhost"
$env['DB']['port']; // 5432

Elephenv::has('DB[host]'); // false — only the inflated key is kept
Elephenv::get('DB');       // ['host' => 'localhost', 'port' => 5432, 'name' => 'myapp']
```

The inflation strategy is injectable via `ArrayFlattenerInterface`:

```php
use Elephenv\Contracts\ArrayFlattenerInterface;

final class DotNotationFlattener implements ArrayFlattenerInterface
{
    public function inflate(array $entries): array
    {
        // Custom implementation supporting DOT.NOTATION keys.
    }
}

// Inject via EnvLoader directly.
$loader = new EnvLoader($repository, flattener: new DotNotationFlattener());
```

---

## Validation

Validation rules are composed using a fluent `RuleSet` builder and passed
as a map of variable name to `RuleSet` in the `rules` option. All violations
from every variable are collected before a `ValidationException` is thrown,
so a single loading call reports all problems at once.

### Built-in rules

```php
use Elephenv\Validation\RuleSet;

$rules = RuleSet::make()
    ->isRequired()                  // Value must not be null.
    ->notEmptyString()              // Value must be a non-empty string.
    ->match('/^https?:\/\//');      // Value must match a PCRE pattern.

Elephenv::load('.env', ['rules' => ['APP_URL' => $rules]]);
```

### Allowing empty strings

When `allowEmpty()` is present, `notEmptyString()` is silently skipped
regardless of its position in the chain:

```php
RuleSet::make()->isRequired()->allowEmpty()->notEmptyString();
// isRequired is enforced; notEmptyString is skipped.
```

### Custom callback rule

```php
RuleSet::make()->callback(function (string $name, mixed $value): bool|string {
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false
        ? true
        : sprintf('"%s" must be a valid email address.', $name);
});
```

### Custom rule class

Implement `ValidatorInterface` and register with `add()`:

```php
use Elephenv\Contracts\ValidatorInterface;
use Elephenv\Exception\ValidationException;

final class PortRangeRule implements ValidatorInterface
{
    public function validate(string $name, mixed $value): void
    {
        if (!is_int($value) || $value < 1 || $value > 65535) {
            throw new ValidationException([[
                'variable' => $name,
                'rule'     => 'port_range',
                'message'  => sprintf('"%s" must be an integer between 1 and 65535.', $name),
            ]]);
        }
    }
}

RuleSet::make()->isRequired()->notEmptyString()->add(new PortRangeRule());
```

### Composing rule sets

Base rule sets can be merged into more specific ones to avoid repetition:

```php
$base = RuleSet::make()->isRequired()->notEmptyString();

$urlRules  = RuleSet::make()->merge($base)->match('/^https?:\/\//');
$portRules = RuleSet::make()->merge($base)->add(new PortRangeRule());
```

### Handling violations

```php
use Elephenv\Exception\ValidationException;

try {
    Elephenv::load('.env', ['rules' => $rules]);
} catch (ValidationException $exception) {
    foreach ($exception->violations() as $violation) {
        echo $violation['variable'] . ': ' . $violation['message'] . PHP_EOL;
    }
}
```

---

## Fluent Value API

`Elephenv::value()` returns an `EnvValue` instance wrapping the resolved value.
Validation methods throw immediately on failure; transformation and casting
methods return `$this` for chaining.

```php
$dsn = Elephenv::value('DATABASE_URL')
    ->required()
    ->notEmptyString()
    ->match('/^postgres:\/\//')
    ->toString();
```

### Default value

```php
$level = Elephenv::value('LOG_LEVEL')
    ->defaults('info')
    ->toString();
```

### Transformation

```php
$tags = Elephenv::value('APP_TAGS')
    ->transform(static fn(string $v): array => explode(',', $v))
    ->toArray();
```

### Bulk assignment

```php
Elephenv::value('APP_URL')
    ->required()
    ->assignMany([
        'APP_HOST'   => parse_url(Elephenv::get('APP_URL'), PHP_URL_HOST),
        'APP_SCHEME' => parse_url(Elephenv::get('APP_URL'), PHP_URL_SCHEME),
    ]);
```

### Applying a full RuleSet

```php
$rules = RuleSet::make()->isRequired()->notEmptyString();

Elephenv::value('API_KEY')->applyRules($rules)->toString();
```

### Raw value access

```php
$raw = Elephenv::value('APP_DEBUG')->raw(); // Returns the value exactly as resolved.
```

### Type casting methods

```php
$value->toString();  // (string)
$value->toInt();     // (int)
$value->toFloat();   // (float)
$value->toBool();    // Uses FILTER_VALIDATE_BOOLEAN for accurate string coercion.
$value->toArray();   // Wraps scalar in array; returns array unchanged.
```

### Type inspection methods

```php
$value->isString();
$value->isBool();
$value->isInt();
$value->isFloat();
$value->isArray();
$value->isNull();
```

### Side-effect assignment and context

Variables written during a chain are tracked internally and accessible via
`context()` after the chain completes.

```php
$chain = Elephenv::value('APP_URL')
    ->required()
    ->assign('APP_HOST', parse_url(Elephenv::get('APP_URL'), PHP_URL_HOST));

$chain->context(); // ['APP_HOST' => 'example.com']
```

---

## Integrity Checking

The integrity checker compares the variable names declared in a `.env.example`
reference file against the active repository. It throws `IntegrityException`
when any required variable is absent, reporting all missing names at once.

```php
// In application bootstrap, after loading all .env files.
Elephenv::checkIntegrity(__DIR__ . '/.env.example');
```

The `.env.example` file follows the same format as a regular `.env` file.
Values are irrelevant; only the variable names are checked.

```dotenv
# .env.example
APP_NAME=
APP_ENV=
DATABASE_URL=
MAIL_HOST=
```

Listing required names without running a check:

```php
use Elephenv\Integrity\IntegrityChecker;

$checker = new IntegrityChecker();
$names   = $checker->listRequired(__DIR__ . '/.env.example');
// ['APP_NAME', 'APP_ENV', 'DATABASE_URL', 'MAIL_HOST']
```

A custom integrity checker can be injected at bootstrap:

```php
Elephenv::swap(integrityChecker: new MyIntegrityChecker());
```

---

## Security Guards

### File size

The loader rejects any file larger than **1 MiB (1 048 576 bytes)** by default.
This prevents loading unexpectedly large files that could exhaust memory.
The limit is configurable via the `EnvLoader` constructor:

```php
use Elephenv\Loader\EnvLoader;
use Elephenv\Repository\EnvironmentRepository;

$repository = new EnvironmentRepository();
$loader     = new EnvLoader($repository, maxBytes: 512 * 1024); // 512 KiB
```

### POSIX permissions

On non-Windows systems, the loader inspects file permissions before reading.
Files readable by the group or by others (world-readable) trigger a PHP warning
by default. When strict mode is enabled, the same condition throws a
`SecurityException` instead:

```php
Elephenv::load('.env', ['strictPermissions' => true]);
```

The guard is skipped entirely on Windows where POSIX permissions do not apply.

---

## Error Rendering

When an exception is raised inside `Elephenv::load()` and its variants,
Elephenv invokes the active error renderer before re-throwing the exception.
This allows a styled error page (HTTP) or formatted terminal output (CLI) to
appear without manual `try/catch` blocks in bootstrap code.

The default renderer is assembled lazily from `getcwd() . '/views'`. To use
a custom views directory or package version string:

```php
use Elephenv\Renderer\ErrorRendererFactory;

$renderer = ErrorRendererFactory::make(
    viewsPath: __DIR__ . '/resources/views',
    debug:     true,
    version:   '2.0.0',
);

Elephenv::setErrorRenderer($renderer);
```

To replace the renderer with a completely custom implementation:

```php
use Elephenv\Contracts\ErrorRendererInterface;
use Elephenv\Exception\ElephenvException;
use Throwable;

final class JsonErrorRenderer implements ErrorRendererInterface
{
    public function render(Throwable $exception): never
    {
        $statusCode = $exception instanceof ElephenvException
            ? $exception->statusCode()
            : 500;

        $context = $exception instanceof ElephenvException
            ? $exception->context()
            : [];

        header('Content-Type: application/json', true, $statusCode);
        echo json_encode([
            'error'   => $exception->getMessage(),
            'context' => $context,
        ]);
        exit(1);
    }
}

Elephenv::setErrorRenderer(new JsonErrorRenderer());
```

---

## Repository API

The `EnvironmentRepository` propagates all writes to `$_ENV` and `$_SERVER`.
Resolution falls back through `$_ENV`, `$_SERVER`, and `getenv()` for variables
set outside of Elephenv.

Accessing the repository directly:

```php
$repository = Elephenv::repository();

$repository->set('APP_NAME', 'Acme');
$repository->get('APP_NAME');   // 'Acme'
$repository->has('APP_NAME');   // true
$repository->forget('APP_NAME');
$repository->all();             // snapshot of all in-memory variables
$repository->clear();           // removes all variables from memory and superglobals
```

Writing and removing variables via the facade shortcuts:

```php
Elephenv::set('FEATURE_FLAG', true);
Elephenv::forget('FEATURE_FLAG');
Elephenv::clear();
```

Seeding the repository with initial values at construction:

```php
use Elephenv\Repository\EnvironmentRepository;

$repository = new EnvironmentRepository([
    'APP_ENV'   => 'testing',
    'APP_DEBUG' => true,
]);
```

---

## Extending Elephenv

Every major component is backed by a contract in the `Elephenv\Contracts`
namespace. Custom implementations can be injected at the singleton level via
`Elephenv::swap()`.

| Contract                    | Default Implementation  | Purpose                                      |
|-----------------------------|-------------------------|----------------------------------------------|
| `LoaderInterface`           | `EnvLoader`             | Parses `.env` sources and populates the repo |
| `RepositoryInterface`       | `EnvironmentRepository` | Stores and resolves environment variables    |
| `CasterInterface`           | `Inferrer`              | Detects and casts raw string values          |
| `ArrayFlattenerInterface`   | `ArrayFlattener`        | Inflates bracket-notation keys into arrays   |
| `IntegrityCheckerInterface` | `IntegrityChecker`      | Compares repo against `.env.example`         |
| `ParserInterface`           | `LineParser`            | Tokenises individual `.env` lines            |
| `InterpolatorInterface`     | `Interpolator`          | Resolves `${VAR}` placeholders recursively   |
| `ValidatorInterface`        | Rule classes            | Validates a single constraint                |
| `ErrorRendererInterface`    | `ErrorRenderer`         | Renders exceptions and terminates execution  |

Swapping multiple services at once:

```php
Elephenv::swap(
    repository:       new RedisEnvironmentRepository($redis),
    integrityChecker: new StrictIntegrityChecker(),
    caster:           new StrictCaster(),
);
```

---

## Testing

Elephenv is designed to be fully testable. The `reset()` method clears the
singleton and the error renderer between test cases:

```php
protected function setUp(): void
{
    Elephenv::reset();
}
```

Load configuration from a raw string to avoid reliance on files on disk:

```php
Elephenv::loadString("APP_ENV=testing\nDB_PORT=5432");
```

Inject an isolated repository pre-seeded with test values:

```php
use Elephenv\Repository\EnvironmentRepository;

$repository = new EnvironmentRepository(['DB_HOST' => 'localhost']);
Elephenv::swap(repository: $repository);
```

Disable process termination in the error renderer by swapping in a custom
renderer that throws exceptions instead of calling `exit()`:

```php
use Elephenv\Contracts\ErrorRendererInterface;
use Throwable;

final class TestErrorRenderer implements ErrorRendererInterface
{
    public function render(Throwable $exception): never
    {
        throw $exception;
    }
}

Elephenv::setErrorRenderer(new TestErrorRenderer());
```

Write and remove variables at runtime for per-test overrides:

```php
Elephenv::set('FEATURE_FLAG', true);
// ... test code ...
Elephenv::forget('FEATURE_FLAG');
```

---

## Contributing

Contributions are welcome. Please open an issue to discuss your proposal before
submitting a pull request. All code must pass **PHPStan at maximum level** and
follow the project coding standard (run `composer cs`).

---

## License

Elephenv is open-source software released under the **MIT License**.
See the `LICENSE` file for the full terms.
