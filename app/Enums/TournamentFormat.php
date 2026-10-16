<?php

namespace App\Enums;

/**
 * 大会の方式(対戦の組み方)。
 */
enum TournamentFormat: string
{
    // 総当たり戦(全員が、全員と対戦する)
    case RoundRobin = 'round_robin';

    // トーナメント戦(勝ち抜き)
    case Knockout = 'knockout';

    /**
     * 画面に表示する名前を返す。
     */
    public function label(): string
    {
        return match ($this) {
            self::RoundRobin => '総当たり戦',
            self::Knockout => 'トーナメント戦',
        };
    }
}
