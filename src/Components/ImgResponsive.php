<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Components;

use Daikazu\LaravelGlider\Facades\Glider;
use Daikazu\LaravelGlider\Support\Dimensions;
use Daikazu\LaravelGlider\Support\FocalPoint;
use Daikazu\LaravelGlider\Support\GlideAttributes;
use Daikazu\LaravelGlider\Support\SrcsetCalculator;
use Illuminate\View\Component;

class ImgResponsive extends Component
{
    private readonly ?array $srcsetWidths;

    /**
     * @var array{width: int, height: int}|null
     */
    private ?array $transformedDimensions = null;

    private bool $transformedDimensionsResolved = false;

    public function __construct(
        public string $src,
        ?string $srcsetWidths = null,
    ) {
        if ($srcsetWidths !== null && $srcsetWidths !== '' && $srcsetWidths !== '0') {
            $parsed = array_values(array_filter(array_map('intval', explode(',', $srcsetWidths)), fn (int $w): bool => $w > 0));
            $this->srcsetWidths = count($parsed) > 0 ? $parsed : null;
        } else {
            $this->srcsetWidths = null;
        }
    }

    public function render()
    {
        return view('glider::components.img-responsive');
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
     */
    public function objectPosition(): ?string
    {
        return FocalPoint::parse($this->attributes->get('focal-point'));
    }

    public function srcset(): ?string
    {
        $widths = app(SrcsetCalculator::class)->widths($this->src, $this->srcsetWidths);

        if ($widths === null) {
            return null;
        }

        $glideAttributes = GlideAttributes::from($this->attributes);

        return collect($widths)->map(function (int $size) use ($glideAttributes): string {
            $url = Glider::getUrl(
                $this->src,
                array_merge($glideAttributes, ['q' => 85, 'fm' => 'webp', 'w' => $size])
            );

            return "{$url} {$size}w";
        })->join(', ');
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
