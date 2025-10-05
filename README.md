<picture>
   <source media="(prefers-color-scheme: dark)" srcset="art/header-dark.png">
   <img alt="Logo for Glider" src="art/header-light.png">
</picture>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daikazu/laravel-glider.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-glider)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laravel-glider/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daikazu/laravel-glider/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daikazu/laravel-glider.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-glider)

# Laravel Glider

Laravel Glider is a powerful Laravel package that provides on-the-fly image manipulation using [League/Glide](https://glide.thephpleague.com/). It offers elegant Blade components for generating responsive images with automatic srcset generation, URL signing for security, and seamless integration with Laravel's filesystem and caching layers.

## Features

- **On-the-fly Image Processing**: Transform images dynamically with URL parameters
- **Responsive Images**: Automatic srcset generation for optimal loading
- **Remote Image Support**: Process and cache images from external URLs
- **Security**: Signed URLs prevent unauthorized image manipulation
- **Performance**: Built-in caching with Laravel's cache system
- **Flexible Sources**: Support for local filesystem and remote HTTP sources
- **Blade Components**: Clean, reusable components for your templates
- **Presets**: Pre-defined image manipulation configurations
- **Artisan Commands**: Clear cache and convert existing image tags

## Installation

You can install the package via composer:

```bash
composer require daikazu/laravel-glider
```

The package will automatically register its service provider.

### Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="laravel-glider-config"
```

This will create a `config/laravel-glider.php` file. See file for more details.

Adjust the paths based on your `GLIDE_BASE_URL` and `GLIDE_CACHE_PATH` configuration.

**Note:** The cache directory is automatically created when the package boots, and a `.gitignore` file is added to prevent committing cached images to version control.

### Views (Optional)

You can publish and customize the Blade component views:

```bash
php artisan vendor:publish --tag="laravel-glider-views"
```

## Usage

### Basic Usage

Laravel Glider provides several ways to generate manipulated image URLs:

#### Using the Facade

```php
use Daikazu\LaravelGlider\Facades\Glide;

// Generate a URL for a resized image
$url = Glide::getUrl('path/to/image.jpg', ['w' => 400, 'h' => 300]);

// Generate a URL with quality and format settings
$url = Glide::getUrl('image.jpg', [
    'w' => 800,
    'q' => 85,
    'fm' => 'webp'
]);
```

#### Using Blade Components

The package provides convenient Blade components for generating image tags:

```html
{{-- Basic image with width and quality --}}
<x-glide-img 
    src="path/to/image.jpg" 
    glide-w="400" 
    glide-q="85" 
    alt="Description"
    class="rounded-lg" 
/>

{{-- Responsive image with multiple sizes using srcset --}}
<x-glide-bg-responsive
    src="hero-image.jpg"
    width="1200"
    loading="lazy"
    alt="Hero image"
/>
```

### Remote Images

Laravel Glider supports processing images from remote URLs. Remote images are automatically downloaded, processed, and cached locally:

```html
{{-- Process remote image with resizing --}}
<x-glide-img
    src="https://example.com/path/to/image.jpg"
    glide-w="600"
    glide-q="85"
    alt="Remote image"
/>

{{-- Remote image with format conversion --}}
<x-glide-img
    src="https://cdn.example.com/photo.png"
    glide-w="400"
    glide-fm="webp"
    alt="Converted to WebP"
/>
```

**How it works:**
- The image is fetched from the remote URL
- All Glide manipulations are applied (resize, format conversion, quality, etc.)
- The processed image is cached locally for better performance
- Subsequent requests serve the cached version

**Benefits:**
- Apply consistent optimization to all images, regardless of source
- Convert remote images to modern formats (WebP, AVIF)
- Resize and optimize external images to fit your design
- Reduce bandwidth by caching processed versions

**Note:** Even without explicit parameters, remote images will be processed using your configured defaults (e.g., `fm: webp`, `q: 85` from `config/laravel-glider.php`).

### Image Manipulation Parameters

Laravel Glider supports all [League/Glide manipulation parameters](https://glide.thephpleague.com/3.0/api/quick-reference/). Common parameters include:

#### Size and Cropping
```html
{{-- Resize to specific width --}}
<x-glide-img src="image.jpg" glide-w="400" />

{{-- Resize to specific height --}}
<x-glide-img src="image.jpg" glide-h="300" />

{{-- Resize with fit modes --}}
<x-glide-img src="image.jpg" glide-w="400" glide-h="300" glide-fit="crop" />
<x-glide-img src="image.jpg" glide-w="400" glide-h="300" glide-fit="contain" />
<x-glide-img src="image.jpg" glide-w="400" glide-h="300" glide-fit="fill" />
```

#### Quality and Format
```html
{{-- Set quality (1-100) --}}
<x-glide-img src="image.jpg" glide-w="400" glide-q="85" />

{{-- Convert format --}}
<x-glide-img src="image.jpg" glide-w="400" glide-fm="webp" />
<x-glide-img src="image.jpg" glide-w="400" glide-fm="avif" />
```

#### Effects and Filters
```html
{{-- Apply blur effect --}}
<x-glide-img src="image.jpg" glide-w="400" glide-blur="5" />

{{-- Apply brightness adjustment --}}
<x-glide-img src="image.jpg" glide-w="400" glide-bri="10" />

{{-- Apply contrast adjustment --}}
<x-glide-img src="image.jpg" glide-w="400" glide-con="15" />

{{-- Convert to grayscale --}}
<x-glide-img src="image.jpg" glide-w="400" glide-filt="greyscale" />
```

#### Focal Point (CSS Positioning)

Control where the image is positioned within its container using CSS `object-position` (for `<img>` tags) or `background-position` (for backgrounds).

This is useful when the container dimensions differ from the image aspect ratio - the focal point determines which part of the image remains visible:

```html
{{-- Named positions --}}
<x-glide-img src="portrait.jpg" focal-point="top" glide-w="400" glide-h="300" />
<x-glide-img src="person.jpg" focal-point="center" glide-w="400" glide-h="300" />
<x-glide-img src="photo.jpg" focal-point="bottom-right" glide-w="400" glide-h="300" />

{{-- Custom percentages (x,y from left/top) --}}
<x-glide-img src="image.jpg" focal-point="75,25" glide-w="400" glide-h="300" />
<x-glide-img src="image.jpg" focal-point="20, 80" glide-w="400" glide-h="300" />

{{-- Works with responsive images too --}}
<x-glide-bg-responsive src="photo.jpg" focal-point="top-left" />

{{-- And background images --}}
<x-glide-bg src="hero.jpg" focal-point="75,25" preset="hero">
    <h1>Hero Content</h1>
</x-glide-bg>
```

**Supported named positions:**
- `center` (default)
- `top`, `bottom`, `left`, `right`
- `top-left`, `top-right`, `bottom-left`, `bottom-right`

**How it works:**
- For `<img>` elements: Adds `object-fit: cover` and `object-position: X% Y%`
- For backgrounds: Sets `background-position: X% Y%` in the generated CSS
- Custom percentages: `"75,25"` = 75% from left, 25% from top (0-100 range)

**Note:** This is CSS-based positioning in the browser. For server-side crop focal point (where Glide physically crops the image before sending), use `glide-fit` with crop positions:

```html
{{-- Server-side crop with named position --}}
<x-glide-img src="image.jpg" glide-w="400" glide-h="300" glide-fit="crop-top-left" />

{{-- Server-side crop with focal point percentages --}}
<x-glide-img src="image.jpg" glide-w="400" glide-h="300" glide-fit="crop-25-75" />

{{-- Server-side crop with zoom (crop-x%-y%-zoom) --}}
<x-glide-img src="image.jpg" glide-w="400" glide-h="300" glide-fit="crop-25-75-2" />

{{-- CSS focal point (full image sent, browser positions it) --}}
<x-glide-img src="image.jpg" glide-w="400" glide-h="300" focal-point="75,25" />

{{-- Both: Server crops at focal point, then browser positions the result --}}
<x-glide-img src="image.jpg" glide-w="400" glide-h="300" glide-fit="crop-25-75" focal-point="top-right" />
```

### Using Presets

Define common image sizes in your configuration and reference them:

```php
// config/glider.php
'presets' => [
    'thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop'],
    'hero' => ['w' => 1200, 'h' => 600, 'fit' => 'crop', 'q' => 90],
    'card' => ['w' => 400, 'h' => 250, 'fit' => 'crop'],
],
```

```html
<x-glide-img src="image.jpg" glide-preset="thumbnail" />
```

### Responsive Images

The Blade components automatically calculate appropriate srcset values:

```html
<x-glide-bg-responsive
    src="large-image.jpg"
    glide-w="800"
    glide-q="85"
