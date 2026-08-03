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
- **Disk-Based Storage** - Source, cache, and watermarks can live on any Laravel filesystem disk (e.g. S3)
- **Cache Prebuilding** - `glider:build` warms the cache from your Blade templates ahead of time
- **Layered Security** - Signed URLs, an on-the-fly kill switch, and presets-only mode, independently stackable

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
<x-glider-img
    src="photo.jpg"
    glide-w="400"
    glide-q="85"
    alt="Photo"
/>
```

### Responsive Image

```html
<x-glider-img-responsive
    src="hero.jpg"
    glide-w="1200"
    alt="Hero image"
/>
```

### Background Image

```html
<x-glider-bg src="banner.jpg" preset="hero" class="hero-section">
    <h1>Welcome</h1>
</x-glider-bg>
```

### Responsive Background

```html
<x-glider-bg-responsive
    src="banner.jpg"
    glide-w="1440"
    glide-h="600"
/>
    <h1>Content</h1>
</x-glider-bg-responsive>
```

### Using the Facade

```php
use Daikazu\LaravelGlider\Facades\Glider;

// Generate URL
$url = Glider::url('photo.jpg', ['w' => 400, 'q' => 85]);
```

> **Upgrading from v3?** Component tags (`x-glide-*` → `x-glider-*`), the
> facade (`Glide` → `Glider`), the config file (`config/laravel-glider.php` →
> `config/glider.php`), and environment variables (`GLIDE_*` → `GLIDER_*`)
> were all renamed in v4. See [UPGRADE.md](UPGRADE.md) for the full mapping
> and step-by-step instructions.

## Usage Guide

### Image Manipulation

All [Glide parameters](https://glide.thephpleague.com/api/quick-reference/) are supported with the `glide-` prefix:

**Sizing**
```html
<x-glider-img src="photo.jpg" glide-w="400" glide-h="300" glide-fit="crop" />
```

**Quality & Format**
```html
<x-glider-img src="photo.jpg" glide-q="85" glide-fm="webp" />
```

**Effects**
```html
<x-glider-img src="photo.jpg" glide-blur="5" glide-filt="greyscale" />
```

### Focal Points

Control image positioning within its container using CSS:

```html
<!-- Named positions -->
<x-glider-img src="portrait.jpg" focal-point="top" glide-w="400" glide-h="300" />

<!-- Custom percentages (x, y) -->
<x-glider-img src="photo.jpg" focal-point="75,25" glide-w="400" glide-h="300" />

<!-- On backgrounds -->
<x-glider-bg src="hero.jpg" focal-point="center" preset="hero">
    <h1>Content</h1>
</x-glider-bg>
```

**Available positions:** `center`, `top`, `bottom`, `left`, `right`, `top-left`, `top-right`, `bottom-left`, `bottom-right`

**How it works:**
- For `<img>`: Applies `object-fit: cover` and `object-position`
- For backgrounds: Sets `background-position` in CSS
- For server-side cropping, use `glide-fit="crop-top"` or `glide-fit="crop-25-75"`

### Presets

Define reusable configurations in `config/glider.php`:

```php
'presets' => [
    'thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop'],
    'hero' => ['w' => 1200, 'h' => 600, 'fit' => 'crop', 'q' => 90],
],
```

Use in components:

```html
<x-glider-img src="photo.jpg" glide-preset="thumbnail" />
```

### Background Images

Create responsive backgrounds with automatic media queries:

```html
<x-glider-bg
    src="hero.jpg"
    preset="hero"
    position="center top"
    class="hero-section"
>
    <div class="content">
        <h1>Hero Title</h1>
    </div>
</x-glider-bg>
```

**Custom breakpoints:**

```html
<x-glider-bg
    src="banner.jpg"
    :breakpoints="[
        'xs' => ['w' => 768, 'h' => 300],
        'lg' => ['w' => 1440, 'h' => 500]
    ]"
>
    <div class="content">
        <h1>Hero Title</h1>
    </div>
</x-glider-bg>
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
<x-glider-img
    src="https://example.com/photo.jpg"
    glide-w="600"
    glide-fm="webp"
    alt="Remote image"
