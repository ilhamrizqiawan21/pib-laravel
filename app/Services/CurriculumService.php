<?php

namespace App\Services;

use App\Exceptions\CurriculumException;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\Score;
use App\Models\Subchapter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class CurriculumService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function createTemplate(int $academicYearId, string $name, bool $isActive = true, ?int $userId = null): CurriculumTemplate
    {
        $this->validateTemplateName($academicYearId, $name);

        return DB::transaction(function () use ($academicYearId, $name, $isActive, $userId): CurriculumTemplate {
            $template = CurriculumTemplate::create([
                'academic_year_id' => $academicYearId,
                'name' => $name,
                'is_active' => $isActive,
            ]);

            $this->auditLogger->record(
                entity: 'template',
                entityId: $template->id,
                action: 'CREATE',
                summary: "Tambah template kurikulum {$template->name}",
                meta: ['academic_year_id' => $academicYearId, 'name' => $name],
                userId: $userId
            );

            return $template;
        });
    }

    public function updateTemplate(CurriculumTemplate $template, string $name, bool $isActive = true, ?int $userId = null): CurriculumTemplate
    {
        $this->validateTemplateName($template->academic_year_id, $name, $template->id);

        return DB::transaction(function () use ($template, $name, $isActive, $userId): CurriculumTemplate {
            $template->update([
                'name' => $name,
                'is_active' => $isActive,
            ]);

            $this->auditLogger->record(
                entity: 'template',
                entityId: $template->id,
                action: 'UPDATE',
                summary: "Ubah template kurikulum {$template->name}",
                meta: ['name' => $name, 'is_active' => $isActive],
                userId: $userId
            );

            return $template;
        });
    }

    public function deleteTemplate(CurriculumTemplate $template, ?int $userId = null): void
    {
        $hasScores = Score::query()
            ->whereIn('assessment_id', function ($query) use ($template): void {
                $query->select('id')
                    ->from('assessments')
                    ->whereIn('subchapter_id', function ($q2) use ($template): void {
                        $q2->select('id')
                            ->from('subchapters')
                            ->whereIn('chapter_id', function ($q3) use ($template): void {
                                $q3->select('id')
                                    ->from('chapters')
                                    ->where('curriculum_template_id', $template->id);
                            });
                    });
            })
            ->exists();

        if ($hasScores) {
            throw new CurriculumException('Template kurikulum tidak dapat dihapus karena materi di dalamnya sudah memiliki nilai siswa.');
        }

        DB::transaction(function () use ($template, $userId): void {
            $name = $template->name;
            $id = $template->id;

            $template->delete();

            $this->auditLogger->record(
                entity: 'template',
                entityId: $id,
                action: 'DELETE',
                summary: "Hapus template kurikulum {$name}",
                userId: $userId
            );
        });
    }

    /**
     * @return array{template_name: string, chapters_count: int, subchapters_count: int, assessments_count: int}
     */
    public function previewCopy(CurriculumTemplate $sourceTemplate): array
    {
        $sourceTemplate->loadCount(['chapters']);

        $chaptersCount = $sourceTemplate->chapters_count;

        $subchaptersCount = Subchapter::query()
            ->whereIn('chapter_id', function ($query) use ($sourceTemplate): void {
                $query->select('id')
                    ->from('chapters')
                    ->where('curriculum_template_id', $sourceTemplate->id);
            })
            ->count();

        $assessmentsCount = Assessment::query()
            ->whereIn('subchapter_id', function ($query) use ($sourceTemplate): void {
                $query->select('id')
                    ->from('subchapters')
                    ->whereIn('chapter_id', function ($q2) use ($sourceTemplate): void {
                        $q2->select('id')
                            ->from('chapters')
                            ->where('curriculum_template_id', $sourceTemplate->id);
                    });
            })
            ->count();

        return [
            'template_name' => $sourceTemplate->name,
            'chapters_count' => $chaptersCount,
            'subchapters_count' => $subchaptersCount,
            'assessments_count' => $assessmentsCount,
        ];
    }

    public function copyToAcademicYear(
        CurriculumTemplate $sourceTemplate,
        int $targetAcademicYearId,
        ?string $newName = null,
        ?int $userId = null
    ): CurriculumTemplate {
        $name = $newName ?? $sourceTemplate->name;

        $this->validateTemplateName($targetAcademicYearId, $name);

        return DB::transaction(function () use ($sourceTemplate, $targetAcademicYearId, $name, $userId): CurriculumTemplate {
            $newTemplate = CurriculumTemplate::create([
                'academic_year_id' => $targetAcademicYearId,
                'name' => $name,
                'is_active' => $sourceTemplate->is_active,
            ]);

            $sourceTemplate->load([
                'chapters' => function ($q): void {
                    $q->orderBy('display_order')->orderBy('id');
                },
                'chapters.subchapters' => function ($q): void {
                    $q->orderBy('display_order')->orderBy('id');
                },
                'chapters.subchapters.assessments' => function ($q): void {
                    $q->orderBy('display_order')->orderBy('id');
                },
            ]);

            $chaptersCopied = 0;
            $assessmentsCopied = 0;

            foreach ($sourceTemplate->chapters as $chapter) {
                $newChapter = Chapter::create([
                    'curriculum_template_id' => $newTemplate->id,
                    'title' => $chapter->title,
                    'display_order' => $chapter->display_order,
                ]);
                $chaptersCopied++;

                foreach ($chapter->subchapters as $subchapter) {
                    $newSubchapter = Subchapter::create([
                        'chapter_id' => $newChapter->id,
                        'title' => $subchapter->title,
                        'display_order' => $subchapter->display_order,
                    ]);

                    foreach ($subchapter->assessments as $assessment) {
                        Assessment::create([
                            'subchapter_id' => $newSubchapter->id,
                            'title' => $assessment->title,
                            'description' => $assessment->description,
                            'weight' => $assessment->weight,
                            'display_order' => $assessment->display_order,
                            'is_active' => $assessment->is_active,
                        ]);
                        $assessmentsCopied++;
                    }
                }
            }

            $this->auditLogger->record(
                entity: 'template',
                entityId: $newTemplate->id,
                action: 'COPY',
                summary: "Salin struktur kurikulum {$sourceTemplate->name} ke tahun ajaran target",
                meta: [
                    'source_template_id' => $sourceTemplate->id,
                    'target_academic_year_id' => $targetAcademicYearId,
                    'chapters_copied' => $chaptersCopied,
                    'assessments_copied' => $assessmentsCopied,
                ],
                userId: $userId
            );

            return $newTemplate;
        });
    }

    public function createChapter(int $templateId, string $title, ?int $order = null, ?int $userId = null): Chapter
    {
        $this->validateChapterTitle($templateId, $title);

        $displayOrder = $order ?? ((Chapter::query()->where('curriculum_template_id', $templateId)->max('display_order') ?? 0) + 1);

        return DB::transaction(function () use ($templateId, $title, $displayOrder, $userId): Chapter {
            $chapter = Chapter::create([
                'curriculum_template_id' => $templateId,
                'title' => $title,
                'display_order' => $displayOrder,
            ]);

            $this->auditLogger->record(
                entity: 'chapter',
                entityId: $chapter->id,
                action: 'CREATE',
                summary: "Tambah bab {$chapter->title}",
                meta: ['template_id' => $templateId, 'title' => $title, 'display_order' => $displayOrder],
                userId: $userId
            );

            return $chapter;
        });
    }

    public function updateChapter(Chapter $chapter, string $title, ?int $userId = null): Chapter
    {
        $this->validateChapterTitle($chapter->curriculum_template_id, $title, $chapter->id);

        return DB::transaction(function () use ($chapter, $title, $userId): Chapter {
            $chapter->update(['title' => $title]);

            $this->auditLogger->record(
                entity: 'chapter',
                entityId: $chapter->id,
                action: 'UPDATE',
                summary: "Ubah bab {$chapter->title}",
                meta: ['title' => $title],
                userId: $userId
            );

            return $chapter;
        });
    }

    public function deleteChapter(Chapter $chapter, ?int $userId = null): void
    {
        $hasScores = Score::query()
            ->whereIn('assessment_id', function ($query) use ($chapter): void {
                $query->select('id')
                    ->from('assessments')
                    ->whereIn('subchapter_id', function ($q2) use ($chapter): void {
                        $q2->select('id')
                            ->from('subchapters')
                            ->where('chapter_id', $chapter->id);
                    });
            })
            ->exists();

        if ($hasScores) {
            throw new CurriculumException('Bab tidak dapat dihapus karena materi di dalamnya sudah memiliki nilai siswa.');
        }

        DB::transaction(function () use ($chapter, $userId): void {
            $title = $chapter->title;
            $id = $chapter->id;

            $chapter->delete();

            $this->auditLogger->record(
                entity: 'chapter',
                entityId: $id,
                action: 'DELETE',
                summary: "Hapus bab {$title}",
                userId: $userId
            );
        });
    }

    public function createSubchapter(int $chapterId, string $title, ?int $order = null, ?int $userId = null): Subchapter
    {
        $this->validateSubchapterTitle($chapterId, $title);

        $displayOrder = $order ?? ((Subchapter::query()->where('chapter_id', $chapterId)->max('display_order') ?? 0) + 1);

        return DB::transaction(function () use ($chapterId, $title, $displayOrder, $userId): Subchapter {
            $subchapter = Subchapter::create([
                'chapter_id' => $chapterId,
                'title' => $title,
                'display_order' => $displayOrder,
            ]);

            $this->auditLogger->record(
                entity: 'subchapter',
                entityId: $subchapter->id,
                action: 'CREATE',
                summary: "Tambah subbab {$subchapter->title}",
                meta: ['chapter_id' => $chapterId, 'title' => $title, 'display_order' => $displayOrder],
                userId: $userId
            );

            return $subchapter;
        });
    }

    public function updateSubchapter(Subchapter $subchapter, string $title, ?int $userId = null): Subchapter
    {
        $this->validateSubchapterTitle($subchapter->chapter_id, $title, $subchapter->id);

        return DB::transaction(function () use ($subchapter, $title, $userId): Subchapter {
            $subchapter->update(['title' => $title]);

            $this->auditLogger->record(
                entity: 'subchapter',
                entityId: $subchapter->id,
                action: 'UPDATE',
                summary: "Ubah subbab {$subchapter->title}",
                meta: ['title' => $title],
                userId: $userId
            );

            return $subchapter;
        });
    }

    public function deleteSubchapter(Subchapter $subchapter, ?int $userId = null): void
    {
        $hasScores = Score::query()
            ->whereIn('assessment_id', function ($query) use ($subchapter): void {
                $query->select('id')
                    ->from('assessments')
                    ->where('subchapter_id', $subchapter->id);
            })
            ->exists();

        if ($hasScores) {
            throw new CurriculumException('Subbab tidak dapat dihapus karena materi di dalamnya sudah memiliki nilai siswa.');
        }

        DB::transaction(function () use ($subchapter, $userId): void {
            $title = $subchapter->title;
            $id = $subchapter->id;

            $subchapter->delete();

            $this->auditLogger->record(
                entity: 'subchapter',
                entityId: $id,
                action: 'DELETE',
                summary: "Hapus subbab {$title}",
                userId: $userId
            );
        });
    }

    public function createAssessment(
        int $subchapterId,
        string $title,
        float $weight = 1.0,
        ?string $description = null,
        bool $isActive = true,
        ?int $order = null,
        ?int $userId = null
    ): Assessment {
        $this->validateWeight($weight);
        $this->validateAssessmentTitle($subchapterId, $title);

        $displayOrder = $order ?? ((Assessment::query()->where('subchapter_id', $subchapterId)->max('display_order') ?? 0) + 1);

        return DB::transaction(function () use ($subchapterId, $title, $weight, $description, $isActive, $displayOrder, $userId): Assessment {
            $assessment = Assessment::create([
                'subchapter_id' => $subchapterId,
                'title' => $title,
                'description' => $description,
                'weight' => $weight,
                'display_order' => $displayOrder,
                'is_active' => $isActive,
            ]);

            $this->auditLogger->record(
                entity: 'assessment',
                entityId: $assessment->id,
                action: 'CREATE',
                summary: "Tambah materi {$assessment->title}",
                meta: [
                    'subchapter_id' => $subchapterId,
                    'title' => $title,
                    'weight' => $weight,
                    'display_order' => $displayOrder,
                ],
                userId: $userId
            );

            return $assessment;
        });
    }

    public function updateAssessment(
        Assessment $assessment,
        string $title,
        float $weight = 1.0,
        ?string $description = null,
        bool $isActive = true,
        ?int $userId = null
    ): Assessment {
        $this->validateWeight($weight);
        $this->validateAssessmentTitle($assessment->subchapter_id, $title, $assessment->id);

        return DB::transaction(function () use ($assessment, $title, $weight, $description, $isActive, $userId): Assessment {
            $assessment->update([
                'title' => $title,
                'description' => $description,
                'weight' => $weight,
                'is_active' => $isActive,
            ]);

            $this->auditLogger->record(
                entity: 'assessment',
                entityId: $assessment->id,
                action: 'UPDATE',
                summary: "Ubah materi {$assessment->title}",
                meta: [
                    'title' => $title,
                    'weight' => $weight,
                    'is_active' => $isActive,
                ],
                userId: $userId
            );

            return $assessment;
        });
    }

    public function toggleAssessmentActive(Assessment $assessment, ?int $userId = null): Assessment
    {
        return DB::transaction(function () use ($assessment, $userId): Assessment {
            $newStatus = ! $assessment->is_active;
            $assessment->update(['is_active' => $newStatus]);

            $this->auditLogger->record(
                entity: 'assessment',
                entityId: $assessment->id,
                action: $newStatus ? 'UPDATE' : 'DEACTIVATE',
                summary: ($newStatus ? 'Aktifkan' : 'Nonaktifkan')." materi {$assessment->title}",
                meta: ['is_active' => $newStatus],
                userId: $userId
            );

            return $assessment;
        });
    }

    public function deleteAssessment(Assessment $assessment, ?int $userId = null): void
    {
        if ($assessment->scores()->exists()) {
            throw new CurriculumException('Materi tidak dapat dihapus karena sudah memiliki data nilai siswa.');
        }

        DB::transaction(function () use ($assessment, $userId): void {
            $title = $assessment->title;
            $id = $assessment->id;

            $assessment->delete();

            $this->auditLogger->record(
                entity: 'assessment',
                entityId: $id,
                action: 'DELETE',
                summary: "Hapus materi {$title}",
                userId: $userId
            );
        });
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function moveOrder(string $modelClass, int $id, string $direction, ?int $userId = null): void
    {
        /** @var (Chapter|Subchapter|Assessment)&Model $item */
        $item = $modelClass::findOrFail($id);

        $parentColumn = match ($modelClass) {
            Chapter::class => 'curriculum_template_id',
            Subchapter::class => 'chapter_id',
            Assessment::class => 'subchapter_id',
            default => throw new CurriculumException("Kelas {$modelClass} tidak mendukung pemindahan urutan."),
        };

        $parentId = $item->getAttribute($parentColumn);

        DB::transaction(function () use ($modelClass, $parentColumn, $parentId, $item, $direction, $userId): void {
            /** @var Collection<int, (Chapter|Subchapter|Assessment)&Model> $siblings */
            $siblings = $modelClass::query()
                ->where($parentColumn, $parentId)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();

            // Renumber stabil 1..N
            $index = 1;
            foreach ($siblings as $sibling) {
                if ($sibling->display_order !== $index) {
                    $sibling->display_order = $index;
                    $sibling->saveQuietly();
                }
                $index++;
            }

            $currentIndex = $siblings->search(fn ($s) => $s->id === $item->id);
            if ($currentIndex === false) {
                return;
            }

            $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

            if ($targetIndex < 0 || $targetIndex >= $siblings->count()) {
                return; // Batas atas / bawah
            }

            /** @var (Chapter|Subchapter|Assessment)&Model $targetSibling */
            $targetSibling = $siblings->get($targetIndex);

            $tempOrder = $item->display_order;
            $item->display_order = $targetSibling->display_order;
            $targetSibling->display_order = $tempOrder;

            $item->saveQuietly();
            $targetSibling->saveQuietly();

            $this->auditLogger->record(
                entity: 'curriculum',
                entityId: $item->id,
                action: 'MOVE',
                summary: "Pindah urutan {$direction} pada id {$item->id}",
                meta: [
                    'direction' => $direction,
                    'new_order' => $item->display_order,
                ],
                userId: $userId
            );
        });
    }

    private function validateWeight(float $weight): void
    {
        $min = (float) config('pib.weight_min', 0.1);
        $max = (float) config('pib.weight_max', 100);

        if ($weight < $min || $weight > $max) {
            throw new CurriculumException("Bobot materi harus bernilai antara {$min} sampai {$max}.");
        }
    }

    private function validateTemplateName(int $academicYearId, string $name, ?int $ignoreId = null): void
    {
        $exists = CurriculumTemplate::query()
            ->where('academic_year_id', $academicYearId)
            ->where('name', $name)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw new CurriculumException("Nama template kurikulum '{$name}' sudah ada pada tahun ajaran ini.");
        }
    }

    private function validateChapterTitle(int $templateId, string $title, ?int $ignoreId = null): void
    {
        $exists = Chapter::query()
            ->where('curriculum_template_id', $templateId)
            ->where('title', $title)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw new CurriculumException("Judul bab '{$title}' sudah ada pada template ini.");
        }
    }

    private function validateSubchapterTitle(int $chapterId, string $title, ?int $ignoreId = null): void
    {
        $exists = Subchapter::query()
            ->where('chapter_id', $chapterId)
            ->where('title', $title)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw new CurriculumException("Judul subbab '{$title}' sudah ada pada bab ini.");
        }
    }

    private function validateAssessmentTitle(int $subchapterId, string $title, ?int $ignoreId = null): void
    {
        $exists = Assessment::query()
            ->where('subchapter_id', $subchapterId)
            ->where('title', $title)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw new CurriculumException("Judul materi '{$title}' sudah ada pada subbab ini.");
        }
    }
}
