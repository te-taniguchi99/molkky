<?php

namespace App\Http\Controllers;

use App\Actions\Tournaments\StartTournament;
use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Http\Requests\StoreTournamentRequest;
use App\Models\Game;
use App\Models\Participant;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

        Tournament::create([...$validated, 'user_id' => $request->user()->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => '大会を作成しました。']);

        return to_route('tournaments.index');
    }

    /**
     * 大会の詳細(参加者の登録、試合の一覧)を表示する。
     */
    public function show(Tournament $tournament): Response
    {
        $participants = $tournament->participants()
            ->with('members')
            ->orderBy('id')
            ->get()
            ->map(fn (Participant $participant) => [
                'id' => $participant->id,
                'name' => $participant->name,
                // 所属するユーザーの名前(チーム戦では、人数も分かる)
                'members' => $participant->members->pluck('name')->all(),
            ]);

        // 参加者に選べるユーザー: 有効で、この大会のどの参加者にも入っていない人
        $candidates = User::query()
            ->where('is_active', true)
            ->whereNotIn('id', DB::table('participant_user')->where('tournament_id', $tournament->id)->pluck('user_id'))
            ->orderBy('id')
            ->get()
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'username' => $user->username]);

        // 試合は、回戦・並び順の順に、出る参加者の名前といっしょに渡す
        $games = $tournament->games()
            ->with('participants')
            ->orderBy('round')
            ->orderBy('position')
            ->get()
            ->map(fn (Game $game) => [
                'id' => $game->id,
                'round' => $game->round,
                'status' => $game->status->label(),
                'is_bye' => $game->is_bye,
                'participants' => $game->participants->pluck('name')->all(),
            ]);

        return Inertia::render('tournaments/Show', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'format' => $tournament->format->label(),
                'participant_type' => $tournament->participant_type->label(),
                'is_team' => $tournament->participant_type === ParticipantType::Team,
                'status' => $tournament->status->label(),
                'is_preparing' => $tournament->status === TournamentStatus::Preparing,
            ],
            'participants' => $participants,
            'candidates' => $candidates,
            'games' => $games,
        ]);
    }

    /**
     * 大会を開始して、試合を自動で作る。
     */
    public function start(Tournament $tournament, StartTournament $startTournament): RedirectResponse
    {
        if ($tournament->status !== TournamentStatus::Preparing) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'この大会は、すでに開始しています。']);

            return to_route('tournaments.show', $tournament);
        }

        if ($tournament->participants()->count() < 2) {
            Inertia::flash('toast', ['type' => 'error', 'message' => '参加者が2つ以上必要です。']);

            return to_route('tournaments.show', $tournament);
        }

        $startTournament($tournament);

        Inertia::flash('toast', ['type' => 'success', 'message' => '大会を開始しました。試合を作成しました。']);

        return to_route('tournaments.show', $tournament);
    }
}
