<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V2\BookController as V2BookController;
use Illuminate\Support\Facades\Route;

/*
| Everything here is prefixed with /api (see "Your First API Routes").
| 'throttle:api' uses the named limiter in AppServiceProvider.
*/

Route::prefix('v1')->name('v1.')->middleware('throttle:api')->group(function () {
    Route::get('/ping', fn () => ['pong' => true, 'time' => now()->toIso8601String()])->name('ping');
    Route::get('/stats', StatsController::class)->name('stats');

    // Auth
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('register');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('logout');
    });

    // Books: public reads...
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/{book}', [BookController::class, 'show'])->whereNumber('book')->name('books.show');

    // ...writes need a token with the books:write ability (see "Token Abilities & Policies")
    Route::middleware(['auth:sanctum', 'abilities:books:write'])->group(function () {
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::patch('/books/{book}', [BookController::class, 'update'])->whereNumber('book')->name('books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->whereNumber('book')->name('books.destroy');
    });
});

// A newer, incompatible version lives side by side (see "Versioning Your API").
Route::prefix('v2')->name('v2.')->middleware('throttle:api')->group(function () {
    Route::get('/books', [V2BookController::class, 'index'])->name('books.index');
});
