<?php

/*
 * Configuration for Laravel Glider
 *
 * This file contains all configuration options for the Laravel Glider package,
 * which provides on-the-fly image manipulation using League/Glide.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Base URL for Image Routes
    |--------------------------------------------------------------------------
    |
    | This setting controls the base URL prefix for all image manipulation routes.
    | Images will be served from URLs like: /img/encoded_path/encoded_params.ext
    |
    | Default: 'img'
    | Example URLs:
    |   - /img/base64_path/base64_params.jpg
    |   - /my-images/base64_path/base64_params.webp (if set to 'my-images')
    |
    */

    'base_url' => env('GLIDE_BASE_URL', 'img'),

    /*
    |--------------------------------------------------------------------------
    | Source Filesystem
    |--------------------------------------------------------------------------
    |
    | The filesystem path where your original images are stored. This directory
    | will be used to read the source images for manipulation.
    |
    | Default: resource_path('assets') => /resources/assets/
    |
    | You can also use other Laravel storage disks:
    | - storage_path('app/images')
    | - public_path('images')
    | - '/var/www/uploads'
    |
    */

    'source' => env('GLIDE_SOURCE_PATH', resource_path('assets')),

    /*
    |--------------------------------------------------------------------------
    | Watermarks Filesystem
    |--------------------------------------------------------------------------
    |
    | The filesystem path where your watermark images are stored. These images
    | can be applied to other images using the 'mark' parameter.
    |
    | Default: resource_path('assets/watermarks')
    |
    | Usage: <x-glide-img src="image.jpg" glide-mark="logo.png" />
    |
    */

    'watermarks' => env('GLIDE_WATERMARKS_PATH', resource_path('assets/watermarks')),

    /*
    |--------------------------------------------------------------------------
    | Cache Filesystem
    |--------------------------------------------------------------------------
    |
    | The filesystem path where processed/manipulated images will be cached.
    | This improves performance by avoiding re-processing the same image
    | with identical parameters.
    |
    | Default: storage_path('app/glider-cache')
    |
    | Note: This directory will be created automatically if it doesn't exist,
    | and a .gitignore file will be added to prevent committing cached images.
    | You can clear the cache using: php artisan glide:clear-cache
    |
    */

    'cache' => env('GLIDE_CACHE_PATH', storage_path('app/glider-cache')),

    /*
    |--------------------------------------------------------------------------
    | Cache with File Extensions
    |--------------------------------------------------------------------------
    |
    | Whether to include the file extension in the cached filename. When set to
    | true, cached files will include extensions like .jpg, .png, etc.
    | When false, cached files will have no extension.
    |
    | Default: false
    |
    | true:  cache/abc123.jpg, cache/def456.png
    | false: cache/abc123, cache/def456
    |
    */

    'cache_with_file_extensions' => env('GLIDE_CACHE_WITH_EXTENSIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Maximum Image Size
    |--------------------------------------------------------------------------
    |
    | Limit how large images can be generated to prevent excessive memory usage
    | and server load. This setting defines the maximum allowed total image
    | size in pixels (width × height).
    |
    | Default: 2000 × 2000 = 4,000,000 pixels
    |
    | Examples:
    | - 2000 × 2000 = 4MP (suitable for most web applications)
    | - 4000 × 4000 = 16MP (for high-resolution applications)
    | - 1000 × 1000 = 1MP (for performance-critical applications)
    |
    */

    'max_image_size' => env('GLIDE_MAX_IMAGE_SIZE', 2000 * 2000),

    /*
    |--------------------------------------------------------------------------
    | Image Manipulation Driver
    |--------------------------------------------------------------------------
    |
    | The image processing driver to use for manipulations. Each driver has
    | different capabilities and performance characteristics.
    |
    | Supported drivers:
    | - 'gd': PHP's built-in GD extension (widely available)
    | - 'imagick': ImageMagick extension (more features, better quality)
    |
    | Default: 'gd'
    |
    | Note: Ensure the chosen driver is installed on your server.
    | Check with: php -m | grep -E '(gd|imagick)'
    |
    */

    'driver' => env('GLIDE_IMAGE_MANIPULATION_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Default Image Manipulation Parameters
    |--------------------------------------------------------------------------
    |
    | These parameters will be applied to all images unless overridden by
    | specific parameters in your Blade components or Facade calls.
    |
    | Common defaults:
    | - 'fm' => 'webp'     : Convert all images to WebP format for better compression
    | - 'q' => 85          : Set default quality to 85%
    |
    | Default: ['fm' => 'webp'] - converts all images to WebP format
    |
    */

    'defaults' => [
        'fm' => env('GLIDE_DEFAULT_FORMAT', 'webp'),
        'q'  => env('GLIDE_DEFAULT_QUALITY', 85),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Manipulation Presets
    |--------------------------------------------------------------------------
    |
    | Define common image manipulation configurations that can be referenced
    | by name in your Blade components. This promotes consistency and makes
    | it easier to maintain image sizes across your application.
    |
    | Usage in Blade:
    | <x-glide-img src="image.jpg" glide-preset="thumbnail" />
    | <x-glide-img src="image.jpg" glide-preset="hero" />
    |
    | Available parameters: any League/Glide parameter
    | - w, h: width and height
    | - fit: crop, contain, fill, stretch, max
    | - q: quality (1-100)
    | - fm: format (jpg, png, gif, webp, avif)
    | - blur, bri, con, gam, sharp: image effects
    | - filt: filters (greyscale, sepia)
    |
    */

    'presets' => [
        // Responsive breakpoint sizes (based on common CSS frameworks)
        'xs'  => ['w' => 320, 'q' => 85],  // Extra small devices
        'sm'  => ['w' => 480, 'q' => 85],  // Small devices
        'md'  => ['w' => 768, 'q' => 85],  // Medium devices
        'lg'  => ['w' => 1280, 'q' => 85], // Large devices
        'xl'  => ['w' => 1440, 'q' => 85], // Extra large devices
        '2xl' => ['w' => 1920, 'q' => 85], // 2X large devices

        // Common use case presets
        'thumbnail' => [
            'w'   => 150,
            'h'   => 150,
            'fit' => 'crop',
            'q'   => 90,
        ],

        'avatar' => [
            'w'   => 80,
            'h'   => 80,
            'fit' => 'crop',
            'q'   => 95,
        ],

        'card' => [
            'w'   => 400,
            'h'   => 250,
            'fit' => 'crop',
            'q'   => 85,
        ],

        'hero' => [
            'w'   => 1200,
            'h'   => 600,
            'fit' => 'crop',
            'q'   => 90,
        ],

        'gallery' => [
            'w'   => 800,
            'h'   => 600,
            'fit' => 'crop',
            'q'   => 85,
        ],

        // Specialty presets
        'low-quality' => [
            'w'    => 400,
            'q'    => 50,
            'blur' => 1,
        ],

        'high-quality' => [
            'q'     => 95,
            'fm'    => 'webp',
            'sharp' => 15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Background Image Presets
    |--------------------------------------------------------------------------
    |
    | Define responsive background image configurations for the
    | <x-glide-bg> component. Each preset contains breakpoint definitions
    | with their corresponding image manipulation parameters.
    |
    | Usage: <x-glide-bg src="hero.jpg" preset="hero" />
    |
    | Breakpoints can use named breakpoints (xs, sm, md, lg, xl) or
    | pixel values (320, 768, 1024, etc.)
    |
    */

    'background_presets' => [
        // Hero section backgrounds
        'hero' => [
            'xs' => ['w' => 768, 'h' => 400, 'fit' => 'crop', 'q' => 85],
            'md' => ['w' => 1024, 'h' => 500, 'fit' => 'crop', 'q' => 85],
            'lg' => ['w' => 1440, 'h' => 600, 'fit' => 'crop', 'q' => 90],
            'xl' => ['w' => 1920, 'h' => 700, 'fit' => 'crop', 'q' => 90],
        ],

        // Full-width banner backgrounds
        'banner' => [
            'xs' => ['w' => 768, 'h' => 200, 'fit' => 'crop', 'q' => 85],
            'sm' => ['w' => 1024, 'h' => 250, 'fit' => 'crop', 'q' => 85],
            'lg' => ['w' => 1440, 'h' => 300, 'fit' => 'crop', 'q' => 90],
            'xl' => ['w' => 1920, 'h' => 350, 'fit' => 'crop', 'q' => 90],
        ],

        // Card/section backgrounds
        'section' => [
            'xs' => ['w' => 480, 'h' => 300, 'fit' => 'crop', 'q' => 80],
            'md' => ['w' => 768, 'h' => 400, 'fit' => 'crop', 'q' => 85],
            'lg' => ['w' => 1200, 'h' => 500, 'fit' => 'crop', 'q' => 85],
        ],

        // Sidebar/aside backgrounds
        'sidebar' => [
            'xs' => ['w' => 320, 'h' => 200, 'fit' => 'crop', 'q' => 80],
            'md' => ['w' => 400, 'h' => 250, 'fit' => 'crop', 'q' => 85],
            'lg' => ['w' => 500, 'h' => 300, 'fit' => 'crop', 'q' => 85],
        ],

        // Portrait-oriented backgrounds
        'portrait' => [
            'xs' => ['w' => 400, 'h' => 600, 'fit' => 'crop', 'q' => 85],
            'md' => ['w' => 600, 'h' => 900, 'fit' => 'crop', 'q' => 85],
            'lg' => ['w' => 800, 'h' => 1200, 'fit' => 'crop', 'q' => 90],
        ],

        // Square/instagram-style backgrounds
        'square' => [
            'xs' => ['w' => 400, 'h' => 400, 'fit' => 'crop', 'q' => 85],
            'md' => ['w' => 600, 'h' => 600, 'fit' => 'crop', 'q' => 85],
            'lg' => ['w' => 800, 'h' => 800, 'fit' => 'crop', 'q' => 90],
        ],

        // Performance-optimized for above-the-fold content
        'above-fold' => [
            'xs' => ['w' => 768, 'h' => 400, 'fit' => 'crop', 'q' => 95],
            'md' => ['w' => 1024, 'h' => 500, 'fit' => 'crop', 'q' => 95],
            'lg' => ['w' => 1440, 'h' => 600, 'fit' => 'crop', 'q' => 95],
        ],

        // Low-quality placeholders for lazy loading
        'placeholder' => [
            'xs' => ['w' => 40, 'h' => 25, 'fit' => 'crop', 'q' => 10, 'blur' => 10],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security-related options for image processing.
    |
    | URL Signing: When enabled, all image URLs must be properly signed to
    | prevent unauthorized manipulation. This prevents users from creating
    | arbitrary image transformations that could overload your server.
    |
    | Signing Key: The key used to sign URLs. Defaults to your APP_KEY for
    | security. You can set a custom key via GLIDE_SIGN_KEY environment variable.
    |
    */

    'secure'   => env('GLIDE_SECURE', false),
    'sign_key' => env('GLIDE_SIGN_KEY', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Advanced Configuration
    |--------------------------------------------------------------------------
    |
    | Additional configuration options for advanced use cases and performance
    | optimization.
    |
    */

    // Group cached images in folders based on their hash for better file organization
    'group_cache_in_folders' => env('GLIDE_GROUP_CACHE', true),

    // Add and Glide custom server configuration here

];
