<?php

namespace App\Livewire;

use App\Services\StudentImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class SiswaImpor extends Component
{
    use WithFileUploads;

    public ?UploadedFile $file = null;

    public ?int $importedCount = null;

    /**
     * @var array<int, array{row: int, error: string, data: array<string, mixed>}>
     */
    public array $failedRows = [];

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function import(StudentImporter $importer): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:2048'],
        ]);

        if (! $this->file) {
            return;
        }

        try {
            $result = $importer->import($this->file, userId: Auth::id());

            $this->importedCount = $result->importedCount;
            $this->failedRows = $result->failed;
            $this->statusMessage = "Berhasil mengimpor {$result->importedCount} siswa.";
            $this->reset('file');
        } catch (ValidationException $e) {
            $this->errorMessage = $e->validator->errors()->first();
        }
    }

    public function render(): View
    {
        return view('livewire.siswa-impor');
    }
}
