<?php

namespace App\Http\Requests;

use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTournamentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * 大会の開始後は、方式やルールを変更できない。その項目は、画面から送られてこないため、
     * 'sometimes'(送られたときだけ検証する)にしている。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 自分自身の名前は、重複とみなさない
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tournaments', 'name')->ignore($this->route('tournament')),
            ],
            'held_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'format' => ['sometimes', 'required', Rule::enum(TournamentFormat::class)],
            'participant_type' => ['sometimes', 'required', Rule::enum(ParticipantType::class)],
            'target_score' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000'],
            'reset_score' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                // 目標点も送られてきたときだけ、「目標点より小さい」ことを確かめる
                Rule::when($this->has('target_score'), 'lt:target_score'),
            ],
            'max_consecutive_misses' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * 入力チェックのエラーメッセージ。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => '同じ名前の大会が、すでにあります。別の名前にしてください。',
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
            'name' => '大会名',
            'held_at' => '開催日時',
            'venue' => '場所',
            'format' => '方式',
            'participant_type' => '参加形態',
            'target_score' => '目標点',
            'reset_score' => '戻る得点',
            'max_consecutive_misses' => '失格になる連続ミス回数',
        ];
    }
}
