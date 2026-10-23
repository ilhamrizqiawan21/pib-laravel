<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ScoreSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'min:8', 'max:100'],
            'device_id' => ['required', 'string', 'max:100'],
            'student_id' => ['required', 'integer'],
            'assessment_id' => ['required', 'integer'],
            'mistakes' => ['nullable', 'integer', 'min:0', 'max:90'],
            'base_version' => ['nullable', 'integer', 'min:1'],
            'initials' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
            'assessed_at' => ['nullable', 'date'],
        ];
    }
}
