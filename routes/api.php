<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AnalysisController;
use App\Http\Controllers\API\PaymentController;

// Auth rotaları (Giriş, Kayıt vb.)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Kimlik doğrulaması gerektiren rotalar
Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);

    // Analiz rotaları
    Route::post('analyze', [AnalysisController::class, 'analyze']);
    Route::get('analyses', [AnalysisController::class, 'getAnalysisHistory']);
    Route::get('analyses/{id}/pdf', [AnalysisController::class, 'downloadPdf']);
    Route::get('analyses/{id}/audio', [AnalysisController::class, 'getAnalysisAudio']);
    
    // YORUM: Reklam izlendikten sonra kredisi olmayan kullanıcı için bu endpoint çağrılacak.
    Route::post('analyze-with-ad', [AnalysisController::class, 'analyzeWithAd']);

    // Ödeme rotaları
    // YORUM: Mobil uygulamadan gelen satın alma bilgisini doğrulamak ve kredi eklemek için.
    Route::post('credits/purchase', [PaymentController::class, 'purchaseCredits']);
});
