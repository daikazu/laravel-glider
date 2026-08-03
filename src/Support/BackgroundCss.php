<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use InvalidArgumentException;

/**
 * Builds responsive background-image URLs and the CSS/media-query
 * rules that reference them.
 */
final class BackgroundCss
{
    public function __construct(private readonly UrlGenerator $urls) {}

    /**
     * Generate responsive background URLs for multiple breakpoints
     */
    public function responsiveUrls(string $path, array $breakpoints = [], array $baseParams = []): array
    {
        $urls = [];

        foreach ($breakpoints as $breakpoint => $params) {
            // Merge base params with breakpoint-specific params
            $finalParams = array_merge($baseParams, $params);

            $urls[$breakpoint] = [
                'url'       => $this->urls->url($path, $finalParams),
                'params'    => $finalParams,
                'min_width' => $this->breakpointWidth($breakpoint),
            ];
        }

        return $urls;
    }

    /**
     * Get background preset configuration
     */
    public function preset(string $presetName): array
    {
        $presets = config('glider.background_presets', []);

        if (! isset($presets[$presetName])) {
            throw new InvalidArgumentException("Background preset '{$presetName}' not found");
        }

        return $presets[$presetName];
    }

    /**
     * Generate CSS for responsive background images
     */
    public function generate(string $path, array $breakpoints, string $selector, array $options = []): string
    {
        $urls = $this->responsiveUrls($path, $breakpoints);

        $position = $options['position'] ?? 'center';
        $size = $options['size'] ?? 'cover';
        $repeat = $options['repeat'] ?? 'no-repeat';
        $attachment = $options['attachment'] ?? 'scroll';

        $cssRules = [];

        // Generate default background (first/smallest breakpoint)
        $firstUrl = reset($urls);
        if ($firstUrl) {
            $cssRules[] = "{$selector} {";
            $cssRules[] = "    background-image: url('{$firstUrl['url']}');";
            $cssRules[] = "    background-position: {$position};";
            $cssRules[] = "    background-size: {$size};";
            $cssRules[] = "    background-repeat: {$repeat};";
            $cssRules[] = "    background-attachment: {$attachment};";
            $cssRules[] = '}';
        }

        // Generate media queries for larger breakpoints
        foreach ($urls as $data) {
            if ($data['min_width'] > 0) {
                $cssRules[] = "@media (min-width: {$data['min_width']}px) {";
                $cssRules[] = "    {$selector} {";
                $cssRules[] = "        background-image: url('{$data['url']}');";
                $cssRules[] = '    }';
                $cssRules[] = '}';
            }
        }

        return implode(PHP_EOL, $cssRules);
    }

    /**
     * Convert breakpoint name to minimum width in pixels
     */
    private function breakpointWidth(string | int $breakpoint): int
    {
        // If it's already numeric, use it
        if (is_numeric($breakpoint)) {
            return (int) $breakpoint;
        }

        // Map common breakpoint names to pixel values
        $breakpointMap = [
            'xs'  => 0,
            'sm'  => 576,
            'md'  => 768,
            'lg'  => 992,
            'xl'  => 1200,
            '2xl' => 1400,
        ];

        return $breakpointMap[$breakpoint] ?? 0;
    }
}
