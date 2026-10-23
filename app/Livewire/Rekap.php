<?php

namespace App\Livewire;

use App\DTO\RecapFilter;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\AcademicYearService;
use App\Services\RecapService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Rekap extends Component
{
    #[Url(as: 'tahun')]
    public ?int $academic_year_id = null;

    #[Url(as: 'kelas')]
    public ?int $class_id = null;

    #[Url(as: 'siswa')]
    public ?string $student = null;

    #[Url(as: 'bab')]
    public ?string $chapter = null;

    #[Url(as: 'subbab')]
    public ?string $subchapter = null;

    #[Url(as: 'materi')]
    public ?int $assessment_id = null;

    #[Url(as: 'tab')]
    public string $active_tab = 'summary'; // 'summary' | 'matrix'

    #[Url(as: 'detail')]
    public ?int $selected_student_id = null;

    public function mount(AcademicYearService $academicYearService): void
    {
        if ($this->academic_year_id === null) {
            $active = $academicYearService->active();
            $this->academic_year_id = $active?->id;
        }
    }

    public function updatedAcademicYearId(): void
    {
        $this->class_id = null;
        $this->chapter = null;
        $this->subchapter = null;
        $this->assessment_id = null;
        $this->selected_student_id = null;
    }

    public function resetFilters(): void
    {
        $this->reset(['class_id', 'student', 'chapter', 'subchapter', 'assessment_id', 'selected_student_id']);
    }

    public function selectStudent(?int $id): void
    {
        $this->selected_student_id = $id;
    }

    public function closeStudentDetail(): void
    {
        $this->selected_student_id = null;
    }

    public function render(AcademicYearService $academicYearService, RecapService $recapService): View
    {
        $academicYears = $academicYearService->all();

        $classes = SchoolClass::query()
            ->when($this->academic_year_id, fn ($q) => $q->where('academic_year_id', $this->academic_year_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $chapters = Chapter::query()
            ->when($this->academic_year_id, function ($q) {
                $q->whereHas('curriculumTemplate', fn ($t) => $t->where('academic_year_id', $this->academic_year_id));
            })
            ->orderBy('display_order')
            ->get();

        $assessments = Assessment::query()
            ->when($this->academic_year_id, function ($q) {
                $q->whereHas('subchapter.chapter.curriculumTemplate', fn ($t) => $t->where('academic_year_id', $this->academic_year_id));
            })
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        $filter = new RecapFilter(
            academicYearId: $this->academic_year_id,
            classId: $this->class_id,
            student: $this->student,
            chapter: $this->chapter,
            subchapter: $this->subchapter,
            assessmentId: $this->assessment_id,
        );

        $recap = $this->active_tab === 'summary'
            ? $recapService->getRecap($filter)
            : ['students' => [], 'classes' => [], 'subchapters' => []];

        $studentDetails = ($this->active_tab === 'summary' && $this->selected_student_id)
            ? $recapService->getStudentDetails($this->selected_student_id, $filter)
            : [];

        $selectedStudentModel = $this->selected_student_id
            ? Student::query()->find($this->selected_student_id)
            : null;

        $matrix = ($this->active_tab === 'matrix' && $this->class_id)
            ? $recapService->getMatrix($this->class_id)
            : null;

        return view('livewire.rekap', [
            'academicYears' => $academicYears,
            'classes' => $classes,
            'chapters' => $chapters,
            'assessments' => $assessments,
            'recap' => $recap,
            'studentDetails' => $studentDetails,
            'selectedStudentModel' => $selectedStudentModel,
            'matrix' => $matrix,
        ]);
    }
}
