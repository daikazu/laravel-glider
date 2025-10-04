<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Components;

use Daikazu\LaravelGlider\Facades\Glide;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\Component;

use function Illuminate\Filesystem\join_paths;

class BaseComponent extends Component
{
    protected string $view;

    private ?array $dimensions = null;

    public function __construct(
        public string $src,
    ) {}

    public function render()
    {
        return view($this->view);
    }

    public function src(): string
    {
        return Glide::getUrl($this->src, $this->glideAttributes()->toArray());
    }

    /**
     * Natural image width of the delivered (possibly transformed) image.
     */
    public function width(): ?int
    {
        $dims = $this->transformedDimensions();
        return $dims['width'] ?? null;
    }

    /**
     * Natural image height of the delivered (possibly transformed) image.
     */
    public function height(): ?int
    {
        $dims = $this->transformedDimensions();
        return $dims['height'] ?? null;
    }

    protected function glideAttributes(): Collection
    {
        return collect($this->attributes->whereStartsWith('glide-'))
            ->mapWithKeys(fn ($item, string $key) => [Str::after($key, 'glide-') => $item]);
    }

    /**
     * Retrieve and cache intrinsic dimensions using getimagesize.
     *
     * @return array{width:int, height:int}|null
     */
    protected function dimensions(): ?array
    {
        if ($this->dimensions !== null) {
            return $this->dimensions;
        }

        $imagePath = join_paths(config('laravel-glider.source'), $this->src);
        if (! is_file($imagePath)) {
            return $this->dimensions = null;
        }

        $info = @getimagesize($imagePath);
        if ($info === false) {
            return $this->dimensions = null;
        }

        return $this->dimensions = [
            'width'  => $info[0],
            'height' => $info[1],
        ];
    }

    /**
     * Calculate the intrinsic dimensions that the transformed image will have,
     * based on glide attributes (w, h, fit, dpr), falling back to original dimensions.
     *
     * Rules:
     * - If only w is provided: scale height proportionally.
     * - If only h is provided: scale width proportionally.
     * - If both w and h:
     *     - fit in [crop, fill, stretch]: output exactly w x h (stretch breaks AR).
     *     - fit in [contain, max] or unknown: scale to fit within the box preserving AR.
     * - Apply dpr multiplier when present.
     */
    protected function transformedDimensions(): ?array
    {
        $orig = $this->dimensions();
        if ($orig === null || empty($orig['width']) || empty($orig['height'])) {
            return null;
        }

        $W0 = (int) $orig['width'];
        $H0 = (int) $orig['height'];

        $attrs = $this->glideAttributes();

        // Parse integers safely
        $w = $attrs->has('w') ? max(0, (int) $attrs->get('w')) : null;
        $h = $attrs->has('h') ? max(0, (int) $attrs->get('h')) : null;

        $fit = strtolower((string) ($attrs->get('fit') ?? ''));
        // Common Glide fits: contain, max, fill, crop, stretch
        $fitFillLike = in_array($fit, ['crop', 'fill', 'stretch'], true);

        $targetW = $W0;
        $targetH = $H0;

        if ($w && $h) {
            if ($fitFillLike) {
                // Exact box size (stretch ignores AR; crop/fill produce exact output size)
                $targetW = $w;
                $targetH = $h;
            } else {
                // Contain-like: preserve AR inside the box
                $scale = min($w / $W0, $h / $H0);
                $scale = $scale > 0 ? $scale : 1.0;
                $targetW = (int) round($W0 * $scale);
                $targetH = (int) round($H0 * $scale);
            }
        } elseif ($w) {
            $targetW = $w;
            $targetH = (int) round($H0 * ($w / $W0));
        } elseif ($h) {
            $targetH = $h;
            $targetW = (int) round($W0 * ($h / $H0));
        }

        // Device pixel ratio support (if provided)
        $dpr = $attrs->has('dpr') ? (float) $attrs->get('dpr') : 1.0;
        if ($dpr > 0 && $dpr !== 1.0) {
            $targetW = (int) round($targetW * $dpr);
            $targetH = (int) round($targetH * $dpr);
        }

        // Avoid zeroes
        $targetW = max(1, (int) $targetW);
        $targetH = max(1, (int) $targetH);

        return [
            'width'  => $targetW,
            'height' => $targetH,
        ];
    }
}
