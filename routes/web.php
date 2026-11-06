<?php

use App\Http\Controllers\SospemController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SospemController::class, 'dashboard'])->name('dashboard');
Route::get('/feed/{issueSlug}/{platform}', [SospemController::class, 'feed'])->name('feed.platform');
Route::post('/ingest', [SospemController::class, 'ingest'])->name('ingest.post');

Route::get('/issues', [SospemController::class, 'issues'])->name('issues.index');
Route::post('/issues', [SospemController::class, 'storeIssue'])->name('issues.store');
Route::patch('/issues/{issue}/trend', [SospemController::class, 'updateIssueTrend'])->name('issues.update-trend');
Route::delete('/issues/{issue}', [SospemController::class, 'destroyIssue'])->name('issues.destroy');
Route::post('/posts', [SospemController::class, 'storePost'])->name('posts.store');

Route::get('/accounts', [SospemController::class, 'accounts'])->name('accounts');
Route::post('/accounts', [SospemController::class, 'storeAccount'])->name('accounts.store');
Route::delete('/accounts/{account}', [SospemController::class, 'destroyAccount'])->name('accounts.destroy');

Route::get('/analytics', [SospemController::class, 'analytics'])->name('analytics');
Route::get('/methodology', [SospemController::class, 'methodology'])->name('methodology');
