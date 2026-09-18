<?php

use Illuminate\Support\Facades\Route;
use Jakyeru\Larascord\Http\Controllers\DiscordController;

Route::group([
    'prefix' => config('larascord.routes.prefix', 'larascord'),
    'middleware' => config('larascord.routes.middleware', ['web']),
], function () {
    Route::get('/redirect', [DiscordController::class, 'redirect'])->name('larascord.redirect');
    Route::get('/callback', [DiscordController::class, 'callback'])->name('larascord.callback');
    Route::post('/logout', [DiscordController::class, 'logout'])->name('larascord.logout');

    Route::middleware(config('larascord.routes.authenticated_middleware', ['web', 'auth']))->group(function () {
        Route::get('/link', [DiscordController::class, 'link'])->name('larascord.link');
        Route::delete('/unlink', [DiscordController::class, 'unlink'])->name('larascord.unlink');
    });
});

if (config('larascord.routes.login_alias', false)) {
    Route::get('/login', [DiscordController::class, 'redirect'])
        ->middleware(config('larascord.routes.middleware', ['web']))
        ->name('login');
}
