<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // ログインIDは、小文字へそろえてから検証・保存する
        $input['username'] = Str::lower(trim((string) ($input['username'] ?? '')));

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->registrationPasswordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'username' => $input['username'],
            // パスワードが空のときは、推測できないランダムな値を入れる(DB の列が空を許さないため)
            'password' => $input['password'] ?? Str::random(40),
        ]);
    }

    /**
     * 新規登録のパスワードの入力チェック。
     * パスワードを確認しない設定(auth.require_password = false)のときは、空でもよい。
     *
     * @return array<int, mixed>
     */
    private function registrationPasswordRules(): array
    {
        if (config('auth.require_password')) {
            return $this->passwordRules();
        }

        // 'required' を 'nullable'(空でもよい)に置き換える。入力したときは、ほかのルールで検証する
        return array_map(
            fn ($rule) => $rule === 'required' ? 'nullable' : $rule,
            $this->passwordRules(),
        );
    }
}
