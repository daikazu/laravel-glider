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
        public ?string $sizes = null,
    ) {
        if (! in_array($srcsetWidths, [null, '', '0'], true)) {
            $parsed = array_values(array_filter(array_map(intval(...), explode(',', $srcsetWidths)), fn (int $w): bool => $w > 0));
            $this->srcsetWidths = count($parsed) > 0 ? $parsed : null;
        } else {
            $this->srcsetWidths = null;
        }
    }

    /**
     * The sizes attribute to render, if any: an explicit `sizes` prop wins;
     * lazy-loaded images default to `sizes="auto"` (the browser derives the
     * slot width from layout); otherwise null — the onload script then
     * back-fills sizes after first paint.
     */
    public function sizesAttribute(): ?string
    {
        if ($this->sizes !== null && $this->sizes !== '') {
            return $this->sizes;
        }

        return $this->attributes->get('loading') === 'lazy' ? 'auto' : null;
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
            // q/fm are srcset defaults the user's glide-q/glide-fm override;
            // the width always comes from the srcset entry. Mirrored by
            // ConversionResolver::imgResponsiveCandidates().
            $url = Glider::getUrl(
                $this->src,
                array_merge(['q' => 85, 'fm' => 'webp'], $glideAttributes, ['w' => $size])
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
