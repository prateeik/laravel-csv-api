<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CSVUploadController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::post('/upload-csv', [CSVUploadController::class, 'upload']);

Route::prefix('csv')->group(function () {
    
    // Upload CSV file for validation and processing
    Route::post('/upload', [CSVUploadController::class, 'upload'])->name('csv.upload');
    
    // Export processed data: all, unique, or duplicates
    // Example: /api/csv/export?file=uploads/csv/file.csv&type=duplicates
    Route::get('/export', [CSVUploadController::class, 'export'])->name('csv.export');
    
});