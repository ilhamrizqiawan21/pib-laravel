<?php

namespace App\DTO;

use Illuminate\Http\Request;

final readonly class RecapFilter
{
    public function __construct(
        public ?int $academicYearId = null,
        public ?int $classId = null,
        public ?string $student = null,
        public ?string $chapter = null,
        public ?string $subchapter = null,
        public ?int $assessmentId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            academicYearId: $request->filled('academic_year_id') ? (int) $request->input('academic_year_id') : null,
            classId: $request->filled('class_id') ? (int) $request->input('class_id') : null,
            student: $request->filled('student') ? trim((string) $request->input('student')) : null,
            chapter: $request->filled('chapter') ? trim((string) $request->input('chapter')) : null,
            subchapter: $request->filled('subchapter') ? trim((string) $request->input('subchapter')) : null,
            assessmentId: $request->filled('assessment_id') ? (int) $request->input('assessment_id') : null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            academicYearId: isset($data['academic_year_id']) && $data['academic_year_id'] !== '' ? (int) $data['academic_year_id'] : null,
            classId: isset($data['class_id']) && $data['class_id'] !== '' ? (int) $data['class_id'] : null,
            student: isset($data['student']) && $data['student'] !== '' ? trim((string) $data['student']) : null,
            chapter: isset($data['chapter']) && $data['chapter'] !== '' ? trim((string) $data['chapter']) : null,
            subchapter: isset($data['subchapter']) && $data['subchapter'] !== '' ? trim((string) $data['subchapter']) : null,
            assessmentId: isset($data['assessment_id']) && $data['assessment_id'] !== '' ? (int) $data['assessment_id'] : null,
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return array_filter([
            'academic_year_id' => $this->academicYearId,
            'class_id' => $this->classId,
            'student' => $this->student,
            'chapter' => $this->chapter,
            'subchapter' => $this->subchapter,
            'assessment_id' => $this->assessmentId,
        ], fn ($val) => $val !== null && $val !== '');
    }
}
