# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel Glider is a Laravel package that provides on-the-fly image manipulation using League/Glide. It offers Blade components for generating responsive images with srcset attributes and integrates with Laravel's filesystem and caching layers.

## Development Commands

```bash
# Run tests
composer test
# or
vendor/bin/pest

# Run tests with coverage
composer test-coverage

# Static analysis
composer analyse
# or
vendor/bin/phpstan analyse

# Code formatting
composer format
# or
vendor/bin/pint

# Prepare package after composer changes
composer prepare
```

## Architecture Overview

This package will mainly be a nice helper wrapper for using the `league/glide` version 3 package.        

### Core Components

- **GlideService** (`src/GlideService.php`): Central service handling URL generation, path encoding/decoding, and filesystem management
- **Img Component** (`src/Components/Img.php`): Blade component for rendering responsive images with automatic srcset generation
- **GlideController** (`src/Http/Controllers/GlideController.php`): HTTP controller serving manipulated images
- **LaravelGliderServiceProvider**: Registers services, routes, and Blade components

### Key Features

- **URL Encoding/Decoding**: Uses base64url encoding for paths and parameters in URLs
- **Responsive Images**: Automatic srcset generation based on file size optimization
- **Caching**: Laravel cache integration for srcset widths and image metadata
- **Security**: Signature-based URL validation using app key
- **Multiple Sources**: Supports local filesystem and HTTP sources via Flysystem

### Configuration

- Main config file: `config/glider.php`
- Source filesystem: `resource_path('assets')` by default
- Cache location: `storage_path('cache/glide')`
- Route prefix: `img` (configurable via `base_url`)
- Uses GD driver by default, supports Imagick

### Blade Usage

```php
<x-glide:img src="path/to/image.jpg" data-glide-w="500" data-glide-q="85" />
```

The component automatically:
- Generates signed URLs for image manipulation
- Creates responsive srcset attributes
- Caches computed widths for performance

### Route Structure

Images are served via: `/img/{encoded_path}/{encoded_params}.{extension}`
- Path and parameters are base64url encoded
- URLs are signed for security
- Extension determines output format
