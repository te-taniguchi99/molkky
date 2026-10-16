<?php

use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// トップは、メイン画面へ移動する(ログインしていなければ、ログイン画面へ移動する)
Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    // メイン画面(大会の一覧・進行中の試合・順位表・ユーザー管理への入口)
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // 大会(一覧・新規作成画面・保存)
    Route::resource('tournaments', TournamentController::class)->only(['index', 'create', 'store']);

    // 進行中の試合・順位表(試合のデータを作る段階で、中身を作る)
    Route::inertia('games', 'games/Index')->name('games.index');
    Route::inertia('standings', 'standings/Index')->name('standings.index');

    // ユーザー管理(一覧・追加・有効/無効の切り替え)
    Route::resource('users', UserController::class)->only(['index', 'create', 'store']);
    Route::patch('users/{user}/active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
});

require __DIR__.'/settings.php';
