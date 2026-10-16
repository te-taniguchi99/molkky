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
        // 「どの参加者(個人・チーム)に、どのユーザーが所属するか」を表す中間テーブル
        Schema::create('participant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // どの大会の所属か(同じ大会で、同じユーザーが複数の参加者に入ることを、DB でも防ぐため)
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['participant_id', 'user_id']);
            $table->unique(['tournament_id', 'user_id']);
        });

        // 個人戦の名前は、ユーザー名になる(同じ名前のユーザーがいてもよい)ため、名前の重複禁止を外す
        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique(['tournament_id', 'name']);
        });

        // 人数は、所属するユーザーの数で決まるため、人数の列は不要になる
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('member_count');
        });

        // 大会の「1チームの人数」も、使わなくなる
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('team_size');
        });
    }

    /**
     * マイグレーションを元に戻す。
     * (名前の重複禁止は、重複した名前がある場合に失敗するため、戻さない)
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->unsignedTinyInteger('team_size')->nullable()->after('participant_type');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->unsignedTinyInteger('member_count')->default(1)->after('name');
        });

        Schema::dropIfExists('participant_user');
    }
};
