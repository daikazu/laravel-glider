<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

/**
 * Parses a `focal-point` attribute value into a CSS `object-position` /
 * `background-position` value.
 *
 * Accepts:
 * - Named positions: center, top, bottom, left, right, top-left, top-right,
 *   bottom-left, bottom-right.
 * - "x,y" percentage coordinates (0-100 each), e.g. "30,70".
 *
 * Returns null for anything it cannot confidently parse.
 */
final class FocalPoint
{
    private const NAMED_POSITIONS = [
        'center'       => '50% 50%',
        'top'          => '50% 0%',
        'bottom'       => '50% 100%',
        'left'         => '0% 50%',
        'right'        => '100% 50%',
        'top-left'     => '0% 0%',
        'top-right'    => '100% 0%',
        'bottom-left'  => '0% 100%',
        'bottom-right' => '100% 100%',
    ];

    public static function parse(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || $value === '0') {
            return null;
        }

        $value = strtolower(trim($value));

        if (isset(self::NAMED_POSITIONS[$value])) {
            return self::NAMED_POSITIONS[$value];
        }

        if (str_contains($value, ',')) {
            $parts = array_map('trim', explode(',', $value));

            if (count($parts) === 2) {
                $x = (int) $parts[0];
                $y = (int) $parts[1];

                if ($x >= 0 && $x <= 100 && $y >= 0 && $y <= 100) {
                    return "{$x}% {$y}%";
                }
            }
        }

        return null;
    }
}
