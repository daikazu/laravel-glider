<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
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

/**
 * Read-only Flysystem adapter that serves files over HTTP.
 *
 * Reads fetch the file body via GET; metadata (size, mime type, last
 * modified) is retrieved via HEAD. Every mutating operation throws the
 * matching Flysystem `UnableTo*` exception since remote HTTP sources
 * cannot be written to.
 */
final class HttpFilesystemAdapter implements FilesystemAdapter
{
    private const READ_ONLY_MESSAGE = 'HTTP source is read-only';

    public function __construct(
        private readonly string $baseUrl,
        private readonly Factory $http,
    ) {}

    public function fileExists(string $path): bool
    {
        try {
            $response = $this->http->head($this->url($path));

            if ($response->status() === 405) {
                $response = $this->http->get($this->url($path));
            }
        } catch (ConnectionException | RequestException $e) {
            throw UnableToCheckFileExistence::forLocation($path, $e);
        }

        return $response->successful();
    }

    public function directoryExists(string $path): bool
    {
        return false;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        throw new UnableToWriteFile(self::READ_ONLY_MESSAGE);
    }

    /**
     * @param  resource  $contents
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        throw new UnableToWriteFile(self::READ_ONLY_MESSAGE);
    }

    public function read(string $path): string
    {
        try {
            $response = $this->http->get($this->url($path));
        } catch (ConnectionException | RequestException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }

        if (! $response->successful()) {
            throw UnableToReadFile::fromLocation($path, "HTTP status {$response->status()}");
        }

        return $response->body();
    }

    /**
     * @return resource
     */
    public function readStream(string $path)
    {
        $contents = $this->read($path);

        $stream = fopen('php://temp', 'r+b');

        if ($stream === false) {
            throw UnableToReadFile::fromLocation($path, 'Unable to open temporary stream');
        }

        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        throw new UnableToDeleteFile(self::READ_ONLY_MESSAGE);
    }

    public function deleteDirectory(string $path): void
    {
        throw new UnableToDeleteDirectory(self::READ_ONLY_MESSAGE);
    }

    public function createDirectory(string $path, Config $config): void
    {
        throw new UnableToCreateDirectory(self::READ_ONLY_MESSAGE);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw new UnableToSetVisibility(self::READ_ONLY_MESSAGE);
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'HTTP source does not expose visibility metadata');
    }

    public function mimeType(string $path): FileAttributes
    {
        $response = $this->head($path, StorageAttributes::ATTRIBUTE_MIME_TYPE);

        $mimeType = $response->header('Content-Type');

        if ($mimeType === '') {
            throw UnableToRetrieveMetadata::mimeType($path, 'Content-Type header missing');
        }

        return new FileAttributes($path, mimeType: $mimeType);
    }

    public function lastModified(string $path): FileAttributes
    {
        $response = $this->head($path, StorageAttributes::ATTRIBUTE_LAST_MODIFIED);

        $header = $response->header('Last-Modified');

        if ($header === '') {
            throw UnableToRetrieveMetadata::lastModified($path, 'Last-Modified header missing');
        }

        $timestamp = strtotime($header);

        if ($timestamp === false) {
            throw UnableToRetrieveMetadata::lastModified($path, 'Invalid Last-Modified header');
        }

        return new FileAttributes($path, lastModified: $timestamp);
    }

    public function fileSize(string $path): FileAttributes
    {
        $response = $this->head($path, StorageAttributes::ATTRIBUTE_FILE_SIZE);

        $size = $response->header('Content-Length');

        if ($size === '') {
            throw UnableToRetrieveMetadata::fileSize($path, 'Content-Length header missing');
        }

        return new FileAttributes($path, fileSize: (int) $size);
    }

    /**
     * @return iterable<StorageAttributes>
     */
    public function listContents(string $path, bool $deep): iterable
    {
        return [];
    }

    public function move(string $source, string $destination, Config $config): void
    {
        throw new UnableToMoveFile(self::READ_ONLY_MESSAGE);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        throw new UnableToCopyFile(self::READ_ONLY_MESSAGE);
    }

    private function head(string $path, string $metadataType): Response
    {
        try {
            $response = $this->http->head($this->url($path));
        } catch (ConnectionException | RequestException $e) {
            throw UnableToRetrieveMetadata::create($path, $metadataType, $e->getMessage(), $e);
        }

        if (! $response->successful()) {
            throw UnableToRetrieveMetadata::create($path, $metadataType, "HTTP status {$response->status()}");
        }

        return $response;
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }
}
