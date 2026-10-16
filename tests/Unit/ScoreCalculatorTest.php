<?php

use App\Support\ScoreCalculator;

/**
 * 記録を、短く書くための補助関数。
 * 例: t(1, 'score', 7) = 参加者 1 が、7 点を取った
 */
function t(int $participantId, string $kind, int $value = 0): array
{
    return ['participant_id' => $participantId, 'kind' => $kind, 'value' => $value];
}

function calc(array $throws, array $ids = [1, 2], int $target = 50, int $reset = 25, int $maxMisses = 3): array
{
    return ScoreCalculator::calculate($ids, $throws, $target, $reset, $maxMisses);
}

test('the first turn goes to the first participant', function () {
    $result = calc([]);

    expect($result['current_participant_id'])->toBe(1)
        ->and($result['winner_id'])->toBeNull();
});

test('turns alternate and points are added', function () {
    $result = calc([t(1, 'score', 7), t(2, 'score', 3)]);

    expect($result['participants'][1]['score'])->toBe(7)
        ->and($result['participants'][2]['score'])->toBe(3)
        ->and($result['current_participant_id'])->toBe(1);
});

test('a participant wins by reaching the target exactly', function () {
    $result = calc([t(1, 'score', 12), t(2, 'miss'), t(1, 'score', 12), t(2, 'miss'), t(1, 'score', 12), t(2, 'score', 1), t(1, 'score', 12), t(2, 'score', 1), t(1, 'score', 2)]);

    // 12 + 12 + 12 + 12 + 2 = 50
    expect($result['participants'][1]['score'])->toBe(50)
        ->and($result['winner_id'])->toBe(1)
        ->and($result['current_participant_id'])->toBeNull();
});

test('going over the target resets the score', function () {
    $result = calc([t(1, 'score', 12), t(2, 'miss'), t(1, 'score', 12), t(2, 'miss'), t(1, 'score', 12), t(2, 'score', 1), t(1, 'score', 12), t(2, 'score', 1), t(1, 'score', 12)]);

    // 48 + 12 = 60 → 50 を超えるので 25 に戻る
    expect($result['participants'][1]['score'])->toBe(25)
        ->and($result['winner_id'])->toBeNull();
});

test('three consecutive misses eliminate a participant and the other wins', function () {
    $result = calc([t(1, 'miss'), t(2, 'score', 5), t(1, 'miss'), t(2, 'score', 5), t(1, 'miss')]);

    expect($result['participants'][1]['eliminated'])->toBeTrue()
        ->and($result['winner_id'])->toBe(2);
});

test('a hit resets the consecutive miss count', function () {
    $result = calc([t(1, 'miss'), t(2, 'score', 5), t(1, 'miss'), t(2, 'score', 5), t(1, 'score', 1), t(2, 'score', 5), t(1, 'miss')]);

    expect($result['participants'][1]['misses'])->toBe(1)
        ->and($result['participants'][1]['eliminated'])->toBeFalse()
        ->and($result['winner_id'])->toBeNull();
});

test('an eliminated participant is skipped when three or more participate', function () {
    // 参加者 2 が 3 回連続ミスで失格になる(1 → 2 → 3 の順に投げる)
    $throws = [];
    foreach (range(1, 3) as $_) {
        $throws[] = t(1, 'score', 1);
        $throws[] = t(2, 'miss');
        $throws[] = t(3, 'score', 1);
    }

    $result = calc($throws, [1, 2, 3]);

    // 失格後は、3 の次は 1 に戻り、2 は飛ばされる
    expect($result['participants'][2]['eliminated'])->toBeTrue()
        ->and($result['winner_id'])->toBeNull()
        ->and($result['current_participant_id'])->toBe(1);
});

test('the rules can be changed per tournament', function () {
    // 目標点 10、戻る得点 5、連続ミス上限 2
    $result = calc([t(1, 'score', 12)], [1, 2], 10, 5, 2);

    expect($result['participants'][1]['score'])->toBe(5);

    $result = calc([t(1, 'miss'), t(2, 'score', 1), t(1, 'miss')], [1, 2], 10, 5, 2);

    expect($result['participants'][1]['eliminated'])->toBeTrue()
        ->and($result['winner_id'])->toBe(2);
});
