<?php

namespace App\Livewire;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subchapter;
use App\Services\BackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Laporan & Backup'])]
class Laporan extends Component
{
    #[Url(as: 'academic_year_id')]
    public string $academicYearId = '';

    #[Url(as: 'class_id')]
    public string $classId = '';

    #[Url(as: 'chapter')]
    public string $chapter = '';

    #[Url(as: 'subchapter')]
    public string $subchapter = '';

    #[Url(as: 'assessment_id')]
    public string $assessmentId = '';

    #[Url(as: 'student')]
    public string $student = '';

    public string $selectedStudentId = '';

    public function mount(): void
    {
        if ($this->academicYearId === '') {
            $activeYear = AcademicYear::query()->where('is_active', true)->first();
            if ($activeYear) {
                $this->academicYearId = (string) $activeYear->id;
            }
        }
    }

    public function updatedAcademicYearId(): void
    {
        $this->classId = '';
        $this->chapter = '';
        $this->subchapter = '';
        $this->assessmentId = '';
        $this->selectedStudentId = '';
    }

    public function updatedClassId(): void
    {
        $this->selectedStudentId = '';
    }

    public function updatedChapter(): void
    {
        $this->subchapter = '';
        $this->assessmentId = '';
    }

    public function updatedSubchapter(): void
    {
        $this->assessmentId = '';
    }

    public function backupNow(BackupService $backupService): void
    {
        $file = $backupService->createScheduledBackup(force: true);
        if ($file) {
            session()->flash('backup_status', 'Cadangan data berhasil dibuat di server: '.basename($file));
        } else {
            session()->flash('backup_status', 'Cadangan data berhasil diperbarui.');
        }
    }

    /**
     * @return array<string, int|string>
     */
    public function getQueryStringParamsProperty(): array
    {
        $params = [];
        if ($this->academicYearId !== '') {
            $params['academic_year_id'] = (int) $this->academicYearId;
        }
        if ($this->classId !== '') {
            $params['class_id'] = (int) $this->classId;
        }
        if ($this->chapter !== '') {
            $params['chapter'] = $this->chapter;
        }
        if ($this->subchapter !== '') {
            $params['subchapter'] = $this->subchapter;
        }
        if ($this->assessmentId !== '') {
            $params['assessment_id'] = (int) $this->assessmentId;
        }
        if ($this->student !== '') {
            $params['student'] = $this->student;
        }

        return $params;
    }

    public function render(BackupService $backupService): View
    {
        /** @var Collection<int, AcademicYear> $years */
        $years = AcademicYear::query()
            ->orderByDesc('is_active')
            ->orderByDesc('name')
            ->orderBy('semester')
            ->get();

        $classesQuery = SchoolClass::query()->where('is_active', true);
        if ($this->academicYearId !== '') {
            $classesQuery->where('academic_year_id', $this->academicYearId);
        }
        /** @var Collection<int, SchoolClass> $classes */
        $classes = $classesQuery->orderBy('name')->get();

        // Daftar siswa untuk pemilih rapor
        $studentsQuery = Student::query()->where('is_active', true);
        if ($this->classId !== '') {
            $studentsQuery->where('school_class_id', $this->classId);
        } elseif ($this->academicYearId !== '') {
            $classIds = $classes->pluck('id')->all();
            $studentsQuery->whereIn('school_class_id', $classIds);
        }
        /** @var Collection<int, Student> $students */
        $students = $studentsQuery->orderBy('name')->get();

        // Opsi Bab, Subbab, Materi
        $chaptersQuery = Chapter::query();
        if ($this->academicYearId !== '') {
            $chaptersQuery->whereHas('curriculumTemplate', function ($q) {
                $q->where('academic_year_id', $this->academicYearId);
            });
        }
        $chapters = $chaptersQuery->orderBy('display_order')->pluck('title')->unique()->values()->all();

        $subchaptersQuery = Subchapter::query();
        if ($this->chapter !== '') {
            $subchaptersQuery->whereHas('chapter', fn ($q) => $q->where('title', $this->chapter));
        } elseif ($this->academicYearId !== '') {
            $subchaptersQuery->whereHas('chapter.curriculumTemplate', fn ($q) => $q->where('academic_year_id', $this->academicYearId));
        }
        $subchapters = $subchaptersQuery->orderBy('display_order')->pluck('title')->unique()->values()->all();

        $assessmentsQuery = Assessment::query()->where('is_active', true);
        if ($this->subchapter !== '') {
            $assessmentsQuery->whereHas('subchapter', fn ($q) => $q->where('title', $this->subchapter));
        } elseif ($this->chapter !== '') {
            $assessmentsQuery->whereHas('subchapter.chapter', fn ($q) => $q->where('title', $this->chapter));
        } elseif ($this->academicYearId !== '') {
            $assessmentsQuery->whereHas('subchapter.chapter.curriculumTemplate', fn ($q) => $q->where('academic_year_id', $this->academicYearId));
        }
        $assessments = $assessmentsQuery->orderBy('display_order')->get(['id', 'title']);

        return view('livewire.laporan', [
            'years' => $years,
            'classes' => $classes,
            'students' => $students,
            'chapters' => $chapters,
            'subchapters' => $subchapters,
            'assessments' => $assessments,
            'backupStatus' => $backupService->status(),
        ]);
    }
}