/>
```

This generates multiple image sizes for different screen densities and viewport sizes.


### Responsive Background Images

Laravel Glider provides a powerful `<x-glide-bg>` component for responsive background images that automatically generates CSS media queries:

```html
{{-- Basic responsive background with preset --}}
<x-glide-bg 
    src="hero-image.jpg" 
    preset="hero" 
    class="hero-section"
>
    <div class="hero-content">
        <h1>Welcome to Our Site</h1>
        <p>This content appears over the responsive background</p>
    </div>
</x-glide-bg>

{{-- Custom breakpoints --}}
<x-glide-bg 
    src="banner.jpg"
    :breakpoints="[
        'xs' => ['w' => 768, 'h' => 300, 'fit' => 'crop'],
        'md' => ['w' => 1024, 'h' => 400, 'fit' => 'crop'],
        'lg' => ['w' => 1440, 'h' => 500, 'fit' => 'crop']
    ]"
    position="center top"
    class="banner-section"
>
    <div class="banner-content">
        <h2>Responsive Banner</h2>
    </div>
</x-glide-bg>

{{-- With lazy loading --}}
<x-glide-bg 
    src="large-bg.jpg" 
    preset="section"
    lazy="true"
    fallback="placeholder.jpg"
    class="content-section"
>
    <div class="section-content">
        <!-- Your content here -->
    </div>
