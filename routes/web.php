<?php

use Daikazu\LaravelGlider\Http\Controllers\GlideController;
use Daikazu\LaravelGlider\Http\Middleware\VerifyGlideSignature;
use Illuminate\Support\Facades\Route;

$middleware = config('laravel-glider.secure', true) ? [VerifyGlideSignature::class] : [];

Route::prefix(config('laravel-glider.base_url'))
    ->middleware($middleware)
    ->group(function () {
        Route::get('{encoded_path}/{encoded_params}.{extension}', GlideController::class)
            ->whereIn('extension', ['jpg', 'pjpg', 'png', 'gif', 'webp', 'avif', 'tiff'])
            ->name('glide');
    });
