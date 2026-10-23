<?php

use App\Livewire\Materi;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\Score;
use App\Models\Subchapter;
use App\Models\User;
use Livewire\Livewire;

test('tamu diarahkan ke login saat mengakses halaman materi', function () {
    $this->get('/materi')->assertRedirect('/login');
});

test('pengguna dapat membuka halaman materi dan melihat struktur kurikulum', function () {
    $user = User::factory()->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $template = CurriculumTemplate::factory()->create(['academic_year_id' => $year->id, 'name' => 'Kurikulum SMP']);
    $chapter = Chapter::factory()->create(['curriculum_template_id' => $template->id, 'title' => 'Bab Shalat']);
    $subchapter = Subchapter::factory()->create(['chapter_id' => $chapter->id, 'title' => 'Shalat Wajib']);
    Assessment::factory()->create(['subchapter_id' => $subchapter->id, 'title' => 'Shalat Subuh', 'weight' => 2.0]);

    $this->actingAs($user)->get('/materi')
        ->assertOk()
        ->assertSee('Kurikulum SMP')
        ->assertSee('Bab Shalat')
        ->assertSee('Shalat Wajib')
        ->assertSee('Shalat Subuh')
        ->assertSee('2x'); // bobot 2.0 ditampilkan sebagai 2x
});

test('pengguna dapat menambah bab, subbab, dan materi lewat ui', function () {
    $user = User::factory()->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $template = CurriculumTemplate::factory()->create(['academic_year_id' => $year->id]);

    $component = Livewire::actingAs($user)
        ->test(Materi::class)
        ->set('selected_template_id', $template->id)
        ->set('new_chapter_title', 'Bab Zakat')
        ->call('createChapter')
        ->assertHasNoErrors();

    $chapter = Chapter::query()->where('title', 'Bab Zakat')->first();
    expect($chapter)->not->toBeNull();

    $component->set('parent_chapter_id', $chapter->id)
        ->set('new_subchapter_title', 'Zakat Fitrah')
        ->call('createSubchapter')
        ->assertHasNoErrors();

    $subchapter = Subchapter::query()->where('title', 'Zakat Fitrah')->first();
    expect($subchapter)->not->toBeNull();

    $component->set('parent_subchapter_id', $subchapter->id)
        ->set('new_assessment_title', 'Besaran Beras')
        ->set('new_assessment_weight', 1.5)
        ->call('createAssessment')
        ->assertHasNoErrors();

    expect(Assessment::query()->where('title', 'Besaran Beras')->exists())->toBeTrue();
});

test('pengguna dapat menyalin kurikulum ke tahun ajaran lain lewat ui', function () {
    $user = User::factory()->create();
    $sourceYear = AcademicYear::factory()->create(['name' => '2024/2025', 'is_active' => true]);
    $targetYear = AcademicYear::factory()->create(['name' => '2025/2026', 'is_active' => false]);

    $sourceTemplate = CurriculumTemplate::factory()->create(['academic_year_id' => $sourceYear->id, 'name' => 'Template PAI']);
    $chapter = Chapter::factory()->create(['curriculum_template_id' => $sourceTemplate->id, 'title' => 'Bab Iman']);
    $subchapter = Subchapter::factory()->create(['chapter_id' => $chapter->id, 'title' => 'Rukun Iman']);
    Assessment::factory()->create(['subchapter_id' => $subchapter->id, 'title' => 'Iman kepada Allah']);

    Livewire::actingAs($user)
        ->test(Materi::class)
        ->set('selected_template_id', $sourceTemplate->id)
        ->set('copy_target_academic_year_id', $targetYear->id)
        ->call('executeCopy')
        ->assertHasNoErrors();

    $copiedTemplate = CurriculumTemplate::query()
        ->where('academic_year_id', $targetYear->id)
        ->where('name', 'Template PAI')
        ->first();

    expect($copiedTemplate)->not->toBeNull();
    expect($copiedTemplate->chapters)->toHaveCount(1);
});

test('hapus materi yang bernilai menampilkan pesan galat di ui', function () {
    $user = User::factory()->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $template = CurriculumTemplate::factory()->create(['academic_year_id' => $year->id]);
    $chapter = Chapter::factory()->create(['curriculum_template_id' => $template->id]);
    $subchapter = Subchapter::factory()->create(['chapter_id' => $chapter->id]);
    $assessment = Assessment::factory()->create(['subchapter_id' => $subchapter->id]);
    Score::factory()->create(['assessment_id' => $assessment->id]);

    Livewire::actingAs($user)
        ->test(Materi::class)
        ->set('selected_template_id', $template->id)
        ->call('deleteAssessment', $assessment->id)
        ->assertSee('tidak dapat dihapus karena sudah memiliki data nilai');

    expect(Assessment::query()->whereKey($assessment->id)->exists())->toBeTrue();
});
