<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションを実行する。
     */
    public function up(): void
    {
        // 1投ごとの記録。点数は保存せず、この記録から計算する
        Schema::create('game_throws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();

            // 入力した審判(ユーザーが削除されても、記録は残す)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // 種類: single(1本) / multiple(2本以上) / miss(ミス)
            $table->string('kind');

            // single は倒れた番号(1〜12)、multiple は倒れた本数(2〜12)、miss は 0
            $table->unsignedTinyInteger('value')->default(0);

            $table->timestamps();
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::dropIfExists('game_throws');
    }
};
