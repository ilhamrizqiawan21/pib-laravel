<?php

namespace App\Http\Controllers;

use App\DTO\RecapFilter;
use App\Http\Requests\ReportFilterRequest;
use App\Models\Student;
use App\Repositories\SettingsRepository;
use App\Services\ExcelReportService;
use App\Services\RecapService;
use App\Services\ReportGrouper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ExcelReportService $excelReportService,
        private readonly RecapService $recapService,
        private readonly ReportGrouper $reportGrouper,
        private readonly SettingsRepository $settingsRepository,
    ) {}

    public function excel(ReportFilterRequest $request): StreamedResponse
    {
        $filter = $request->toRecapFilter();
        $spreadsheet = $this->excelReportService->generate($filter);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'laporan-pib.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function pdf(ReportFilterRequest $request): Response
    {
        $filter = $request->toRecapFilter();
        $rows = $this->recapService->getRows($filter);
        $groups = $this->reportGrouper->groupAverageRows($rows);

        $averagedRows = [];
        foreach ($groups as $group) {
            foreach ($group['classes'] as $cls) {
                foreach ($cls['rows'] as $r) {
                    $averagedRows[] = $r;
                }
            }
        }
        $overall = $this->reportGrouper->averageStats($averagedRows);

        $schoolName = $this->settingsRepository->schoolName();
        if ($schoolName === '') {
            $schoolName = 'PIB Penilaian';
        }
        $schoolAddress = $this->settingsRepository->schoolAddress();
        $scope = $this->reportGrouper->formatScope($filter, $schoolName);
        $assessmentsCount = count(array_filter($rows, fn ($r) => $r['assessment_id'] !== null));

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadView('pdf.class-report', [
            'groups' => $groups,
            'grouper' => $this->reportGrouper,
            'overall' => $overall,
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'scope' => $scope,
            'assessmentsCount' => $assessmentsCount,
        ]);

        $pdf->setPaper('a4', 'portrait');

        /** @var string $output */
        $output = $pdf->output();

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="laporan-pib.pdf"',
        ]);
    }

    public function studentPdf(Request $request, Student $student): Response
    {
        $student->loadMissing(['schoolClass.academicYear']);

        $rows = $this->recapService->getRows(new RecapFilter, $student->id);
        $stats = $this->recapService->calculateScoreStats($rows);

        $schoolName = $this->settingsRepository->schoolName();
        if ($schoolName === '') {
            $schoolName = 'PIB Penilaian';
        }
        $schoolAddress = $this->settingsRepository->schoolAddress();
        $teacherName = $this->settingsRepository->teacherName();

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadView('pdf.student-report', [
            'student' => $student,
            'rows' => $rows,
            'stats' => $stats,
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'teacherName' => $teacherName,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $slug = Str::slug($student->name) ?: 'siswa';
        $filename = "rapor-{$slug}.pdf";

        /** @var string $output */
        $output = $pdf->output();

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
