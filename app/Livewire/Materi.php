<?php

namespace App\Livewire;

use App\Exceptions\CurriculumException;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\Subchapter;
use App\Services\AcademicYearService;
use App\Services\CurriculumService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Materi extends Component
{
    public ?int $selected_academic_year_id = null;

    public ?int $selected_template_id = null;

    // Form Template
    public string $new_template_name = '';

    // Form Bab
    public ?int $editing_chapter_id = null;

    public string $new_chapter_title = '';

    // Form Subbab
    public ?int $parent_chapter_id = null;

    public ?int $editing_subchapter_id = null;

    public string $new_subchapter_title = '';

    // Form Materi
    public ?int $parent_subchapter_id = null;

    public ?int $editing_assessment_id = null;

    public string $new_assessment_title = '';

    public float $new_assessment_weight = 1.0;

    public string $new_assessment_description = '';

    // Copy modal
    public ?int $copy_target_academic_year_id = null;

    public string $copy_new_name = '';

    /**
     * @var array{template_name: string, chapters_count: int, subchapters_count: int, assessments_count: int}|null
     */
    public ?array $copyPreview = null;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(AcademicYearService $academicYearService): void
    {
        $activeYear = $academicYearService->active();
        $this->selected_academic_year_id = $activeYear !== null
            ? $activeYear->id
            : AcademicYear::query()->latest('id')->value('id');

        $this->loadDefaultTemplate();
    }

    public function updatedSelectedAcademicYearId(): void
    {
        $this->loadDefaultTemplate();
    }

    private function loadDefaultTemplate(): void
    {
        if ($this->selected_academic_year_id) {
            $template = CurriculumTemplate::query()
                ->where('academic_year_id', $this->selected_academic_year_id)
                ->first();
            $this->selected_template_id = $template?->id;
        } else {
            $this->selected_template_id = CurriculumTemplate::query()->value('id');
        }
    }

    public function createTemplate(CurriculumService $service): void
    {
        $this->validate([
            'new_template_name' => ['required', 'string', 'max:100'],
            'selected_academic_year_id' => ['required', 'exists:academic_years,id'],
        ]);

        try {
            $template = $service->createTemplate(
                academicYearId: (int) $this->selected_academic_year_id,
                name: $this->new_template_name,
                userId: Auth::id()
            );

            $this->selected_template_id = $template->id;
            $this->reset('new_template_name');
            $this->statusMessage = "Template kurikulum {$template->name} berhasil dibuat.";
            $this->errorMessage = null;
            $this->dispatch('close-modal', 'modal-tambah-template');
        } catch (CurriculumException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function createChapter(CurriculumService $service): void
    {
        $this->validate([
            'new_chapter_title' => ['required', 'string', 'max:150'],
            'selected_template_id' => ['required', 'exists:curriculum_templates,id'],
        ]);

        try {
            $service->createChapter(
                templateId: (int) $this->selected_template_id,
                title: $this->new_chapter_title,
                userId: Auth::id()
            );

            $this->reset('new_chapter_title');
            $this->statusMessage = 'Bab berhasil ditambahkan.';
            $this->errorMessage = null;
            $this->dispatch('close-modal', 'modal-form-bab');
        } catch (CurriculumException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function createSubchapter(CurriculumService $service): void
    {
        $this->validate([
            'parent_chapter_id' => ['required', 'exists:chapters,id'],
            'new_subchapter_title' => ['required', 'string', 'max:150'],
        ]);

        try {
            $service->createSubchapter(
                chapterId: (int) $this->parent_chapter_id,
                title: $this->new_subchapter_title,
                userId: Auth::id()
            );

            $this->reset(['new_subchapter_title', 'parent_chapter_id']);
            $this->statusMessage = 'Subbab berhasil ditambahkan.';
            $this->errorMessage = null;
            $this->dispatch('close-modal', 'modal-form-subbab');
        } catch (CurriculumException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function createAssessment(CurriculumService $service): void
    {
        $this->validate([
            'parent_subchapter_id' => ['required', 'exists:subchapters,id'],
            'new_assessment_title' => ['required', 'string', 'max:150'],
            'new_assessment_weight' => ['required', 'numeric', 'between:0.1,100'],
            'new_assessment_description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->createAssessment(
                subchapterId: (int) $this->parent_subchapter_id,
                title: $this->new_assessment_title,
                weight: (float) $this->new_assessment_weight,
                description: $this->new_assessment_description ?: null,
                userId: Auth::id()
            );

            $this->reset(['new_assessment_title', 'new_assessment_weight', 'new_assessment_description', 'parent_subchapter_id']);
            $this->statusMessage = 'Materi berhasil ditambahkan.';
            $this->errorMessage = null;
            $this->dispatch('close-modal', 'modal-form-materi');
        } catch (CurriculumException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function toggleAssessment(int $id, CurriculumService $service): void
    {
        $assessment = Assessment::findOrFail($id);
        $service->toggleAssessmentActive($assessment, userId: Auth::id());
        $this->statusMessage = "Status materi {$assessment->title} berhasil diperbarui.";
    }

    public function deleteAssessment(int $id, CurriculumService $service): void
    {
        $assessment = Assessment::findOrFail($id);

        try {
            $service->deleteAssessment($assessment, userId: Auth::id());
            $this->statusMessage = "Materi {$assessment->title} berhasil dihapus.";
            $this->errorMessage = null;
        } catch (CurriculumException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function deleteChapter(int $id, CurriculumService $service): void
    {
        $chapter = Chapter::findOrFail($id);

        try {
            $service->deleteChapter($chapter, userId: Auth::id());
            $this->statusMessage = "Bab {$chapter->title} berhasil dihapus.";
            $this->errorMessage = null;
        } catch (CurriculumException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function deleteSubchapter(int $id, CurriculumService $service): void
    {
        $subchapter = Subchapter::findOrFail($id);

        try {
            $service->deleteSubchapter($subchapter, userId: Auth::id());
            $this->statusMessage = "Subbab {$subchapter->title} berhasil dihapus.";
            $this->errorMessage = null;
        } catch (CurriculumException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function moveItem(string $type, int $id, string $direction, CurriculumService $service): void
    {
        $modelClass = match ($type) {
            'chapter' => Chapter::class,
            'subchapter' => Subchapter::class,
            'assessment' => Assessment::class,
            default => null,
        };

        if (! $modelClass) {
            return;
        }

        $service->moveOrder($modelClass, $id, $direction, userId: Auth::id());
    }

    public function openCopyModal(CurriculumService $service): void
    {
        if (! $this->selected_template_id) {
            return;
        }

        $template = CurriculumTemplate::findOrFail($this->selected_template_id);
        $this->copyPreview = $service->previewCopy($template);
        $this->copy_new_name = $template->name;
        $this->copy_target_academic_year_id = AcademicYear::query()
            ->where('id', '!=', $template->academic_year_id)
            ->value('id');

        $this->dispatch('open-modal', 'modal-salin-struktur');
    }

    public function executeCopy(CurriculumService $service): void
    {
        $this->validate([
            'selected_template_id' => ['required', 'exists:curriculum_templates,id'],
            'copy_target_academic_year_id' => ['required', 'exists:academic_years,id'],
            'copy_new_name' => ['nullable', 'string', 'max:100'],
        ]);

        $sourceTemplate = CurriculumTemplate::findOrFail($this->selected_template_id);

        try {
            $copied = $service->copyToAcademicYear(
                sourceTemplate: $sourceTemplate,
                targetAcademicYearId: (int) $this->copy_target_academic_year_id,
                newName: $this->copy_new_name ?: null,
                userId: Auth::id()
            );

            $this->selected_academic_year_id = $copied->academic_year_id;
            $this->selected_template_id = $copied->id;
            $this->statusMessage = "Struktur kurikulum berhasil disalin ke template {$copied->name}.";
            $this->errorMessage = null;
            $this->dispatch('close-modal', 'modal-salin-struktur');
        } catch (CurriculumException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(AcademicYearService $academicYearService): View
    {
        $academicYears = $academicYearService->all();

        $templates = CurriculumTemplate::query()
            ->when($this->selected_academic_year_id, fn ($q) => $q->where('academic_year_id', $this->selected_academic_year_id))
            ->get();

        $template = $this->selected_template_id
            ? CurriculumTemplate::query()
                ->with(['chapters.subchapters.assessments'])
                ->find($this->selected_template_id)
            : null;

        return view('livewire.materi', [
            'academicYears' => $academicYears,
            'templates' => $templates,
            'template' => $template,
        ]);
    }
}
