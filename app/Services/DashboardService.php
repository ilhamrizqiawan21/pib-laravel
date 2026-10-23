<?php

namespace App\Services;

use App\Domain\Recap\Progress;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Repositories\SettingsRepository;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository,
    ) {}

    /**
     * Dapatkan status 6 langkah penyiapan awal aplikasi (Onboarding).
     *
     * @return array{
     *     steps: array<int, array{
     *         key: string,
     *         title: string,
     *         detail: string,
     *         href: string,
     *         action: string,
     *         done: bool
     *     }>,
     *     isComplete: bool
     * }
     */
    public function getSetupSteps(): array
    {
        $hasSchoolName = ! empty(trim((string) $this->settingsRepository->schoolName()));
        $hasYears = AcademicYear::query()->exists();
        $hasClasses = SchoolClass::query()->where('is_active', true)->exists();
        $hasStudents = Student::query()->where('is_active', true)->exists();
        $hasMaterials = Assessment::query()->where('is_active', true)->exists();
        $hasScores = Score::query()->whereNotNull('score')->exists();

        $steps = [
            [
                'key' => 'schoolName',
                'title' => 'Isi identitas sekolah',
                'detail' => 'Nama sekolah dan guru penilai akan dicantumkan pada header dokumen laporan.',
                'href' => '/sekolah',
                'action' => 'Isi Identitas',
                'done' => $hasSchoolName,
            ],
            [
                'key' => 'years',
                'title' => 'Buat tahun ajaran',
                'detail' => 'Periode semester yang menaungi rombongan belajar kelas dan kurikulum.',
                'href' => '/sekolah',
                'action' => 'Tambah Tahun Ajaran',
                'done' => $hasYears,
            ],
            [
                'key' => 'classes',
                'title' => 'Tambah kelas',
                'detail' => 'Rombongan belajar siswa yang akan mengikuti penilaian praktik ibadah.',
                'href' => '/kelas',
                'action' => 'Tambah Kelas',
                'done' => $hasClasses,
            ],
            [
                'key' => 'students',
                'title' => 'Daftarkan siswa',
                'detail' => 'Tambah siswa secara manual atau gunakan template impor Excel/CSV.',
                'href' => '/siswa',
                'action' => 'Tambah Siswa',
                'done' => $hasStudents,
            ],
            [
                'key' => 'materials',
                'title' => 'Susun materi penilaian',
                'detail' => 'Atur struktur bab, subbab, materi praktik, serta bobot nilai penilaian.',
                'href' => '/materi',
                'action' => 'Susun Kurikulum',
                'done' => $hasMaterials,
            ],
            [
                'key' => 'scores',
                'title' => 'Mulai menilai siswa',
                'detail' => 'Masukkan jumlah kesalahan siswa; nilai akhir dihitung otomatis oleh sistem.',
                'href' => '/penilaian',
                'action' => 'Mulai Menilai',
                'done' => $hasScores,
            ],
        ];

        $isComplete = ! in_array(false, array_column($steps, 'done'), true);

        return [
            'steps' => $steps,
            'isComplete' => $isComplete,
        ];
    }

    /**
     * Dapatkan daftar kemajuan setiap kelas aktif.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     academic_year_id: int,
     *     period: string,
     *     students_count: int,
     *     materials_count: int,
     *     assessed_count: int,
     *     expected_count: int,
     *     percent: int
     * }>
     */
    public function getClassProgress(?int $academicYearId = null): array
    {
        $classes = SchoolClass::query()
            ->with(['academicYear'])
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($classes as $c) {
            $studentsCount = Student::query()
                ->where('school_class_id', $c->id)
                ->where('is_active', true)
                ->count();

            $materialsCount = Assessment::query()
                ->where('is_active', true)
                ->whereHas('subchapter.chapter.curriculumTemplate', function ($q) use ($c) {
                    $q->where('academic_year_id', $c->academic_year_id);
                })
                ->count();

            $assessedCount = Score::query()
                ->join('students', 'students.id', '=', 'scores.student_id')
                ->where('students.school_class_id', $c->id)
                ->where('students.is_active', true)
                ->whereNotNull('scores.score')
                ->count();

            $expectedCount = $studentsCount * $materialsCount;
            $progress = new Progress($expectedCount, $assessedCount);

            $rows[] = [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'academic_year_id' => (int) $c->academic_year_id,
                'period' => $c->academicYear ? $c->academicYear->label() : '—',
                'students_count' => $studentsCount,
                'materials_count' => $materialsCount,
                'assessed_count' => $assessedCount,
                'expected_count' => $expectedCount,
                'percent' => $progress->percent(),
            ];
        }

        return $rows;
    }

    /**
     * Dapatkan ringkasan statistik total untuk dashboard.
     *
     * @return array{
     *     total_students: int,
     *     total_classes: int,
     *     total_assessed: int,
     *     total_expected: int,
     *     overall_percent: int
     * }
     */
    public function getSummary(?int $academicYearId = null): array
    {
        $classes = $this->getClassProgress($academicYearId);

        $totalStudents = array_sum(array_column($classes, 'students_count'));
        $totalClasses = count($classes);
        $totalAssessed = array_sum(array_column($classes, 'assessed_count'));
        $totalExpected = array_sum(array_column($classes, 'expected_count'));

        $progress = new Progress($totalExpected, $totalAssessed);

        return [
            'total_students' => $totalStudents,
            'total_classes' => $totalClasses,
            'total_assessed' => $totalAssessed,
            'total_expected' => $totalExpected,
            'overall_percent' => $progress->percent(),
        ];
    }

    /**
     * Dapatkan kelas prioritas (yang belum selesai, diurutkan dari progres terendah).
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     academic_year_id: int,
     *     period: string,
     *     students_count: int,
     *     materials_count: int,
     *     assessed_count: int,
     *     expected_count: int,
     *     percent: int
     * }>
     */
    public function getPriorityClasses(?int $academicYearId = null, int $limit = 5): array
    {
        $classes = $this->getClassProgress($academicYearId);

        $incomplete = array_filter($classes, fn ($c) => $c['expected_count'] > $c['assessed_count']);

        usort($incomplete, function ($a, $b) {
            return $a['percent'] <=> $b['percent'];
        });

        return array_slice($incomplete, 0, $limit);
    }

    /**
     * Dapatkan N audit log aktivitas terbaru.
     *
     * @return Collection<int, AuditLog>
     */
    public function getRecentActivities(int $limit = 5): Collection
    {
        return AuditLog::query()
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
