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
        Schema::table('tournaments', function (Blueprint $table) {
            // 開催日時(決まっていないときは null)
            $table->dateTime('held_at')->nullable()->after('name');

            // 開催場所(決まっていないときは null)
            $table->string('venue')->nullable()->after('held_at');
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['held_at', 'venue']);
        });
    }
};
