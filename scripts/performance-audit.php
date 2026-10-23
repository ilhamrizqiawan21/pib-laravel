<?php

use App\DTO\RecapFilter;
use App\Http\Controllers\ReportController;
use App\Http\Requests\ReportFilterRequest;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subchapter;
use App\Services\ClassProgressService;
use App\Services\ExcelReportService;
use App\Services\RecapService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$databasePath = DB::connection()->getDatabaseName();
$temporaryDirectory = realpath(sys_get_temp_dir());
$databaseDirectory = realpath(dirname($databasePath));

if (
    DB::connection()->getDriverName() !== 'sqlite'
    || $databasePath === ':memory:'
    || $temporaryDirectory === false
    || $databaseDirectory !== $temporaryDirectory
    || ! Schema::hasTable('students')
    || Student::query()->exists()
) {
    throw new RuntimeException('Gunakan SQLite kosong yang lokasinya langsung di direktori sementara sistem.');
}

$year = AcademicYear::factory()->active()->create([
    'name' => '2026/2027',
    'semester' => 'Ganjil',
]);

$classes = [];
for ($classNumber = 1; $classNumber <= 5; $classNumber++) {
    $classes[] = SchoolClass::factory()->create([
        'academic_year_id' => $year->id,
        'name' => 'Kelas '.str_pad((string) $classNumber, 2, '0', STR_PAD_LEFT),
    ]);
}

$template = CurriculumTemplate::factory()->create([
    'academic_year_id' => $year->id,
    'name' => 'Template Audit Performa',
]);

$assessments = [];
for ($chapterNumber = 1; $chapterNumber <= 10; $chapterNumber++) {
    $chapter = Chapter::factory()->create([
        'curriculum_template_id' => $template->id,
        'title' => 'Bab '.str_pad((string) $chapterNumber, 2, '0', STR_PAD_LEFT),
        'display_order' => $chapterNumber,
    ]);
    $subchapter = Subchapter::factory()->create([
        'chapter_id' => $chapter->id,
        'title' => 'Subbab '.$chapterNumber,
        'display_order' => 1,
    ]);

    for ($assessmentNumber = 1; $assessmentNumber <= 10; $assessmentNumber++) {
        $assessments[] = Assessment::factory()->create([
            'subchapter_id' => $subchapter->id,
            'title' => "Materi {$chapterNumber}-{$assessmentNumber}",
            'weight' => 1 + ($assessmentNumber % 3),
            'display_order' => $assessmentNumber,
        ]);
    }
}

$students = [];
foreach ($classes as $classIndex => $class) {
    for ($studentNumber = 1; $studentNumber <= 40; $studentNumber++) {
        $students[] = Student::factory()->create([
            'school_class_id' => $class->id,
            'nis' => null,
            'name' => sprintf('Siswa %02d-%02d', $classIndex + 1, $studentNumber),
        ]);
    }
}

