<p align="center">
    <img src="https://repository-images.githubusercontent.com/441249043/9b8f03d6-f165-4033-98b8-12d7cb700a84" alt="Laravel Glider banner" style="width: 100%; max-width: 800px;" />
</p>

<p align="center">
    <a href="https://packagist.org/packages/daikazu/laravel-glider">
        <img src="https://img.shields.io/packagist/dt/daikazu/laravel-glider?style=flat-square" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/daikazu/laravel-glider">
        <img src="https://img.shields.io/packagist/v/daikazu/laravel-glider?style=flat-square" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/daikazu/laravel-glider">
        <img src="https://img.shields.io/packagist/l/daikazu/laravel-glider?style=flat-square" alt="License">
    </a>
</p>

# Laravel Glider

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


#### <x-glider-img src="image.jpg?w=300&h=300&fit=crop">

#### <x-glider-picture src="image.jpg?w=300&h=300&fit=crop">

#### <x-glider-figure src="image.jpg?w=300&h=300&fit=crop">




### Glider background image srcset style creation.

```php

Glider::backgroundImage('image.jpg')


```



## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author@email.com instead of using the issue tracker.

## Credits

- [Mike Wall][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/daikazu/laravel-glider.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/daikazu/laravel-glider.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/daikazu/laravel-glider
[link-downloads]: https://packagist.org/packages/daikazu/laravel-glider
[link-author]: https://github.com/daikazu
[link-contributors]: ../../contributors
