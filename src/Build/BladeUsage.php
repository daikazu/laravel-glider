<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Build;

/**
 * A single, statically-resolvable glider usage discovered by the
 * {@see TemplateScanner} while scanning Blade templates for the
 * prebuild command.
 */
final readonly class BladeUsage
{
    /**
     * @param  string  $component  One of: img, img-responsive, bg, bg-responsive, url.
     * @param  array<string, string>  $attributes  Raw string attribute map, excluding `src`.
     */
    public function __construct(
        public string $component,
        public string $src,
        public array $attributes,
        public string $file,
    ) {}
}
