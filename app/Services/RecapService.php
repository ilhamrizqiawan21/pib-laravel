<?php

namespace App\Services;

use App\Domain\Recap\Progress;
use App\DTO\RecapFilter;
use App\Enums\CompletionStatus;
use Illuminate\Support\Facades\DB;

class RecapService
{
    /**
     * Ambil baris data gabungan rekap per siswa x materi berdasarkan filter.
     * Menggunakan ANSI SQL JOIN tanpa N+1 query.
     *
     * @return array<int, array{
     *     student_id: int,
     *     class_id: int,
     *     name: string,
     *     nis: string|null,
     *     gender: string|null,
     *     class_name: string,
     *     academic_year_id: int,
     *     academic_year_name: string,
     *     semester: string,
     *     assessment_id: int|null,
     *     chapter_id: int|null,
     *     subchapter_id: int|null,
     *     chapter: string|null,
     *     subchapter: string|null,
     *     assessment: string|null,
     *     weight: float|null,
     *     score: int|null,
     *     mistakes: int|null,
     *     assessed_at: string|null,
     *     initials: string|null,
     *     note: string|null,
     *     status: string
     * }>
     */
    public function getRows(RecapFilter $filter, ?int $studentId = null): array
    {
        $query = DB::table('students as s')
            ->join('school_classes as c', 'c.id', '=', 's.school_class_id')
            ->join('academic_years as y', 'y.id', '=', 'c.academic_year_id')
            ->leftJoin('curriculum_templates as ct', 'ct.academic_year_id', '=', 'c.academic_year_id')
            ->leftJoin('chapters as ch', 'ch.curriculum_template_id', '=', 'ct.id')
            ->leftJoin('subchapters as sub', 'sub.chapter_id', '=', 'ch.id')
            ->leftJoin('assessments as a', function ($join) {
                $join->on('a.subchapter_id', '=', 'sub.id')
                    ->where('a.is_active', '=', true);
            })
            ->leftJoin('scores as sc', function ($join) {
                $join->on('sc.student_id', '=', 's.id')
                    ->on('sc.assessment_id', '=', 'a.id');
            })
            ->where('s.is_active', true)
            ->where('c.is_active', true);

        if ($filter->academicYearId) {
            $query->where('c.academic_year_id', $filter->academicYearId);
        }

        if ($filter->classId) {
            $query->where('c.id', $filter->classId);
        }

        if ($filter->student) {
            $query->where('s.name', 'LIKE', '%'.$filter->student.'%');
        }

        if ($filter->chapter) {
            $query->where('ch.title', $filter->chapter);
        }

        if ($filter->subchapter) {
            $query->where('sub.title', $filter->subchapter);
        }

        if ($filter->assessmentId) {
            $query->where('a.id', $filter->assessmentId);
        }

        if ($studentId) {
            $query->where('s.id', $studentId);
        }

        $records = $query->select([
            's.id as student_id',
            'c.id as class_id',
            's.name as name',
            's.nis as nis',
            's.gender as gender',
            'c.name as class_name',
            'y.id as academic_year_id',
            'y.name as academic_year_name',
            'y.semester as semester',
            'a.id as assessment_id',
            'ch.id as chapter_id',
            'sub.id as subchapter_id',
            'ch.title as chapter',
            'sub.title as subchapter',
            'a.title as assessment',
            'a.weight as weight',
            'sc.score as score',
            'sc.mistakes as mistakes',
            'sc.assessed_at as assessed_at',
            'sc.initials as initials',
            'sc.note as note',
        ])
            ->orderBy('y.name')
            ->orderBy('y.semester')
            ->orderBy('c.name')
            ->orderBy('s.name')
            ->orderBy('ch.display_order')
            ->orderBy('ch.id')
            ->orderBy('sub.display_order')
            ->orderBy('sub.id')
            ->orderBy('a.display_order')
            ->orderBy('a.id')
            ->get();

        return $records->map(function ($row) {
            $status = match (true) {
                $row->assessment_id === null => 'Materi belum tersedia',
                $row->score === null => 'Belum dinilai',
                default => 'Dinilai',
            };

            return [
                'student_id' => (int) $row->student_id,
                'class_id' => (int) $row->class_id,
                'name' => (string) $row->name,
                'nis' => $row->nis !== null ? (string) $row->nis : null,
                'gender' => $row->gender !== null ? (string) $row->gender : null,
                'class_name' => (string) $row->class_name,
                'academic_year_id' => (int) $row->academic_year_id,
                'academic_year_name' => (string) $row->academic_year_name,
                'semester' => (string) $row->semester,
                'assessment_id' => $row->assessment_id !== null ? (int) $row->assessment_id : null,
                'chapter_id' => $row->chapter_id !== null ? (int) $row->chapter_id : null,
                'subchapter_id' => $row->subchapter_id !== null ? (int) $row->subchapter_id : null,
                'chapter' => $row->chapter !== null ? (string) $row->chapter : null,
                'subchapter' => $row->subchapter !== null ? (string) $row->subchapter : null,
                'assessment' => $row->assessment !== null ? (string) $row->assessment : null,
                'weight' => $row->weight !== null ? (float) $row->weight : null,
                'score' => $row->score !== null ? (int) $row->score : null,
                'mistakes' => $row->mistakes !== null ? (int) $row->mistakes : null,
                'assessed_at' => $row->assessed_at !== null ? (string) $row->assessed_at : null,
                'initials' => $row->initials !== null ? (string) $row->initials : null,
                'note' => $row->note !== null ? (string) $row->note : null,
                'status' => $status,
            ];
        })->all();
    }

