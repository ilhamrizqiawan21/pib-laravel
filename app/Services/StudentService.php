<?php

namespace App\Services;

use App\Enums\Gender;
use App\Models\Student;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudentService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Student>
     */
    public function paginate(
        int $perPage = 25,
        ?int $schoolClassId = null,
        ?string $search = null,
        ?bool $isActive = null
    ): LengthAwarePaginator {
        return Student::query()
            ->with(['schoolClass'])
            ->withCount('scores')
            ->when($schoolClassId !== null, fn ($q) => $q->where('school_class_id', $schoolClassId))
            ->when(! blank($search), function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nis', 'like', '%'.$search.'%')
                        ->orWhere('nisn', 'like', '%'.$search.'%');
                });
            })
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(
        int $schoolClassId,
        string $name,
        ?string $nis = null,
        string $nisn = '',
        Gender|string|null $gender = null,
        bool $isActive = true,
        ?int $userId = null
    ): Student {
        $normalizedNis = $this->normalizeNis($nis);
        $normalizedGender = $this->resolveGender($gender);

        $this->validateNisUniqueness($normalizedNis);

        return DB::transaction(function () use ($schoolClassId, $name, $normalizedNis, $nisn, $normalizedGender, $isActive, $userId): Student {
            $student = Student::create([
                'school_class_id' => $schoolClassId,
                'name' => $name,
                'nis' => $normalizedNis,
                'nisn' => $nisn,
                'gender' => $normalizedGender,
                'is_active' => $isActive,
            ]);

            $this->auditLogger->record(
                entity: 'student',
                entityId: $student->id,
                action: 'CREATE',
                summary: "Tambah siswa {$student->name}",
                meta: [
                    'school_class_id' => $schoolClassId,
                    'name' => $name,
                    'nis' => $normalizedNis,
                    'gender' => $normalizedGender?->value,
                ],
                userId: $userId
            );

            return $student;
        });
    }

    public function update(
        Student $student,
        int $schoolClassId,
        string $name,
        ?string $nis = null,
        string $nisn = '',
        Gender|string|null $gender = null,
        bool $isActive = true,
        ?int $userId = null
    ): Student {
        $normalizedNis = $this->normalizeNis($nis);
        $normalizedGender = $this->resolveGender($gender);

        $this->validateNisUniqueness($normalizedNis, $student->id);

        return DB::transaction(function () use ($student, $schoolClassId, $name, $normalizedNis, $nisn, $normalizedGender, $isActive, $userId): Student {
            $oldClassId = $student->school_class_id;

            $student->update([
                'school_class_id' => $schoolClassId,
                'name' => $name,
                'nis' => $normalizedNis,
                'nisn' => $nisn,
                'gender' => $normalizedGender,
                'is_active' => $isActive,
            ]);

            $this->auditLogger->record(
                entity: 'student',
                entityId: $student->id,
                action: 'UPDATE',
                summary: "Ubah data siswa {$student->name}",
                meta: [
                    'old_school_class_id' => $oldClassId,
                    'new_school_class_id' => $schoolClassId,
                    'name' => $name,
                    'nis' => $normalizedNis,
                    'is_active' => $isActive,
                ],
                userId: $userId
            );

            return $student;
        });
    }

    public function toggleActive(Student $student, ?int $userId = null): Student
    {
        return DB::transaction(function () use ($student, $userId): Student {
            $newStatus = ! $student->is_active;
            $student->update(['is_active' => $newStatus]);

            $this->auditLogger->record(
                entity: 'student',
                entityId: $student->id,
                action: $newStatus ? 'UPDATE' : 'DEACTIVATE',
                summary: ($newStatus ? 'Aktifkan' : 'Nonaktifkan')." siswa {$student->name}",
                meta: ['is_active' => $newStatus],
                userId: $userId
            );

            return $student;
        });
    }

    public function deletePermanently(Student $student, ?int $userId = null): int
    {
        return DB::transaction(function () use ($student, $userId): int {
            $name = $student->name;
            $id = $student->id;
            $affectedScoresCount = $student->scores()->count();

            // Hapus nilai terdampak
            $student->scores()->delete();
            $student->delete();

            $this->auditLogger->record(
                entity: 'student',
                entityId: $id,
                action: 'DELETE',
                summary: "Hapus permanen siswa {$name}",
                meta: [
                    'student_name' => $name,
                    'scores_deleted' => $affectedScoresCount,
                ],
                userId: $userId
            );

            return $affectedScoresCount;
        });
    }

    private function normalizeNis(?string $nis): ?string
    {
        if ($nis === null) {
            return null;
        }

        $trimmed = trim($nis);

        return $trimmed === '' ? null : $trimmed;
    }

    private function resolveGender(Gender|string|null $gender): ?Gender
    {
        if ($gender === null || $gender === '') {
            return null;
        }

        if ($gender instanceof Gender) {
            return $gender;
        }

        return Gender::tryFrom($gender);
    }

    private function validateNisUniqueness(?string $nis, ?int $ignoreId = null): void
    {
        if ($nis === null) {
            return;
        }

        $exists = Student::query()
            ->where('nis', $nis)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'nis' => "NIS {$nis} sudah digunakan oleh siswa lain.",
            ]);
        }
    }
}
