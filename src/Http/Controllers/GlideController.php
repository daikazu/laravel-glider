<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Http\Controllers;

use Daikazu\LaravelGlider\Facades\Glider;
use Illuminate\Http\Request;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Filesystem\FilesystemException;
use League\Glide\Server;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GlideController
{
    public function __invoke(Request $request, Server $server, string $encodedPath, string $encodedParams, string $extension): Response
    {
        $path = Glider::decodePath($encodedPath);
        $params = Glider::decodeParams($encodedParams);
        $params['fm'] ??= $extension;
        $sourceFilesystem = Glider::getSourceFilesystem($path);

        $server->setSource($sourceFilesystem);
        $server->setCachePathCallable(fn (string $path, array $params = []): string => Glider::getCachePath($path, $params));

        // For HTTP sources, extract just the filename since the adapter has the base URL
        $imagePath = Glider::getImagePath($path);

        try {
            return $server->getImageResponse($imagePath, $params);
        } catch (FileNotFoundException | FilesystemException) {
            throw new NotFoundHttpException;
        }
    }
}
