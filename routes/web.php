<?php

use Daikazu\LaravelGlider\Http\Controllers\GlideController;
use Daikazu\LaravelGlider\Http\Middleware\VerifyGlideSignature;
use Illuminate\Support\Facades\Route;

Route::prefix(config('laravel-glider.base_url'))
    ->when(config('laravel-glider.secure', true), fn ($route) => $route->middleware(VerifyGlideSignature::class))
    ->group(function () {
        Route::get('{encoded_path}/{encoded_params}.{extension}', GlideController::class)
            ->whereIn('extension', ['jpg', 'pjpg', 'png', 'gif', 'webp', 'avif', 'tiff'])
            ->name('glide');
    });
