<?php

use Daikazu\LaravelGlider\Http\Controllers\GlideController;
use Daikazu\LaravelGlider\Http\Middleware\VerifyGlideSignature;
use Illuminate\Support\Facades\Route;

Route::prefix(config('glider.base_url'))
    ->middleware([VerifyGlideSignature::class])
    ->group(function () {
        // {path} is the readable relative path: {dirs...}/{name}~{token}.{ext}
        Route::get('{path}', GlideController::class)
            ->where('path', '.+~[A-Za-z0-9_-]+\.(?:jpg|png|gif|webp|avif|tiff)')
            ->name('glider');
    });
