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
        Schema::create('games', function (Blueprint $table) {
            $table->id();

            // どの大会の試合か(大会を削除したら、試合も削除する)
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();

            // 回戦(トーナメントの 1回戦、2回戦 ...)。総当たり戦は、すべて 1
            $table->unsignedSmallInteger('round')->default(1);

            // 同じ回戦の中での並び順
            $table->unsignedSmallInteger('position')->default(1);

            // 進行状況: waiting(待機中) / in_progress(進行中) / finished(終了)
            $table->string('status')->default('waiting');

            // 不戦勝(相手がいない試合)かどうか
            $table->boolean('is_bye')->default(false);

            // 勝者(決まるまでは null。参加者を削除しても、試合の記録は残す)
            $table->foreignId('winner_id')->nullable()->constrained('participants')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
