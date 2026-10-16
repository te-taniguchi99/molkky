<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * マイグレーションを実行する。
     * 「1本」「2本以上」の区別をなくしたため、すでにある記録の種類を、「得点」にまとめる。
     * (得点の値は、そのまま変わらない)
     */
    public function up(): void
    {
        DB::table('game_throws')
            ->whereIn('kind', ['single', 'multiple'])
            ->update(['kind' => 'score']);
    }

    /**
     * マイグレーションを元に戻す。
     * 元の「1本」「2本以上」には、値だけからは戻せないため、何もしない。
     */
    public function down(): void
    {
        //
    }
};
