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
        Schema::table('users', function (Blueprint $table) {
            // アカウントが有効かどうか(true = 有効 / false = 無効)
            // default(true): 既存のユーザーと、新規登録のユーザーは、有効な状態で作られる
            // after('password'): 列の並びを見やすくするため、password の次に置く
            $table->boolean('is_active')->default(true)->after('password');
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 追加した列を削除する
            $table->dropColumn('is_active');
        });
    }
};
