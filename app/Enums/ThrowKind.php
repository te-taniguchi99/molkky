<?php

namespace App\Enums;

/**
 * 1投の種類。
 * 1本だけ倒れた場合も、2本以上倒れた場合も、得点(1〜12)をそのまま入力する。
 */
enum ThrowKind: string
{
    // 得点が入った(得点は 1〜12)
    case Score = 'score';

    // 1本も倒れなかった(ミス)
    case Miss = 'miss';
}
