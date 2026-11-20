<?php

use App\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

// The course
Route::get('/', [CourseController::class, 'home'])->name('home');
Route::get('/lessons/{slug}', [CourseController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('lessons.show');
Route::post('/lessons/{slug}/complete', [CourseController::class, 'complete'])->where('slug', '[a-z0-9-]+')->name('lessons.complete');
Route::get('/search', [CourseController::class, 'search'])->name('search');
Route::post('/progress/reset', [CourseController::class, 'reset'])->name('progress.reset');

// The API playground: call this app's real API from the browser
Route::view('/playground', 'playground')->name('playground');
