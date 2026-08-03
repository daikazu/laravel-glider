<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

/**
 * Sanitizes untrusted strings before they are interpolated into
 * inline CSS generated for the background components, to prevent
 * CSS/HTML injection (XSS) via user-controlled values.
 */
final class CssSanitizer
{
    /**
     * Escape quotes and backslashes that could break out of a CSS
     * `url('...')` context.
     */
    public static function url(string $url): string
    {
        return addcslashes($url, "'\\");
    }

    /**
     * Allow only safe CSS characters: alphanumeric, spaces, hyphens,
     * underscores, percentages, dots, commas, and parentheses.
     */
    public static function value(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9\s\-_%.,()]/i', '', $value) ?? '';
    }
}
