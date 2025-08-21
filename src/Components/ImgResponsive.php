<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Components;

use Daikazu\LaravelGlider\Facades\Glide;
use Illuminate\Support\Facades\Cache;

use function Illuminate\Filesystem\join_paths;

class ImgResponsive extends BaseComponent
{
    protected string $view = 'laravel-glider::components.img-responsive';

    private readonly ?array $srcsetWidths;

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

    public function srcset(): ?string
    {

        $widths = $this->getSrcsetWidthsCached();

        if (is_null($widths)) {
            return null;
        }

        return collect($widths)->map(function (int $size): string {
            $url = Glide::getUrl(
                $this->src,
                $this->glideAttributes()->merge(['q' => 85, 'fm' => 'webp', 'w' => $size])->toArray()
            );

            return "{$url} {$size}w";
        })->join(', ');
    }

    /**
     * Automatically calculate the widths used for the srcset attribute.
     */
    protected function getSrcsetWidthsFromImg(): ?array
    {
        $imagePath = join_paths(config('glider.source'), $this->src);
        if (! file_exists($imagePath)) {
            return null;
        }

        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            return null;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $filesize = filesize($imagePath);

        if ($filesize === 0 || $filesize === false) {
            return null;
        }

        $srcsetWidths = [$width];

        $ratio = $height / $width;
        $area = $width * $height;

        $pixelPrice = $filesize / $area;

        while ($filesize *= 0.7) {
            $newWidth = (int) floor(sqrt(($filesize / $pixelPrice) / $ratio));
            $srcsetWidths[] = $newWidth;
            if ($newWidth < 20 || $filesize < 10240) {
                break;
            }
        }

        return $srcsetWidths;
    }

    /**
     * Get the widths that should be used for the srcset attribute.
     */
    protected function getSrcsetWidths(): ?array
    {
        if ($this->srcsetWidths !== null) {
            $normalized = $this->normalizeWidths($this->srcsetWidths);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        $calculated = $this->getSrcsetWidthsFromImg();
        return $this->normalizeWidths($calculated ?? []);
    }

    /**
     * Get the widths that should be used for the srcset attribute.
     */
    protected function getSrcsetWidthsCached(): ?array
    {
        // Create a deterministic cache key that changes when inputs change.
        // - If custom widths are provided, the key is based on those.
        // - Otherwise, the key is based on the source image mtime to auto-bust on updates.
        if ($this->srcsetWidths !== null) {
            $key = 'glide:' . sha1($this->src) . ':srcset_widths:custom:' . md5(implode(',', $this->srcsetWidths));
        } else {
            $imagePath = join_paths(config('glider.source'), $this->src);
            $mtime = is_file($imagePath) ? (filemtime($imagePath) ?: 0) : 0;
            $key = 'glide:' . sha1($this->src) . ':srcset_widths:img:' . $mtime;
        }

        // Return a cached value if present.
        if (Cache::has($key)) {
            /** @var ?array $cached */
            $cached = Cache::get($key);
            return $cached;
        }

        // Compute and only cache non-null to avoid permanently caching a "missing" state.
        $widths = $this->getSrcsetWidths();
        if ($widths !== null) {
            Cache::forever($key, $widths);
        }

        return $widths;
    }

    /**
     * Normalize a list of widths: keep positive integers, unique, ascending.
     */
    private function normalizeWidths(array $widths): ?array
    {
        $filtered = array_values(array_filter(
            array_unique(array_map('intval', $widths)),
            static fn (int $w): bool => $w > 0
        ));

        sort($filtered, SORT_NUMERIC);

        return $filtered === [] ? null : $filtered;
    }
}
