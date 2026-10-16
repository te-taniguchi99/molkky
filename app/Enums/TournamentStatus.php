<?php

namespace App\Enums;

/**
 * 大会の進行状況。
 */
enum TournamentStatus: string
{
    // 準備中(参加者や対戦を設定している段階)
    case Preparing = 'preparing';

    // 進行中
    case InProgress = 'in_progress';

    // 終了
    case Finished = 'finished';

    /**
     * 画面に表示する名前を返す。
     */
    public function label(): string
    {
        return match ($this) {
            self::Preparing => '準備中',
            self::InProgress => '進行中',
            self::Finished => '終了',
        };
    }
}
