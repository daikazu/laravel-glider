<?php

namespace Daikazu\LaravelGlider\Imaging;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use League\Glide\Server;
use League\Glide\ServerFactory;


class GlideServer
{

    public static function create(): Server
    {

        return ServerFactory::create([
            'source'                 => Config::get('glider.source'),
            'source_path_prefix'     => Config::get('glider.source_path_prefix'),
            'cache'                  => self::cachePath(),
            'cache_path_prefix'      => Config::get('glider.cache_path_prefix'),
            'watermarks'             => Config::get('glider.watermarks'),
            'watermarks_path_prefix' => Config::get('glider.watermarks_path_prefix'),
            'base_url'               => Config::get('glider.route'),
            'max_image_size'         => Config::get('glider.max_image_size'),
            'driver'                 => Config::get('glider.driver'),
            'presets'                => self::manipulationPresets(),
            'response'               => new ResponseFactory(app('request')),
        ]);

    }

    public static function cachePath()
    {
        return Config::get('glider.cache')
            ? Config::get('glider.cache_path')
            : storage_path('app/glide');
    }

    private static function manipulationPresets(): array
    {

        return collect(config('glider.presets', []))
            ->map(fn($preset) => self::normalizePreset($preset))
            ->all();
    }

    protected static function normalizePreset($preset)
    {
        if (Arr::get($preset, 'fit') === 'crop_focal') {
            Arr::forget($preset, 'fit');
        }
        return $preset;
    }

}
