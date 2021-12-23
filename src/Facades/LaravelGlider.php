<?php

namespace Daikazu\LaravelGlider\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelGlider extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-glider';
    }
}
