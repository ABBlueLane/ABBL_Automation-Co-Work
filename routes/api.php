<?php

use App\Http\Controllers\Api\TokenValidationController;
use App\Http\Middleware\CheckApiToken;
use Illuminate\Support\Facades\Route;

Route::middleware(CheckApiToken::class)->group(function (): void {
    Route::get('/check-token', [TokenValidationController::class, 'check']);
});
