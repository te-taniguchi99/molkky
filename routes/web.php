<?php

use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // 大会(一覧・新規作成画面・保存)
    Route::resource('tournaments', TournamentController::class)->only(['index', 'create', 'store']);
});

require __DIR__.'/settings.php';
