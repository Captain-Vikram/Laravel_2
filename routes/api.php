<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UrlController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// URL Shortener API Routes
Route::post('/shorten', [UrlController::class, 'apiShorten']);
Route::get('/url/{code}', [UrlController::class, 'getUrl']);
Route::get('/stats/{code}', [UrlController::class, 'getUrlStats']);
Route::delete('/url/{code}', [UrlController::class, 'deleteUrl'])->middleware('auth:sanctum');

// In web.php routes file
Route::get('/{code}', [UrlController::class, 'redirect']);