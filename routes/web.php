<?php

use App\Http\Controllers\ApiClientController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('api_clients.index');
});

Route::controller(AuthController::class)->group(function (): void {
    Route::get('/login', 'showLoginForm')->name('login');
    Route::post('/login', 'login')->name('login.submit');
    Route::post('/logout', 'logout')->name('logout');
});

Route::middleware('auth')->group(function (): void {
    Route::controller(ApiClientController::class)->prefix('api-clients')->name('api_clients.')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{apiClient}/edit', 'edit')->name('edit');
        Route::put('/{apiClient}', 'update')->name('update');
    });

    Route::controller(UserController::class)->prefix('users')->name('users.')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}', 'show')->name('show');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
        Route::post('/{user}/change-status', 'changeStatus')->name('changeStatus');
    });
});
