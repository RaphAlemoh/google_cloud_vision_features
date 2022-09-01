<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::get('/', function () {
    return view('welcome');
});



Route::middleware(['auth'])->group(function () {
    Route::get('/home', [UploadController::class, 'upload'])->name('home');
    Route::post('uploads/store', [UploadController::class, 'store'])->name('uploads.store');
});

