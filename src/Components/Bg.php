<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Components;

use Daikazu\LaravelGlider\Facades\Glider;
use Daikazu\LaravelGlider\Support\CssSanitizer;
use Daikazu\LaravelGlider\Support\FocalPoint;
use Daikazu\LaravelGlider\Support\GlideAttributes;
use Illuminate\View\Component;

class Bg extends Component
{
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
     * The inline background style for the container. A single non-responsive
     * background needs no <style> block or generated class — inline CSS
     * removes the per-component ID machinery entirely.
     *
     * When a fallback is set it is the image shown inline (a lazy loader
     * swaps in the real image from data-bg-src).
     */
    public function backgroundStyle(): string
    {
        $url = CssSanitizer::url($this->getFallbackUrl() ?? $this->getBackgroundUrl());

        $properties = [
            "background-image: url('{$url}')",
            'background-position: ' . CssSanitizer::value($this->getBackgroundPosition()),
            'background-size: ' . CssSanitizer::value($this->size),
            'background-repeat: ' . CssSanitizer::value($this->repeat),
            'background-attachment: ' . CssSanitizer::value($this->attachment),
        ];

        return implode('; ', $properties) . ';';
    }

    /**
     * Get the background image URL
     */
    public function getBackgroundUrl(): string
    {
        return Glider::getUrl($this->src, GlideAttributes::from($this->attributes));
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
