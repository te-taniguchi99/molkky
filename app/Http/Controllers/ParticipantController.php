<?php

namespace App\Http\Controllers;

use App\Enums\ParticipantType;
use App\Enums\TournamentStatus;
use App\Http\Requests\StoreParticipantRequest;
use App\Models\Participant;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
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

        // 個人戦は 1 人。チーム戦は、入力した人数(未入力なら、大会で決めた目安の人数)
        $memberCount = $tournament->participant_type === ParticipantType::Individual
            ? 1
            : ($validated['member_count'] ?? $tournament->team_size ?? 1);

        $tournament->participants()->create([
            'name' => $validated['name'],
            'member_count' => $memberCount,
        ]);

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
