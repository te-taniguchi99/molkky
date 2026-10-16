<?php

namespace App\Enums;

/**
 * 試合の進行状況。
 */
enum GameStatus: string
{
    // 待機中(まだ始まっていない)
    case Waiting = 'waiting';

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
            self::Waiting => '待機中',
            self::InProgress => '進行中',
            self::Finished => '終了',
        };
    }
}
