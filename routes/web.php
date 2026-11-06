<?php

use App\Http\Controllers\SospemController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SospemController::class, 'dashboard'])->name('dashboard');
Route::get('/feed/{issueSlug}/{platform}', [SospemController::class, 'feed'])->name('feed.platform');
Route::get('/accounts', [SospemController::class, 'accounts'])->name('accounts');
Route::get('/analytics', [SospemController::class, 'analytics'])->name('analytics');
Route::get('/methodology', [SospemController::class, 'methodology'])->name('methodology');
