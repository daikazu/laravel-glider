<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\HttpFilesystemAdapter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;

function adapter(): HttpFilesystemAdapter
{
    return new HttpFilesystemAdapter('https://example.com', app(Factory::class));
}

it('reads a remote file body', function () {
    Http::fake(['example.com/*' => Http::response('IMAGE-BYTES')]);
    expect(adapter()->read('photos/a.jpg'))->toBe('IMAGE-BYTES');
});

it('readStream returns a rewound resource', function () {
    Http::fake(['example.com/*' => Http::response('BYTES')]);
    $stream = adapter()->readStream('a.jpg');
    expect(stream_get_contents($stream))->toBe('BYTES');
    fclose($stream);
});

it('throws UnableToReadFile on HTTP error', function () {
    Http::fake(['example.com/*' => Http::response('', 404)]);
    adapter()->read('missing.jpg');
})->throws(UnableToReadFile::class);

it('reports metadata from HEAD headers', function () {
    Http::fake(['example.com/*' => Http::response('', 200, [
        'Content-Length' => '1234', 'Content-Type' => 'image/jpeg',
        'Last-Modified'  => 'Wed, 01 Jan 2025 00:00:00 GMT',
    ])]);
    expect(adapter()->fileSize('a.jpg')->fileSize())->toBe(1234)
        ->and(adapter()->mimeType('a.jpg')->mimeType())->toBe('image/jpeg')
        ->and(adapter()->lastModified('a.jpg')->lastModified())->toBe(1735689600);
});

it('is read-only', function () {
    adapter()->write('a.jpg', 'x', new Config);
})->throws(UnableToWriteFile::class);

it('fileExists uses HEAD and succeeds', function () {
    Http::fake(['example.com/*' => Http::response('', 200)]);
    expect(adapter()->fileExists('a.jpg'))->toBeTrue();
});

it('fileExists returns false when HEAD errors', function () {
    Http::fake(['example.com/*' => Http::response('', 404)]);
    expect(adapter()->fileExists('missing.jpg'))->toBeFalse();
});

it('fileExists retries as GET when HEAD is not allowed (405)', function () {
    Http::fake([
        'example.com/*' => Http::sequence()
            ->push('', 405)
            ->push('OK', 200),
    ]);
    expect(adapter()->fileExists('a.jpg'))->toBeTrue();
});

it('directoryExists always returns false', function () {
    expect(adapter()->directoryExists('anything'))->toBeFalse();
});

it('listContents yields nothing', function () {
    $items = iterator_to_array(adapter()->listContents('anything', true));
    expect($items)->toBe([]);
});

it('visibility throws UnableToRetrieveMetadata', function () {
    adapter()->visibility('a.jpg');
})->throws(UnableToRetrieveMetadata::class);

it('writeStream is read-only', function () {
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, 'x');
    rewind($stream);
    try {
        adapter()->writeStream('a.jpg', $stream, new Config);
    } finally {
        fclose($stream);
    }
})->throws(UnableToWriteFile::class);

it('delete is read-only', function () {
    adapter()->delete('a.jpg');
})->throws(UnableToDeleteFile::class);

it('deleteDirectory is read-only', function () {
    adapter()->deleteDirectory('dir');
})->throws(UnableToDeleteDirectory::class);

it('createDirectory is read-only', function () {
    adapter()->createDirectory('dir', new Config);
})->throws(UnableToCreateDirectory::class);

it('setVisibility is read-only', function () {
    adapter()->setVisibility('a.jpg', 'public');
})->throws(UnableToSetVisibility::class);

it('move is read-only', function () {
    adapter()->move('a.jpg', 'b.jpg', new Config);
})->throws(UnableToMoveFile::class);

it('copy is read-only', function () {
    adapter()->copy('a.jpg', 'b.jpg', new Config);
})->throws(UnableToCopyFile::class);

it('mutating methods report the read-only message', function () {
    try {
        adapter()->write('a.jpg', 'x', new Config);
        expect(false)->toBeTrue('Expected exception was not thrown');
    } catch (UnableToWriteFile $e) {
        expect($e->getMessage())->toBe('HTTP source is read-only');
    }
});

it('converts a connection failure on read() into UnableToReadFile', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    adapter()->read('a.jpg');
})->throws(UnableToReadFile::class);

it('converts a connection failure on fileSize() into UnableToRetrieveMetadata', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    adapter()->fileSize('a.jpg');
})->throws(UnableToRetrieveMetadata::class);

it('converts a connection failure on mimeType() into UnableToRetrieveMetadata', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    adapter()->mimeType('a.jpg');
})->throws(UnableToRetrieveMetadata::class);

it('converts a connection failure on lastModified() into UnableToRetrieveMetadata', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    adapter()->lastModified('a.jpg');
})->throws(UnableToRetrieveMetadata::class);

it('converts a connection failure on fileExists() into UnableToCheckFileExistence', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    adapter()->fileExists('a.jpg');
})->throws(UnableToCheckFileExistence::class);
