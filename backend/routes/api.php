<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogController;

Route::post('/catalog/generate', [CatalogController::class, 'generate']);
Route::post('/catalog/generate-async', [CatalogController::class, 'generateAsync']);
Route::get('/catalog/progress/{sessionId}', [CatalogController::class, 'checkProgress']);
Route::get('/catalog/download/{sessionId}', [CatalogController::class, 'downloadCatalog']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
