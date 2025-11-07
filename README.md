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
    <h1>Content</h1>
</x-glide-bg-responsive>
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
>
    <div class="content">
        <h1>Hero Title</h1>
    </div>
</x-glide-bg>
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

## Security

Laravel Glider implements multiple security layers to protect your application from common attacks. These features work together to ensure safe image processing.

### URL Signing

**Status:** Enabled by default (`GLIDE_SECURE=true`)

URL signing prevents unauthorized image manipulation and protects against denial-of-service attacks. When enabled, all image URLs are cryptographically signed using your application key.

```php
// config/laravel-glider.php
'secure' => env('GLIDE_SECURE', true),
```

**Important:** URL signing should **NEVER** be disabled in production environments. Unsigned URLs allow attackers to:
- Generate infinite variations of images, exhausting server resources
- Perform expensive image operations repeatedly
- Fill disk space with cached attack images

The signing mechanism uses Laravel's `APP_KEY` by default. Ensure your application key is:
- Generated with `php artisan key:generate`
- Kept secure and never committed to version control
- Properly configured in production environments

### Path Traversal Protection

Laravel Glider validates all file paths to prevent directory traversal attacks. The package automatically:

- **Validates path boundaries** - Ensures all paths resolve within the configured source directory
- **Blocks null bytes** - Prevents null byte injection attacks (`../../../etc/passwd%00.jpg`)
- **Prevents symlink attacks** - Validates real paths to stop symlink-based directory escapes
- **Sanitizes input** - Removes dangerous characters from file paths

Example of blocked attacks:
```
❌ ../../../etc/passwd
❌ /var/www/../../etc/shadow
❌ image.jpg%00.php
❌ symlink-to-sensitive-dir/file.jpg
```

These protections are automatic and require no configuration.

### XSS Protection

Background image components sanitize CSS values to prevent cross-site scripting attacks via CSS injection:

```html
<!-- Safe: CSS values are sanitized -->
<x-glide-bg src="hero.jpg" position="center top" />

<!-- Protected: Malicious CSS is blocked -->
<x-glide-bg src="hero.jpg" position="center; background: url(javascript:alert('XSS'))" />
```

The package validates and sanitizes:
- `position` attributes
- `size` attributes
- `repeat` attributes
- `attachment` attributes
- `focal-point` values

### SSRF Protection

When processing remote images via URLs, Laravel Glider protects against Server-Side Request Forgery (SSRF) attacks:

```html
<!-- Safe: Public URLs are allowed -->
<x-glide-img src="https://cdn.example.com/image.jpg" glide-w="400" />

<!-- Blocked: Private/internal targets are prevented -->
❌ http://localhost/admin/secret.jpg
❌ http://127.0.0.1/internal/image.jpg
❌ http://192.168.1.1/router-config.jpg
❌ http://10.0.0.5/database-backup.jpg
❌ http://169.254.169.254/latest/meta-data (AWS metadata)
```

**Protections implemented:**

- **Blocks localhost access** - Prevents requests to `localhost`, `127.0.0.1`, `::1`, and `0.0.0.0`
- **Blocks private IP ranges** - Rejects RFC1918 private addresses (10.x.x.x, 172.16-31.x.x, 192.168.x.x)
- **Blocks link-local addresses** - Prevents access to 169.254.x.x range (cloud metadata endpoints)
- **Blocks dangerous ports** - Rejects connections to common internal service ports (SSH:22, MySQL:3306, Redis:6379, etc.)
- **Validates URL schemes** - Only allows `http://` and `https://` protocols
- **DNS resolution validation** - Resolves hostnames to IPs and validates against private ranges

These protections prevent attackers from:
- Scanning internal network infrastructure
- Accessing cloud provider metadata endpoints
- Reaching internal services and databases
- Port scanning internal systems
- Bypassing firewall rules via your server

All SSRF protections are automatic and require no configuration.

### Security Best Practices

Follow these recommendations to maintain secure image processing:

1. **Keep URL signing enabled**
   ```bash
   # .env (production)
   GLIDE_SECURE=true
   ```

2. **Use a strong application key**
   ```bash
   # Generate a secure key
   php artisan key:generate
   ```

3. **Validate source paths**
   ```php
   // Ensure images are within intended directories
   'source' => resource_path('assets/images'),
   ```

4. **Limit maximum image dimensions**
   ```php
   // config/laravel-glider.php
   'max_image_size' => 2000 * 2000, // Prevent memory exhaustion
   ```

5. **Keep the package updated**
   ```bash
   composer update daikazu/laravel-glider
   ```

6. **Use HTTPS in production**
    - Protects signed URLs from interception
    - Prevents man-in-the-middle attacks on image requests

7. **Configure appropriate cache permissions**
   ```bash
   # Ensure cache directory has proper permissions
   chmod 755 storage/app/glider-cache
   ```

### Reporting Security Issues

If you discover a security vulnerability, please email [daikazu@gmail.com] or use the [GitHub Security Advisory](https://github.com/daikazu/laravel-glider/security) feature. Do not create public issues for security vulnerabilities.

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
