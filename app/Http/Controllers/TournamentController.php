<?php

namespace App\Http\Controllers;

use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Http\Requests\StoreTournamentRequest;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    /**
     * 大会の一覧を表示する。
     */
    public function index(): Response
    {
        // 新しい大会が上に来るように、作成日時の降順で取得する
        $tournaments = Tournament::query()
            ->latest()
            ->get()
            ->map(fn (Tournament $tournament) => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'format' => $tournament->format->label(),
                'participant_type' => $tournament->participant_type->label(),
                'team_size' => $tournament->team_size,
                'status' => $tournament->status->label(),
            ]);

        return Inertia::render('tournaments/Index', [
            'tournaments' => $tournaments,
        ]);
    }

    /**
     * 大会の設定画面(新規作成)を表示する。
     */
    public function create(): Response
    {
        return Inertia::render('tournaments/Create', [
            // 選択肢を、「値」と「表示名」の組にして画面へ渡す
            'formats' => collect(TournamentFormat::cases())
                ->map(fn (TournamentFormat $case) => ['value' => $case->value, 'label' => $case->label()]),
            'participantTypes' => collect(ParticipantType::cases())
                ->map(fn (ParticipantType $case) => ['value' => $case->value, 'label' => $case->label()]),
        ]);
    }

    /**
     * 大会を保存する。
     */
    public function store(StoreTournamentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // 個人戦のときは、1チームの人数を持たせない
        if ($validated['participant_type'] === ParticipantType::Individual->value) {
            $validated['team_size'] = null;
        }

        Tournament::create([...$validated, 'user_id' => $request->user()->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => '大会を作成しました。']);

        return to_route('tournaments.index');
    }
}
