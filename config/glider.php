<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Prefixes
    |--------------------------------------------------------------------------
    |
    | The route prefix for serving HTTP based manipulated images through Glide.
    |     |
    */

    'source'             => resource_path(),
    'source_path_prefix' => 'assets',


    'watermarks'             => resource_path(),
    'watermarks_path_prefix' => 'assets/watermarks',


    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The route prefix for serving HTTP based manipulated images through Glide.
    |
    */

    'route' => 'img',

    /*
    |--------------------------------------------------------------------------
    | Require Glide security token
    |--------------------------------------------------------------------------
    |
    | With this option enabled, you are protecting your website from mass image
    | resize attacks. You will need to generate tokens using Glider::url('image.jpg')
    | but may want to disable this while in development to tinker.
    |
    */

    'secure'   => true,
    'sign_key' => env('GLIDE_SIGN_KEY', 'MY_SUPER_SECURE_SIGN_KEY'),


    /*
    |--------------------------------------------------------------------------
    | Image Manipulation Driver
    |--------------------------------------------------------------------------
    |
    | The driver that will be used under the hood for image manipulation.
    | Supported: "gd" or "imagick" (if installed on your server)
    |
    */

    'driver' => env('IMAGE_MANIPULATION_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Save Cached Images
    |--------------------------------------------------------------------------
    |
    | Enabling this will make Glide save publicly accessible images. It will
    | increase performance at the cost of the dynamic nature of HTTP based
    | image manipulation. You will need to invalidate images manually.
    |
    */

    'cache'             => env('SAVE_CACHED_IMAGES', true),
    'cache_path_prefix' => '.cache',
    'cache_path'        => storage_path('app/glide'), //default caache location

    /*
    |--------------------------------------------------------------------------
    | Image Manipulation Presets
    |--------------------------------------------------------------------------
    |
    | Rather than specifying your manipulation params in your templates with
    | the glide tag, you may define them here and reference their handles.
    | They will also be automatically generated when you upload assets.
    |
    */

    'presets' => [
        'xs-webp'  => ['w' => 320, 'h' => 10000, 'q' => 85, 'fit' => 'contain', 'fm' => 'webp'],
        'sm-webp'  => ['w' => 480, 'h' => 10000, 'q' => 85, 'fit' => 'contain', 'fm' => 'webp'],
        'md-webp'  => ['w' => 768, 'h' => 10000, 'q' => 85, 'fit' => 'contain', 'fm' => 'webp'],
        'lg-webp'  => ['w' => 1280, 'h' => 10000, 'q' => 85, 'fit' => 'contain', 'fm' => 'webp'],
        'xl-webp'  => ['w' => 1440, 'h' => 10000, 'q' => 95, 'fit' => 'contain', 'fm' => 'webp'],
        '2xl-webp' => ['w' => 1680, 'h' => 10000, 'q' => 95, 'fit' => 'contain', 'fm' => 'webp'],
        'xs'       => ['w' => 320, 'h' => 10000, 'q' => 85, 'fit' => 'contain'],
        'sm'       => ['w' => 480, 'h' => 10000, 'q' => 85, 'fit' => 'contain'],
        'md'       => ['w' => 768, 'h' => 10000, 'q' => 85, 'fit' => 'contain'],
        'lg'       => ['w' => 1280, 'h' => 10000, 'q' => 85, 'fit' => 'contain'],
        'xl'       => ['w' => 1440, 'h' => 10000, 'q' => 95, 'fit' => 'contain'],
        '2xl'      => ['w' => 1680, 'h' => 10000, 'q' => 95, 'fit' => 'contain'],
    ],

];
