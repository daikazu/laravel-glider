<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Expands a responsive-background configuration (a named preset, custom
 * breakpoints, or the library defaults) into a sorted collection of
 * `{name, min_width, params}` items.
 *
 * URL generation is intentionally left to the caller: this class only
 * resolves *which* breakpoints and Glide params apply, so it can be
 * reused anywhere breakpoint expansion is needed without forcing image
 * URLs to be built.
 */
final class BackgroundBreakpoints
{
    private const array BREAKPOINT_WIDTHS = [
        'xs'  => 0,
        'sm'  => 576,
        'md'  => 768,
        'lg'  => 992,
        'xl'  => 1200,
        '2xl' => 1400,
    ];

    private const array DEFAULT_BREAKPOINTS = [
        'xs' => ['w' => 480],
        'sm' => ['w' => 768],
        'md' => ['w' => 1024],
        'lg' => ['w' => 1280],
        'xl' => ['w' => 1920],
    ];

    /**
     * @param  array<string|int, mixed>|null  $breakpoints
     * @param  array<string, mixed>  $glideAttributes
     */
    public function expand(?string $preset, ?array $breakpoints, array $glideAttributes): Collection
    {
        if (! in_array($preset, [null, '', '0'], true)) {
            return $this->fromPreset($preset, $glideAttributes);
        }

        if ($breakpoints !== null && $breakpoints !== []) {
            return $this->build($breakpoints, $glideAttributes);
        }

        return $this->build(self::DEFAULT_BREAKPOINTS, $glideAttributes);
    }

    /**
     * @param  array<string, mixed>  $glideAttributes
     */
    private function fromPreset(string $preset, array $glideAttributes): Collection
    {
        $presets = config('glider.background_presets', []);

        if (! isset($presets[$preset])) {
            throw new InvalidArgumentException("Background preset '{$preset}' not found in config");
        }

        $config = $presets[$preset];

        return $this->build($config['breakpoints'] ?? $config, $glideAttributes);
    }

    /**
     * @param  array<string|int, mixed>  $breakpoints
     * @param  array<string, mixed>  $glideAttributes
     */
    private function build(array $breakpoints, array $glideAttributes): Collection
    {
        if ($breakpoints === []) {
            throw new InvalidArgumentException('Breakpoints array cannot be empty');
        }

        $collection = collect();

        foreach ($breakpoints as $key => $params) {
            if (! is_array($params)) {
                throw new InvalidArgumentException("Breakpoint '{$key}' must have an array of parameters");
            }

            $collection->push([
                'name'      => $key,
                'min_width' => $this->minWidth($key),
                'params'    => array_merge($glideAttributes, $params),
            ]);
        }

        return $collection->sortBy('min_width')->values();
    }

    private function minWidth(string | int $breakpoint): int
    {
        if (is_numeric($breakpoint)) {
            return (int) $breakpoint;
        }

        return self::BREAKPOINT_WIDTHS[$breakpoint] ?? 0;
    }
}
