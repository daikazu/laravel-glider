<picture>
   <source media="(prefers-color-scheme: dark)" srcset="art/header-dark.png">
   <img alt="Logo for Glider" src="art/header-light.png">
</picture>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daikazu/laravel-glider.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-glider)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laravel-glider/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daikazu/laravel-glider/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laravel-glider/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/daikazu/laravel-glider/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daikazu/laravel-glider.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-glider)

# Laravel Glider

Laravel Glider is a powerful Laravel package that provides on-the-fly image manipulation using [League/Glide](https://glide.thephpleague.com/). It offers elegant Blade components for generating responsive images with automatic srcset generation, URL signing for security, and seamless integration with Laravel's filesystem and caching layers.

## Features

- **On-the-fly Image Processing**: Transform images dynamically with URL parameters
- **Responsive Images**: Automatic srcset generation for optimal loading
- **Security**: Signed URLs prevent unauthorized image manipulation
- **Performance**: Built-in caching with Laravel's cache system
- **Flexible Sources**: Support for local filesystem
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

This will create a `config/glider.php` file. See file for more details. 

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
<x-glide-img-responsive 
    src="hero-image.jpg"
    width="1200"
    loading="lazy"
    alt="Hero image"
/>
```

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
<x-glide-img-responsive
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

### Convert Image Tags

Convert existing HTML img tags to use Glide components:

```bash
php artisan glide:convert-tags
```

## API Reference

### Facade Methods

The `Glide` facade provides the following methods:

#### `getUrl(string $path, array $params = []): string`

Generate a signed URL for an image with the given parameters.

```php
$url = Glide::getUrl('image.jpg', ['w' => 400, 'q' => 85]);
```

#### `getResponsiveBackgroundUrls(string $path, array $breakpoints = [], array $baseParams = []): array`

Generate multiple URLs for responsive backgrounds.

```php
$urls = Glide::getResponsiveBackgroundUrls('hero.jpg', [
    'xs' => ['w' => 768, 'h' => 400],
    'lg' => ['w' => 1440, 'h' => 600]
]);
```

#### `generateBackgroundCSS(string $path, array $breakpoints, string $selector, array $options = []): string`

Generate CSS for responsive background images.

```php
$css = Glide::generateBackgroundCSS('hero.jpg', $breakpoints, '.hero-section');
```

#### `getBackgroundPreset(string $presetName): array`

Get a background preset configuration.

```php
$preset = Glide::getBackgroundPreset('hero');
```

#### `getCachePath(string $path, array $params = []): string`

Get the cache path for a processed image.

```php
$cachePath = Glide::getCachePath('image.jpg', ['w' => 400]);
```

#### `decodePath(string $encoded): string`

Decode a base64url-encoded file path.

#### `decodeParams(string $encoded): array`

Decode base64url-encoded parameters.

### Blade Component Attributes

#### Image Components

The `<x-glide-img>` and `<x-glide-img-responsive>` components accept all standard HTML img attributes plus Glide parameters prefixed with `glide-`:

##### Standard HTML Attributes
- `src` - Image source path (required)
- `alt` - Alternative text
- `class` - CSS classes
- `width` - HTML width attribute
- `height` - HTML height attribute
- `loading` - Loading behavior (`lazy`, `eager`)
- `sizes` - Sizes attribute for responsive images

#### Background Component

The `<x-glide-bg>` component provides responsive background image functionality:

##### Background-Specific Attributes
- `src` - Background image source path (required)
- `preset` - Use predefined background preset from config
- `breakpoints` - Array of custom breakpoints and parameters
- `position` - CSS background-position (default: 'center')
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
- `glide-fit` - Fit mode (`crop`, `contain`, `fill`, `stretch`, `max`)
- `glide-q` - Quality (1-100)
- `glide-fm` - Format (`jpg`, `png`, `gif`, `webp`, `avif`)
- `glide-blur` - Blur amount (0.5-1000)
- `glide-bri` - Brightness (-100 to 100)
- `glide-con` - Contrast (-100 to 100)
- `glide-gam` - Gamma (0.1-9.99)
- `glide-sharp` - Sharpen (0-100)
- `glide-filt` - Filter (`greyscale`, `sepia`)
- `glide-crop` - Crop coordinates
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
    'cache' => storage_path('cache/glide'),
    
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
        '2xl' => ['w' => 1000],
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
