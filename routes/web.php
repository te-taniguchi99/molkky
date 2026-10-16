<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\GameThrowController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\StandingController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// トップは、メイン画面へ移動する(ログインしていなければ、ログイン画面へ移動する)
Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    // メイン画面(大会の一覧・進行中の試合・順位表・ユーザー管理への入口)
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // 大会(一覧・新規作成画面・保存)
    Route::resource('tournaments', TournamentController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::post('tournaments/{tournament}/start', [TournamentController::class, 'start'])->name('tournaments.start');

    // 参加者(大会に、個人またはチームを登録・削除する)
    Route::post('tournaments/{tournament}/participants', [ParticipantController::class, 'store'])->name('participants.store');
    Route::delete('participants/{participant}', [ParticipantController::class, 'destroy'])->name('participants.destroy');

    // 進行中の試合の一覧・点数の入力
    Route::get('games', [GameController::class, 'index'])->name('games.index');
    Route::get('games/{game}', [GameController::class, 'show'])->name('games.show');
    Route::post('games/{game}/throws', [GameThrowController::class, 'store'])->name('games.throws.store');
    Route::delete('games/{game}/throws/last', [GameThrowController::class, 'destroy'])->name('games.throws.destroy');

    // 順位表(大会を選ぶ → 大会の順位表)
    Route::get('standings', [StandingController::class, 'index'])->name('standings.index');
    Route::get('standings/{tournament}', [StandingController::class, 'show'])->name('standings.show');

    // ユーザー管理(一覧・追加・有効/無効の切り替え)
    Route::resource('users', UserController::class)->only(['index', 'create', 'store']);
    Route::patch('users/{user}/active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
});

require __DIR__.'/settings.php';
