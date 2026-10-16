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
            'name' => ['required', 'string', 'max:255'],
            'format' => ['required', Rule::enum(TournamentFormat::class)],
            'participant_type' => ['required', Rule::enum(ParticipantType::class)],
            'target_score' => ['required', 'integer', 'min:1', 'max:1000'],
            // 戻る得点は、目標点より小さい値にする
            'reset_score' => ['required', 'integer', 'min:0', 'lt:target_score'],
            'max_consecutive_misses' => ['required', 'integer', 'min:1', 'max:10'],
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
