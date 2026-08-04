<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Http\Controllers;

use Daikazu\LaravelGlider\Facades\Glider;
use Daikazu\LaravelGlider\Glider as GliderService;
use Daikazu\LaravelGlider\Security\PresetPolicy;
use Illuminate\Http\Request;
use InvalidArgumentException;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Filesystem\FilesystemException;
use League\Glide\Server;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GlideController
{
    public function __invoke(Request $request, Server $server, PresetPolicy $presets, string $path): Response
    {
        try {
            $parsed = app(GliderService::class)->parsePath($path);
        } catch (InvalidArgumentException) {
            // PathValidator rejects traversal/null-byte payloads by throwing;
            // that's an invalid request (400), not a server error (500).
            abort(400);
        }

        abort_if($parsed === null, 404);

        $path = $parsed['path'];
        $params = $parsed['params'];
        $params['fm'] ??= $parsed['extension'];

        abort_if(config('glider.restrict_to_presets') && ! $presets->allows($params), 403);

        $server->setSource(Glider::getSourceFilesystem($path));
        $server->setCachePathCallable(fn (string $p, array $ps = []): string => Glider::getCachePath($p, $ps));
        $imagePath = Glider::getImagePath($path);

        try {
            if (! config('glider.on_the_fly', true) && ! $server->cacheFileExists($imagePath, $params)) {
                abort(404);
            }

            return $server->getImageResponse($imagePath, $params);
        } catch (FileNotFoundException | FilesystemException | \League\Flysystem\FilesystemException) {
            // Glide's exceptions cover local misses; Flysystem's interface covers the
            // HTTP adapter's UnableToReadFile on remote fetch failure (spec §8: 404, never 500).
            // Also covers a flaky cache disk failing cacheFileExists() above.
            throw new NotFoundHttpException;
        } catch (InvalidArgumentException) {
            abort(400);
        }
    }
}
