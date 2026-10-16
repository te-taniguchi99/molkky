<?php

namespace App\Enums;

/**
 * 1投の種類。
 */
enum ThrowKind: string
{
    // 1本だけ倒れた(得点は、その番号)
    case Single = 'single';

    // 2本以上倒れた(得点は、倒れた本数)
    case Multiple = 'multiple';

    // 1本も倒れなかった(ミス)
    case Miss = 'miss';
}
