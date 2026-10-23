<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ClassProgressService
{
    /**
     * Ambil data kemajuan satu kelas lengkap (materials, students, scores) dalam 3 query optimal.
     *
     * @return array{
     *     classId: int,
     *     className: string,
     *     materials: array<int, array{
     *         id: int,
     *         title: string,
     *         weight: float,
     *         chapter_id: int,
     *         chapter: string,
     *         subchapter_id: int,
     *         subchapter: string
     *     }>,
     *     students: array<int, array{
     *         id: int,
     *         name: string,
     *         nis: string|null
     *     }>,
     *     scores: array<int, array{
     *         student_id: int,
     *         assessment_id: int,
     *         score: int|null,
     *         mistakes: int|null,
     *         version: int,
     *         assessed_at: string|null,
     *         initials: string,
     *         note: string
     *     }>
     * }
     */
    public function getProgress(SchoolClass $class): array
    {
        // 1. Materials aktif untuk tahun ajaran kelas, urut sesuai kurikulum
        $materials = DB::table('assessments')
            ->select([
                'assessments.id',
                'assessments.title',
                'assessments.weight',
                'chapters.id as chapter_id',
                'chapters.title as chapter',
                'subchapters.id as subchapter_id',
                'subchapters.title as subchapter',
            ])
            ->join('subchapters', 'subchapters.id', '=', 'assessments.subchapter_id')
            ->join('chapters', 'chapters.id', '=', 'subchapters.chapter_id')
            ->join('curriculum_templates', 'curriculum_templates.id', '=', 'chapters.curriculum_template_id')
            ->where('assessments.is_active', true)
            ->where('curriculum_templates.academic_year_id', $class->academic_year_id)
            ->orderBy('chapters.display_order')
            ->orderBy('chapters.id')
            ->orderBy('subchapters.display_order')
            ->orderBy('subchapters.id')
            ->orderBy('assessments.display_order')
            ->orderBy('assessments.id')
            ->get()
            ->map(fn ($m) => [
                'id' => (int) $m->id,
                'title' => (string) $m->title,
                'weight' => (float) $m->weight,
                'chapter_id' => (int) $m->chapter_id,
                'chapter' => (string) $m->chapter,
                'subchapter_id' => (int) $m->subchapter_id,
                'subchapter' => (string) $m->subchapter,
            ])
            ->values()
            ->all();

        // 2. Siswa aktif kelas ini, urut nama
        $students = Student::query()
            ->select(['id', 'name', 'nis'])
            ->where('school_class_id', $class->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
                'nis' => $s->nis !== null ? (string) $s->nis : null,
            ])
            ->values()
            ->all();

        // 3. Semua baris nilai milik siswa aktif kelas ini (termasuk yang bernilai null / dikosongkan)
        $scores = Score::query()
            ->select([
                'scores.student_id',
                'scores.assessment_id',
                'scores.score',
                'scores.mistakes',
                'scores.version',
                'scores.assessed_at',
                'scores.initials',
                'scores.note',
            ])
            ->join('students', 'students.id', '=', 'scores.student_id')
            ->where('students.school_class_id', $class->id)
            ->where('students.is_active', true)
            ->get()
            ->map(fn ($sc) => [
                'student_id' => (int) $sc->student_id,
                'assessment_id' => (int) $sc->assessment_id,
                'score' => $sc->score !== null ? (int) $sc->score : null,
                'mistakes' => $sc->mistakes !== null ? (int) $sc->mistakes : null,
                'version' => (int) $sc->version,
                'assessed_at' => $sc->assessed_at?->toISOString(),
                'initials' => (string) $sc->initials,
                'note' => (string) $sc->note,
            ])
            ->values()
            ->all();

        return [
            'classId' => (int) $class->id,
            'className' => (string) $class->name,
            'materials' => $materials,
            'students' => $students,
            'scores' => $scores,
        ];
    }
}
