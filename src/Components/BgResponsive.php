<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Components;

use Daikazu\LaravelGlider\Facades\Glider;
use Daikazu\LaravelGlider\Support\BackgroundBreakpoints;
use Daikazu\LaravelGlider\Support\CssSanitizer;
use Daikazu\LaravelGlider\Support\FocalPoint;
use Daikazu\LaravelGlider\Support\GlideAttributes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class BgResponsive extends Component
{
    private ?string $componentId = null;

    public function __construct(
        public string $src,
        public ?string $preset = null,
        public ?array $breakpoints = null,
        public ?string $position = null,
        public string $size = 'cover',
        public string $repeat = 'no-repeat',
        public string $attachment = 'scroll',
        public ?string $fallback = null,
        public bool $lazy = false,
    ) {}

    public function render()
    {
        return view('glider::components.responsive-background');
    }

    /**
     * Generate CSS for responsive background images
     */
    public function generateBackgroundCSS(): string
    {
        $breakpoints = $this->breakpointsWithUrls();
        $cssRules = [];
        $componentId = $this->getComponentId();

        // Generate default background (smallest breakpoint)
        $defaultBreakpoint = $breakpoints->first();
        if ($defaultBreakpoint) {
            $cssRules[] = $this->generateCSSRule(
                ".glider-bg-{$componentId}",
                $defaultBreakpoint['url'],
            );
        }

        // Generate media queries for larger breakpoints
        $breakpoints->slice(1)->each(function (array $breakpoint) use (&$cssRules, $componentId): void {
            $mediaQuery = "@media (min-width: {$breakpoint['min_width']}px)";
            $rule = $this->generateCSSRule(
                ".glider-bg-{$componentId}",
                $breakpoint['url'],
                true  // Include selector in media queries
            );
            $cssRules[] = $mediaQuery . ' {' . PHP_EOL .
                '    ' . $rule . PHP_EOL .
                '}';
        });

        return '<style>' . PHP_EOL . implode(PHP_EOL, $cssRules) . PHP_EOL . '</style>';
    }

    /**
     * Get the unique component ID for CSS targeting. Random rather than a
     * static counter: statics persist across requests in long-running
     * workers (Octane), and a per-render random suffix cannot collide
     * with other instances on the page either way.
     */
    public function getComponentId(): string
    {
        return $this->componentId ??= 'comp-'
            . Str::slug(basename($this->src, pathinfo($this->src, PATHINFO_EXTENSION)))
            . '-' . strtolower(Str::random(6));
    }

    /**
     * Get CSS class name for this component
     */
    public function getCSSClass(): string
    {
        return 'glider-bg-' . $this->getComponentId();
    }

    /**
     * Get fallback image URL if specified
     */
    public function getFallbackUrl(): ?string
    {
        if (in_array($this->fallback, [null, '', '0'], true)) {
            return null;
        }

        return Glider::getUrl($this->fallback, GlideAttributes::from($this->attributes));
    }

    /**
     * Generate lazy loading data attributes
     */
    public function getLazyAttributes(): array
    {
        if (! $this->lazy) {
            return [];
        }

        $breakpoints = $this->breakpointsWithUrls();

        return [
            'data-bg-lazy' => 'true',
            'data-bg-src'  => $breakpoints->first()['url'] ?? '',
            // For background images with media queries, we store the breakpoint info differently
            // The lazy loader should use min_width as the media query breakpoint, not as the image width
            'data-bg-srcset' => $breakpoints->map(fn (array $bp): string => "{$bp['url']} {$bp['min_width']}px")->implode(', '),
        ];
    }

    /**
     * Get the background-position CSS value.
     * Uses the focus attribute if provided, otherwise falls back to
     * the position property.
     */
    public function getBackgroundPosition(): string
    {
        return FocalPoint::parse($this->attributes->get('focus')) ?? $this->position ?? 'center';
    }

    /**
     * Expand this component's preset/breakpoints/glide-attributes into a
     * sorted collection of breakpoints, each with its resolved image URL.
     */
    private function breakpointsWithUrls(): Collection
    {
        $glideAttributes = GlideAttributes::from($this->attributes);

        return app(BackgroundBreakpoints::class)
            ->expand($this->preset, $this->breakpoints, $glideAttributes)
            ->map(fn (array $bp): array => [
                ...$bp,
                'url' => Glider::getUrl($this->src, $bp['params']),
            ]);
    }

    /**
     * Generate a single CSS rule
     */
    private function generateCSSRule(string $selector, string $url, bool $includeSelector = true): string
    {
        // Sanitize all values for CSS context
        $safeUrl = CssSanitizer::url($url);
        $safePosition = CssSanitizer::value($this->getBackgroundPosition());
        $safeSize = CssSanitizer::value($this->size);
        $safeRepeat = CssSanitizer::value($this->repeat);
        $safeAttachment = CssSanitizer::value($this->attachment);

        $properties = [
            "background-image: url('{$safeUrl}')",
            "background-position: {$safePosition}",
            "background-size: {$safeSize}",
            "background-repeat: {$safeRepeat}",
            "background-attachment: {$safeAttachment}",
        ];

        $rule = implode('; ', $properties) . ';';

        return $includeSelector ? "{$selector} { {$rule} }" : $rule;
    }
}
