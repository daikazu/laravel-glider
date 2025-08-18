<?php

namespace Daikazu\LaravelGlider\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Daikazu\LaravelGlider\LaravelGlider
 */
class LaravelGlider extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Daikazu\LaravelGlider\LaravelGlider::class;
    }
}
