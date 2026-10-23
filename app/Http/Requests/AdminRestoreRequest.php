<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminRestoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:51200'],
            'confirmation' => ['required', 'string', 'in:PULIHKAN'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Berkas cadangan wajib diunggah.',
            'file.max' => 'Ukuran berkas cadangan maksimal 50 MB.',
            'confirmation.required' => 'Ketik kata "PULIHKAN" untuk mengonfirmasi pemulihan.',
            'confirmation.in' => 'Ketik kata "PULIHKAN" dengan huruf kapital persis untuk mengonfirmasi pemulihan.',
        ];
    }
}
