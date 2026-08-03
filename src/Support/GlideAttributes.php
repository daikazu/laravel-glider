<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

/**
 * Extracts `glide-*` prefixed Blade component attributes into a plain
 * array of Glide manipulation params (e.g. `glide-w` => `w`).
 */
final class GlideAttributes
{
    /**
     * @return array<string, mixed>
     */
    public static function from(ComponentAttributeBag $bag): array
    {
        return collect($bag->whereStartsWith('glide-'))
            ->mapWithKeys(fn ($item, string $key): array => [Str::after($key, 'glide-') => $item])
            ->toArray();
    }
}
