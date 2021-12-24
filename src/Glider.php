<?php

namespace Daikazu\LaravelGlider;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use League\Glide\Urls\UrlBuilderFactory;

class Glider
{
    public function url($path, string|array $params = [], $absolute = true)
    {
        if (is_string($params)) {
            parse_str($params, $params);
        }

        $path_params = Str::after($path, '?');

        if ($path_params !== $path) {

            parse_str($path_params, $additional_params);

            $params = array_merge($params, $additional_params);
        }

        $urlBuilder = UrlBuilderFactory::create('/img/', Config::get('glider.sign_key'));

        if (!$absolute) {
            return $urlBuilder->getUrl($path, $params);
        }

        return url('/').$urlBuilder->getUrl($path, $params);
    }


    public function backgroundClass($class_name, $src)
    {
        return view('glider::_background_image_class')->with(['src'=> $src, 'class_name' => $class_name] );
    }


}
