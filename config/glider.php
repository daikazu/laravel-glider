<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Prefixes and Sources
    |--------------------------------------------------------------------------
    |
    | The route prefix for serving HTTP based manipulated images through Glide.
    |
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
    | Image size limit
    |--------------------------------------------------------------------------
    |
    | Limit how large images can be generated. The following setting will set
    | the maximum allowed total image size, in pixels.
    |
    */

    'max_image_size' => env('GLIDE_MAX_IMAGE_SIZE', 2000 * 2000),


    /*
    |--------------------------------------------------------------------------
    | Require Glide security token
    |--------------------------------------------------------------------------
    |
    | With this option enabled, you are protecting your website from mass image
    | resize attacks. We recommend using a 128 character (or larger) signing
    | key to prevent trivial key attacks but may want to disable this while
    | in development to tinker.
    |
    */

    'secure'   => env('GLIDE_SECURE', true),
    'sign_key' => env('GLIDE_SIGN_KEY',
        'xa9uSX5l1HCR7F/2ywqLU66NZtp0q+dDR/x3c53saR935zBfUpFgv15kJF1rJGkk4VNO/yKpKCDknDdwXyPZABW7VLGvVc2+8oyl1QOCYwbVtjn/tPCiCI8fNNnXJQRVP6QnHi/CB4ey7Vaaf/tPkZrPntZCGVFiP3utmvXBam8='),


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
    'cache_path'        => storage_path('app/glide'), //default cache location

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
    ]

];
