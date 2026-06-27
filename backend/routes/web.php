<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

// Public AI chat (Gemini-style) — the website front door.
Route::get('/', [ChatController::class, 'index']);
Route::get('/chat', [ChatController::class, 'index'])->name('chat');
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
Route::get('/chat/card', [ChatController::class, 'card'])->name('chat.card');
Route::get('/chat/status', [ChatController::class, 'status'])->name('chat.status');
Route::post('/chat/reset', [ChatController::class, 'reset'])->name('chat.reset');

// CRM (Filament) lives under /admin.
