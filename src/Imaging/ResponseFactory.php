<?php

namespace Daikazu\LaravelGlider\Imaging;


use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\ResponseFactoryInterface;
use League\Glide\Responses\SymfonyResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseFactory extends SymfonyResponseFactory
{


    /**
     * Create the response.
     *
     * @param  FilesystemOperator  $cache  Cache file system.
     * @param  string  $path  Cached file path.
     * @return StreamedResponse The response object.
     * @throws \League\Flysystem\FilesystemException
     */
    public function create(FilesystemOperator $cache, $path): StreamedResponse
    {

        $stream = $cache->readStream($path);
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $cache->mimeType($path));
        $response->headers->set('Content-Length', $cache->fileSize($path));
        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setExpires(date_create()->modify('+1 years'));



        if ($this->request) {
            $response->setLastModified(date_create()->setTimestamp($cache->lastModified($path)));
            $response->isNotModified($this->request);
        }

        $response->setCallback(function () use ($stream) {
            if (ftell($stream) !== 0) {
                rewind($stream);
            }
            fpassthru($stream);
            fclose($stream);
        });

        return $response;
    }

}
