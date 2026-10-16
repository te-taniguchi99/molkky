<?php

namespace App\Http\Controllers;

use App\Enums\ParticipantType;
use App\Enums\TournamentStatus;
use App\Http\Requests\StoreParticipantRequest;
use App\Models\Participant;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ParticipantController extends Controller
{
    /**
     * 参加者(個人またはチーム)を登録する。
     */
    public function store(StoreParticipantRequest $request, Tournament $tournament): RedirectResponse
    {
        // 大会の開始後は、参加者を変更できない(試合がすでに作られているため)
        if ($tournament->status !== TournamentStatus::Preparing) {
            Inertia::flash('toast', ['type' => 'error', 'message' => '開始した大会には、参加者を追加できません。']);

            return to_route('tournaments.show', $tournament);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($tournament, $validated) {
            if ($tournament->participant_type === ParticipantType::Individual) {
                // 個人戦: 選んだユーザーが 1 人の参加者になる。名前は、ユーザー名
                $userIds = [$validated['user_id']];
                $name = User::findOrFail($validated['user_id'])->name;
            } else {
                // チーム戦: 入力したチーム名に、選んだユーザーが所属する
                $userIds = $validated['user_ids'];
                $name = $validated['name'];
            }

            $participant = $tournament->participants()->create(['name' => $name]);

            // 所属するユーザーを登録する(どの大会かも、いっしょに記録する)
            foreach ($userIds as $userId) {
                $participant->members()->attach($userId, ['tournament_id' => $tournament->id]);
            }
        });

        return to_route('tournaments.show', $tournament);
    }

    /**
     * 参加者を削除する。
     */
    public function destroy(Participant $participant): RedirectResponse
    {
        $tournament = $participant->tournament;

        if ($tournament->status !== TournamentStatus::Preparing) {
            Inertia::flash('toast', ['type' => 'error', 'message' => '開始した大会の参加者は、削除できません。']);

            return to_route('tournaments.show', $tournament);
        }

        $participant->delete();

        return to_route('tournaments.show', $tournament);
    }
}
