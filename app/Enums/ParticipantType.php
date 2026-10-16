<?php

namespace App\Enums;

/**
 * 大会の参加形態(個人で出るか、チームで出るか)。
 */
enum ParticipantType: string
{
    // 個人戦
    case Individual = 'individual';

    // チーム戦
    case Team = 'team';

    /**
     * 画面に表示する名前を返す。
     */
    public function label(): string
    {
        return match ($this) {
            self::Individual => '個人戦',
            self::Team => 'チーム戦',
        };
    }
}
