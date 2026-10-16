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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();

            // どの大会の参加者か(大会を削除したら、参加者も削除する)
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();

            // 個人名、またはチーム名
            $table->string('name');

            // 人数(個人戦は 1。チーム戦はチームごとに自由に決められる)
            $table->unsignedTinyInteger('member_count')->default(1);

            $table->timestamps();

            // 同じ大会の中で、同じ名前を重複させない
            $table->unique(['tournament_id', 'name']);
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
