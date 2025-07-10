<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;

Route::get('/', function () {
    return view('welcome');
});

// Test Interface Routes (only enabled in debug mode)
Route::middleware('web')->group(function () {
    Route::get('/test', [TestController::class, 'index'])->name('test.index');
    Route::post('/test', [TestController::class, 'index'])->name('test.login');
    Route::post('/test/api', [TestController::class, 'apiTest'])->name('test.api');
});
