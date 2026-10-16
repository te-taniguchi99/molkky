<?php

namespace App\Actions\Tournaments;

use App\Enums\GameStatus;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * 大会を開始して、参加者から試合を自動で作る。
 */
class StartTournament
{
    /**
     * 大会を開始する。
     *
     * @param  bool  $shuffle  true のとき、総当たり戦の試合の順番を、ランダムに並べ替える
     */
    public function __invoke(Tournament $tournament, bool $shuffle = false): void
    {
        // 登録した順に、参加者の id を並べる
        $ids = $tournament->participants()->orderBy('id')->pluck('id')->all();

        DB::transaction(function () use ($tournament, $ids, $shuffle) {
            // 作る試合を、「出る参加者の id の配列」の一覧として用意する
            $matches = $tournament->format === TournamentFormat::RoundRobin
                ? $this->roundRobin($ids, $shuffle)
                : $this->knockoutFirstRound($ids);

            foreach ($matches as $index => $participantIds) {
                // 参加者が1人だけの試合は、不戦勝(その場で終了し、勝者も決まる)
                $isBye = count($participantIds) === 1;

                $game = Game::create([
                    'tournament_id' => $tournament->id,
                    'round' => 1,
                    'position' => $index + 1,
                    'status' => $isBye ? GameStatus::Finished : GameStatus::Waiting,
                    'is_bye' => $isBye,
                    'winner_id' => $isBye ? $participantIds[0] : null,
                ]);

                // 投げる順番は、並んでいる順(1 から始まる)
                foreach (array_values($participantIds) as $order => $participantId) {
                    $game->participants()->attach($participantId, ['throw_order' => $order + 1]);
                }
            }

            $tournament->update(['status' => TournamentStatus::InProgress]);
        });
    }

    /**
     * 総当たり戦: 参加者の全ての組み合わせを作る。
     * $shuffle が true のときは、試合の順番(戦う順)を、ランダムに並べ替える。
     *
     * @param  array<int, int>  $ids
     * @return array<int, array<int, int>>
     */
    private function roundRobin(array $ids, bool $shuffle = false): array
    {
        $matches = [];
        $count = count($ids);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $matches[] = [$ids[$i], $ids[$j]];
            }
        }

        // 組み合わせは変えずに、試合の順番だけを入れ替える
        if ($shuffle) {
            shuffle($matches);
        }

        return $matches;
    }

    /**
     * トーナメント戦の 1回戦を作る。
     * 参加数が 2 の累乗にならない分は、登録順の先頭から、不戦勝にする。
     *
     * @param  array<int, int>  $ids
     * @return array<int, array<int, int>>
     */
    private function knockoutFirstRound(array $ids): array
    {
        $count = count($ids);

        // 参加数以上で、最も小さい 2 の累乗(例: 5 人なら 8、4 人なら 4)
        $size = 1;
        while ($size < $count) {
            $size *= 2;
        }

        // 不戦勝になる人数(例: 5 人なら 8 - 5 = 3 人)
        $byes = $size - $count;

        // 先頭から $byes 人が不戦勝(1人だけの試合)。残りは、2人ずつ対戦する
        $matches = array_map(fn (int $id) => [$id], array_slice($ids, 0, $byes));

        return [...$matches, ...array_chunk(array_slice($ids, $byes), 2)];
    }
}
