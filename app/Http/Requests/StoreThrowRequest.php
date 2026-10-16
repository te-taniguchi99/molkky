<?php

namespace App\Http\Requests;

use App\Enums\ThrowKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreThrowRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 画面に表示されていた、手番の参加者(別の端末で手番が変わっていないかを確かめるため)
            'participant_id' => ['required', 'integer'],
            'kind' => ['required', Rule::enum(ThrowKind::class)],
            // 得点は 1〜12(1本なら倒れた番号、2本以上なら倒れた本数)。ミスは値なし
            'value' => match ($this->input('kind')) {
                ThrowKind::Score->value => ['required', 'integer', 'between:1,12'],
                default => ['nullable', 'integer'],
            },
        ];
    }
}
