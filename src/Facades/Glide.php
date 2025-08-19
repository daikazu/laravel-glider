<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Facades;

use Daikazu\LaravelGlider\GlideService;
use Illuminate\Support\Facades\Facade;
use League\Flysystem\Filesystem;

/**
 * @see GlideService
 *
 * @method static string decodePath(string $string)
 * @method static array decodeParams(string $string)
 * @method static string getCachePath(string $path, array $params = [])
 * @method static Filesystem getSourceFilesystem(string $path)
 * @method static string getUrl(string $path, array $params = [])
 * @method static array getResponsiveBackgroundUrls(string $path, array $breakpoints = [], array $baseParams = [])
 * @method static array getBackgroundPreset(string $presetName)
 * @method static string generateBackgroundCSS(string $path, array $breakpoints, string $selector, array $options = [])
 */
class Glide extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GlideService::class;
    }
}
