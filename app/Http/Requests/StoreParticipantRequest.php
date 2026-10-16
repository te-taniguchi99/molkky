<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
        return [
            // 同じ大会の中で、同じ名前を重複させない
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('participants', 'name')->where('tournament_id', $this->route('tournament')->id),
            ],
            // チーム戦の人数。チームごとに自由に決められる(個人戦では使わない)
            'member_count' => ['nullable', 'integer', 'min:1', 'max:30'],
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
            'member_count' => '人数',
        ];
    }
}
