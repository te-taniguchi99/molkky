<?php

namespace App\Support;

/**
 * 試合の点数を、投擲の記録から計算する。
 *
 * 点数そのものは保存せず、記録(投げた順の一覧)から、毎回計算する。
 * こうすることで、「1つ前に戻す」や、ルールの変更に強くなり、計算が1か所にまとまる。
 *
 * このクラスは、データベースを使わない(配列だけで計算する)ため、単体で動作を確認できる。
 *
 * ルール(公式資料より):
 * - 1本だけ倒れたら、その数字が得点。2本以上倒れたら、倒れた本数が得点(入力は、得点をそのまま受け取る)
 * - ちょうど目標点(通常 50 点)になったら勝ち
 * - 目標点を超えたら、戻る得点(通常 25 点)に戻る
 * - 連続でミスが上限(通常 3 回)に達したら失格。失格で残りが1つになったら、その1つが勝ち
 */
class ScoreCalculator
{
    /**
     * 点数と手番を計算する。
     *
     * @param  array<int, int>  $participantIds  投げる順に並べた、参加者の id
     * @param  array<int, array{participant_id: int, kind: string, value: int}>  $throws  投げた順の記録(kind は score / miss)
     * @return array{participants: array<int, array{score: int, misses: int, eliminated: bool, throw_count: int}>, current_participant_id: int|null, winner_id: int|null}
     */
    public static function calculate(
        array $participantIds,
        array $throws,
        int $targetScore,
        int $resetScore,
        int $maxMisses,
    ): array {
        // 参加者ごとの状態を、0 点から始める
        $participants = [];
        foreach ($participantIds as $id) {
            $participants[$id] = ['score' => 0, 'misses' => 0, 'eliminated' => false, 'throw_count' => 0];
        }

        $winnerId = null;
        $lastThrowerId = null;

        foreach ($throws as $throw) {
            // 勝敗が決まったあとの記録は、無視する
            if ($winnerId !== null) {
                break;
            }

            $id = $throw['participant_id'];
            $lastThrowerId = $id;
            $participants[$id]['throw_count']++;

            if ($throw['kind'] === 'miss') {
                // ミス: 連続ミスを数える。上限に達したら失格
                $participants[$id]['misses']++;

                if ($participants[$id]['misses'] >= $maxMisses) {
                    $participants[$id]['eliminated'] = true;
                }
            } else {
                // 倒れた: 連続ミスを 0 に戻して、得点を加える
                $participants[$id]['misses'] = 0;
                $participants[$id]['score'] += $throw['value'];

                if ($participants[$id]['score'] > $targetScore) {
                    // 目標点を超えたら、戻る得点まで戻る
                    $participants[$id]['score'] = $resetScore;
                } elseif ($participants[$id]['score'] === $targetScore) {
                    // ちょうど目標点: 勝ち
                    $winnerId = $id;
                }
            }

            // 失格で、残りが1つだけになったら、その1つが勝ち
            if ($winnerId === null) {
                $active = array_keys(array_filter($participants, fn (array $p) => ! $p['eliminated']));

                if (count($active) === 1) {
                    $winnerId = $active[0];
                }
            }
        }

        return [
            'participants' => $participants,
            'current_participant_id' => $winnerId === null
                ? self::nextParticipantId($participantIds, $participants, $lastThrowerId)
                : null,
            'winner_id' => $winnerId,
        ];
    }

    /**
     * 次に投げる参加者を返す(最後に投げた人の次から、失格していない人を探す)。
     *
     * @param  array<int, int>  $participantIds
     * @param  array<int, array{eliminated: bool}>  $participants
     */
    private static function nextParticipantId(array $participantIds, array $participants, ?int $lastThrowerId): ?int
    {
        $count = count($participantIds);

        // まだ誰も投げていないときは、先頭の前(-1)から探し始める
        $start = $lastThrowerId === null ? -1 : (int) array_search($lastThrowerId, $participantIds, true);

        for ($step = 1; $step <= $count; $step++) {
            $id = $participantIds[($start + $step) % $count];

            if (! $participants[$id]['eliminated']) {
                return $id;
            }
        }

        return null;
    }
}
