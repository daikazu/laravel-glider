# Changelog

All notable changes to `laravel-glider` will be documented in this file.

## v3.2.0 - Security Hardening & Component Fixes - 2025-11-07

🔒 Security Enhancements

Comprehensive security protections now enabled by default:

- SSRF Protection - Blocks malicious remote URLs targeting internal networks, cloud metadata endpoints, and
  private IP ranges
- Path Traversal Protection - Prevents directory traversal attacks, null byte injection, and symlink exploits
- XSS Protection - Sanitizes CSS values in background components to prevent injection attacks

All protections are automatic and require no configuration.

⚠️ Breaking Change

URL signing now defaults to true (previously false) to prevent DoS attacks. For local development, add
GLIDE_SECURE=false to your .env file if needed. Never disable in production.

✨ New Features

- Added missing `<x-glide-bg>` component for non-responsive background images

🐛 Bug Fixes

- Fixed inconsistent vendor:publish tag naming (now consistently prefixed with glider-)
- Fixed component view namespace resolution

📚 Documentation

- Added comprehensive Security section to README
- Refactored README for better readability
- Added security best practices and examples

🧪 Tests

- Added 50+ security tests (Path Traversal, XSS, SSRF)

🔄 Upgrade

composer update daikazu/laravel-glider

## v3.1.0 - 2025-10-05

### Features

- **Remote Image Support**: Images from external URLs are now properly processed, cached, and optimized #
  
  - Remote images automatically apply config defaults (format, quality, etc.)
  - Processed images are cached locally for better performance
  - All Glide manipulations work seamlessly with remote sources
  
- Added `Glider::url()` alias for `Glider::getUrl()`
  

### Bug Fixes

- Resolved PHPStan level 5 analysis errors with `parse_url()` type checks
- Fixed test compatibility between Orchestra Testbench v9 and v10

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.0.5...v3.1.0

## v3.0.4 - 2025-10-04

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.0.3...v3.0.4

## v3.0.3 - 2025-10-04

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.0.2...v3.0.3

## v3.0.2 - 2025-09-29

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.0.1...v3.0.2

## v3.0.1 - 2025-08-21

### What's Changed

* fixed storage:link filesystem location
* Bump actions/checkout from 4 to 5 by @dependabot[bot] in https://github.com/daikazu/laravel-glider/pull/6
* Bump stefanzweifel/git-auto-commit-action from 5 to 6 by @dependabot[bot] in https://github.com/daikazu/laravel-glider/pull/5

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/daikazu/laravel-glider/pull/6

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.0.0...v3.0.1

## v3.0.0 - Major Release - 2025-08-20

🚀 Laravel Glider v3.0.0 - Major Release

Breaking Changes ⚠️

This is a major rewrite with breaking changes. Please review the README before upgrading.

- Minimum Requirements: Now requires PHP 8.3+ and Laravel 11+
- League/Glide v3: Updated to use the latest version of League/Glide
- Component Names: Some component naming has changed for consistency
- Configuration: New configuration structure with enhanced options

🎉 What's New

🖼️ Responsive Background Images

The marquee feature of v3! Introducing the powerful <x-glide-bg-responsive> component for responsive background images:

  <x-glide-bg-responsive src="hero.jpg" preset="hero" class="hero-section">
      <div class="hero-content">
          <h1>Welcome to Our Site</h1>
      </div>
  </x-glide-bg-responsive>
Features:
- Automatic CSS generation with media queries
- Preset system for consistent backgrounds
- Custom breakpoint support
- Lazy loading capabilities
- Fallback image support
🎨 Enhanced Blade Components
- New: <x-glide-bg-responsive> for responsive backgrounds
- Improved: Better attribute handling and performance
- Enhanced: Automatic srcset generation
⚙️ Advanced Configuration System
- Background Presets: Pre-configured responsive breakpoints for common use cases
- Enhanced Security: Improved URL signing and validation
- Better Defaults: WebP format and optimized quality settings by default
- Environment Variables: More configuration options via .env
📚 Comprehensive Documentation

- Complete API Reference: Full documentation of all components and methods
- Usage Examples: Extensive examples for all features
- Configuration Guide: Detailed configuration documentation

🛠️ Improvements

Performance

- Better caching strategies
- Optimized URL generation
- Reduced memory usage for large image sets

Developer Experience

- IDE Support: Full PhpDoc annotations and autocomplete
- Error Handling: Better error messages and validation
- Debugging: Enhanced debugging capabilities

Security

- Improved URL signing
- Better parameter validation
- Enhanced security defaults

📦 What's Included

- ✅ On-the-fly image processing
- ✅ Responsive background images (NEW!)
- ✅ Automatic srcset generation
- ✅ Security with signed URLs
- ✅ Performance optimizations
- ✅ Preset system for consistency
- ✅ Laravel 11+ compatibility
- ✅ PHP 8.3+ support
- ✅ Comprehensive documentation
