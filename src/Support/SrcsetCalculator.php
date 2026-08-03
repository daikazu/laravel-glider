<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Illuminate\Support\Facades\Cache;

use function Illuminate\Filesystem\join_paths;

/**
 * Calculates (and caches) the set of widths to use for an `<img srcset>`
 * attribute: either a caller-supplied custom list, or a list derived from
 * the source image's dimensions and file size.
 */
final class SrcsetCalculator
{
    public function __construct(private readonly FilesystemResolver $resolver) {}

    /**
     * @param  int[]|null  $custom
     * @return int[]|null
     */
    public function widths(string $src, ?array $custom): ?array
    {
        $key = $this->cacheKey($src, $custom);

        if (Cache::has($key)) {
            /** @var int[]|null $cached */
            $cached = Cache::get($key);

            return $cached;
        }

        $widths = $this->compute($src, $custom);

        if ($widths !== null) {
            Cache::forever($key, $widths);
        }

        return $widths;
    }

    /**
     * @param  int[]|null  $custom
     */
    private function cacheKey(string $src, ?array $custom): string
    {
        $configHash = md5(json_encode([
            config('glider.defaults'),
            config('glider.presets'),
        ]) ?: '');

        if ($custom !== null) {
            return 'glider:' . sha1($src) . ':srcset_widths:custom:' . md5(implode(',', $custom)) . ':' . $configHash;
        }

        $imagePath = $this->localPath($src);
        $mtime = ($imagePath !== null && is_file($imagePath)) ? (filemtime($imagePath) ?: 0) : 0;

        return 'glider:' . sha1($src) . ':srcset_widths:img:' . $mtime . ':' . $configHash;
    }

    /**
     * @param  int[]|null  $custom
     * @return int[]|null
     */
    private function compute(string $src, ?array $custom): ?array
    {
        if ($custom !== null) {
            $normalized = $this->normalizeWidths($custom);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        $calculated = $this->fromImage($src);

        return $this->normalizeWidths($calculated ?? []);
    }

    /**
     * Automatically calculate the widths used for the srcset attribute
     * based on the source image's dimensions and file size.
     *
     * @return int[]|null
     */
    private function fromImage(string $src): ?array
    {
        $imagePath = $this->localPath($src);

        if ($imagePath === null || ! file_exists($imagePath)) {
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

    private function localPath(string $src): ?string
    {
        $root = $this->resolver->localPath(config('glider.source'));

        if ($root === null) {
            return null;
        }

        return join_paths($root, $src);
    }

    /**
     * Normalize a list of widths: keep positive integers, unique, ascending.
     *
     * @return int[]|null
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
