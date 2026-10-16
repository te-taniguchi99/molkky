<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FillBlankLoginPassword
{
    /**
     * パスワードを確認しない設定のとき、ログインの送信で空のパスワードを、仮の値で埋める。
     *
     * Fortify のログインの入力チェックは、パスワードを必須としているため、
     * 空のままだと、確認以前にエラーになってしまう。
     * 仮の値は、ログインの判定(FortifyServiceProvider)で使われない。
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('auth.require_password')
            && $request->routeIs('login.store')
            && blank($request->input('password'))) {
            $request->merge(['password' => '-']);
        }

        return $next($request);
    }
}
