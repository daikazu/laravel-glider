<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Http\Controllers;

use Daikazu\LaravelGlider\Facades\Glide;
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
        $path = Glide::decodePath($encodedPath);
        $params = Glide::decodeParams($encodedParams);
        $params['fm'] ??= $extension;
        $sourceFilesystem = Glide::getSourceFilesystem($path);

        $server->setSource($sourceFilesystem);
        $server->setCachePathCallable(fn (string $path, array $params = []): string => Glide::getCachePath($path, $params));

        // For HTTP sources, extract just the filename since the adapter has the base URL
        $imagePath = Glide::getImagePath($path);

        try {
            return $server->getImageResponse($imagePath, $params);
        } catch (FileNotFoundException | FilesystemException) {
            throw new NotFoundHttpException;
        }
    }
}
