<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use function Illuminate\Filesystem\join_paths;

/**
 * Resolves intrinsic image dimensions from the configured `glider.source`
 * root, and calculates the dimensions a transformed (Glide) image will
 * have once a set of glide params (w, h, fit, dpr) is applied.
 */
final readonly class Dimensions
{
    public function __construct(private FilesystemResolver $resolver) {}

    /**
     * @return array{width: int, height: int}|null
     */
    public function intrinsic(string $src): ?array
    {
        $root = $this->resolver->localPath(config('glider.source'));

        if ($root === null) {
            return null;
        }

        $imagePath = join_paths($root, $src);

        if (! is_file($imagePath)) {
            return null;
        }

        $info = @getimagesize($imagePath);

        if ($info === false) {
            return null;
        }

        return [
            'width'  => $info[0],
            'height' => $info[1],
        ];
    }

    /**
     * Calculate the intrinsic dimensions that the transformed image will
     * have, based on glide params (w, h, fit, dpr), falling back to the
     * original dimensions.
     *
     * Rules:
     * - If only w is provided: scale height proportionally.
     * - If only h is provided: scale width proportionally.
     * - If both w and h:
     *     - fit in [crop, fill, stretch]: output exactly w x h (stretch breaks AR).
     *     - fit in [contain, max] or unknown: scale to fit within the box preserving AR.
     * - Apply dpr multiplier when present.
     *
     * @param  array<string, mixed>  $glideParams
     * @return array{width: int, height: int}|null
     */
    public function transformed(string $src, array $glideParams): ?array
    {
        $orig = $this->intrinsic($src);

        if ($orig === null || empty($orig['width']) || empty($orig['height'])) {
            return null;
        }

        $W0 = (int) $orig['width'];
        $H0 = (int) $orig['height'];

        $w = array_key_exists('w', $glideParams) ? max(0, (int) $glideParams['w']) : null;
        $h = array_key_exists('h', $glideParams) ? max(0, (int) $glideParams['h']) : null;

        $fit = strtolower((string) ($glideParams['fit'] ?? ''));
        $fitFillLike = in_array($fit, ['crop', 'fill', 'stretch'], true);

        $targetW = $W0;
        $targetH = $H0;

        if ($w && $h) {
            if ($fitFillLike) {
                $targetW = $w;
                $targetH = $h;
            } else {
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

        $dpr = array_key_exists('dpr', $glideParams) ? (float) $glideParams['dpr'] : 1.0;
        if ($dpr > 0 && $dpr !== 1.0) {
            $targetW = (int) round($targetW * $dpr);
            $targetH = (int) round($targetH * $dpr);
        }

        $targetW = max(1, (int) $targetW);
        $targetH = max(1, (int) $targetH);

        return [
            'width'  => $targetW,
            'height' => $targetH,
        ];
    }
}
