<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Facades\Glide;
use Daikazu\LaravelGlider\Http\Controllers\GlideController;
use Illuminate\Http\Request;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Filesystem\FilesystemException as GlideFilesystemException;
use League\Glide\Server;
use Mockery as m;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function makeGlideStub(string $decodedPath, array $decodedParams, Filesystem $filesystem, ?\Closure $onGetCachePath = null): object
{
    return new class($decodedPath, $decodedParams, $filesystem, $onGetCachePath)
    {
        public function __construct(
            private string $decodedPath,
            private array $decodedParams,
            private Filesystem $filesystem,
            private ?\Closure $onGetCachePath = null,
        ) {}

        public function decodePath(string $string): string
        {
            return $this->decodedPath;
        }

        public function decodeParams(string $string): array
        {
            return $this->decodedParams;
        }

        public function getSourceFilesystem(string $path): Filesystem
        {
            return $this->filesystem;
        }

        public function getCachePath(string $path, array $params = []): string
        {
            if ($this->onGetCachePath) {
                return ($this->onGetCachePath)($path, $params);
            }
            return 'some/cache/path';
        }

        public function getImagePath(string $path): string
        {
            return $path;
        }
    };
}

it('returns the server response and sets fm from extension when missing', function () {
    $controller = new GlideController;

    $encodedPath = 'ignored';
    $encodedParams = 'ignored';
    $extension = 'webp';

    $decodedPath = 'images/pic.jpg';
    $decodedParams = ['w' => 200]; // fm missing intentionally

    // Real Filesystem instance is fine for setSource type expectations
    $filesystem = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir()));

    // Swap the Glide facade with a simple stub object
    Glide::swap(makeGlideStub(
        $decodedPath,
        $decodedParams,
        $filesystem,
        function (string $path, array $params) use ($decodedPath, $extension) {
            expect($path)->toBe($decodedPath);
            expect(($params['fm'] ?? null))->toBe($extension);
            return 'some/cache/path';
        }
    ));

    // Mock the Server
    $server = m::mock(Server::class);

    // setSource should be called with our filesystem
    $server->shouldReceive('setSource')->once()->with($filesystem);

    // Capture and test the cache path callable
    $server->shouldReceive('setCachePathCallable')
        ->once()
        ->with(m::on(function ($callable) use ($decodedPath, $extension) {
            expect(is_callable($callable))->toBeTrue();
            // When we call it, it should return the value from Glide::getCachePath
            $result = $callable($decodedPath, ['w' => 200, 'fm' => $extension]);
            expect($result)->toBe('some/cache/path');
            return true;
        }));

    // getImageResponse should be called with the decoded path and params where fm is added
    $expectedResponse = new Response('ok', 200, ['Content-Type' => 'text/plain']);
    $server->shouldReceive('getImageResponse')
        ->once()
        ->with($decodedPath, ['w' => 200, 'fm' => $extension])
        ->andReturn($expectedResponse);

    $request = Request::create('/');

    $response = $controller($request, $server, $encodedPath, $encodedParams, $extension);

    expect($response)->toBe($expectedResponse);
});

it('does not override fm when provided in params', function () {
    $controller = new GlideController;

    $encodedPath = 'ignored2';
    $encodedParams = 'ignored2';
    $extension = 'jpg';

    $decodedPath = 'images/photo.png';
    $decodedParams = ['fm' => 'png', 'h' => 300];

    $filesystem = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir()));

    Glide::swap(makeGlideStub(
        $decodedPath,
        $decodedParams,
        $filesystem,
        fn (string $path, array $params) => 'cache/other'
    ));

    $server = m::mock(Server::class);
    $server->shouldReceive('setSource')->once()->with($filesystem);
    $server->shouldReceive('setCachePathCallable')->once()->with(m::type('callable'));

    $expectedResponse = new Response('image-bytes', 200, ['Content-Type' => 'image/png']);
    $server->shouldReceive('getImageResponse')
        ->once()
        ->with($decodedPath, ['fm' => 'png', 'h' => 300])
        ->andReturn($expectedResponse);

    $request = Request::create('/');

    $response = $controller($request, $server, $encodedPath, $encodedParams, $extension);

    expect($response)->toBe($expectedResponse);
});

it('throws NotFoundHttpException when Server throws FileNotFoundException', function () {
    $controller = new GlideController;

    $encodedPath = 'p';
    $encodedParams = 'q';
    $extension = 'jpg';

    $decodedPath = 'not/existing.jpg';
    $decodedParams = [];

    $filesystem = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir()));

    Glide::swap(makeGlideStub(
        $decodedPath,
        $decodedParams,
        $filesystem
    ));

    $server = m::mock(Server::class);
    $server->shouldReceive('setSource')->once()->with($filesystem);
    $server->shouldReceive('setCachePathCallable')->once()->with(m::type('callable'));

    $server->shouldReceive('getImageResponse')
        ->once()
        ->with($decodedPath, ['fm' => $extension])
        ->andThrow(new FileNotFoundException('missing'));

    $request = Request::create('/');

    expect(fn () => $controller($request, $server, $encodedPath, $encodedParams, $extension))
        ->toThrow(NotFoundHttpException::class);
});

it('throws NotFoundHttpException when Server throws FilesystemException', function () {
    $controller = new GlideController;

    $encodedPath = 'p2';
    $encodedParams = 'q2';
    $extension = 'png';

    $decodedPath = 'erroring/image.png';
    $decodedParams = [];

    $filesystem = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir()));

    Glide::swap(makeGlideStub(
        $decodedPath,
        $decodedParams,
        $filesystem
    ));

    $server = m::mock(Server::class);
    $server->shouldReceive('setSource')->once()->with($filesystem);
    $server->shouldReceive('setCachePathCallable')->once()->with(m::type('callable'));

    $server->shouldReceive('getImageResponse')
        ->once()
        ->with($decodedPath, ['fm' => $extension])
        ->andThrow(new GlideFilesystemException('fs error'));

    $request = Request::create('/');

    expect(fn () => $controller($request, $server, $encodedPath, $encodedParams, $extension))
        ->toThrow(NotFoundHttpException::class);
});
