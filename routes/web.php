<?php

use App\Http\Controllers\SospemController;
use App\Models\Issue;
use Illuminate\Support\Facades\Route;

Route::get('/', [SospemController::class, 'dashboard'])->name('dashboard');
Route::get('/feed/{issueSlug}/{platform}', [SospemController::class, 'feed'])->name('feed.platform');

Route::post('/ingest', [SospemController::class, 'ingest'])->name('ingest.post');
Route::post('/api/ingest', [SospemController::class, 'ingest'])->name('api.ingest');
Route::post('/api/ingest-batch', [SospemController::class, 'ingestBatch'])->name('api.ingest-batch');
Route::get('/api/live-stats', [SospemController::class, 'liveStats'])->name('api.live-stats');
Route::get('/api/issues', fn () => response()->json(Issue::orderBy('rank')->get(['id', 'rank', 'title', 'slug', 'keywords', 'trend', 'total_posts'])))->name('api.issues');

Route::get('/issues', [SospemController::class, 'issues'])->name('issues.index');
Route::post('/issues', [SospemController::class, 'storeIssue'])->name('issues.store');
Route::post('/issues/sync-json', [SospemController::class, 'syncJsonFile'])->name('issues.sync-json');
Route::patch('/issues/{issue}/trend', [SospemController::class, 'updateIssueTrend'])->name('issues.update-trend');
Route::delete('/issues/{issue}', [SospemController::class, 'destroyIssue'])->name('issues.destroy');
Route::post('/posts', [SospemController::class, 'storePost'])->name('posts.store');

Route::get('/accounts', [SospemController::class, 'accounts'])->name('accounts');
Route::post('/accounts', [SospemController::class, 'storeAccount'])->name('accounts.store');
Route::delete('/accounts/{account}', [SospemController::class, 'destroyAccount'])->name('accounts.destroy');

Route::get('/analytics', [SospemController::class, 'analytics'])->name('analytics');
Route::get('/chatbot', [SospemController::class, 'chatbot'])->name('chatbot');
Route::post('/api/chatbot/ask', [SospemController::class, 'askChatbot'])->name('api.chatbot.ask');
Route::get('/api/chatbot/context', [SospemController::class, 'chatbotContext'])->name('api.chatbot.context');
Route::get('/methodology', [SospemController::class, 'methodology'])->name('methodology');
