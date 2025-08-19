<?php

use Daikazu\LaravelGlider\Http\Controllers\GlideController;
use Daikazu\LaravelGlider\Http\Middleware\VerifyGlideSignature;
use Illuminate\Support\Facades\Route;

Route::prefix(config('glider.base_url'))
    ->middleware(VerifyGlideSignature::class)
    ->group(function () {
        Route::get('{encoded_path}/{encoded_params}.{extension}', GlideController::class)
            ->whereIn('extension', ['jpg', 'pjpg', 'png', 'gif', 'webp', 'avif', 'tiff'])
            ->name('glide');
    });
