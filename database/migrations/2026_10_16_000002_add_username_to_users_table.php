<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * マイグレーションを実行する。
     */
    public function up(): void
    {
        // 1. ログインID用の列を追加する(まだ重複チェックは付けない)
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('name');
        });

        // 2. すでにいるユーザーには、メールアドレスの「@」より前の部分を、ログインIDとして入れる
        $taken = [];

        DB::table('users')->orderBy('id')->each(function ($user) use (&$taken) {
            // 使える文字(英小文字・数字・ピリオド・アンダースコア・ハイフン)だけを残す
            $base = Str::of(Str::before((string) $user->email, '@'))
                ->lower()
                ->replaceMatches('/[^a-z0-9._-]/', '')
                ->toString();

            $base = $base === '' ? 'user' : $base;

            // 重複したときは、末尾にユーザーの id を付けて区別する
            $username = $base;
            while (isset($taken[$username])) {
                $username = $base.$user->id;
                $base = $username;
            }
            $taken[$username] = true;

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        // 3. ログインIDを重複不可にし、メールアドレスは空でもよい列にする(使わなくなるため)
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 追加した重複チェックと列を削除する
            // (email を空不可に戻すと、空のユーザーがいる場合に失敗するため、ここでは戻さない)
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
