<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Factories;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Http\Request;
use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseFactory implements ResponseFactoryInterface
{
    private const int DEFAULT_MAX_AGE = 31_536_000; // 1 year in seconds
    private const string ONE_YEAR_INTERVAL = 'P1Y';

    public function __construct(
        protected ResponseFactoryContract $responseFactory,
        protected ?Request $request = null,
    ) {}

    public function create(FilesystemOperator $filesystem, string $filePath): StreamedResponse
    {
        $stream = $filesystem->readStream($filePath);

        $mimeType = $filesystem->mimeType($filePath);
        $contentLength = (string) $filesystem->fileSize($filePath);

        // Prepare common cache headers
        $expires = (new DateTimeImmutable)->add(new DateInterval(self::ONE_YEAR_INTERVAL));

        // Use a provisional response to leverage Symfony's conditional request handling.
        $probe = new StreamedResponse;
        $probe->headers->set('Content-Type', $mimeType);
        $probe->headers->set('Content-Length', $contentLength);
        $probe->setPublic();
        $probe->setMaxAge(self::DEFAULT_MAX_AGE);
        $probe->setExpires($expires);

        $lastModified = null;
        if ($this->request instanceof Request) {
            $lastModified = (new DateTimeImmutable)->setTimestamp($filesystem->lastModified($filePath));
            $probe->setLastModified($lastModified);

            if ($probe->isNotModified($this->request)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }

                // Return the 304 Not Modified response
                return $probe;
            }
        }

        // Build the actual streamed response using Laravel's ResponseFactory
        $response = $this->responseFactory->stream(function () use ($stream): void {
            $this->outputStream($stream);
        }, 200, [
            'Content-Type'   => $mimeType,
            'Content-Length' => $contentLength,
        ]);

        // Apply cache headers to the streamed response
        $response->setPublic();
        $response->setMaxAge(self::DEFAULT_MAX_AGE);
        $response->setExpires($expires);

        if ($lastModified !== null) {
            $response->setLastModified($lastModified);
        }

        return $response;
    }

    /**
     * @param  resource  $stream
     */
    private function outputStream($stream): void
    {
        if (is_resource($stream)) {
            if (ftell($stream) !== 0) {
                rewind($stream);
            }
            fpassthru($stream);
            fclose($stream);
        }
    }
}
