<?php

namespace App\Models;

use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Materi yang dinilai (butir penilaian) di dalam sebuah subbab. */
#[Fillable(['subchapter_id', 'title', 'description', 'weight', 'display_order', 'is_active'])]
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Subchapter, $this> */
    public function subchapter(): BelongsTo
    {
        return $this->belongsTo(Subchapter::class);
    }

    /** @return HasMany<Score, $this> */
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    /**
     * @param  Builder<Assessment>  $query
     * @return Builder<Assessment>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