    /**
     * Hitung ringkasan statistik (expected, assessed, total, average, percent, status).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     expected: int,
     *     assessed: int,
     *     total: int,
     *     average: float|null,
     *     percent: int,
     *     status: CompletionStatus
     * }
     */
    public function calculateScoreStats(array $rows): array
    {
        $materials = array_filter($rows, fn ($r) => $r['assessment_id'] !== null);
        $scored = array_filter($materials, fn ($r) => $r['score'] !== null);

        $totalScore = array_sum(array_map(fn ($r) => (int) $r['score'], $scored));
        $totalWeight = array_sum(array_map(fn ($r) => (float) ($r['weight'] ?? 1.0), $scored));
        $weightedSum = array_sum(array_map(
            fn ($r) => ((int) $r['score']) * ((float) ($r['weight'] ?? 1.0)),
            $scored
        ));

        $average = $totalWeight > 0.0 ? round($weightedSum / $totalWeight, 1) : null;
        $progress = new Progress(count($materials), count($scored));

        return [
            'expected' => count($materials),
            'assessed' => count($scored),
            'total' => $totalScore,
            'average' => $average,
            'percent' => $progress->percent(),
            'status' => $progress->status(),
        ];
    }

    /**
     * Ringkas baris-baris rekap menjadi kelompok Siswa, Kelas, dan Subbab.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     students: array<int, array<string, mixed>>,
     *     classes: array<int, array<string, mixed>>,
     *     subchapters: array<int, array<string, mixed>>
     * }
     */
    public function summarize(array $rows): array
    {
        // 1. Kelompokkan per Siswa
        $studentGroups = [];
        foreach ($rows as $row) {
            $studentId = (int) $row['student_id'];
            $studentGroups[$studentId][] = $row;
        }

        $students = [];
        foreach ($studentGroups as $studentId => $items) {
            $first = $items[0];
            $stats = $this->calculateScoreStats($items);

            $students[] = array_merge([
                'id' => $studentId,
                'name' => (string) $first['name'],
                'nis' => $first['nis'] !== null ? (string) $first['nis'] : null,
                'gender' => $first['gender'] !== null ? (string) $first['gender'] : null,
                'class_id' => (int) $first['class_id'],
                'class_name' => (string) $first['class_name'],
                'academic_year_id' => (int) $first['academic_year_id'],
                'academic_year_name' => (string) $first['academic_year_name'],
                'semester' => (string) $first['semester'],
            ], $stats);
        }

        // 2. Kelompokkan per Kelas
        $classGroups = [];
        foreach ($rows as $row) {
            $classId = (int) $row['class_id'];
            $classGroups[$classId][] = $row;
        }

        $classes = [];
        foreach ($classGroups as $classId => $items) {
            $first = $items[0];
            $uniqueStudentIds = array_unique(array_column($items, 'student_id'));
            $stats = $this->calculateScoreStats($items);

            $classes[] = array_merge([
                'id' => $classId,
                'class_name' => (string) $first['class_name'],
                'academic_year_id' => (int) $first['academic_year_id'],
                'academic_year_name' => (string) $first['academic_year_name'],
                'semester' => (string) $first['semester'],
                'students_count' => count($uniqueStudentIds),
            ], $stats);
        }

        // 3. Kelompokkan per Subbab
        $subchapterGroups = [];
        foreach ($rows as $row) {
            if ($row['subchapter_id'] !== null) {
                $subchapterId = (int) $row['subchapter_id'];
                $subchapterGroups[$subchapterId][] = $row;
            }
        }

        $subchapters = [];
        foreach ($subchapterGroups as $subchapterId => $items) {
            $first = $items[0];
            $stats = $this->calculateScoreStats($items);

            $subchapters[] = array_merge([
                'id' => $subchapterId,
                'chapter' => (string) ($first['chapter'] ?? ''),
                'subchapter' => (string) ($first['subchapter'] ?? ''),
                'academic_year_id' => (int) $first['academic_year_id'],
                'academic_year_name' => (string) $first['academic_year_name'],
                'semester' => (string) $first['semester'],
            ], $stats);
        }

        return [
            'students' => $students,
            'classes' => $classes,
            'subchapters' => $subchapters,
        ];
    }

