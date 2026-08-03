<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Components;

use Daikazu\LaravelGlider\Facades\Glider;
use Daikazu\LaravelGlider\Support\CssSanitizer;
use Daikazu\LaravelGlider\Support\FocalPoint;
use Daikazu\LaravelGlider\Support\GlideAttributes;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Bg extends Component
{
    private ?string $componentId = null;

    public function __construct(
        public string $src,
        public ?string $position = null,
        public string $size = 'cover',
        public string $repeat = 'no-repeat',
        public string $attachment = 'scroll',
        public ?string $fallback = null,
        public bool $lazy = false,
    ) {}

    public function render()
    {
        return view('glider::components.background');
    }

    /**
     * Generate CSS for background image
     */
    public function generateBackgroundCSS(): string
    {
        $componentId = $this->getComponentId();
        $url = CssSanitizer::url($this->getBackgroundUrl());

        $properties = [
            "background-image: url('{$url}')",
            'background-position: ' . CssSanitizer::value($this->getBackgroundPosition()),
            'background-size: ' . CssSanitizer::value($this->size),
            'background-repeat: ' . CssSanitizer::value($this->repeat),
            'background-attachment: ' . CssSanitizer::value($this->attachment),
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
        return Glider::getUrl($this->src, GlideAttributes::from($this->attributes));
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

        return [
            'data-bg-lazy' => 'true',
            'data-bg-src'  => $this->getBackgroundUrl(),
        ];
    }

    /**
     * Get the background-position CSS value.
     * Uses the focal-point attribute if provided, otherwise falls back to
     * the position property.
     */
    public function getBackgroundPosition(): string
    {
        return FocalPoint::parse($this->attributes->get('focal-point')) ?? $this->position ?? 'center';
    }
}
