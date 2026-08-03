<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Build;

use Daikazu\LaravelGlider\Support\BackgroundBreakpoints;
use Daikazu\LaravelGlider\Support\GlideAttributes;
use Daikazu\LaravelGlider\Support\ParamResolver;
use Daikazu\LaravelGlider\Support\SrcsetCalculator;
use Illuminate\Support\Str;

/**
 * Resolves a single statically-discovered {@see BladeUsage} into the list
 * of Glide conversion jobs it implies, mirroring exactly the params each
 * component/facade usage would produce at request time. This is the
 * linchpin of the build-time/request-time equivalence invariant: any
 * divergence here means `glider:build` would warm the wrong cache entry.
 */
final class ConversionResolver
{
    public function __construct(
        private readonly ParamResolver $paramResolver,
        private readonly SrcsetCalculator $srcsetCalculator,
        private readonly BackgroundBreakpoints $backgroundBreakpoints,
    ) {}

    /**
     * @return list<array{path: string, params: array}>
     */
    public function jobs(BladeUsage $usage): array
    {
        return match ($usage->component) {
            'img', 'bg'      => $this->staticJobs($usage),
            'url'            => [['path' => $usage->src, 'params' => $usage->attributes]],
            'img-responsive' => $this->imgResponsiveJobs($usage),
            'bg-responsive'  => $this->bgResponsiveJobs($usage),
            default          => [],
        };
    }

    /**
     * @return list<array{path: string, params: array}>
     */
    private function staticJobs(BladeUsage $usage): array
    {
        $params = $this->paramResolver->mapPresetAlias($this->glideAttributes($usage->attributes));

        return [['path' => $usage->src, 'params' => $params]];
    }

    /**
     * @return list<array{path: string, params: array}>
     */
    private function imgResponsiveJobs(BladeUsage $usage): array
    {
        $base = $this->glideAttributes($usage->attributes);
        $custom = $this->parseSrcsetWidths($usage->attributes['srcset-widths'] ?? null);
        $widths = $this->srcsetCalculator->widths($usage->src, $custom);

        $jobs = [];

        if ($widths !== null) {
            foreach ($widths as $width) {
                $jobs[] = [
                    'path'   => $usage->src,
                    'params' => array_merge($base, ['q' => 85, 'fm' => 'webp', 'w' => (string) $width]),
                ];
            }
        }

        // Plus the plain `src()` conversion (mirrors ImgResponsive::src()).
        $jobs[] = ['path' => $usage->src, 'params' => $base];

        return $jobs;
    }

    /**
     * @return list<array{path: string, params: array}>
     */
    private function bgResponsiveJobs(BladeUsage $usage): array
    {
        $base = $this->glideAttributes($usage->attributes);
        $preset = $usage->attributes['preset'] ?? null;

        return $this->backgroundBreakpoints
            ->expand($preset, null, $base)
            ->map(fn (array $breakpoint): array => ['path' => $usage->src, 'params' => $breakpoint['params']])
            ->all();
    }

    /**
     * Strip the `glide-` prefix from raw string attributes, mirroring
     * {@see GlideAttributes::from()} but
     * operating on a plain string map rather than a ComponentAttributeBag.
     *
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    private function glideAttributes(array $attributes): array
    {
        $glide = [];

        foreach ($attributes as $key => $value) {
            if (str_starts_with($key, 'glide-')) {
                $glide[Str::after($key, 'glide-')] = $value;
            }
        }

        return $glide;
    }

    /**
     * Mirrors ImgResponsive's constructor parsing of the `srcset-widths`
     * attribute: comma-separated ints, non-positive values filtered out,
     * null if nothing usable remains.
     *
     * @return int[]|null
     */
    private function parseSrcsetWidths(?string $raw): ?array
    {
        if ($raw === null || $raw === '' || $raw === '0') {
            return null;
        }

        $parsed = array_values(array_filter(
            array_map('intval', explode(',', $raw)),
            static fn (int $w): bool => $w > 0
        ));

        return $parsed === [] ? null : $parsed;
    }
}
