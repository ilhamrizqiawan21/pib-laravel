<?php

namespace App\Livewire;

use App\Enums\Gender;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Siswa extends Component
{
    use WithPagination;

    public ?int $filter_class_id = null;

    public string $filter_active = '';

    public string $search = '';

    public ?int $editing_id = null;

    public ?int $form_school_class_id = null;

    public string $form_name = '';

    public ?string $form_nis = null;

    public string $form_nisn = '';

    public ?string $form_gender = '';

    public bool $form_is_active = true;

    // State untuk konfirmasi hapus permanen
    public ?int $deleting_id = null;

    public string $deleting_name = '';

    public int $deleting_scores_count = 0;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterClassId(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['editing_id', 'form_name', 'form_nis', 'form_nisn', 'form_gender', 'errorMessage']);
        $this->form_school_class_id = $this->filter_class_id ?? SchoolClass::query()->value('id');
        $this->form_is_active = true;
        $this->dispatch('open-modal', 'modal-form-siswa');
    }

    public function openEditModal(int $id): void
    {
        $student = Student::findOrFail($id);

        $this->editing_id = $student->id;
        $this->form_school_class_id = $student->school_class_id;
        $this->form_name = $student->name;
        $this->form_nis = $student->nis;
        $this->form_nisn = $student->nisn;
        $this->form_gender = $student->gender instanceof Gender ? $student->gender->value : '';
        $this->form_is_active = $student->is_active;
        $this->errorMessage = null;

        $this->dispatch('open-modal', 'modal-form-siswa');
    }

    public function saveStudent(StudentService $service): void
    {
        $this->validate([
            'form_school_class_id' => ['required', 'exists:school_classes,id'],
            'form_name' => ['required', 'string', 'max:120'],
            'form_nis' => ['nullable', 'string', 'max:30'],
            'form_nisn' => ['nullable', 'string', 'max:30'],
            'form_gender' => ['nullable', 'in:L,P'],
            'form_is_active' => ['boolean'],
        ]);

        $genderEnum = ! blank($this->form_gender) ? Gender::from($this->form_gender) : null;

        try {
            if ($this->editing_id) {
                $student = Student::findOrFail($this->editing_id);
                $service->update(
                    student: $student,
                    schoolClassId: (int) $this->form_school_class_id,
                    name: $this->form_name,
                    nis: $this->form_nis,
                    nisn: $this->form_nisn,
                    gender: $genderEnum,
                    isActive: $this->form_is_active,
                    userId: Auth::id()
                );
                $this->statusMessage = "Data siswa {$this->form_name} berhasil diperbarui.";
            } else {
                $service->create(
                    schoolClassId: (int) $this->form_school_class_id,
                    name: $this->form_name,
                    nis: $this->form_nis,
                    nisn: $this->form_nisn,
                    gender: $genderEnum,
                    isActive: $this->form_is_active,
                    userId: Auth::id()
                );
                $this->statusMessage = "Siswa {$this->form_name} berhasil ditambahkan.";
            }

            $this->dispatch('close-modal', 'modal-form-siswa');
            $this->reset(['editing_id', 'form_name', 'form_nis', 'form_nisn', 'form_gender']);
            $this->errorMessage = null;
        } catch (ValidationException $e) {
            $this->addError('form_nis', $e->validator->errors()->first());
        }
    }

    public function toggleStudentActive(int $id, StudentService $service): void
    {
        $student = Student::findOrFail($id);
        $service->toggleActive($student, userId: Auth::id());
        $this->statusMessage = "Status siswa {$student->name} berhasil diperbarui.";
    }

    public function confirmDelete(int $id): void
    {
        $student = Student::withCount('scores')->findOrFail($id);
        $this->deleting_id = $student->id;
        $this->deleting_name = $student->name;
        $this->deleting_scores_count = $student->scores_count;

        $this->dispatch('open-modal', 'modal-konfirmasi-hapus');
    }

    public function deleteConfirmed(StudentService $service): void
    {
        if (! $this->deleting_id) {
            return;
        }

        $student = Student::findOrFail($this->deleting_id);
        $name = $student->name;
        $deletedScores = $service->deletePermanently($student, userId: Auth::id());

        $this->statusMessage = "Siswa {$name} berhasil dihapus permanen".($deletedScores > 0 ? " beserta {$deletedScores} nilai terdampak." : '.');
        $this->dispatch('close-modal', 'modal-konfirmasi-hapus');
        $this->reset(['deleting_id', 'deleting_name', 'deleting_scores_count']);
    }

    public function render(StudentService $studentService): View
    {
        $classes = SchoolClass::query()->orderBy('name')->get();

        $isActiveFilter = match ($this->filter_active) {
            '1' => true,
            '0' => false,
            default => null,
        };

        $students = $studentService->paginate(
            perPage: 25,
            schoolClassId: $this->filter_class_id,
            search: $this->search,
            isActive: $isActiveFilter
        );

        return view('livewire.siswa', [
            'classes' => $classes,
            'students' => $students,
        ]);
    }
}