$timestamp = now();
$scoreRows = [];
foreach ($students as $studentIndex => $student) {
    foreach ($assessments as $assessmentIndex => $assessment) {
        $mistakes = ($studentIndex + $assessmentIndex) % 11;
        $scoreRows[] = [
            'student_id' => $student->id,
            'assessment_id' => $assessment->id,
            'mistakes' => $mistakes,
            'score' => 90 - $mistakes,
            'assessed_at' => $timestamp,
            'assessor_id' => null,
            'initials' => 'AU',
            'note' => '',
            'version' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }
}

foreach (array_chunk($scoreRows, 500) as $chunk) {
    DB::table('scores')->insert($chunk);
}
$scoreCount = count($scoreRows);
unset($scoreRows);
gc_collect_cycles();

$filter = new RecapFilter(academicYearId: $year->id, classId: $classes[0]->id);
$measure = static function (callable $callback): array {
    memory_reset_peak_usage();
    $start = hrtime(true);
    $result = $callback();

    return [
        'result' => $result,
        'milliseconds' => (hrtime(true) - $start) / 1_000_000,
        'peak_memory_bytes' => memory_get_peak_usage(true),
    ];
};

$recap = app(RecapService::class);
$recapRuns = [];
for ($run = 0; $run < 3; $run++) {
    $recapRuns[] = $measure(fn () => $recap->getRecap($filter));
}

$excelRuns = [];
for ($run = 0; $run < 3; $run++) {
    $excelRuns[] = $measure(function () use ($filter): array {
        $generationStart = hrtime(true);
        $spreadsheet = app(ExcelReportService::class)->generate($filter);
        $generationMilliseconds = (hrtime(true) - $generationStart) / 1_000_000;
        $path = tempnam(sys_get_temp_dir(), 'pib-excel-audit-');
        if ($path === false) {
            throw new RuntimeException('Tidak dapat membuat berkas Excel sementara.');
        }

        try {
            $writeStart = hrtime(true);
            (new Xlsx($spreadsheet))->save($path);
            $writeMilliseconds = (hrtime(true) - $writeStart) / 1_000_000;

            return [
                'bytes' => filesize($path) ?: 0,
                'sheets' => $spreadsheet->getSheetCount(),
                'generation_milliseconds' => $generationMilliseconds,
                'write_milliseconds' => $writeMilliseconds,
            ];
        } finally {
            unlink($path);
            $spreadsheet->disconnectWorksheets();
        }
    });
}

$request = ReportFilterRequest::create('/laporan/pdf?academic_year_id='.$year->id, 'GET');
$request->setContainer($app);
$request->setRedirector($app->make('redirect'));
$request->validateResolved();
$pdfController = app(ReportController::class);
$pdfRuns = [];
for ($run = 0; $run < 3; $run++) {
    $pdfRuns[] = $measure(function () use ($pdfController, $request): array {
        $response = $pdfController->pdf($request);

        return ['bytes' => strlen((string) $response->getContent())];
    });
}

$payloadClass = $classes[0];
DB::flushQueryLog();
DB::enableQueryLog();
$payload = $measure(fn () => app(ClassProgressService::class)->getProgress($payloadClass));
$payloadQueries = count(DB::getQueryLog());
DB::disableQueryLog();
$payloadJson = json_encode($payload['result'], JSON_THROW_ON_ERROR);

$summarize = static function (array $runs): array {
    $times = array_column($runs, 'milliseconds');
    sort($times);

    return [
        'milliseconds_median' => round($times[1], 2),
        'milliseconds_min' => round($times[0], 2),
        'milliseconds_max' => round($times[2], 2),
        'peak_memory_bytes_max' => max(array_column($runs, 'peak_memory_bytes')),
    ];
};
$median = static function (array $values): float {
    sort($values);

    return $values[intdiv(count($values), 2)];
};

$report = [
    'php' => PHP_VERSION,
    'database' => 'SQLite '.SQLite3::version()['versionString'],
    'fixture' => [
        'classes' => count($classes),
        'students' => count($students),
        'students_per_class' => 40,
        'assessments' => count($assessments),
        'scores' => $scoreCount,
    ],
    'recap' => $summarize($recapRuns),
    'excel' => $summarize($excelRuns) + [
        'bytes' => $excelRuns[0]['result']['bytes'],
        'sheets' => $excelRuns[0]['result']['sheets'],
        'generation_milliseconds_median' => round($median(array_column(array_column($excelRuns, 'result'), 'generation_milliseconds')), 2),
        'write_milliseconds_median' => round($median(array_column(array_column($excelRuns, 'result'), 'write_milliseconds')), 2),
    ],
    'pdf' => $summarize($pdfRuns) + ['bytes' => $pdfRuns[0]['result']['bytes']],
    'setoran_payload' => [
        'milliseconds' => round($payload['milliseconds'], 2),
        'queries' => $payloadQueries,
        'bytes_json' => strlen($payloadJson),
        'students' => count($payload['result']['students']),
        'assessments' => count($payload['result']['materials']),
        'scores' => count($payload['result']['scores']),
    ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
