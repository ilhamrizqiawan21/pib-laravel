<?php

namespace App\Livewire;

use App\Enums\Semester;
use App\Models\AcademicYear;
use App\Repositories\SettingsRepository;
use App\Services\AcademicYearService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('layouts.app')]
class Sekolah extends Component
{
    #[Rule(['nullable', 'string', 'max:150'])]
    public string $school_name = '';

    #[Rule(['nullable', 'string', 'max:250'])]
    public string $school_address = '';

    #[Rule(['nullable', 'string', 'max:120'])]
    public string $teacher_name = '';

    public string $new_year_name = '';

    public string $new_year_semester = 'Ganjil';

    public bool $new_year_is_active = false;

    public ?string $statusMessage = null;

    public ?string $academicYearErrorMessage = null;

    public function mount(SettingsRepository $settings): void
    {
        $this->school_name = $settings->schoolName();
        $this->school_address = $settings->schoolAddress();
        $this->teacher_name = $settings->teacherName();
    }

    public function saveIdentity(SettingsRepository $settings): void
    {
        $validated = $this->validate([
            'school_name' => ['nullable', 'string', 'max:150'],
            'school_address' => ['nullable', 'string', 'max:250'],
            'teacher_name' => ['nullable', 'string', 'max:120'],
        ]);

        $settings->setMany([
            'school_name' => $validated['school_name'] ?? '',
            'school_address' => $validated['school_address'] ?? '',
            'teacher_name' => $validated['teacher_name'] ?? '',
        ], userId: Auth::id());

        $this->statusMessage = 'Identitas sekolah berhasil disimpan.';
    }

    public function createAcademicYear(AcademicYearService $service): void
    {
        $this->validate([
            'new_year_name' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'new_year_semester' => ['required', 'in:Ganjil,Genap'],
            'new_year_is_active' => ['boolean'],
        ], [
            'new_year_name.regex' => 'Format tahun ajaran harus YYYY/YYYY (contoh: 2024/2025).',
        ]);

        try {
            $service->create(
                name: $this->new_year_name,
                semester: Semester::from($this->new_year_semester),
                isActive: $this->new_year_is_active,
                userId: Auth::id()
            );

            $this->reset(['new_year_name', 'new_year_semester', 'new_year_is_active']);
            $this->academicYearErrorMessage = null;
            $this->statusMessage = 'Tahun ajaran berhasil ditambahkan.';
            $this->dispatch('close-modal', 'modal-tambah-tahun');
        } catch (ValidationException $e) {
            $this->addError('new_year_name', $e->validator->errors()->first());
        }
    }

    public function activateAcademicYear(int $id, AcademicYearService $service): void
    {
        $year = AcademicYear::findOrFail($id);
        $service->activate($year, userId: Auth::id());
        $this->statusMessage = "Tahun ajaran {$year->label()} berhasil diaktifkan.";
    }

    public function deleteAcademicYear(int $id, AcademicYearService $service): void
    {
        $year = AcademicYear::findOrFail($id);

        try {
            $service->delete($year, userId: Auth::id());
            $this->statusMessage = 'Tahun ajaran berhasil dihapus.';
            $this->academicYearErrorMessage = null;
        } catch (ValidationException $e) {
            $this->academicYearErrorMessage = $e->validator->errors()->first();
        }
    }

    public function render(AcademicYearService $academicYearService): View
    {
        return view('livewire.sekolah', [
            'academicYears' => $academicYearService->all(),
        ]);
    }
}
