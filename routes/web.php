<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::get('/', function () {
    return view('welcome');
});



Route::middleware(['auth'])->group(function () {
    Route::get('/home', [UploadController::class, 'upload'])->name('home');

    //we are using the same form to upload files and explore the features of Google cloud vision

    //route for safe search detection
    // Route::post('uploads/store', [UploadController::class, 'SafeSearchDetection'])->name('uploads.store');

    //route for detectText in image
    // Route::post('uploads/store', [UploadController::class, 'detectTextInImage'])->name('uploads.store');

    //route for detectText in image using
    // Route::post('uploads/store', [UploadController::class, 'documentTextDetection'])->name('uploads.store');

    //detect text in PDF file in GCS
    // Route::get('uploads/pdf', [UploadController::class, 'detectPDFinGCS'])->name('uploads.pdf');

    //detect faces on image upload
    // Route::post('uploads/store', [UploadController::class, 'detectFaces'])->name('uploads.store');

    //detect multiple objects on image upload
    // Route::post('uploads/store', [UploadController::class, 'detectObject'])->name('uploads.store');


    //detect Logo on image upload
    // Route::post('uploads/store', [UploadController::class, 'detectLogo'])->name('uploads.store');

    //detect Landmark on image upload
    // Route::post('uploads/store', [UploadController::class, 'detectLandmarks'])->name('uploads.store');


    //detect image properties  on image upload
    // Route::post('uploads/store', [UploadController::class, 'detectImageProperties'])->name('uploads.store');


    //detect label definition of entities or objects on image upload
    Route::post('uploads/store', [UploadController::class, 'detectLabels'])->name('uploads.store');
});
