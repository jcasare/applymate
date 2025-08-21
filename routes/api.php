<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('ai')->group(function () {
        Route::post('/generate-text', [AIController::class, 'generateText']);
        Route::post('/generate-embedding', [AIController::class, 'generateEmbedding']);
        Route::post('/analyze-image', [AIController::class, 'analyzeImage']);
        Route::get('/providers', [AIController::class, 'getProviders']);
        
        Route::post('/cover-letter', [AIController::class, 'generateCoverLetter']);
        Route::post('/optimize-resume', [AIController::class, 'optimizeResume']);
    });
    
    Route::prefix('applications')->group(function () {
        Route::post('/{application}/regenerate', [\App\Http\Controllers\ApplicationController::class, 'regenerate']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});