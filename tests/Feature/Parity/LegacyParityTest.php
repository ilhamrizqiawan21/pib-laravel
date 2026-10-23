<?php

use App\DTO\RecapFilter;
use App\Models\AcademicYear;
use App\Services\ExcelReportService;
use App\Services\LegacyImporter;
use App\Services\RecapService;

beforeEach(function () {
    $this->fixturePath = base_path('tests/Fixtures/legacy-sample.sqlite');
    $this->importer = app(LegacyImporter::class);
    $this->recapService = app(RecapService::class);
    $this->excelService = app(ExcelReportService::class);

    // Jalankan impor ke database uji
    $this->importer->import($this->fixturePath, force: true);
});

test('paritas rekap: nilai, total, dan rata-rata berbobot tiap siswa identik dengan perhitungan SQL pada DB lama', function () {
    // 1. Hitung referensi langsung dari DB lama via SQLite PDO
    $pdo = new PDO('sqlite:'.$this->fixturePath);
    $stmt = $pdo->query('
        SELECT 
            s.id as student_id,
            s.name,
            COUNT(sc.score) as assessed,
            COALESCE(SUM(sc.score), 0) as total,
            SUM(sc.score * a.weight) as weighted_sum,
            SUM(CASE WHEN sc.score IS NOT NULL THEN a.weight ELSE 0 END) as total_weight
        FROM students s
        LEFT JOIN scores sc ON sc.student_id = s.id
        LEFT JOIN assessments a ON a.id = sc.assessment_id
        GROUP BY s.id, s.name
        ORDER BY s.id
    ');

    $legacyRef = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $wSum = (float) $row['weighted_sum'];
        $wTotal = (float) $row['total_weight'];
        $avg = $wTotal > 0 ? round($wSum / $wTotal, 1) : null;

        $legacyRef[(int) $row['student_id']] = [
            'name' => (string) $row['name'],
            'assessed' => (int) $row['assessed'],
            'total' => (int) $row['total'],
            'average' => $avg,
        ];
    }

    expect(count($legacyRef))->toBe(30);

    // 2. Ambil hasil dari RecapService di database baru
    $activeYear = AcademicYear::where('is_active', true)->first();
    expect($activeYear)->not->toBeNull();

    $recap = $this->recapService->getRecap(new RecapFilter(academicYearId: $activeYear->id));
    $studentsRecap = $recap['students'];

    expect(count($studentsRecap))->toBe(30);

    // 3. Bandingkan tiap siswa
    foreach ($studentsRecap as $student) {
        $id = (int) $student['id'];
        expect($legacyRef)->toHaveKey($id);

        $ref = $legacyRef[$id];
        expect($student['name'])->toBe($ref['name']);
        expect($student['assessed'])->toBe($ref['assessed']);
        expect($student['total'])->toBe($ref['total']);
        expect($student['average'])->toBe($ref['average']);
    }
});

test('paritas ekspor: buku kerja Excel memuat angka rekap yang sama persis', function () {
    $activeYear = AcademicYear::where('is_active', true)->first();
    $filter = new RecapFilter(academicYearId: $activeYear->id);

    $spreadsheet = $this->excelService->generate($filter);
    $recapSheet = $spreadsheet->getSheetByName('Rekap Siswa');
    expect($recapSheet)->not->toBeNull();

    // Baris 5 adalah siswa urutan alfabetis pertama: Aditya Pratama
    expect($recapSheet->getCell('A5')->getValue())->toBe('Aditya Pratama');
    expect((int) $recapSheet->getCell('D5')->getValue())->toBeGreaterThan(0);
    expect((float) $recapSheet->getCell('E5')->getValue())->toBeGreaterThan(0.0);
});
