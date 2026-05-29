<?php
declare(strict_types=1);

namespace Elephenv\Support;

use Stringable;

/**
 * Wraps htmlspecialchars() with a configurable, reusable instance.
 *
 * Designed to be injected into view renderers so that the encoding flags
 * and character set are declared once at the composition root rather than
 * repeated at every call site.
 */
final readonly class HtmlEscaper
{
    /**
     * @param string $encoding The character encoding forwarded to htmlspecialchars().
     * @param int $flags Bitmask of ENT_* flags forwarded to htmlspecialchars().
     * @param bool $doubleEncode When false, existing HTML entities are not double-encoded.
     */
    public function __construct(
        private string $encoding = 'UTF-8',
        private int $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
        private bool $doubleEncode = false,
    ) {
    }

    /**
     * Escape a scalar or stringable value for safe output inside an HTML document.
     *
     * @param bool|float|int|null|string|Stringable $value The value to escape.
     * @return string The HTML-safe string representation.
     */
    public function escape(bool|float|int|null|string|Stringable $value): string
    {
        return htmlspecialchars(
            (string) $value,
            $this->flags,
            $this->encoding,
            $this->doubleEncode,
        );
    }
}
