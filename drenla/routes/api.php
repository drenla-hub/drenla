<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PublicChatController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('site')->group(function () {
    Route::get('/settings', [SiteController::class, 'settings']);
    Route::get('/home', [SiteController::class, 'home']);
    Route::get('/work', [SiteController::class, 'workIndex']);
    Route::get('/work/{slug}', [SiteController::class, 'workShow']);
    Route::get('/insights', [SiteController::class, 'insightsIndex']);
    Route::get('/insights/{slug}', [SiteController::class, 'insightShow']);
    Route::get('/resources', [SiteController::class, 'resourcesIndex']);
    Route::get('/focus-areas', [SiteController::class, 'focusAreasIndex']);
    Route::post('/contact', [ContactController::class, 'store']);
});

// Public site chat (anonymous visitor <-> AI assistant, with admin takeover —
// see app/Http/Controllers/Admin/ChatSessionController for the admin side).
Route::prefix('chat')->group(function () {
    Route::post('/sessions', [PublicChatController::class, 'store']);
    Route::get('/sessions/{sessionToken}', [PublicChatController::class, 'show']);
    Route::get('/sessions/{sessionToken}/messages', [PublicChatController::class, 'messages']);
    Route::post('/sessions/{sessionToken}/messages', [PublicChatController::class, 'storeMessage'])
        ->middleware('throttle:public-chat');
});