</x-glide-bg>
```

#### Background Component Attributes

- `src` - Background image source path (required)
- `preset` - Use predefined background preset from config
- `breakpoints` - Array of custom breakpoints and parameters
- `position` - CSS background-position (default: 'center')
- `size` - CSS background-size (default: 'cover')
- `repeat` - CSS background-repeat (default: 'no-repeat')
- `attachment` - CSS background-attachment (default: 'scroll')
- `fallback` - Fallback image for loading states
- `lazy` - Enable lazy loading with data attributes
- Any `glide-*` attributes for global parameters

### Using Presets for Backgrounds

Background presets are defined in your config file:

```php
// config/glider.php
'background_presets' => [
    'hero' => [
        'xs' => ['w' => 768, 'h' => 400, 'fit' => 'crop'],
        'md' => ['w' => 1024, 'h' => 500, 'fit' => 'crop'], 
        'lg' => ['w' => 1440, 'h' => 600, 'fit' => 'crop'],
        'xl' => ['w' => 1920, 'h' => 700, 'fit' => 'crop'],
    ],
    'banner' => [
        'xs' => ['w' => 768, 'h' => 200, 'fit' => 'crop'],
        'lg' => ['w' => 1440, 'h' => 300, 'fit' => 'crop'],
    ],
],
```

### Security

URLs are automatically signed to prevent unauthorized manipulation. The signing key is derived from your application key, ensuring secure image processing.

## Artisan Commands

Laravel Glider includes helpful Artisan commands:

### Clear Glide Cache

Clear all cached processed images:

```bash
php artisan glide:clear-cache
```

### Convert Image Tags (⚠️ WIP - USE AT YOUR OWN RISK - Run with --dry-run first)

Convert existing HTML img tags to use Glide components:

```bash
php artisan glider:convert-img-tags
```

## API Reference

### Blade Component Attributes

#### Image Components

The `<x-glide-img>` and `<x-glide-bg-responsive>` components accept all standard HTML img attributes plus:

##### Image-Specific Attributes
- `src` - Image source path (required)
- `focal-point` - Image focal point for cropping (see [Focal Point](#focal-point-image-positioning) section)
  - Named positions: `center`, `top`, `bottom`, `left`, `right`, `top-left`, `top-right`, `bottom-left`, `bottom-right`
  - Custom percentages: `"75,25"` (x,y from left/top, 0-100 range)
  - Applies CSS `object-fit: cover` and `object-position` automatically
- All Glide parameters (prefix with `glide-`)
- Any standard HTML `<img>` attributes

#### Background Component

The `<x-glide-bg>` component provides responsive background image functionality:

##### Background-Specific Attributes
- `src` - Background image source path (required)
- `preset` - Use predefined background preset from config
- `breakpoints` - Array of custom breakpoints and parameters
- `focal-point` - Background focal point for all breakpoints (see [Focal Point](#focal-point-image-positioning) section)
  - Named positions: `center`, `top`, `bottom`, `left`, `right`, etc.
  - Custom percentages: `"75,25"` (x,y from left/top, 0-100 range)
  - Sets CSS `background-position` in generated media queries
- `position` - CSS background-position (default: 'center', overridden by `focal-point`)
- `size` - CSS background-size (default: 'cover')
- `repeat` - CSS background-repeat (default: 'no-repeat')
- `attachment` - CSS background-attachment (default: 'scroll')
- `fallback` - Fallback image for loading states
- `lazy` - Enable lazy loading with data attributes
- `class` - CSS classes for the container element
- Any standard HTML attributes for the container div

#### Glide Parameters (prefix with `glide-`)
- `glide-w` - Width in pixels
- `glide-h` - Height in pixels
- `glide-fit` - Fit mode with optional crop position
  - Basic: `crop`, `contain`, `fill`, `stretch`, `max`
  - Crop with position: `crop-top`, `crop-top-left`, `crop-center`, `crop-bottom-right`, etc.
  - Crop with focal point: `crop-25-75` (x%-y% percentages)
  - Crop with zoom: `crop-25-75-2` (x%-y%-zoom, zoom range 1-10)
- `glide-q` - Quality (1-100)
- `glide-fm` - Format (`jpg`, `png`, `gif`, `webp`, `avif`)
- `glide-blur` - Blur amount (0.5-1000)
- `glide-bri` - Brightness (-100 to 100)
- `glide-con` - Contrast (-100 to 100)
- `glide-gam` - Gamma (0.1-9.99)
- `glide-sharp` - Sharpen (0-100)
- `glide-filt` - Filter (`greyscale`, `sepia`)
- `glide-crop` - Specific crop dimensions (`width,height,x,y`)
- `glide-bg` - Background color
- `glide-border` - Border width and color
- `glide-or` - Orientation (0, 90, 180, 270)
- `glide-flip` - Flip direction (`h`, `v`, `both`)

for further details see [League/Glide documentation](https://glide.thephpleague.com/3.0/api/quick-reference/)

### Configuration Options

All configuration options available in `config/glider.php`:

```php
return [
    // Source filesystem path
    'source' => resource_path('assets'),

    // Cache filesystem path
    'cache' => storage_path('app/glider-cache'),
    
    // Watermarks filesystem path
    'watermarks' => resource_path('assets/watermarks'),
    
    // Route base URL
    'base_url' => 'img',
    
    // Maximum image size (pixels)
    'max_image_size' => env('GLIDE_MAX_IMAGE_SIZE', 2000 * 2000),
    
    // Image manipulation driver
    'driver' => env('IMAGE_MANIPULATION_DRIVER', 'imagick'),
    
    // Include file extensions in cache filenames
    'cache_with_file_extensions' => false,
    
    // Default manipulation parameters
    'defaults' => ['fm' => 'webp'],
    
    // Predefined manipulation presets
    'presets' => [
        'xs'  => ['w' => 320],
        'sm'  => ['w' => 480],
        'md'  => ['w' => 768],
        'lg'  => ['w' => 1280],
        'xl'  => ['w' => 1440],
        '2xl' => ['w' => 1920],
    ],
    
    // Responsive background image presets
    'background_presets' => [
        'hero' => [
            'xs' => ['w' => 768, 'h' => 400, 'fit' => 'crop'],
            'lg' => ['w' => 1440, 'h' => 600, 'fit' => 'crop'],
        ],
        'banner' => [
            'xs' => ['w' => 768, 'h' => 200, 'fit' => 'crop'],
            'lg' => ['w' => 1440, 'h' => 300, 'fit' => 'crop'],
        ],
    ],
];
```

## Requirements

- PHP 8.3+
- Laravel 11.x, or 12.x
- GD or Imagick PHP extension
- League/Glide 3.x

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

Run static analysis:

```bash
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mike Wall](https://github.com/daikazu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
