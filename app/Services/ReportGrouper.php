<?php

namespace App\Services;

use App\DTO\RecapFilter;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Repositories\SettingsRepository;

class ReportGrouper
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository,
    ) {}

    /**
     * Kelompokkan baris rekap menjadi Bab -> Kelas -> Siswa (AveragedRow).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{
     *     label: string,
     *     classes: array<int, array{
     *         name: string,
     *         rows: array<int, array{
     *             nis: string,
     *             name: string,
     *             gender: string,
     *             className: string,
     *             chapter: string,
     *             average: float|null,
     *             weightedTotal: float,
     *             scoredWeight: float,
     *             rawTotal: int,
     *             assessed: int,
     *             totalItems: int,
     *             status: string
     *         }>
     *     }>
     * }>
     */
    public function groupAverageRows(array $rows): array
    {
        /** @var array<string, array<string, array<string, array<int, array<string, mixed>>>>> $chapters */
        $chapters = [];

        foreach ($rows as $row) {
            $chapter = (string) ($row['chapter'] ?? 'Materi belum tersedia');
            $className = implode(' · ', array_filter([
                $row['class_name'] ?? '',
                $row['academic_year_name'] ?? '',
                $row['semester'] ?? '',
            ]));
            $studentKey = (string) ($row['student_id'] ?? $row['nis'] ?? $row['name']);

            $chapters[$chapter][$className][$studentKey][] = $row;
        }

        $result = [];
        foreach ($chapters as $chapterLabel => $classes) {
            $classGroups = [];
            foreach ($classes as $className => $students) {
                $studentRows = [];
                foreach ($students as $studentItems) {
                    $first = $studentItems[0];
                    $available = array_values(array_filter($studentItems, fn ($r) => $r['assessment_id'] !== null));
                    $scored = array_values(array_filter($available, fn ($r) => $r['score'] !== null));

                    $total = 0.0;
                    $weight = 0.0;
                    $rawTotal = 0;
                    foreach ($scored as $s) {
                        $score = (int) $s['score'];
                        $w = (float) ($s['weight'] ?? 1.0);
                        $total += $score * $w;
                        $weight += $w;
                        $rawTotal += $score;
                    }

                    $status = match (true) {
                        count($available) === 0 => 'Materi belum tersedia',
                        count($scored) === 0 => 'Belum dinilai',
                        count($scored) === count($available) => 'Dinilai',
                        default => 'Sebagian dinilai',
                    };

                    $studentRows[] = [
                        'nis' => (string) ($first['nis'] ?? ''),
                        'name' => (string) $first['name'],
                        'gender' => (string) ($first['gender'] ?: '-'),
                        'className' => (string) $first['class_name'],
                        'chapter' => $chapterLabel,
                        'average' => $weight > 0 ? round($total / $weight, 1) : null,
                        'weightedTotal' => $total,
                        'scoredWeight' => $weight,
                        'rawTotal' => $rawTotal,
                        'assessed' => count($scored),
                        'totalItems' => count($available),
                        'status' => $status,
                    ];
                }

                // Urutkan siswa berdasarkan nama
                usort($studentRows, fn ($a, $b) => strcmp($a['name'], $b['name']));

                $classGroups[] = [
                    'name' => $className,
                    'rows' => $studentRows,
                ];
            }

            // Urutkan kelas berdasarkan nama
            usort($classGroups, fn ($a, $b) => strcmp($a['name'], $b['name']));

            $result[] = [
                'label' => $chapterLabel,
                'classes' => $classGroups,
            ];
        }

        // Urutkan bab berdasarkan label
        usort($result, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return $result;
    }

    /**
     * Hitung statistik gabungan dari kumpulan baris hasil groupAverageRows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     scored: int,
     *     total: float,
     *     average: float|null
     * }
     */
    public function averageStats(array $rows): array
    {
        $scoredRows = array_filter($rows, fn ($r) => $r['average'] !== null);

        $totalRaw = 0.0;
        $totalWeighted = 0.0;
        $totalWeight = 0.0;
        $totalScoredCount = 0;

        foreach ($scoredRows as $r) {
            $totalRaw += (float) $r['rawTotal'];
            $totalWeighted += (float) $r['weightedTotal'];
            $totalWeight += (float) $r['scoredWeight'];
            $totalScoredCount += (int) $r['assessed'];
        }

        return [
            'scored' => $totalScoredCount,
            'total' => round($totalRaw, 1),
            'average' => $totalWeight > 0 ? round($totalWeighted / $totalWeight, 1) : null,
        ];
    }

    /**
     * Buat ringkasan cakupan filter laporan dalam teks.
     */
    public function formatScope(RecapFilter $filter, ?string $schoolName = null): string
    {
        $school = $schoolName ?? $this->settingsRepository->schoolName();
        if ($school === '') {
            $school = 'PIB Penilaian';
        }

        $periodText = 'Semua periode';
        if ($filter->classId) {
            $schoolClass = SchoolClass::query()
                ->with('academicYear')
                ->find($filter->classId);
            if ($schoolClass && $schoolClass->academicYear) {
                $periodText = $schoolClass->academicYear->name.' · '.$schoolClass->academicYear->semester->value;
            }
        } elseif ($filter->academicYearId) {
            $year = AcademicYear::query()->find($filter->academicYearId);
            if ($year) {
                $periodText = $year->name.' · '.$year->semester->value;
            }
        }

        $classText = 'Semua kelas';
        if ($filter->classId) {
            $schoolClass = SchoolClass::query()->find($filter->classId);
            if ($schoolClass) {
                $classText = 'Kelas: '.$schoolClass->name;
            }
        }

        $parts = [
            $periodText,
            $classText,
        ];

        if ($filter->student) {
            $parts[] = 'Nama: '.$filter->student;
        }

        if ($filter->chapter) {
            $parts[] = 'Bab: '.$filter->chapter;
        }

        if ($filter->subchapter) {
            $parts[] = 'Subbab: '.$filter->subchapter;
        }

        if ($filter->assessmentId) {
            $assessment = Assessment::query()->find($filter->assessmentId);
            $parts[] = 'Materi: '.($assessment ? $assessment->title : '#'.$filter->assessmentId);
        } else {
            $parts[] = 'Semua materi dalam cakupan';
        }

        return implode(' | ', $parts);
    }
}
