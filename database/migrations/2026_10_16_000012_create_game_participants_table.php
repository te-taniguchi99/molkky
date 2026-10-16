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
        // 「どの試合に、どの参加者が出るか」を表す中間テーブル
        Schema::create('game_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();

            // 投げる順番(1 から始まる)
            $table->unsignedTinyInteger('throw_order');

            $table->timestamps();

            // 同じ試合に、同じ参加者を重複して入れない
            $table->unique(['game_id', 'participant_id']);
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::dropIfExists('game_participants');
    }
};
