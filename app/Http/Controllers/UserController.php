<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * ユーザーの一覧を表示する。
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->orderBy('id')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'is_active' => $user->is_active,
                // ログイン中の本人かどうか(本人は、自分を無効にできない)
                'is_me' => $user->is($request->user()),
            ]);

        return Inertia::render('users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * ユーザーの追加画面を表示する。
     */
    public function create(): Response
    {
        return Inertia::render('users/Create');
    }

    /**
     * ユーザーを追加する。
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            // パスワードが空のときは、推測できないランダムな値を入れる(DB の列が空を許さないため)
            'password' => $validated['password'] ?? Str::random(40),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'ユーザーを追加しました。']);

        return to_route('users.index');
    }

    /**
     * ユーザーの有効/無効を切り替える。
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        // 自分自身を無効にすると、ログインできなくなるため、止める
        if ($user->is($request->user())) {
            Inertia::flash('toast', ['type' => 'error', 'message' => '自分自身は無効にできません。']);

            return to_route('users.index');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $user->is_active ? '有効にしました。' : '無効にしました。',
        ]);

        return to_route('users.index');
    }
}
