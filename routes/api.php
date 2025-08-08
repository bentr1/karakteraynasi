<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AnalysisController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    // ...
    
    // Analiz Rotaları
    Route::post('/analyze', [AnalysisController::class, 'startAnalysisWithCredit']); // Kredi kontrolü yapan rota
    Route::post('/analyze-with-ad', [AnalysisController::class, 'startAnalysisWithAd']); // Reklam sonrası çalışan rota
    Route::get('/analyses', [AnalysisController::class, 'index']);
    Route::get('/analysis/{id}/pdf', [AnalysisController::class, 'downloadPdf']);

    // Mağaza Rotaları
    Route::post('/store/add-credits', [AnalysisController::class, 'addCredits']); // Kredi ekleme rotası
});