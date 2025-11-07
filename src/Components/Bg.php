<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Components;

use Daikazu\LaravelGlider\Facades\Glide;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Bg extends Component
{
    protected string $view = 'laravel-glider::components.background';

    private ?string $componentId = null;

    public function __construct(
        public string $src,
        public ?string $position = null,
        public string $size = 'cover',
        public string $repeat = 'no-repeat',
        public string $attachment = 'scroll',
        public ?string $fallback = null,
        public bool $lazy = false,
    ) {
        // Handle focal-point attribute for background-position
        // Will be set via attributes, but we initialize position with a default if not provided
    }

    public function render()
    {
        return view($this->view);
    }

    /**
     * Generate CSS for background image
     */
    public function generateBackgroundCSS(): string
    {
        $componentId = $this->getComponentId();
        $url = $this->getBackgroundUrl();

        $properties = [
            "background-image: url('{$url}')",
            "background-position: {$this->getBackgroundPosition()}",
            "background-size: {$this->size}",
            "background-repeat: {$this->repeat}",
            "background-attachment: {$this->attachment}",
        ];

        $rule = implode('; ', $properties) . ';';
        $cssRule = ".glide-bg-{$componentId} { {$rule} }";

        return '<style>' . PHP_EOL . $cssRule . PHP_EOL . '</style>';
    }

    /**
     * Get the background image URL
     */
    public function getBackgroundUrl(): string
    {
        return Glide::getUrl($this->src, $this->mergeGlideAttributes());
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

        return [
            'data-bg-lazy' => 'true',
            'data-bg-src'  => $this->getBackgroundUrl(),
        ];
    }

    /**
     * Get the background-position CSS value
     * Uses focal-point attribute if provided, otherwise falls back to position property
     */
    public function getBackgroundPosition(): string
    {
        // Check for focal-point attribute first
        if ($this->attributes->has('focal-point')) {
            $bgPosition = $this->parseFocalPoint($this->attributes->get('focal-point'));
            if ($bgPosition !== null) {
                return $bgPosition;
            }
        }

        // Fall back to position property
        return $this->position ?? 'center';
    }

    /**
     * Parse focal point attribute into CSS background-position value
     *
     * Accepts formats:
     * - "50,50" or "50, 50" - x,y percentages (0-100)
     * - "center" - shorthand for 50% 50%
     * - "top" - shorthand for 50% 0%
     * - "bottom" - shorthand for 50% 100%
     * - "left" - shorthand for 0% 50%
     * - "right" - shorthand for 100% 50%
     * - "top-left" - shorthand for 0% 0%
     * - "top-right" - shorthand for 100% 0%
     * - "bottom-left" - shorthand for 0% 100%
     * - "bottom-right" - shorthand for 100% 100%
     */
    protected function parseFocalPoint(mixed $focalPoint): ?string
    {
        if (! is_string($focalPoint) || $focalPoint === '' || $focalPoint === '0') {
            return null;
        }

        $focalPoint = strtolower(trim($focalPoint));

        // Named positions
        $namedPositions = [
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

        if (isset($namedPositions[$focalPoint])) {
            return $namedPositions[$focalPoint];
        }

        // Parse x,y coordinates
        if (str_contains($focalPoint, ',')) {
            $parts = array_map('trim', explode(',', $focalPoint));
            if (count($parts) === 2) {
                $x = (int) $parts[0];
                $y = (int) $parts[1];

                // Validate range 0-100
                if ($x >= 0 && $x <= 100 && $y >= 0 && $y <= 100) {
                    return "{$x}% {$y}%";
                }
            }
        }

        return null;
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
}
