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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();

            // 大会を作ったユーザー(ユーザーが削除されても、大会は残す)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // 大会名
            $table->string('name');

            // 方式: round_robin(総当たり戦) / knockout(トーナメント戦)
            $table->string('format');

            // 参加形態: individual(個人戦) / team(チーム戦)
            $table->string('participant_type');

            // チーム戦のときの、1チームの人数(個人戦では null)
            $table->unsignedTinyInteger('team_size')->nullable();

            // 勝利に必要な得点(モルックの一般的なルールは 50 点)
            $table->unsignedSmallInteger('target_score')->default(50);

            // 目標点を超えたときに戻る得点(一般的なルールは 25 点)
            $table->unsignedSmallInteger('reset_score')->default(25);

            // 失格になる連続ミスの回数(一般的なルールは 3 回)
            $table->unsignedTinyInteger('max_consecutive_misses')->default(3);

            // 進行状況: preparing(準備中) / in_progress(進行中) / finished(終了)
            $table->string('status')->default('preparing');

            $table->timestamps();
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
