<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Components;

use Daikazu\LaravelGlider\Facades\Glide;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use InvalidArgumentException;

class ResponsiveBackground extends Component
{
    protected string $view = 'laravel-glider::components.responsive-background';

    private ?string $componentId = null;

    public function __construct(
        public string $src,
        public ?string $preset = null,
        public ?array $breakpoints = null,
        public string $position = 'center',
        public string $size = 'cover',
        public string $repeat = 'no-repeat',
        public string $attachment = 'scroll',
        public ?string $fallback = null,
        public bool $lazy = false,
    ) {}

    public function render()
    {
        return view($this->view);
    }

    /**
     * Generate CSS for responsive background images
     */
    public function generateBackgroundCSS(): string
    {
        $breakpoints = $this->getBreakpoints();
        $cssRules = [];
        $componentId = $this->getComponentId();

        // Generate default background (smallest breakpoint)
        $defaultBreakpoint = $breakpoints->first();
        if ($defaultBreakpoint) {
            $cssRules[] = $this->generateCSSRule(
                ".glide-bg-{$componentId}",
                $defaultBreakpoint['url'],
                $defaultBreakpoint['params']
            );
        }

        // Generate media queries for larger breakpoints
        $breakpoints->slice(1)->each(function (array $breakpoint) use (&$cssRules, $componentId): void {
            $mediaQuery = "@media (min-width: {$breakpoint['min_width']}px)";
            $cssRules[] = $mediaQuery . ' {' . PHP_EOL .
                '    ' . $this->generateCSSRule(
                    ".glide-bg-{$componentId}",
                    $breakpoint['url'],
                    $breakpoint['params'],
                    false
                ) . PHP_EOL .
                '}';
        });

        return '<style>' . PHP_EOL . implode(PHP_EOL, $cssRules) . PHP_EOL . '</style>';
    }

    /**
     * Get the unique component ID for CSS targeting
     */
    public function getComponentId(): string
    {
        if ($this->componentId === null) {
            static $counter = 0;
            $counter++;

            $this->componentId = 'comp-' . Str::slug(basename($this->src, pathinfo($this->src, PATHINFO_EXTENSION))) . '-' . $counter;
        }

        return $this->componentId;
    }

    /**
     * Get CSS class name for this component
     */
    public function getCSSClass(): string
    {
        return 'glide-bg-' . $this->getComponentId();
    }

    /**
     * Get fallback image URL if specified
     */
    public function getFallbackUrl(): ?string
    {
        if ($this->fallback === null || $this->fallback === '' || $this->fallback === '0') {
            return null;
        }

        return Glide::getUrl($this->fallback, $this->mergeGlideAttributes());
    }

    /**
     * Generate lazy loading data attributes
     */
    public function getLazyAttributes(): array
    {
        if (! $this->lazy) {
            return [];
        }

        $breakpoints = $this->getBreakpoints();

        return [
            'data-bg-lazy'   => 'true',
            'data-bg-src'    => $breakpoints->first()['url'] ?? '',
            'data-bg-srcset' => $breakpoints->map(fn (array $bp): string => "{$bp['url']} {$bp['min_width']}w")->implode(', '),
        ];
    }

    /**
     * Get all breakpoints with their URLs and parameters
     */
    protected function getBreakpoints(): Collection
    {
        // If using a preset, get breakpoints from config
        if ($this->preset !== null && $this->preset !== '' && $this->preset !== '0') {
            return $this->getPresetBreakpoints();
        }

        // Use custom breakpoints if provided
        if ($this->breakpoints !== null && $this->breakpoints !== []) {
            return $this->buildBreakpointsFromArray($this->breakpoints);
        }

        // Default responsive breakpoints
        return $this->getDefaultBreakpoints();
    }

    /**
     * Get breakpoints from a preset configuration
     */
    protected function getPresetBreakpoints(): Collection
    {
        $presets = config('glider.background_presets', []);

        if (! isset($presets[$this->preset])) {
            throw new InvalidArgumentException("Background preset '{$this->preset}' not found in config");
        }

        $preset = $presets[$this->preset];
        return $this->buildBreakpointsFromArray($preset['breakpoints'] ?? $preset);
    }

    /**
     * Build breakpoints collection from array
     */
    protected function buildBreakpointsFromArray(array $breakpoints): Collection
    {
        $collection = collect();

        foreach ($breakpoints as $key => $params) {
            // Handle named breakpoints (xs, sm, md, lg, xl) vs numeric breakpoints
            $minWidth = $this->getMinWidthFromBreakpoint($key);
            $glideParams = $this->mergeGlideAttributes($params);

            $collection->push([
                'name'      => $key,
                'min_width' => $minWidth,
                'params'    => $glideParams,
                'url'       => Glide::getUrl($this->src, $glideParams),
            ]);
        }

        // Sort by min_width
        return $collection->sortBy('min_width')->values();
    }

    /**
     * Get default responsive breakpoints
     */
    protected function getDefaultBreakpoints(): Collection
    {
        $defaultBreakpoints = [
            'xs' => ['w' => 480],
            'sm' => ['w' => 768],
            'md' => ['w' => 1024],
            'lg' => ['w' => 1280],
            'xl' => ['w' => 1920],
        ];

        return $this->buildBreakpointsFromArray($defaultBreakpoints);
    }

    /**
     * Get minimum width for a breakpoint
     */
    protected function getMinWidthFromBreakpoint(string | int $breakpoint): int
    {
        // If it's already numeric, use it
        if (is_numeric($breakpoint)) {
            return (int) $breakpoint;
        }

        // Map common breakpoint names to pixel values
        $breakpointMap = [
            'xs'  => 0,
            'sm'  => 576,
            'md'  => 768,
            'lg'  => 992,
            'xl'  => 1200,
            '2xl' => 1400,
        ];

        return $breakpointMap[$breakpoint] ?? 0;
    }

    /**
     * Merge Glide attributes from component attributes
     */
    protected function mergeGlideAttributes(array $params = []): array
    {
        $glideAttributes = collect($this->attributes->whereStartsWith('glide-'))
            ->mapWithKeys(fn ($item, string $key) => [Str::after($key, 'glide-') => $item]);

        return array_merge($glideAttributes->toArray(), $params);
    }

    /**
     * Generate a single CSS rule
     */
    protected function generateCSSRule(string $selector, string $url, array $params, bool $includeSelector = true): string
    {
        $properties = [
            "background-image: url('{$url}')",
            "background-position: {$this->position}",
            "background-size: {$this->size}",
            "background-repeat: {$this->repeat}",
            "background-attachment: {$this->attachment}",
        ];

        $rule = implode('; ', $properties) . ';';

        return $includeSelector ? "{$selector} { {$rule} }" : $rule;
    }
}
