<?php

namespace App\Http\Controllers;

use App\Actions\Tournaments\StartTournament;
use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Http\Requests\StoreTournamentRequest;
use App\Http\Requests\UpdateTournamentRequest;
use App\Models\Game;
use App\Models\Participant;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'held_at' => $tournament->held_at?->format('Y/m/d H:i'),
                'venue' => $tournament->venue,
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
                'held_at' => $tournament->held_at?->format('Y/m/d H:i'),
                'venue' => $tournament->venue,
                'format' => $tournament->format->label(),
                'participant_type' => $tournament->participant_type->label(),
                'is_team' => $tournament->participant_type === ParticipantType::Team,
                'is_round_robin' => $tournament->format === TournamentFormat::RoundRobin,
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
    public function start(Request $request, Tournament $tournament, StartTournament $startTournament): RedirectResponse
    {
        if ($tournament->status !== TournamentStatus::Preparing) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'この大会は、すでに開始しています。']);

            return to_route('tournaments.show', $tournament);
        }

        if ($tournament->participants()->count() < 2) {
            Inertia::flash('toast', ['type' => 'error', 'message' => '参加者が2つ以上必要です。']);

            return to_route('tournaments.show', $tournament);
        }

        // 「試合の順番をランダムにする」にチェックがあるときだけ、総当たり戦の順番を並べ替える
        $startTournament($tournament, $request->boolean('shuffle'));

        Inertia::flash('toast', ['type' => 'success', 'message' => '大会を開始しました。試合を作成しました。']);

        return to_route('tournaments.show', $tournament);
    }

    /**
     * 大会の編集画面を表示する。
     */
    public function edit(Tournament $tournament): Response
    {
        return Inertia::render('tournaments/Edit', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                // 日時の入力欄(datetime-local)に入れる形式
                'held_at' => $tournament->held_at?->format('Y-m-d\\TH:i'),
                'venue' => $tournament->venue,
                'format' => $tournament->format->value,
                'participant_type' => $tournament->participant_type->value,
                'target_score' => $tournament->target_score,
                'reset_score' => $tournament->reset_score,
                'max_consecutive_misses' => $tournament->max_consecutive_misses,
                // 方式とルールを変更できるのは、準備中だけ
                'can_edit_rules' => $tournament->status === TournamentStatus::Preparing,
                // 参加形態を変更できるのは、準備中で、参加者がまだいないときだけ
                'can_edit_type' => $tournament->status === TournamentStatus::Preparing
                    && ! $tournament->participants()->exists(),
            ],
            'formats' => collect(TournamentFormat::cases())
                ->map(fn (TournamentFormat $case) => ['value' => $case->value, 'label' => $case->label()]),
            'participantTypes' => collect(ParticipantType::cases())
                ->map(fn (ParticipantType $case) => ['value' => $case->value, 'label' => $case->label()]),
        ]);
    }

    /**
     * 大会の情報を更新する。
     */
    public function update(UpdateTournamentRequest $request, Tournament $tournament): RedirectResponse
    {
        $validated = $request->validated();

        $preparing = $tournament->status === TournamentStatus::Preparing;

        // 準備中でなければ、方式とルールは変更しない(試合がすでに作られているため)
        if (! $preparing) {
            unset(
                $validated['format'],
                $validated['target_score'],
                $validated['reset_score'],
                $validated['max_consecutive_misses'],
            );
        }

        // 参加形態は、準備中で、参加者がまだいないときだけ変更できる(参加者の登録の形が違うため)
        if (! $preparing || $tournament->participants()->exists()) {
            unset($validated['participant_type']);
        }

        $tournament->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => '大会の情報を更新しました。']);

        return to_route('tournaments.show', $tournament);
    }

    /**
     * 大会を削除する。
     * 大会の参加者と試合も、DB の設定(連動して削除)で、いっしょに削除される。
     */
    public function destroy(Tournament $tournament): RedirectResponse
    {
        $tournament->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => '大会を削除しました。']);

        return to_route('tournaments.index');
    }
}
