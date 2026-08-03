<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Daikazu\LaravelGlider\Security\PathValidator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Glide\Signatures\SignatureInterface;

use function Illuminate\Filesystem\join_paths;

final readonly class UrlGenerator
{
    public function __construct(
        private PathCodec $codec,
        private ParamResolver $params,
        private PathValidator $pathValidator,
        private SignatureInterface $signature,
    ) {}

    public function url(string $path, array $params = []): string
    {
        // The signature is created later and should be ignored even if provided as a parameter
        unset($params['s']);

        // Map 'preset' to 'p' for League/Glide compatibility
        // Users use glide-preset="name" which becomes ['preset' => 'name']
        // But League/Glide expects ['p' => 'name'] for preset lookups
        $params = $this->params->mapPresetAlias($params);

        // Sometimes we can directly serve the image from the public disk
        // (Only for local paths, not URLs)
        if ($params === [] && ! Str::isUrl($path)) {
            $publicRoot = config('filesystems.disks.public.root');
            $sourceRoot = config('glider.source');

            if (is_string($publicRoot) && is_string($sourceRoot) && Str::startsWith($sourceRoot, $publicRoot)) {
                return Storage::disk('public')->url($path);
            }

            if (Str::startsWith($sourceRoot, storage_path())) {
                return asset(join_paths(Str::after($sourceRoot, storage_path()), $path));
            }
        }

        // Now we determine the route parameters
        $routeParams = $this->routeParams($path, $params);

        // Only add signature if secure mode is enabled
        if (config('glider.secure', true)) {
            $signedParams = $this->signature->addSignature(route('glider', $routeParams, false), []);

            $routeParams['s'] = $signedParams['s'];
        }

        return route('glider', $routeParams);
    }

    public function cachePath(string $path, array $params = []): string
    {
        $routeParams = $this->routeParams($path, $params);
        $fullRoute = route('glider', $routeParams, false);

        return ltrim(Str::after($fullRoute, '/' . config('glider.base_url')), '/');
    }

    private function routeParams(string $path, array $parameters = []): array
    {
        $resolved = $this->params->routeParams($path, $parameters);

        return [
            'encoded_path'   => $this->encodePath($path),
            'encoded_params' => $this->encodeParams($resolved['params']),
            'extension'      => $resolved['extension'],
        ];
    }

    private function encodePath(string $path): string
    {
        if (Str::isUrl($path) && Str::startsWith($path, config('app.url')) && ! Str::startsWith($path, url(config('glider.base_url')))) {
            $path = Str::after($path, config('app.url'));
        }

        // Remove query parameters from path if they exist
        if (str_contains($path, '?')) {
            $path = explode('?', $path)[0];
        }

        $path = ltrim($path, '/');

        // Validate local paths for security
        if (! Str::isUrl($path)) {
            $this->pathValidator->validate($path);
        }

        return $this->codec->encode($path);
    }

    private function encodeParams(array $params): string
    {
        $normalized = $this->params->normalize($params);

        return $this->codec->encodeParams($normalized);
    }
}
