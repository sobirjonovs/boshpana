<?php

use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\IngestController;
use App\Http\Controllers\Api\NegotiationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\SearchRequestController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Boshpana.ai API (v1)
|--------------------------------------------------------------------------
| Consumed by: the Telegram bot (service.token:bot), the parser service
| (service.token:ingest) and the AI userbot (service.token:userbot).
*/

Route::prefix('v1')->group(function () {

    Route::get('health', fn () => response()->json(['ok' => true, 'service' => 'boshpana.ai']));

    // ---- Public chat API (Flutter mobile app) — guided cards + free text ---
    Route::prefix('chat')->middleware('throttle:60,1')->group(function () {
        Route::post('card', [ChatApiController::class, 'card']);
        Route::post('send', [ChatApiController::class, 'send']);
        Route::get('status/{search}', [ChatApiController::class, 'status']);
    });

    // ---- Bot-facing -------------------------------------------------------
    Route::middleware('service.token:bot')->group(function () {
        // Users
        Route::post('users/sync', [UserController::class, 'sync']);
        Route::get('users/{telegramId}', [UserController::class, 'show']);
        Route::patch('users/{telegramId}', [UserController::class, 'update']);

        // Reference data
        Route::get('regions', [RegionController::class, 'index']);
        Route::get('regions/{region}/districts', [RegionController::class, 'districts']);

        // Search requests
        Route::post('search-requests', [SearchRequestController::class, 'store']);
        Route::get('search-requests/{searchRequest}', [SearchRequestController::class, 'show']);
        Route::patch('search-requests/{searchRequest}', [SearchRequestController::class, 'update']);
        Route::post('search-requests/{searchRequest}/start', [SearchRequestController::class, 'start']);
        Route::post('search-requests/{searchRequest}/cancel', [SearchRequestController::class, 'cancel']);
        Route::get('search-requests/{searchRequest}/results', [SearchRequestController::class, 'results']);

        // Billing
        Route::get('plans', [PlanController::class, 'index']);
        Route::post('payments', [PaymentController::class, 'store']);
        Route::get('payments/{payment}', [PaymentController::class, 'show']);
    });

    // ---- Parser-facing (ingestion) ---------------------------------------
    Route::middleware('service.token:ingest')->group(function () {
        Route::get('ingest/sources', [IngestController::class, 'sources']);
        Route::post('ingest/listings', [IngestController::class, 'listings']);
    });

    // ---- Userbot-facing (AI negotiation transport) -----------------------
    Route::middleware('service.token:userbot')->group(function () {
        Route::get('negotiation/tasks', [NegotiationController::class, 'tasks']);
        Route::post('negotiation/{conversation}/reply', [NegotiationController::class, 'reply']);
        Route::post('negotiation/{conversation}/outcome', [NegotiationController::class, 'outcome']);
    });
});
