<?php

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subchapter;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true, 'role' => 'teacher']);

    $this->year = AcademicYear::factory()->create(['name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
    $this->class = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => 'VII A']);
    $this->student = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Ahmad', 'gender' => 'L', 'nis' => '1001']);

    $this->template = CurriculumTemplate::factory()->create(['academic_year_id' => $this->year->id]);
    $this->chapter = Chapter::factory()->create(['curriculum_template_id' => $this->template->id, 'title' => 'Wudhu', 'display_order' => 1]);
    $this->sub = Subchapter::factory()->create(['chapter_id' => $this->chapter->id, 'title' => 'Praktik']);
    $this->assessment = Assessment::factory()->create(['subchapter_id' => $this->sub->id, 'title' => 'Niat', 'weight' => 1.0]);

    Score::factory()->create([
        'student_id' => $this->student->id,
        'assessment_id' => $this->assessment->id,
        'score' => 85,
        'mistakes' => 5,
        'note' => 'Cukup lancar',
    ]);
});

test('tamu tidak dapat mengunduh laporan excel', function () {
    $this->get('/laporan/excel')
        ->assertRedirect('/login');
});

test('filter tidak valid mengembalikan 422 dengan pesan jelas', function () {
    $this->actingAs($this->user)
        ->getJson('/laporan/excel?academic_year_id=-999')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['academic_year_id']);

    $this->actingAs($this->user)
        ->getJson('/laporan/excel?class_id=bukan-angka')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['class_id']);
});

test('guru dapat mengunduh excel dengan struktur sheet dan data yang sesuai', function () {
    $secondStudent = Student::factory()->create([
        'school_class_id' => $this->class->id,
        'name' => 'Zainab',
    ]);
    Score::factory()->create([
        'student_id' => $secondStudent->id,
        'assessment_id' => $this->assessment->id,
        'score' => 80,
        'mistakes' => 10,
    ]);

    $response = $this->actingAs($this->user)
        ->get('/laporan/excel?academic_year_id='.$this->year->id.'&class_id='.$this->class->id);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->assertHeader('Content-Disposition', 'attachment; filename=laporan-pib.xlsx');

    // Simpan ke temporary file dan baca dengan PhpSpreadsheet untuk verifikasi isi
    $tempFile = tempnam(sys_get_temp_dir(), 'pib_test_');
    file_put_contents($tempFile, $response->streamedContent());

    $spreadsheet = IOFactory::load($tempFile);
    $sheetNames = $spreadsheet->getSheetNames();

    expect($sheetNames)->toContain('Ringkasan', 'Laporan per Materi', 'Rekap Siswa', 'Rekap Kelas', 'Detail Nilai');

    // Cek sheet Ringkasan
    $summary = $spreadsheet->getSheetByName('Ringkasan');
    expect($summary)->not->toBeNull();
    expect($summary->getCell('A1')->getValue())->toContain('Ringkasan Laporan Penilaian');

    // Cek sheet Detail Nilai
    $detail = $spreadsheet->getSheetByName('Detail Nilai');
    expect($detail)->not->toBeNull();
    expect($detail->getCell('B5')->getValue())->toBe('Ahmad');
    expect($detail->getCell('I5')->getValue())->toBe(85);
    expect($detail->getCell('B6')->getValue())->toBe('Zainab');
    expect($detail->getConditionalStyles('A6'))->not->toBeEmpty();

    $materialSheet = $spreadsheet->getSheetByName('Laporan per Materi');
    expect($materialSheet)->not->toBeNull();
    expect($materialSheet->getConditionalStyles('A9'))->not->toBeEmpty();

    // Cek sheet Rekap Siswa
    $recapSheet = $spreadsheet->getSheetByName('Rekap Siswa');
    expect($recapSheet)->not->toBeNull();
    expect($recapSheet->getCell('A5')->getValue())->toBe('Ahmad');
    expect($recapSheet->getCell('E5')->getValue())->toBe(85.0);

    unlink($tempFile);
});
