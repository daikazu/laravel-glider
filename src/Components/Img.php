<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Components;

use Daikazu\LaravelGlider\Facades\Glider;
use Daikazu\LaravelGlider\Support\Dimensions;
use Daikazu\LaravelGlider\Support\FocalPoint;
use Daikazu\LaravelGlider\Support\GlideAttributes;
use Illuminate\View\Component;

class Img extends Component
{
    /**
     * @var array{width: int, height: int}|null
     */
    private ?array $transformedDimensions = null;

    private bool $transformedDimensionsResolved = false;

    public function __construct(
        public string $src,
    ) {}

    public function render()
    {
        return view('glider::components.img');
    }

    public function src(): string
    {
        return Glider::getUrl($this->src, GlideAttributes::from($this->attributes));
    }

    /**
     * Natural image width of the delivered (possibly transformed) image.
     */
    public function width(): ?int
    {
        return $this->transformed()['width'] ?? null;
    }

    /**
     * Natural image height of the delivered (possibly transformed) image.
     */
    public function height(): ?int
    {
        return $this->transformed()['height'] ?? null;
    }

    /**
     * Get the object-position CSS value from the focal-point attribute.
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
    public function objectPosition(): ?string
    {
        return FocalPoint::parse($this->attributes->get('focal-point'));
    }

    /**
     * @return array{width: int, height: int}|null
     */
    private function transformed(): ?array
    {
        if (! $this->transformedDimensionsResolved) {
            $this->transformedDimensionsResolved = true;
            $this->transformedDimensions = app(Dimensions::class)->transformed(
                $this->src,
                GlideAttributes::from($this->attributes)
            );
        }

        return $this->transformedDimensions;
    }
}
