<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Illuminate\Contracts\Container\Container;
use League\Glide\Server;

final class ParamResolver
{
    public function __construct(private readonly Container $app) {}

    public function normalize(array $params): array
    {
        if ($this->app->bound(Server::class)) {
            $params = $this->app->make(Server::class)->getAllParams($params);
        }

        unset($params['s'], $params['p']);
        $params = array_map('strval', $params);
        ksort($params);

        return $params;
    }

    public function mapPresetAlias(array $params): array
    {
        if (isset($params['preset'])) {
            $params['p'] = $params['preset'];
            unset($params['preset']);
        }

        return $params;
    }

    /**
     * @return array{extension: string, params: array}
     */
    public function routeParams(string $path, array $params): array
    {
        $pathForExt = (string) parse_url($path, PHP_URL_PATH);
        $ext = strtolower(in_array(pathinfo($pathForExt, PATHINFO_EXTENSION), ['', '0'], true) ? '' : pathinfo($pathForExt, PATHINFO_EXTENSION));

        // Merge with server defaults/presets so fm from presets/defaults is considered
        $resolvedParams = $params;
        if ($this->app->bound(Server::class)) {
            $resolvedParams = $this->app->make(Server::class)->getAllParams($params);
        }

        $format = $resolvedParams['fm'] ?? ($ext !== '' ? $ext : null);
        $extension = $format === 'pjpg' ? 'jpg' : ($format ?? 'jpg');

        // If fm is redundant (same as chosen extension), avoid including it explicitly in the URL params
        if (array_key_exists('fm', $params) && ($params['fm'] === $extension || $params['fm'] === 'pjpg' && $extension === 'jpg')) {
            unset($params['fm']);
        }

        return [
            'extension' => $extension,
            'params'    => $params,
        ];
    }
}
