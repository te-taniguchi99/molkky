<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * 無効なアカウントでログインしている場合は、ログアウトさせてログイン画面へ戻す。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ログイン中で、かつ無効なアカウントの場合
        if ($user !== null && ! $user->is_active) {
            // ログアウトする
            Auth::logout();

            // セッションを破棄して、新しい CSRF トークンを発行する(安全のための定型処理)
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // ログイン画面へ戻し、理由をメッセージで表示する
            return redirect()->route('login')
                ->with('status', 'このアカウントは無効になっています。管理者に連絡してください。');
        }

        return $next($request);
    }
}
