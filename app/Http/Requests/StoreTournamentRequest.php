<?php

namespace App\Http\Requests;

use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 同じ名前の大会は、作れない
            'name' => ['required', 'string', 'max:255', Rule::unique('tournaments', 'name')],
            'format' => ['required', Rule::enum(TournamentFormat::class)],
            'participant_type' => ['required', Rule::enum(ParticipantType::class)],
            'target_score' => ['required', 'integer', 'min:1', 'max:1000'],
            // 戻る得点は、目標点より小さい値にする
            'reset_score' => ['required', 'integer', 'min:0', 'lt:target_score'],
            'max_consecutive_misses' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * 入力チェックのエラーメッセージ(標準のメッセージを、分かりやすく置き換える)。
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
            'format' => '方式',
            'participant_type' => '参加形態',
            'target_score' => '目標点',
            'reset_score' => '戻る得点',
            'max_consecutive_misses' => '失格になる連続ミス回数',
        ];
    }
}
