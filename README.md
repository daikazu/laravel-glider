<picture>
   <source media="(prefers-color-scheme: dark)" srcset="art/header-dark.png">
   <img alt="Logo for Glider" src="art/header-light.png">
</picture>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daikazu/laravel-glider.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-glider)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laravel-glider/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daikazu/laravel-glider/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daikazu/laravel-glider.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-glider)

# Laravel Glider

On-the-fly image manipulation for Laravel using [League/Glide](https://glide.thephpleague.com/). Transform, optimize, and serve images with elegant Blade components.

## Features

- **On-demand Processing** - Transform images via URL parameters
- **Responsive Images** - Automatic srcset and responsive backgrounds
- **Remote Images** - Process external URLs with caching
- **Secure URLs** - Signed URLs prevent unauthorized manipulation
- **Performance** - Built-in caching layer
- **Blade Components** - Clean syntax for templates
- **Presets** - Reusable image configurations

## Installation

```bash
composer require daikazu/laravel-glider
```

Publish configuration:

```bash
php artisan vendor:publish --tag="glider-config"
```

The cache directory is created automatically with `.gitignore` added.

## Quick Start

### Basic Image

```html
<x-glide-img
    src="photo.jpg"
    glide-w="400"
    glide-q="85"
    alt="Photo"
/>
```

### Responsive Image

```html
<x-glide-img-responsive
    src="hero.jpg"
    glide-w="1200"
    alt="Hero image"
/>
```

### Background Image

```html
<x-glide-bg src="banner.jpg" preset="hero" class="hero-section">
    <h1>Welcome</h1>
</x-glide-bg>
```

### Responsive Background

```html
<x-glide-bg-responsive
    src="banner.jpg"
    glide-w="1440"
    glide-h="600"
/>
```

### Using the Facade

```php
use Daikazu\LaravelGlider\Facades\Glide;

// Generate URL
$url = Glide::url('photo.jpg', ['w' => 400, 'q' => 85]);
```

## Usage Guide

### Image Manipulation

All [Glide parameters](https://glide.thephpleague.com/3.0/api/quick-reference/) are supported with the `glide-` prefix:

**Sizing**
```html
<x-glide-img src="photo.jpg" glide-w="400" glide-h="300" glide-fit="crop" />
```

**Quality & Format**
```html
<x-glide-img src="photo.jpg" glide-q="85" glide-fm="webp" />
```

**Effects**
```html
<x-glide-img src="photo.jpg" glide-blur="5" glide-filt="greyscale" />
```

### Focal Points

Control image positioning within its container using CSS:

```html
<!-- Named positions -->
<x-glide-img src="portrait.jpg" focal-point="top" glide-w="400" glide-h="300" />

<!-- Custom percentages (x, y) -->
<x-glide-img src="photo.jpg" focal-point="75,25" glide-w="400" glide-h="300" />

<!-- On backgrounds -->
<x-glide-bg src="hero.jpg" focal-point="center" preset="hero">
    <h1>Content</h1>
</x-glide-bg>
```

**Available positions:** `center`, `top`, `bottom`, `left`, `right`, `top-left`, `top-right`, `bottom-left`, `bottom-right`

**How it works:**
- For `<img>`: Applies `object-fit: cover` and `object-position`
- For backgrounds: Sets `background-position` in CSS
- For server-side cropping, use `glide-fit="crop-top"` or `glide-fit="crop-25-75"`

### Presets

Define reusable configurations in `config/laravel-glider.php`:

```php
'presets' => [
    'thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop'],
    'hero' => ['w' => 1200, 'h' => 600, 'fit' => 'crop', 'q' => 90],
],
```

Use in components:

```html
<x-glide-img src="photo.jpg" glide-preset="thumbnail" />
```

### Background Images

Create responsive backgrounds with automatic media queries:

```html
<x-glide-bg
    src="hero.jpg"
    preset="hero"
    position="center top"
    class="hero-section"
>
    <div class="content">
        <h1>Hero Title</h1>
    </div>
</x-glide-bg>
```

**Custom breakpoints:**

```html
<x-glide-bg
    src="banner.jpg"
    :breakpoints="[
        'xs' => ['w' => 768, 'h' => 300],
        'lg' => ['w' => 1440, 'h' => 500]
    ]"
/>
```

**Background presets** in config:

```php
'background_presets' => [
    'hero' => [
        'xs' => ['w' => 768, 'h' => 400, 'fit' => 'crop'],
        'md' => ['w' => 1024, 'h' => 500, 'fit' => 'crop'],
        'lg' => ['w' => 1440, 'h' => 600, 'fit' => 'crop'],
    ],
],
```

### Remote Images

Process images from external URLs automatically:

```html
<x-glide-img
    src="https://example.com/photo.jpg"
    glide-w="600"
    glide-fm="webp"
    alt="Remote image"
/>
```

Remote images are fetched, processed, and cached locally. Default config values apply automatically.

## Components Reference

### `<x-glide-img>`

Basic image with transformations.

**Attributes:**
- `src` - Image path (required)
- `focal-point` - CSS positioning (e.g., `top`, `75,25`)
- `glide-*` - Any Glide parameter (see [Parameters](#glide-parameters))
- Standard HTML `<img>` attributes (alt, class, loading, etc.)

### `<x-glide-img-responsive>`

Responsive image with automatic srcset generation.

**Attributes:**
- Same as `<x-glide-img>`
- Generates multiple sizes for different viewports

### `<x-glide-bg>`

Background image container (non-responsive).

**Attributes:**
- `src` - Image path (required)
- `preset` - Background preset name
- `focal-point` - CSS positioning
- `position` - CSS background-position (default: `center`)
- `size` - CSS background-size (default: `cover`)
- `repeat` - CSS background-repeat (default: `no-repeat`)
- `attachment` - CSS background-attachment (default: `scroll`)
- `class` - CSS classes for container
- `glide-*` - Any Glide parameter

### `<x-glide-bg-responsive>`

Responsive background with media queries.

**Attributes:**
- `src` - Image path (required)
- `preset` - Background preset name
- `breakpoints` - Custom breakpoint array
- `focal-point` - CSS positioning for all breakpoints
- `lazy` - Enable lazy loading
- `fallback` - Fallback image path
- `glide-*` - Any Glide parameter

## Glide Parameters

Common parameters (use `glide-` prefix in components):

| Parameter | Values | Description |
|-----------|--------|-------------|
| `w` | pixels | Width |
| `h` | pixels | Height |
| `fit` | `crop`, `contain`, `fill`, `max` | Resize mode |
| `fit` | `crop-{position}` | Crop with position (e.g., `crop-top`, `crop-center`) |
| `fit` | `crop-{x}-{y}[-{zoom}]` | Crop with focal point/zoom (e.g., `crop-25-75-2`) |
| `q` | 1-100 | Quality |
| `fm` | `jpg`, `png`, `webp`, `avif` | Format |
| `blur` | 0-100 | Blur amount |
| `bri` | -100 to 100 | Brightness |
| `con` | -100 to 100 | Contrast |
| `filt` | `greyscale`, `sepia` | Filter |

See [full Glide documentation](https://glide.thephpleague.com/3.0/api/quick-reference/) for all parameters.

## Artisan Commands

**Clear image cache:**
```bash
php artisan glider:clear-cache
```

**Convert HTML img tags to components (WIP):**
```bash
php artisan glider:convert-img-tags --dry-run
```

## Configuration

Key options in `config/laravel-glider.php`:

```php
return [
    'source' => resource_path('assets'),
    'cache' => storage_path('app/glider-cache'),
    'base_url' => 'img',
    'max_image_size' => 2000 * 2000,
    'driver' => 'imagick', // or 'gd'

    'defaults' => ['fm' => 'webp', 'q' => 85],

    'presets' => [
        'thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop'],
    ],

    'background_presets' => [
        'hero' => [
            'xs' => ['w' => 768, 'h' => 400],
            'lg' => ['w' => 1440, 'h' => 600],
        ],
    ],
];
```

## Requirements

- PHP 8.3+
- Laravel 11.x or 12.x
- GD or Imagick extension
- League/Glide 3.x

## Testing

```bash
composer test          # Run tests
composer test-coverage # With coverage
composer analyse       # Static analysis
```

## Resources

- [Changelog](CHANGELOG.md)
- [League/Glide Documentation](https://glide.thephpleague.com/)
- [Report Security Issues](https://github.com/daikazu/laravel-glider/security)

## Credits

- [Mike Wall](https://github.com/daikazu)
- [All Contributors](https://github.com/daikazu/laravel-glider/contributors)

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.
