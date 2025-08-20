# Changelog

All notable changes to `laravel-glider` will be documented in this file.

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
