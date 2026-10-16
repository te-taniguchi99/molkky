<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションを実行する。
     */
    public function up(): void
    {
        // 1. すでに同じ名前の大会がある場合は、2つ目以降の名前の末尾に番号を付けて区別する
        //    (例: 「第1回大会」「第1回大会 (2)」)。重複したままでは、重複禁止を付けられないため
        $used = DB::table('tournaments')->pluck('name')->flip()->all();
        $seen = [];

        DB::table('tournaments')->orderBy('id')->each(function ($tournament) use (&$seen, &$used) {
            // 最初に出てきた名前は、そのまま使う
            if (! isset($seen[$tournament->name])) {
                $seen[$tournament->name] = 1;

                return;
            }

            // 2つ目以降は、まだ使われていない番号を探して付ける
            do {
                $seen[$tournament->name]++;
                $newName = $tournament->name.' ('.$seen[$tournament->name].')';
            } while (isset($used[$newName]));

            $used[$newName] = true;
            DB::table('tournaments')->where('id', $tournament->id)->update(['name' => $newName]);
        });

        // 2. 大会名を、重複不可にする
        Schema::table('tournaments', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * マイグレーションを元に戻す。
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
