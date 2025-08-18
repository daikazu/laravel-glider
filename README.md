# Start using Glide on-the-fly instantly in your Laravel blade templates.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daikazu/laravel-glider.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-glider)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laravel-glider/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daikazu/laravel-glider/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laravel-glider/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/daikazu/laravel-glider/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daikazu/laravel-glider.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-glider)

Glider is a simple package to quickly get started using Glide in your Laravel blade templates. It provides some convienent blade components for generating various img related html tags. This package was heavily inspired Statamic and Spatie Media library.

## Installation

You can install the package via composer:

```bash
composer require daikazu/laravel-glider
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-glider-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-glider-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-glider-views"
```

## Usage

```php
$laravelGlider = new Daikazu\LaravelGlider();
echo $laravelGlider->echoPhrase('Hello, Daikazu!');
```

## Testing

```bash
composer test
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
