<?php

namespace Daikazu\LaravelGlider\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use League\Glide\Server;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureFactory;


class GlideController extends Controller
{
    private $server;
    private $request;

    public function __construct(Server $server, Request $request)
    {

        $this->server = $server;
        $this->request = $request;
    }


    public function show()
    {

        $this->validateSignature();

        return $this->server->getImageResponse($this->request->path(), $this->request->all());

    }


    private function validateSignature()
    {
        if (!Config::get('glider.secure')) {
            return;
        }

        $path = Str::after($this->request->url(), url('/'));

        try {
            SignatureFactory::create(Config::get('glider.sign_key'))->validateRequest($path,
                $this->request->query->all());
        } catch (SignatureException $e) {
            abort(400, $e->getMessage());
        }

    }







//    private function generateBy($type, $item)
//    {
//        $method = 'generateBy'.ucfirst($type);
//
//        try {
//            return $this->generator->$method($item, $this->request->all());
//        } catch (FileNotFoundException $e) {
////            throw new NotFoundHttpException;
//        }
//    }

//    public function generateByPath($path)
//    {
//        $this->validateSignature();
//
//        // If the auto crop setting is enabled, we will attempt to resolve an asset from the
//        // given path in order to get its focal point. A little overhead for convenience.
//        if (Config::get('glider.auto_crop')) {
////            if ($asset = Asset::find(Str::ensureLeft($path, '/'))) {
////                return $this->createResponse($this->generateBy('asset', $asset));
////            }
//        }
//
//        return $this->createResponse($this->generateBy('path', $path));
//    }

//    public function generateByUrl($url)
//    {
//        $this->validateSignature();
//
//        $url = base64_decode($url);
//
//        return $this->createResponse($this->generateBy('url', $url));
//    }


//    public function generateByAsset($encoded)
//    {
//        $this->validateSignature();
//
//        $decoded = base64_decode($encoded);
//
//        // The string before the first slash is the container
//        [$container, $path] = explode('/', $decoded, 2);
//
//        $asset = AssetContainer::find($container)->asset($path);
//
//        throw_unless($asset, new NotFoundHttpException);
//
//        return $this->createResponse($this->generateBy('asset', $asset));
//    }


}
