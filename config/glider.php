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

    'base_url' => env('GLIDER_BASE_URL', 'img'),

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
    | Accepts either form:
    | - A plain path string, e.g. resource_path('assets')
    | - A Laravel storage disk reference, e.g. ['disk' => 's3', 'prefix' => 'images']
    |   ('prefix' is optional and scopes the disk to a subdirectory)
    |
    | 'source', 'cache', and 'watermarks' below all accept both forms independently,
    | which supports two common deployment recipes:
    |
    | 1. S3 shared cache: 'source' and 'cache' both point at S3 disk references
    |    (e.g. ['disk' => 's3', 'prefix' => 'glider-cache']). A CI step runs
    |    `php artisan glider:build` once against the shared bucket; every
    |    application server then reads from the same warm cache instead of each
    |    one processing images independently.
    |
    | 2. Baked into the release artifact: 'cache' points at a plain local path
    |    (e.g. public_path('glider-cache')) that is populated by `glider:build`
    |    during the build step and shipped as part of the deployed artifact, so
    |    the cache is present on disk the moment the release goes live.
    |
    */

    'source' => env('GLIDER_SOURCE_PATH', resource_path('assets')),

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
    | Usage: <x-glider-img src="image.jpg" glide-mark="logo.png" />
    |
    | Accepts either form:
    | - A plain path string, e.g. resource_path('assets/watermarks')
    | - A Laravel storage disk reference, e.g. ['disk' => 's3', 'prefix' => 'watermarks']
    |   ('prefix' is optional and scopes the disk to a subdirectory)
    |
    | Like 'source' and 'cache' above, this can be a disk reference so watermark
    | assets are available identically under either deployment recipe (S3 shared
    | cache or baked-into-artifact).
    |
    */

    'watermarks' => env('GLIDER_WATERMARKS_PATH', resource_path('assets/watermarks')),

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
    | You can clear the cache using: php artisan glider:clear
    |
    | Accepts either form:
    | - A plain path string, e.g. storage_path('app/glider-cache')
    | - A Laravel storage disk reference, e.g. ['disk' => 's3', 'prefix' => 'glider-cache']
    |   ('prefix' is optional and scopes the disk to a subdirectory; note that the
    |   directory auto-creation and .gitignore behavior above only apply to the
    |   plain path form)
    |
    | This setting is the pivot point for both deployment recipes described under
    | 'source' above: an S3 disk reference here gives every server a shared,
    | pre-warmed cache; a plain local path (e.g. public_path('glider-cache'))
    | lets `glider:build` bake the cache directly into the release artifact.
    |
    */

    'cache' => env('GLIDER_CACHE_PATH', storage_path('app/glider-cache')),

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

    'cache_with_file_extensions' => env('GLIDER_CACHE_WITH_EXTENSIONS', false),

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
    | WARNING: NEVER set 'secure' to false in production environments!
    | Disabling URL signing exposes your application to Denial of Service
    | attacks through unlimited image generation. Only disable for local
    | development if absolutely necessary.
    |
    | Signing Key: The key used to sign URLs. Defaults to your APP_KEY for
    | security. You can set a custom key via GLIDER_SIGN_KEY environment variable.
    |
    */

    'secure'   => env('GLIDER_SECURE', true),
    'sign_key' => env('GLIDER_SIGN_KEY', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Three-Layer Security Model
    |--------------------------------------------------------------------------
    |
    | Glider protects your server from abuse and denial-of-service attacks
    | through three independent, stackable layers:
    |
    | 1. URL Signing ('secure' above): every URL must carry a valid HMAC
    |    signature, so only your application (which knows the sign_key) can
    |    mint valid image URLs. This is the primary defense and should stay
    |    enabled in production.
    |
    | 2. On-the-Fly Kill Switch ('on_the_fly' below): once you've warmed the
    |    cache for the conversions you actually use (e.g. by running
    |    `php artisan glider:build` in CI as part of your deploy pipeline),
    |    you can disable on-the-fly generation entirely. A request whose
    |    conversion is already cached is served from cache as normal; a
    |    request for anything not already cached returns a 404 instead of
    |    invoking the image processor. Combined with `glider:build` in CI,
    |    this means production does zero request-time image processing.
    |
    | 3. Presets-Only Mode ('restrict_to_presets' below): restricts allowed
    |    manipulations to the named presets configured in 'presets' above
    |    (plus defaults-only requests), collapsing the parameter space down
    |    to (number of images) x (number of presets). Any request whose
    |    parameters don't exactly match a configured preset is rejected with
    |    a 403, closing off the arbitrary-parameter attack surface even when
    |    signed URLs are otherwise trusted (e.g. user-supplied or third-party
    |    URLs).
    |
    |    IMPORTANT: this mode does not mean "only presets are ever allowed" —
    |    format-only conversions (the 'fm' parameter, as set implicitly via the
    |    URL's file extension) are still permitted even when the requested
    |    params don't match any preset. The parameter space stays finite
    |    because the route itself whitelists which extensions are accepted
    |    (jpg, pjpg, png, gif, webp, avif, tiff) — so presets-only mode should
    |    be described as "images x presets x whitelisted-extensions", not as
    |    an absolute "presets only" guarantee.
    |
    | These layers compose: you can run signed URLs only, signed + presets-only,
    | on-the-fly disabled after a cache-warming step, or any combination.
    |
    */

    // Disable to serve only pre-cached conversions; new (uncached) requests 404.
    'on_the_fly' => env('GLIDER_ON_THE_FLY', true),

    // Enable to allow only defaults-only requests, exact preset expansions, or
    // format-only ('fm') conversions; everything else 403s.
    'restrict_to_presets' => env('GLIDER_RESTRICT_TO_PRESETS', false),

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
    'group_cache_in_folders' => env('GLIDER_GROUP_CACHE', true),

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

    'max_image_size' => env('GLIDER_MAX_IMAGE_SIZE', 2000 * 2000),

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

    'driver' => env('GLIDER_IMAGE_MANIPULATION_DRIVER', 'gd'),

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
    | - 'strip' => true    : (Glide 4.1+) Strip EXIF/metadata from output images. This
    |                        removes camera info, GPS coordinates, and other embedded
    |                        metadata from generated images, which can reduce file size
    |                        and avoid leaking potentially sensitive data (e.g. GPS
    |                        location) baked into uploaded originals. Left commented out
    |                        below — Glide's own default behavior (metadata preserved) is
    |                        unchanged unless you opt in.
    |
    | Default: ['fm' => 'webp'] - converts all images to WebP format
    |
    */

    'defaults' => [
        'fm' => env('GLIDER_DEFAULT_FORMAT', 'webp'),
        'q'  => env('GLIDER_DEFAULT_QUALITY', 85),
        // 'strip' => true, // Uncomment to strip EXIF/metadata from all generated images.
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
    | <x-glider-img src="image.jpg" glide-preset="thumbnail" />
    | <x-glider-img src="image.jpg" glide-preset="hero" />
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
    | <x-glider-bg> component. Each preset contains breakpoint definitions
    | with their corresponding image manipulation parameters.
    |
    | Usage: <x-glider-bg src="hero.jpg" preset="hero" />
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
    | Build (glider:build) Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the `php artisan glider:build` command, which scans
    | your Blade templates for statically-discoverable glider usages
    | (`<x-glider-img>`, `<x-glider-bg>`, and `Glider::url()` facade calls)
    | and generates their cached conversions ahead of time, so the first real
    | request for each one is already a cache hit. This is what makes the
    | on-the-fly kill switch above practical in production: run
    | `glider:build` as a step in your CI/deploy pipeline, then set
    | 'on_the_fly' => false so production only ever serves pre-warmed cache
    | entries (see the Three-Layer Security Model note above `on_the_fly`).
    |
    | 'paths': the directories `glider:build` scans for templates. Only
    | usages with a statically-resolvable `src` (a literal string, not a
    | variable or expression) can be discovered this way; dynamic usages are
    | reported as skipped rather than silently ignored. Run with `--dry-run`
    | to preview what would be generated without writing anything to cache.
    |
    | Default: [resource_path('views')]
    |
    */

    'build' => [
        'paths' => [resource_path('views')],
    ],
];
