<?php

namespace App\Services;

use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SchoolClassService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, SchoolClass>
     */
    public function forAcademicYear(?int $academicYearId = null, ?string $search = null): Collection
    {
        return SchoolClass::query()
            ->with(['academicYear'])
            ->withCount('students')
            ->when($academicYearId !== null, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when(! blank($search), fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->get();
    }

    public function create(
        int $academicYearId,
        string $name,
        string $gradeLevel = '',
        string $homeroomTeacher = '',
        bool $isActive = true,
        ?int $userId = null
    ): SchoolClass {
        $this->validateUniqueness($academicYearId, $name);

        return DB::transaction(function () use ($academicYearId, $name, $gradeLevel, $homeroomTeacher, $isActive, $userId): SchoolClass {
            $class = SchoolClass::create([
                'academic_year_id' => $academicYearId,
                'name' => $name,
                'grade_level' => $gradeLevel,
                'homeroom_teacher' => $homeroomTeacher,
                'is_active' => $isActive,
            ]);

            $this->auditLogger->record(
                entity: 'class',
                entityId: $class->id,
                action: 'CREATE',
                summary: "Tambah kelas {$class->name}",
                meta: [
                    'academic_year_id' => $academicYearId,
                    'name' => $name,
                    'grade_level' => $gradeLevel,
                    'homeroom_teacher' => $homeroomTeacher,
                ],
                userId: $userId
            );

            return $class;
        });
    }

    public function update(
        SchoolClass $class,
        string $name,
        string $gradeLevel = '',
        string $homeroomTeacher = '',
        bool $isActive = true,
        ?int $userId = null
    ): SchoolClass {
        $this->validateUniqueness($class->academic_year_id, $name, $class->id);

        return DB::transaction(function () use ($class, $name, $gradeLevel, $homeroomTeacher, $isActive, $userId): SchoolClass {
            $class->update([
                'name' => $name,
                'grade_level' => $gradeLevel,
                'homeroom_teacher' => $homeroomTeacher,
                'is_active' => $isActive,
            ]);

            $this->auditLogger->record(
                entity: 'class',
                entityId: $class->id,
                action: 'UPDATE',
                summary: "Ubah kelas {$class->name}",
                meta: [
                    'name' => $name,
                    'grade_level' => $gradeLevel,
                    'homeroom_teacher' => $homeroomTeacher,
                    'is_active' => $isActive,
                ],
                userId: $userId
            );

            return $class;
        });
    }

    public function toggleActive(SchoolClass $class, ?int $userId = null): SchoolClass
    {
        return DB::transaction(function () use ($class, $userId): SchoolClass {
            $newStatus = ! $class->is_active;
            $class->update(['is_active' => $newStatus]);

            $this->auditLogger->record(
                entity: 'class',
                entityId: $class->id,
                action: $newStatus ? 'UPDATE' : 'DEACTIVATE',
                summary: ($newStatus ? 'Aktifkan' : 'Nonaktifkan')." kelas {$class->name}",
                meta: ['is_active' => $newStatus],
                userId: $userId
            );

            return $class;
        });
    }

    public function delete(SchoolClass $class, ?int $userId = null): void
    {
        if ($class->students()->exists()) {
            throw ValidationException::withMessages([
                'class' => 'Kelas tidak dapat dihapus karena masih memiliki siswa. Silakan nonaktifkan kelas ini.',
            ]);
        }

        DB::transaction(function () use ($class, $userId): void {
            $name = $class->name;
            $id = $class->id;

            $class->delete();

            $this->auditLogger->record(
                entity: 'class',
                entityId: $id,
                action: 'DELETE',
                summary: "Hapus kelas {$name}",
                userId: $userId
            );
        });
    }

    private function validateUniqueness(int $academicYearId, string $name, ?int $ignoreId = null): void
    {
        $exists = SchoolClass::query()
            ->where('academic_year_id', $academicYearId)
            ->where('name', $name)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => "Nama kelas {$name} sudah ada pada tahun ajaran ini.",
            ]);
        }
    }
}
