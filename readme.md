# LaravelGlider

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]


Glider is a simple package to quickly get started using [Glide](https://glide.thephpleague.com) in your Laravel blade templates. It provides some convienent blade components for generating various img related html tags. This package was heavily inspired Statamic and Spatie Media library. 

Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

Via Composer

``` bash
$ composer require daikazu/laravel-glider
```

## Configuration



## Usage

Glider uses native Glide syntax for URLs.

Check out the [Glide API](https://glide.thephpleague.com/2.0/api/quick-reference/) for more details.


### Glider URL Builder

```php

// Array syntax
{{ Glider::url('image.jpg', ['w' => 300, 'h' => 300, 'fit' => 'crop']) }}

// String syntax
{{ Glider::url('image.jpg', 'w=300&h=300&fit=crop') }}

or

{{ Glider::url('image.jpg?w=300&h=300&fit=crop') }}

```


### Glider Blade Components


#### <x-glider::img src="image.jpg?w=300&h=300&fit=crop">

#### <x-glider::picture src="image.jpg?w=300&h=300&fit=crop">

#### <x-glider::figure src="image.jpg?w=300&h=300&fit=crop">




### Glider backgroud image srcset style creation.

```php

Glider::backgroundImage('image.jpg')


```























## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author@email.com instead of using the issue tracker.

## Credits

- [Author Name][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/daikazu/laravel-glider.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/daikazu/laravel-glider.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/daikazu/laravel-glider/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/daikazu/laravel-glider
[link-downloads]: https://packagist.org/packages/daikazu/laravel-glider
[link-travis]: https://travis-ci.org/daikazu/laravel-glider
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/daikazu
[link-contributors]: ../../contributors