/>
```

Remote images are fetched, processed, and cached locally. Default config values apply automatically. Fetching is handled by a first-party, read-only HTTP filesystem adapter built on Laravel's own HTTP client — no extra dependencies or configuration required.

## Components Reference

### `<x-glider-img>`

Basic image with transformations.

**Attributes:**
- `src` - Image path (required)
- `focal-point` - CSS positioning (e.g., `top`, `75,25`)
- `glide-*` - Any Glide parameter (see [Parameters](#glide-parameters))
- Standard HTML `<img>` attributes (alt, class, loading, etc.)

### `<x-glider-img-responsive>`

Responsive image with automatic srcset generation.

**Attributes:**
- Same as `<x-glider-img>`
- Generates multiple sizes for different viewports

### `<x-glider-bg>`

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

### `<x-glider-bg-responsive>`

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

See [full Glide documentation](https://glide.thephpleague.com/) for all parameters.

## Artisan Commands

**Clear image cache:**
```bash
php artisan glider:clear
```

**Prebuild all statically discoverable image conversions:**
```bash
php artisan glider:build
```

**Convert HTML img tags to components (WIP):**
```bash
php artisan glider:convert-img-tags --dry-run
```

See [Prebuilding the Cache](#prebuilding-the-cache-gliderbuild) below for details on `glider:build`.

## Configuration

Key options in `config/glider.php`:

```php
return [
    'source' => resource_path('assets'),
    'cache' => storage_path('app/glider-cache'),
    'base_url' => 'img',
    'max_image_size' => 2000 * 2000,
    'driver' => 'gd', // or 'imagick'

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

`source`, `cache`, and `watermarks` each accept either a plain path string
or a Laravel disk reference (`['disk' => 's3', 'prefix' => 'glider']`) — see
[Disk-Based Cache](#disk-based-cache) below.

League/Glide 4.1 also adds a `strip` parameter (add `'strip' => true` to
`defaults` or a preset) to remove EXIF/metadata — such as GPS coordinates —
from generated images. It's documented in the published config but left
disabled by default.

## Prebuilding the Cache (`glider:build`)

`php artisan glider:build` scans your Blade templates (by default,
everything under `resources/views`, configurable via `glider.build.paths`)
for `<x-glider-img>`, `<x-glider-img-responsive>`, `<x-glider-bg>`,
`<x-glider-bg-responsive>`, and `Glider::url()` usages whose `src` is a
literal string, and generates their cache entries ahead of time — using the
exact same param resolution a live request would use. This means the first
real request for a prebuilt conversion is already a cache hit.

```bash
php artisan glider:build
```

```bash
# Preview what would be generated without writing anything to cache
php artisan glider:build --dry-run
```

Usages with a dynamic `src` (a variable or expression rather than a literal
string) can't be discovered by scanning and are reported as **skipped**
rather than silently ignored — they'll still be processed on-the-fly at
request time. The command reports generated / skipped (dynamic src) /
failed counts, and exits non-zero if any conversion failed to generate.

**CI example** — run the build step after your asset build so the cache is
warm before the release ships:

```yaml
- name: Build assets
  run: npm run build

- name: Prebuild image cache
  run: php artisan glider:build
```

Pair this with `'on_the_fly' => false` (see [Security](#security) below) to
make production serve nothing but pre-warmed cache entries — zero
request-time image processing.

## Disk-Based Cache

`source`, `cache`, and `watermarks` in `config/glider.php` each accept
either a plain path string (as before) or a Laravel disk reference:

```php
'cache' => ['disk' => 's3', 'prefix' => 'glider-cache'],
```

This supports two common deployment recipes:

**1. S3 shared cache** — point `source` and `cache` at S3 disk references.
Run `php artisan glider:build` once (e.g. in CI, against the shared
bucket); every application server then reads from the same warm cache
instead of each one processing images independently.

```php
'source' => ['disk' => 's3', 'prefix' => 'images'],
'cache'  => ['disk' => 's3', 'prefix' => 'glider-cache'],
```

> **Note:** a non-local `source` (any disk reference other than the local
> filesystem) disables automatic `width()`/`height()` attributes and
> automatic `srcset` width calculation, since both require reading the
> source image's dimensions from disk. An explicit `srcset-widths` list
> still works.

**2. Baked into the release artifact** — keep `cache` as a plain local
path (e.g. `public_path('glider-cache')`), populate it with
`glider:build` during your build step, and ship it as part of the deployed
artifact so the cache is present on disk the moment the release goes live.

```php
'cache' => public_path('glider-cache'),
```

```yaml
- name: Build assets
  run: npm run build

- name: Prebuild image cache into the artifact
  run: php artisan glider:build

- name: Package release
  run: tar -czf release.tar.gz public/ ...
```

## Requirements

- PHP 8.3+
- Laravel 13.x
- GD or Imagick extension
- League/Glide 4.1+

## Security

Laravel Glider implements multiple security layers to protect your application from common attacks. These features work together to ensure safe image processing.

### Three-Layer Security Model

Beyond automatic protections (path traversal, XSS, SSRF), Glider offers
three independent, stackable layers you can combine to control how much of
the parameter space is reachable at request time:

1. **URL Signing** (`secure`) — every URL must carry a valid HMAC
   signature, so only your application can mint valid image URLs. Primary
   defense; keep enabled in production.
2. **On-the-Fly Kill Switch** (`on_the_fly`) — once you've warmed the
   cache for the conversions you actually use (via `php artisan
   glider:build`), disable on-the-fly generation entirely. Requests for
   already-cached conversions are served normally; requests for anything
   uncached 404 instead of invoking the image processor.
3. **Presets-Only Mode** (`restrict_to_presets`) — restricts allowed
   manipulations to your configured presets (plus defaults-only and
   format-only requests), rejecting anything else with a 403.

These compose freely: signed URLs only, signed + presets-only, on-the-fly
disabled after a cache-warming step, or any combination.

### URL Signing

**Status:** Enabled by default (`GLIDER_SECURE=true`)

URL signing prevents unauthorized image manipulation and protects against denial-of-service attacks. When enabled, all image URLs are cryptographically signed using your application key.

```php
// config/glider.php
'secure' => env('GLIDER_SECURE', true),
```

**Important:** URL signing should **NEVER** be disabled in production environments. Unsigned URLs allow attackers to:
- Generate infinite variations of images, exhausting server resources
- Perform expensive image operations repeatedly
- Fill disk space with cached attack images

The signing mechanism uses Laravel's `APP_KEY` by default. Ensure your application key is:
- Generated with `php artisan key:generate`
- Kept secure and never committed to version control
- Properly configured in production environments

### On-the-Fly Kill Switch

**Status:** Enabled by default (`GLIDER_ON_THE_FLY=true`)

When `on_the_fly` is disabled, the server never runs the image processor at
request time — a request for a conversion that's already in cache is
served from cache; a request for anything not already cached returns a
404. This turns image processing into a build-time step rather than a
request-time one.

```php
// config/glider.php
'on_the_fly' => env('GLIDER_ON_THE_FLY', true),
```

Pair this with [`php artisan glider:build`](#prebuilding-the-cache-gliderbuild)
in your CI/deploy pipeline: warm the cache for every conversion your
templates statically reference, then disable `on_the_fly` so production
does zero request-time image processing. Any usage with a dynamic `src`
(not discoverable by the build scan) will 404 under this mode unless it
happens to already be cached from an earlier request.

### Presets-Only Mode

**Status:** Disabled by default (`GLIDER_RESTRICT_TO_PRESETS=false`)

When enabled, any request whose parameters don't exactly match a
configured preset's expansion (or a defaults-only / format-only request)
is rejected with a 403. This collapses the reachable parameter space down
to `(number of images) × (number of presets)`, closing off the
arbitrary-parameter attack surface even when signed URLs are otherwise
trusted.

```php
// config/glider.php
'restrict_to_presets' => env('GLIDER_RESTRICT_TO_PRESETS', false),
```

**Important:** this is not an absolute "presets only" guarantee — format
conversions via the URL's file extension (`.webp`, `.avif`, etc.) are
still permitted even when the request's other params don't match any
preset, because the route itself already whitelists which extensions are
accepted (`jpg`, `pjpg`, `png`, `gif`, `webp`, `avif`, `tiff`). The
practical guarantee is "images × presets × whitelisted extensions," not
"presets only."

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
<x-glider-bg src="hero.jpg" position="center top" />

<!-- Protected: Malicious CSS is blocked -->
<x-glider-bg src="hero.jpg" position="center; background: url(javascript:alert('XSS'))" />
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
<x-glider-img src="https://cdn.example.com/image.jpg" glide-w="400" />

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
   GLIDER_SECURE=true
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
   // config/glider.php
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

8. **Consider `restrict_to_presets` for user-influenced sources**
   ```bash
   # Collapse the parameter space to images x presets x whitelisted extensions
   GLIDER_RESTRICT_TO_PRESETS=true
   ```

9. **Consider `on_the_fly=false` once your cache is warm**
   ```bash
   # Serve only pre-cached conversions generated by `glider:build`
   GLIDER_ON_THE_FLY=false
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
