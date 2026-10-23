<?php

namespace App\Models;

use App\Enums\Semester;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Semester $semester
 */
#[Fillable(['name', 'semester', 'is_active'])]
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<SchoolClass, $this> */
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    /** @return HasMany<CurriculumTemplate, $this> */
    public function curriculumTemplates(): HasMany
    {
        return $this->hasMany(CurriculumTemplate::class);
    }

    public function label(): string
    {
        return "{$this->name} {$this->semester->value}";
    }
}
