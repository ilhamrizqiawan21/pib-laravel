<?php

namespace App\Services;

use App\Enums\Semester;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcademicYearService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, AcademicYear>
     */
    public function all(): Collection
    {
        return AcademicYear::query()
            ->orderByDesc('name')
            ->orderBy('semester')
            ->get();
    }

    public function active(): ?AcademicYear
    {
        return AcademicYear::query()->where('is_active', true)->first();
    }

    public function create(string $name, Semester|string $semester, bool $isActive = false, ?int $userId = null): AcademicYear
    {
        $this->validateNameFormat($name);
        $semesterEnum = $this->resolveSemester($semester);
        $this->validateUniqueness($name, $semesterEnum);

        return DB::transaction(function () use ($name, $semesterEnum, $isActive, $userId): AcademicYear {
            if ($isActive) {
                AcademicYear::query()->update(['is_active' => false]);
            }

            $year = AcademicYear::create([
                'name' => $name,
                'semester' => $semesterEnum,
                'is_active' => $isActive,
            ]);

            $this->auditLogger->record(
                entity: 'academic_year',
                entityId: $year->id,
                action: 'CREATE',
                summary: 'Tambah tahun ajaran '.$year->label(),
                meta: [
                    'name' => $name,
                    'semester' => $semesterEnum->value,
                    'is_active' => $isActive,
                ],
                userId: $userId
            );

            return $year;
        });
    }

    public function update(AcademicYear $year, string $name, Semester|string $semester, bool $isActive, ?int $userId = null): AcademicYear
    {
        $this->validateNameFormat($name);
        $semesterEnum = $this->resolveSemester($semester);
        $this->validateUniqueness($name, $semesterEnum, $year->id);

        return DB::transaction(function () use ($year, $name, $semesterEnum, $isActive, $userId): AcademicYear {
            if ($isActive && ! $year->is_active) {
                AcademicYear::query()->where('id', '!=', $year->id)->update(['is_active' => false]);
            }

            $year->update([
                'name' => $name,
                'semester' => $semesterEnum,
                'is_active' => $isActive,
            ]);

            $this->auditLogger->record(
                entity: 'academic_year',
                entityId: $year->id,
                action: 'UPDATE',
                summary: 'Ubah tahun ajaran '.$year->label(),
                meta: [
                    'name' => $name,
                    'semester' => $semesterEnum->value,
                    'is_active' => $isActive,
                ],
                userId: $userId
            );

            return $year;
        });
    }

    public function activate(AcademicYear $year, ?int $userId = null): AcademicYear
    {
        return DB::transaction(function () use ($year, $userId): AcademicYear {
            AcademicYear::query()->where('id', '!=', $year->id)->update(['is_active' => false]);
            AcademicYear::query()->where('id', $year->id)->update(['is_active' => true]);

            $year->refresh();

            $this->auditLogger->record(
                entity: 'academic_year',
                entityId: $year->id,
                action: 'UPDATE',
                summary: 'Jadikan aktif tahun ajaran '.$year->label(),
                meta: ['is_active' => true],
                userId: $userId
            );

            return $year;
        });
    }

    public function delete(AcademicYear $year, ?int $userId = null): void
    {
        if ($year->classes()->exists() || $year->curriculumTemplates()->exists()) {
            throw ValidationException::withMessages([
                'academic_year' => 'Tahun ajaran tidak dapat dihapus karena masih digunakan oleh data kelas atau materi.',
            ]);
        }

        DB::transaction(function () use ($year, $userId): void {
            $label = $year->label();
            $yearId = $year->id;

            $year->delete();

            $this->auditLogger->record(
                entity: 'academic_year',
                entityId: $yearId,
                action: 'DELETE',
                summary: 'Hapus tahun ajaran '.$label,
                userId: $userId
            );
        });
    }

    private function validateNameFormat(string $name): void
    {
        if (! preg_match('/^\d{4}\/\d{4}$/', $name)) {
            throw ValidationException::withMessages([
                'name' => 'Format tahun ajaran harus YYYY/YYYY (contoh: 2024/2025).',
            ]);
        }
    }

    private function resolveSemester(Semester|string $semester): Semester
    {
        if ($semester instanceof Semester) {
            return $semester;
        }

        $resolved = Semester::tryFrom($semester);
        if ($resolved === null) {
            throw ValidationException::withMessages([
                'semester' => 'Semester harus bernilai Ganjil atau Genap.',
            ]);
        }

        return $resolved;
    }

    private function validateUniqueness(string $name, Semester $semester, ?int $ignoreId = null): void
    {
        $exists = AcademicYear::query()
            ->where('name', $name)
            ->where('semester', $semester)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => "Tahun ajaran {$name} semester {$semester->value} sudah ada sebelumnya.",
            ]);
        }
    }
}
