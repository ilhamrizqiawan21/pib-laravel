<?php

namespace App\Models;

use Database\Factories\ScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Nilai satu siswa untuk satu materi. JANGAN ubah mistakes/score langsung dari UI:
 * lewat ScoreService (TODO fase 3) agar score dihitung server-side, version naik, dan audit tercatat.
 *
 * @property int $id
 * @property int $student_id
 * @property int $assessment_id
 * @property int|null $mistakes
 * @property int|null $score
 * @property Carbon|null $assessed_at
 * @property int|null $assessor_id
 * @property string $initials
 * @property string $note
 * @property int $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Student $student
 * @property-read Assessment $assessment
 * @property-read User|null $assessor
 */
#[Fillable(['student_id', 'assessment_id', 'mistakes', 'score', 'assessed_at', 'assessor_id', 'initials', 'note', 'version'])]
class Score extends Model
{
    /** @use HasFactory<ScoreFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'mistakes' => 'integer',
            'score' => 'integer',
            'assessed_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Assessment, $this> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }
}
