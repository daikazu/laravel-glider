<?php

use Illuminate\Support\Facades\Route;
use Daikazu\LaravelGlider\Http\Controllers\GlideController;

Route::get(config('glider.route').'/{path}', [GlideController::class, 'show'])->where('path', '.+');

