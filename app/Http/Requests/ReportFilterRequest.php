<?php

namespace App\Http\Requests;

use App\DTO\RecapFilter;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'min:1'],
            'class_id' => ['nullable', 'integer', 'min:1'],
            'student' => ['nullable', 'string', 'max:100'],
            'chapter' => ['nullable', 'string', 'max:255'],
            'subchapter' => ['nullable', 'string', 'max:255'],
            'assessment_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'academic_year_id.integer' => 'Filter tahun ajaran tidak valid.',
            'academic_year_id.min' => 'Filter tahun ajaran tidak valid.',
            'class_id.integer' => 'Filter kelas tidak valid.',
            'class_id.min' => 'Filter kelas tidak valid.',
            'assessment_id.integer' => 'Filter materi tidak valid.',
            'assessment_id.min' => 'Filter materi tidak valid.',
        ];
    }

    public function toRecapFilter(): RecapFilter
    {
        $validated = $this->validated();

        return new RecapFilter(
            academicYearId: isset($validated['academic_year_id']) ? (int) $validated['academic_year_id'] : null,
            classId: isset($validated['class_id']) ? (int) $validated['class_id'] : null,
            student: isset($validated['student']) && $validated['student'] !== '' ? (string) $validated['student'] : null,
            chapter: isset($validated['chapter']) && $validated['chapter'] !== '' ? (string) $validated['chapter'] : null,
            subchapter: isset($validated['subchapter']) && $validated['subchapter'] !== '' ? (string) $validated['subchapter'] : null,
            assessmentId: isset($validated['assessment_id']) ? (int) $validated['assessment_id'] : null,
        );
    }
}
