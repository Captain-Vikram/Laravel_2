<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UrlController;

Route::get('/', [UrlController::class, 'index'])->name('urls.index');
Route::post('/shorten', [UrlController::class, 'store'])->name('urls.store');
Route::get('/stats/{shortCode}', [UrlController::class, 'stats'])->name('urls.stats');
Route::get('/{shortCode}', [UrlController::class, 'redirect'])->name('urls.redirect');

Route::get('/api-docs', function() {
    return view('api-docs');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [UrlController::class, 'dashboard'])->name('dashboard');
    Route::delete('/url/{code}', [UrlController::class, 'destroy'])->name('urls.destroy');
});
