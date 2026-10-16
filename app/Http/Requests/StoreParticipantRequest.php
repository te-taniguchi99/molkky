<?php

namespace App\Http\Requests;

use App\Enums\ParticipantType;
use App\Models\Tournament;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreParticipantRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Tournament $tournament */
        $tournament = $this->route('tournament');

        // この大会で、すでにどこかの参加者に入っているユーザーの id
        $assignedUserIds = DB::table('participant_user')
            ->where('tournament_id', $tournament->id)
            ->pluck('user_id')
            ->all();

        // 選べるユーザー: 存在して、有効で、まだどの参加者にも入っていない人
        $selectableUser = Rule::exists('users', 'id')
            ->where('is_active', true)
            ->whereNotIn('id', $assignedUserIds);

        if ($tournament->participant_type === ParticipantType::Individual) {
            // 個人戦: ユーザーを 1 人選ぶ(名前は、ユーザー名になる)
            return [
                'user_id' => ['required', 'integer', $selectableUser],
            ];
        }

        // チーム戦: チーム名と、所属するユーザー(1 人以上)
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // 同じ大会の中で、同じチーム名を重複させない
                Rule::unique('participants', 'name')->where('tournament_id', $tournament->id),
            ],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', $selectableUser],
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
            'name' => 'チーム名',
            'user_id' => 'ユーザー',
            'user_ids' => '所属するユーザー',
            'user_ids.*' => '所属するユーザー',
        ];
    }
}
