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
            // 1本は番号(1〜12)、2本以上は本数(2〜12)、ミスは値なし
            'value' => match ($this->input('kind')) {
                ThrowKind::Single->value => ['required', 'integer', 'between:1,12'],
                ThrowKind::Multiple->value => ['required', 'integer', 'between:2,12'],
                default => ['nullable', 'integer'],
            },
        ];
    }
}
