<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Factories\ResponseFactory;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Http\Request;
use League\Flysystem\FilesystemOperator;
use Mockery as m;
use Symfony\Component\HttpFoundation\StreamedResponse;

function makeStreamedResponse(callable $callback, int $status = 200, array $headers = []): StreamedResponse
{
    // Mimic Laravel's ResponseFactory->stream behavior by returning a Symfony StreamedResponse
    return new StreamedResponse($callback, $status, $headers);
}

it('creates a 200 streamed response with correct headers and outputs the stream (rewinds and closes)', function () {
    $content = 'hello world';
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $content);
    // Move pointer to end to ensure rewind happens inside outputStream
    fseek($stream, 3);

    $filesystem = m::mock(FilesystemOperator::class);
    $filesystem->shouldReceive('readStream')->once()->with('path/to/file.jpg')->andReturn($stream);
    $filesystem->shouldReceive('mimeType')->once()->with('path/to/file.jpg')->andReturn('image/jpeg');
    $filesystem->shouldReceive('fileSize')->once()->with('path/to/file.jpg')->andReturn(strlen($content));

    $responseFactory = m::mock(ResponseFactoryContract::class);
    $responseFactory->shouldReceive('stream')
        ->once()
        ->andReturnUsing(function (callable $callback, int $status, array $headers) {
            expect($status)->toBe(200);
            // Return a real StreamedResponse so we can execute the callback
            return makeStreamedResponse($callback, $status, $headers);
        });

    $factory = new ResponseFactory($responseFactory);

    $response = $factory->create($filesystem, 'path/to/file.jpg');

    // Assert basic headers from creation
    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('image/jpeg');
    expect($response->headers->get('Content-Length'))->toBe((string) strlen($content));

    // Cache headers
    expect($response->headers->get('cache-control'))->toContain('public');
    expect($response->headers->get('cache-control'))->toContain('max-age=31536000');
    expect($response->getExpires())->not()->toBeNull();

    // Execute streaming and ensure it rewinds and closes the resource
    ob_start();
    $response->sendContent();
    $output = ob_get_clean();

    expect($output)->toBe($content);
    expect(is_resource($stream))->toBeFalse();
});

it('includes Last-Modified when a Request is provided and content is modified', function () {
    $content = 'xyz';
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $content);
    rewind($stream);

    $lastModifiedTs = time() - 60; // 1 minute ago

    $filesystem = m::mock(FilesystemOperator::class);
    $filesystem->shouldReceive('readStream')->once()->andReturn($stream);
    $filesystem->shouldReceive('mimeType')->once()->andReturn('text/plain');
    $filesystem->shouldReceive('fileSize')->once()->andReturn(strlen($content));
    $filesystem->shouldReceive('lastModified')->once()->andReturn($lastModifiedTs);

    $responseFactory = m::mock(ResponseFactoryContract::class);
    $responseFactory->shouldReceive('stream')->once()->andReturnUsing(function (callable $callback, int $status, array $headers) {
        return makeStreamedResponse($callback, $status, $headers);
    });

    // Provide a request with If-Modified-Since older than last modified to force 200
    $request = Request::create('/test');
    $request->headers->set('If-Modified-Since', gmdate('D, d M Y H:i:s', $lastModifiedTs - 120) . ' GMT');

    $factory = new ResponseFactory($responseFactory, $request);

    $response = $factory->create($filesystem, 'file.txt');
    expect($response->getStatusCode())->toBe(200)
        ->and($response->getLastModified())->not()->toBeNull();
});

it('returns 304 Not Modified and closes stream when request indicates not modified', function () {
    $content = 'image-bytes';
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $content);
    rewind($stream);

    $lastModifiedTs = time() - 3600; // 1 hour ago

    $filesystem = m::mock(FilesystemOperator::class);
    $filesystem->shouldReceive('readStream')->once()->andReturn($stream);
    $filesystem->shouldReceive('mimeType')->once()->andReturn('image/png');
    $filesystem->shouldReceive('fileSize')->once()->andReturn(strlen($content));
    $filesystem->shouldReceive('lastModified')->once()->andReturn($lastModifiedTs);

    // ResponseFactoryContract->stream should NOT be called when 304 branch is taken
    $responseFactory = m::mock(ResponseFactoryContract::class);
    $responseFactory->shouldNotReceive('stream');

    // If-Modified-Since later than last modified => not modified
    $request = Request::create('/img');
    $request->headers->set('If-Modified-Since', gmdate('D, d M Y H:i:s', $lastModifiedTs + 120) . ' GMT');

    $factory = new ResponseFactory($responseFactory, $request);

    $response = $factory->create($filesystem, 'image.png');

    expect($response->getStatusCode())->toBe(304);
    // Symfony strips entity headers on 304 responses
    expect($response->headers->get('Content-Type'))->toBeNull()
        ->and($response->headers->get('Content-Length'))->toBeNull();
    // Cache headers should still be present
    expect($response->headers->get('cache-control'))->toContain('public')
        ->and($response->headers->get('cache-control'))->toContain('max-age=31536000')
        ->and($response->getExpires())->not()->toBeNull();

    // Stream should be closed because factory closes it on 304 path
    expect(is_resource($stream))->toBeFalse();
});
