<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Build;

use Daikazu\LaravelGlider\Glider;
use Daikazu\LaravelGlider\Support\BackgroundBreakpoints;
use Daikazu\LaravelGlider\Support\GlideAttributes;
use Daikazu\LaravelGlider\Support\SrcsetCalculator;
use Daikazu\LaravelGlider\Support\UrlGenerator;
use Illuminate\Support\Str;

/**
 * Resolves a single statically-discovered {@see BladeUsage} into the list
 * of Glide conversion jobs it implies.
 *
 * This is the linchpin of the build-time/request-time equivalence
 * invariant: any divergence here means `glider:build` would warm the wrong
 * cache entry. Rather than re-deriving final Glide params in parallel to
 * the request-time code path (preset expansion, default merging, and the
 * `fm`/extension redundancy handling in `ParamResolver` all have subtle,
 * easy-to-miss quirks — see git history for two that shipped), each
 * candidate is round-tripped through the *exact* runtime URL-generation
 * and decode pipeline: build the real URL via `Glider::url()`, then decode
 * it back exactly as `GlideController` would. This makes the params
 * byte-identical to what a live request for the same conversion would
 * produce, by construction, for any current or future param quirk.
 */
final readonly class ConversionResolver
{
    public function __construct(
        private Glider $glider,
        private UrlGenerator $urlGenerator,
        private SrcsetCalculator $srcsetCalculator,
        private BackgroundBreakpoints $backgroundBreakpoints,
    ) {}

    /**
     * @return list<array{path: string, params: array}>
     */
    public function jobs(BladeUsage $usage): array
    {
        $candidates = match ($usage->component) {
            'img', 'bg'      => [$this->glideAttributes($usage->attributes)],
            'url'            => [$usage->attributes],
            'img-responsive' => $this->imgResponsiveCandidates($usage),
            'bg-responsive'  => $this->bgResponsiveCandidates($usage),
            default          => [],
        };

        $jobs = [];

        foreach ($candidates as $inputParams) {
            $params = $this->canonicalize($usage->src, $inputParams);

            if ($params !== null) {
                $jobs[] = ['path' => $usage->src, 'params' => $params];
            }
        }

        return $jobs;
    }

    /**
     * The candidate input params for each `ImgResponsive` conversion: one
     * per srcset width (mirrors `ImgResponsive::srcset()`), plus one for
     * the plain `src()` conversion (mirrors `ImgResponsive::src()`).
     *
     * @return list<array<string, mixed>>
     */
    private function imgResponsiveCandidates(BladeUsage $usage): array
    {
        $base = $this->glideAttributes($usage->attributes);
        $custom = $this->parseSrcsetWidths($usage->attributes['srcset-widths'] ?? null);
        $widths = $this->srcsetCalculator->widths($usage->src, $custom);

        $candidates = [];

        if ($widths !== null) {
            foreach ($widths as $width) {
                // Defaults-then-user order mirrors ImgResponsive::srcset()
                $candidates[] = array_merge(['q' => 85, 'fm' => 'webp'], $base, ['w' => $width]);
            }
        }

        $candidates[] = $base;

        return $candidates;
    }

    /**
     * The candidate input params for each `BgResponsive` breakpoint
     * (mirrors `BgResponsive::breakpointsWithUrls()`).
     *
     * @return list<array<string, mixed>>
     */
    private function bgResponsiveCandidates(BladeUsage $usage): array
    {
        $base = $this->glideAttributes($usage->attributes);
        $preset = $usage->attributes['preset'] ?? null;

        return $this->backgroundBreakpoints
            ->expand($preset, null, $base)
            ->pluck('params')
            ->all();
    }

    /**
     * Round-trips $inputParams through the real URL-generation and
     * decoding pipeline: build the URL exactly as a component would
     * (`Glider::url()` — which maps preset -> p, expands presets/defaults,
     * and resolves the redundant-fm/extension quirk), then parse the
     * resulting URL exactly as `GlideController` would (including the
     * `$params['fm'] ??= $extension` step).
     *
     * Returns null when the usage wouldn't hit the Glide route at all
     * (e.g. a direct-serve passthrough for an unmanipulated local image) —
     * `UrlGenerator::parseUrl()` anchors on the route prefix AND requires
     * the `name~token.ext` shape, so direct-serve URLs can't false-positive.
     *
     * @param  array<string, mixed>  $inputParams
     * @return array<string, mixed>|null
     */
    private function canonicalize(string $path, array $inputParams): ?array
    {
        $parsed = $this->urlGenerator->parseUrl($this->glider->url($path, $inputParams));

        if ($parsed === null) {
            return null;
        }

        $params = $parsed['params'];
        $params['fm'] ??= $parsed['extension'];

        return $params;
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
        if (in_array($raw, [null, '', '0'], true)) {
            return null;
        }

        $parsed = array_values(array_filter(
            array_map(intval(...), explode(',', $raw)),
            static fn (int $w): bool => $w > 0
        ));

        return $parsed === [] ? null : $parsed;
    }
}
