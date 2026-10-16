<?php

namespace App\Http\Requests;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreUserRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * 入力チェックの前に、ログインIDを小文字へそろえる。
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('username'))) {
            $this->merge(['username' => Str::lower(trim($this->input('username')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 名前とログインID(プロフィールと同じルール)
            ...$this->profileRules(),
            // パスワードは、空でもよい(確認しない設定のときは、使われない)
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * 画面に表示する項目名。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '名前',
            'username' => 'ログインID',
            'password' => 'パスワード',
        ];
    }
}