    /**
     * Dapatkan rekap lengkap sesuai filter.
     *
     * @return array{
     *     students: array<int, array<string, mixed>>,
     *     classes: array<int, array<string, mixed>>,
     *     subchapters: array<int, array<string, mixed>>
     * }
     */
    public function getRecap(RecapFilter $filter): array
    {
        $rows = $this->getRows($filter);

        return $this->summarize($rows);
    }

    /**
     * Dapatkan rincian detail per materi untuk siswa tertentu.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStudentDetails(int $studentId, RecapFilter $filter): array
    {
        return $this->getRows($filter, $studentId);
    }

    /**
     * Bangun matriks 2 dimensi (Siswa x Materi) untuk kelas tertentu (FR-R2).
     *
     * @return array{
     *     students: array<int, array{id: int, name: string, nis: string|null}>,
     *     materials: array<int, array{id: int, title: string, chapter: string, subchapter: string, weight: float}>,
     *     cells: array<string, array{score: int|null, mistakes: int|null, weight: float|null, status: string}>
     * }
     */
    public function getMatrix(int $classId): array
    {
        $filter = new RecapFilter(classId: $classId);
        $rows = $this->getRows($filter);

        $studentMap = [];
        $materialMap = [];
        $cells = [];

        foreach ($rows as $r) {
            $sid = (int) $r['student_id'];
            if (! isset($studentMap[$sid])) {
                $studentMap[$sid] = [
                    'id' => $sid,
                    'name' => (string) $r['name'],
                    'nis' => $r['nis'] !== null ? (string) $r['nis'] : null,
                ];
            }

            if ($r['assessment_id'] !== null) {
                $mid = (int) $r['assessment_id'];
                if (! isset($materialMap[$mid])) {
                    $materialMap[$mid] = [
                        'id' => $mid,
                        'title' => (string) $r['assessment'],
                        'chapter' => (string) $r['chapter'],
                        'subchapter' => (string) $r['subchapter'],
                        'weight' => (float) ($r['weight'] ?? 1.0),
                    ];
                }

                $key = "{$sid}:{$mid}";
                $cells[$key] = [
                    'score' => $r['score'] !== null ? (int) $r['score'] : null,
                    'mistakes' => $r['mistakes'] !== null ? (int) $r['mistakes'] : null,
                    'weight' => $r['weight'] !== null ? (float) $r['weight'] : null,
                    'status' => (string) $r['status'],
                ];
            }
        }

        return [
            'students' => array_values($studentMap),
            'materials' => array_values($materialMap),
            'cells' => $cells,
        ];
    }
}
