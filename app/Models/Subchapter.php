<?php

namespace App\Models;

use Database\Factories\SubchapterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['chapter_id', 'title', 'display_order'])]
class Subchapter extends Model
{
    /** @use HasFactory<SubchapterFactory> */
    use HasFactory;

    /** @return BelongsTo<Chapter, $this> */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /** @return HasMany<Assessment, $this> */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class)->orderBy('display_order')->orderBy('id');
    }
}
