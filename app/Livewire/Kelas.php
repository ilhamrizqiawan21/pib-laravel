<?php

namespace App\Livewire;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Services\AcademicYearService;
use App\Services\SchoolClassService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Kelas extends Component
{
    public ?int $filter_academic_year_id = null;

    public string $search = '';

    public ?int $editing_id = null;

    public ?int $form_academic_year_id = null;

    public string $form_name = '';

    public string $form_grade_level = '';

    public string $form_homeroom_teacher = '';

    public bool $form_is_active = true;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(AcademicYearService $academicYearService): void
    {
        $activeYear = $academicYearService->active();
        $this->filter_academic_year_id = $activeYear !== null
            ? $activeYear->id
            : AcademicYear::query()->latest('id')->value('id');
        $this->form_academic_year_id = $this->filter_academic_year_id;
    }

    public function openCreateModal(): void
    {
        $this->reset(['editing_id', 'form_name', 'form_grade_level', 'form_homeroom_teacher', 'errorMessage']);
        $this->form_academic_year_id = $this->filter_academic_year_id ?? AcademicYear::query()->value('id');
        $this->form_is_active = true;
        $this->dispatch('open-modal', 'modal-form-kelas');
    }

    public function openEditModal(int $id): void
    {
        $class = SchoolClass::findOrFail($id);

        $this->editing_id = $class->id;
        $this->form_academic_year_id = $class->academic_year_id;
        $this->form_name = $class->name;
        $this->form_grade_level = $class->grade_level;
        $this->form_homeroom_teacher = $class->homeroom_teacher;
        $this->form_is_active = $class->is_active;
        $this->errorMessage = null;

        $this->dispatch('open-modal', 'modal-form-kelas');
    }

    public function saveClass(SchoolClassService $service): void
    {
        $this->validate([
            'form_academic_year_id' => ['required', 'exists:academic_years,id'],
            'form_name' => ['required', 'string', 'max:50'],
            'form_grade_level' => ['nullable', 'string', 'max:20'],
            'form_homeroom_teacher' => ['nullable', 'string', 'max:100'],
            'form_is_active' => ['boolean'],
        ]);

        try {
            if ($this->editing_id) {
                $class = SchoolClass::findOrFail($this->editing_id);
                $service->update(
                    class: $class,
                    name: $this->form_name,
                    gradeLevel: $this->form_grade_level,
                    homeroomTeacher: $this->form_homeroom_teacher,
                    isActive: $this->form_is_active,
                    userId: Auth::id()
                );
                $this->statusMessage = "Kelas {$this->form_name} berhasil diperbarui.";
            } else {
                $service->create(
                    academicYearId: (int) $this->form_academic_year_id,
                    name: $this->form_name,
                    gradeLevel: $this->form_grade_level,
                    homeroomTeacher: $this->form_homeroom_teacher,
                    isActive: $this->form_is_active,
                    userId: Auth::id()
                );
                $this->statusMessage = "Kelas {$this->form_name} berhasil ditambahkan.";
            }

            $this->dispatch('close-modal', 'modal-form-kelas');
            $this->reset(['editing_id', 'form_name', 'form_grade_level', 'form_homeroom_teacher']);
            $this->errorMessage = null;
        } catch (ValidationException $e) {
            $this->addError('form_name', $e->validator->errors()->first());
        }
    }

    public function toggleClassActive(int $id, SchoolClassService $service): void
    {
        $class = SchoolClass::findOrFail($id);
        $service->toggleActive($class, userId: Auth::id());
        $this->statusMessage = "Status kelas {$class->name} berhasil diperbarui.";
    }

    public function deleteClass(int $id, SchoolClassService $service): void
    {
        $class = SchoolClass::findOrFail($id);

        try {
            $service->delete($class, userId: Auth::id());
            $this->statusMessage = "Kelas {$class->name} berhasil dihapus.";
            $this->errorMessage = null;
        } catch (ValidationException $e) {
            $this->errorMessage = $e->validator->errors()->first();
        }
    }

    public function render(AcademicYearService $academicYearService, SchoolClassService $schoolClassService): View
    {
        $academicYears = $academicYearService->all();
        $classes = $schoolClassService->forAcademicYear(
            academicYearId: $this->filter_academic_year_id,
            search: $this->search
        );

        return view('livewire.kelas', [
            'academicYears' => $academicYears,
            'classes' => $classes,
        ]);
    }
}
