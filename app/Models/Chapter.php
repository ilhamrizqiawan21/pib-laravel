<?php

namespace App\Models;

use Database\Factories\ChapterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['curriculum_template_id', 'title', 'display_order'])]
class Chapter extends Model
{
    /** @use HasFactory<ChapterFactory> */
    use HasFactory;

    /** @return BelongsTo<CurriculumTemplate, $this> */
    public function curriculumTemplate(): BelongsTo
    {
        return $this->belongsTo(CurriculumTemplate::class);
    }

    /** @return HasMany<Subchapter, $this> */
    public function subchapters(): HasMany
    {
        return $this->hasMany(Subchapter::class)->orderBy('display_order')->orderBy('id');
    }
}
