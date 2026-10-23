<?php

namespace App\Services;

use App\DTO\RecapFilter;
use App\Repositories\SettingsRepository;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelReportService
{
    private const COLOR_GREEN = '176B57';

    private const COLOR_SOFT_GREEN = 'EAF5F1';

    private const COLOR_STRIPE = 'F7FAF8';

    private const COLOR_LINE = 'DCE3DF';

    private const COLOR_MUTED = '657186';

    private const COLOR_INK = '17202D';

    public function __construct(
        private readonly RecapService $recapService,
        private readonly ReportGrouper $reportGrouper,
        private readonly SettingsRepository $settingsRepository,
    ) {}

    public function generate(RecapFilter $filter): Spreadsheet
    {
        $rows = $this->recapService->getRows($filter);
        $recap = $this->recapService->getRecap($filter);
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

        $school = $this->settingsRepository->schoolName();
        if ($school === '') {
            $school = 'PIB Penilaian';
        }
        $scope = $this->reportGrouper->formatScope($filter, $school);
        $subtitle = $school.' | '.$scope.' | Dibuat '.now()->translatedFormat('d F Y, H:i');

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('PIB Penilaian')
            ->setTitle('Laporan Penilaian Praktik Ibadah');

        // Sheet 1: Ringkasan
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Ringkasan');
        $this->buildSummarySheet($summarySheet, $rows, $recap, $overall, $subtitle);

        // Sheet 2: Laporan per Materi
        $reportSheet = $spreadsheet->createSheet();
        $reportSheet->setTitle('Laporan per Materi');
        $this->buildMaterialSheet($reportSheet, $rows, $groups, $overall, $subtitle);

        // Sheet 3: Rekap Siswa
        $studentSheet = $spreadsheet->createSheet();
        $studentSheet->setTitle('Rekap Siswa');
        $this->buildStudentRecapSheet($studentSheet, $recap['students'], $subtitle);

        // Sheet 4: Rekap Kelas
        $classSheet = $spreadsheet->createSheet();
        $classSheet->setTitle('Rekap Kelas');
        $this->buildClassRecapSheet($classSheet, $recap['classes'], $subtitle);

        // Sheet 5: Detail Nilai
        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('Detail Nilai');
        $this->buildDetailSheet($detailSheet, $rows, $subtitle);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function applyBaseHeader(Worksheet $sheet, string $title, string $subtitle, int $columns): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($columns);

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::COLOR_GREEN);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', $subtitle);
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB(self::COLOR_MUTED);
        $sheet->getStyle('A2')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(24);

        $sheet->freezePane('A5');
    }

    private function styleTableHeader(Worksheet $sheet, int $rowNumber, int $columns): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($columns);
        $range = "A{$rowNumber}:{$lastCol}{$rowNumber}";

        $sheet->getRowDimension($rowNumber)->setRowHeight(26);
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_GREEN);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_LINE);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $recap
     * @param  array{scored: int, total: float, average: float|null}  $overall
     */
    private function buildSummarySheet(Worksheet $sheet, array $rows, array $recap, array $overall, string $subtitle): void
    {
        $this->applyBaseHeader($sheet, 'Ringkasan Laporan Penilaian', $subtitle, 4);

        $sheet->setCellValue('A4', 'Indikator');
        $sheet->setCellValue('B4', 'Hasil');
        $sheet->setCellValue('C4', 'Keterangan');
        $sheet->setCellValue('D4', '');
        $this->styleTableHeader($sheet, 4, 3);

        $assessmentsCount = count(array_filter($rows, fn ($r) => $r['assessment_id'] !== null));
        $chaptersCount = count(array_unique(array_filter(array_column($rows, 'chapter_id'))));
        $studentsList = $recap['students'] ?? [];
        $classesList = $recap['classes'] ?? [];

        $items = [
            ['Data penilaian', $assessmentsCount, 'Data mentah dari subbab/materi'],
            ['Bab/Materi', $chaptersCount, 'Bab dalam cakupan'],
            ['Siswa', count($studentsList), 'Siswa aktif'],
            ['Kelas', count($classesList), 'Kelas aktif'],
            ['Sudah dinilai', $overall['scored'], 'Nilai tercatat'],
            ['Belum dinilai', $assessmentsCount - $overall['scored'], 'Nilai kosong tidak dianggap nol'],
            ['Jumlah nilai', $overall['total'], 'Total dari nilai yang sudah tercatat'],
            ['Rata-rata berbobot', $overall['average'] ?? '-', 'Berbobot; hanya nilai terisi'],
        ];

        $currentRow = 5;
        foreach ($items as $index => $item) {
            $sheet->setCellValue("A{$currentRow}", $item[0]);
            $sheet->setCellValue("B{$currentRow}", $item[1]);
            $sheet->setCellValue("C{$currentRow}", $item[2]);

            $sheet->getRowDimension($currentRow)->setRowHeight(22);
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB(self::COLOR_INK);
            $sheet->getStyle("B{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB(self::COLOR_GREEN);
            $sheet->getStyle("C{$currentRow}")->getFont()->getColor()->setRGB(self::COLOR_MUTED);
            $sheet->getStyle("A{$currentRow}:C{$currentRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            if ($index % 2 === 1) {
                $sheet->getStyle("A{$currentRow}:C{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STRIPE);
            }

            $currentRow++;
        }

        $currentRow++;
        $sheet->setCellValue("A{$currentRow}", 'Catatan');
        $sheet->setCellValue("B{$currentRow}", 'Nilai kosong tetap berstatus belum dinilai dan tidak dihitung sebagai nol.');
        $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB(self::COLOR_GREEN);
        $sheet->getStyle("B{$currentRow}")->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);

        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(4);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array<string, mixed>>  $groups
     * @param  array{scored: int, total: float, average: float|null}  $overall
     */
    private function buildMaterialSheet(Worksheet $sheet, array $rows, array $groups, array $overall, string $subtitle): void
    {
        $this->applyBaseHeader($sheet, 'Laporan Nilai per Bab/Materi', $subtitle, 5);

        $sheet->setCellValue('A3', 'Rata-rata berbobot dari nilai terisi. Status sebagian berarti hasil belum lengkap.');
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);
        $sheet->getRowDimension(3)->setRowHeight(20);

        $currentRow = 5;

        foreach ($groups as $material) {
            // Band Bab
            $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", 'BAB/MATERI: '.$material['label']);
            $style = $sheet->getStyle("A{$currentRow}:E{$currentRow}");
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_GREEN);
            $style->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($currentRow)->setRowHeight(24);
            $currentRow++;

            foreach ($material['classes'] as $classGroup) {
                // Band Kelas
                $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", 'KELAS: '.$classGroup['name']);
                $classStyle = $sheet->getStyle("A{$currentRow}:E{$currentRow}");
                $classStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_SOFT_GREEN);
                $classStyle->getFont()->setBold(true)->setSize(11)->getColor()->setRGB(self::COLOR_GREEN);
                $classStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($currentRow)->setRowHeight(22);
                $currentRow++;

                // Table Header
                $sheet->setCellValue("A{$currentRow}", 'No');
                $sheet->setCellValue("B{$currentRow}", 'Nama siswa');
                $sheet->setCellValue("C{$currentRow}", 'JK');
                $sheet->setCellValue("D{$currentRow}", 'Nilai');
                $sheet->setCellValue("E{$currentRow}", 'Status');
                $this->styleTableHeader($sheet, $currentRow, 5);
                $currentRow++;

                foreach ($classGroup['rows'] as $index => $row) {
                    $sheet->setCellValue("A{$currentRow}", $index + 1);
                    $sheet->setCellValue("B{$currentRow}", $row['name']);
                    $sheet->setCellValue("C{$currentRow}", $row['gender'] ?: '-');
                    $sheet->setCellValue("D{$currentRow}", $row['average'] ?? '');
                    $sheet->setCellValue("E{$currentRow}", $row['status']);

                    $sheet->getRowDimension($currentRow)->setRowHeight(20);
                    $sheet->getStyle("A{$currentRow}:E{$currentRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    if ($index % 2 === 1) {
                        $sheet->getStyle("A{$currentRow}:E{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STRIPE);
                    }

                    $currentRow++;
                }

                $classStats = $this->reportGrouper->averageStats($classGroup['rows']);
                $sheet->setCellValue("B{$currentRow}", 'Ringkasan kelas');
                $sheet->setCellValue("D{$currentRow}", $classStats['total']);
                $sheet->setCellValue("E{$currentRow}", 'Rata-rata: '.($classStats['average'] ?? '-'));

                $sheet->getStyle("B{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB(self::COLOR_GREEN);
                $sheet->getStyle("D{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB(self::COLOR_GREEN);
                $sheet->getStyle("E{$currentRow}")->getFont()->setItalic(true)->getColor()->setRGB(self::COLOR_MUTED);
                $sheet->getRowDimension($currentRow)->setRowHeight(22);
                $currentRow += 2;
            }
        }

        // Ringkasan Akhir
        $sheet->mergeCells("A{$currentRow}:E{$currentRow}");
        $sheet->setCellValue("A{$currentRow}", 'RINGKASAN AKHIR LAPORAN');
        $sheet->getStyle("A{$currentRow}:E{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_GREEN);
        $sheet->getStyle("A{$currentRow}:E{$currentRow}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $sheet->getRowDimension($currentRow)->setRowHeight(24);
        $currentRow++;

        $assessmentsCount = count(array_filter($rows, fn ($r) => $r['assessment_id'] !== null));
        $finals = [
            ['Jumlah data penilaian', $assessmentsCount],
            ['Sudah dinilai', $overall['scored']],
            ['Belum dinilai', $assessmentsCount - $overall['scored']],
            ['Jumlah nilai', $overall['total']],
            ['Rata-rata berbobot', $overall['average'] ?? '-'],
        ];

        foreach ($finals as $index => $item) {
            $sheet->setCellValue("A{$currentRow}", $item[0]);
            $sheet->setCellValue("B{$currentRow}", $item[1]);

            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB(self::COLOR_INK);
            $sheet->getStyle("B{$currentRow}")->getFont()->setBold(true)->getColor()->setRGB(self::COLOR_GREEN);
            $sheet->getRowDimension($currentRow)->setRowHeight(22);

            if ($index % 2 === 1) {
                $sheet->getStyle("A{$currentRow}:B{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STRIPE);
            }
            $currentRow++;
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(22);
    }

    /**
     * @param  array<int, array<string, mixed>>  $students
     */
    private function buildStudentRecapSheet(Worksheet $sheet, array $students, string $subtitle): void
    {
        $this->applyBaseHeader($sheet, 'Rekap Nilai per Siswa', $subtitle, 6);

        $sheet->setCellValue('A4', 'Nama');
        $sheet->setCellValue('B4', 'Kelas / periode');
        $sheet->setCellValue('C4', 'Dinilai / diharapkan');
        $sheet->setCellValue('D4', 'Total nilai');
        $sheet->setCellValue('E4', 'Rata-rata berbobot');
        $sheet->setCellValue('F4', 'Kelengkapan');
        $this->styleTableHeader($sheet, 4, 6);

        $currentRow = 5;
        foreach ($students as $index => $item) {
            $period = implode(' · ', array_filter([
                $item['class_name'] ?? '',
                $item['academic_year_name'] ?? '',
                $item['semester'] ?? '',
            ]));

            $statusText = match (true) {
                ($item['expected'] ?? 0) === 0 => 'Materi belum tersedia',
                ($item['assessed'] ?? 0) === 0 => 'Belum dinilai',
                ($item['assessed'] ?? 0) === ($item['expected'] ?? 0) => 'Lengkap',
                default => 'Sebagian dinilai',
            };

            $sheet->setCellValue("A{$currentRow}", $item['name']);
            $sheet->setCellValue("B{$currentRow}", $period);
            $sheet->setCellValue("C{$currentRow}", ($item['assessed'] ?? 0).' / '.($item['expected'] ?? 0));
            $sheet->setCellValue("D{$currentRow}", $item['total'] ?? 0);
            $sheet->setCellValue("E{$currentRow}", $item['average'] ?? '');
            $sheet->setCellValue("F{$currentRow}", $statusText);

            $sheet->getRowDimension($currentRow)->setRowHeight(20);
            $sheet->getStyle("A{$currentRow}:F{$currentRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            if ($index % 2 === 1) {
                $sheet->getStyle("A{$currentRow}:F{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STRIPE);
            }

            $currentRow++;
        }

        $sheet->setAutoFilter('A4:F'.max(4, $currentRow - 1));

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(32);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(26);
    }

    /**
     * @param  array<int, array<string, mixed>>  $classes
     */
    private function buildClassRecapSheet(Worksheet $sheet, array $classes, string $subtitle): void
    {
        $this->applyBaseHeader($sheet, 'Rekap Nilai per Kelas', $subtitle, 5);

        $sheet->setCellValue('A4', 'Kelas / periode');
        $sheet->setCellValue('B4', 'Jumlah siswa');
        $sheet->setCellValue('C4', 'Dinilai / diharapkan');
        $sheet->setCellValue('D4', 'Rata-rata berbobot');
        $sheet->setCellValue('E4', 'Kelengkapan');
        $this->styleTableHeader($sheet, 4, 5);

        $currentRow = 5;
        foreach ($classes as $index => $item) {
            $period = implode(' · ', array_filter([
                $item['class_name'] ?? '',
                $item['academic_year_name'] ?? '',
                $item['semester'] ?? '',
            ]));

            $statusText = match (true) {
                ($item['expected'] ?? 0) === 0 => 'Materi belum tersedia',
                ($item['assessed'] ?? 0) === 0 => 'Belum dinilai',
                ($item['assessed'] ?? 0) === ($item['expected'] ?? 0) => 'Lengkap',
                default => 'Sebagian dinilai',
            };

            $sheet->setCellValue("A{$currentRow}", $period);
            $sheet->setCellValue("B{$currentRow}", $item['students'] ?? 0);
            $sheet->setCellValue("C{$currentRow}", ($item['assessed'] ?? 0).' / '.($item['expected'] ?? 0));
            $sheet->setCellValue("D{$currentRow}", $item['average'] ?? '');
            $sheet->setCellValue("E{$currentRow}", $statusText);

            $sheet->getRowDimension($currentRow)->setRowHeight(20);
            $sheet->getStyle("A{$currentRow}:E{$currentRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            if ($index % 2 === 1) {
                $sheet->getStyle("A{$currentRow}:E{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STRIPE);
            }

            $currentRow++;
        }

        $sheet->setAutoFilter('A4:E'.max(4, $currentRow - 1));

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(26);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function buildDetailSheet(Worksheet $sheet, array $rows, string $subtitle): void
    {
        $this->applyBaseHeader($sheet, 'Laporan Detail Nilai', $subtitle, 14);

        $headers = [
            'NIS', 'Nama', 'JK', 'Kelas', 'Bab', 'Subbab', 'Materi', 'Bobot',
            'Nilai', 'Kesalahan', 'Status', 'Tanggal', 'Paraf', 'Keterangan',
        ];

        foreach ($headers as $index => $header) {
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$col}4", $header);
        }
        $this->styleTableHeader($sheet, 4, 14);

        $currentRow = 5;
        foreach ($rows as $index => $row) {
            $sheet->setCellValue("A{$currentRow}", $row['nis'] ?? '');
            $sheet->setCellValue("B{$currentRow}", $row['name']);
            $sheet->setCellValue("C{$currentRow}", $row['gender'] ?: '-');
            $sheet->setCellValue("D{$currentRow}", $row['class_name']);
            $sheet->setCellValue("E{$currentRow}", $row['chapter'] ?? '');
            $sheet->setCellValue("F{$currentRow}", $row['subchapter'] ?? '');
            $sheet->setCellValue("G{$currentRow}", $row['assessment'] ?? '');
            $sheet->setCellValue("H{$currentRow}", $row['weight'] ?? '');
            $sheet->setCellValue("I{$currentRow}", $row['score'] ?? '');
            $sheet->setCellValue("J{$currentRow}", $row['mistakes'] ?? '');
            $sheet->setCellValue("K{$currentRow}", $row['status']);
            $sheet->setCellValue("L{$currentRow}", $row['assessed_at'] ?? '');
            $sheet->setCellValue("M{$currentRow}", $row['initials'] ?? '');
            $sheet->setCellValue("N{$currentRow}", $row['note'] ?? '');

            $sheet->getRowDimension($currentRow)->setRowHeight(20);
            $sheet->getStyle("A{$currentRow}:N{$currentRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            if ($index % 2 === 1) {
                $sheet->getStyle("A{$currentRow}:N{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_STRIPE);
            }

            $currentRow++;
        }

        $sheet->setAutoFilter('A4:N'.max(4, $currentRow - 1));

        $widths = [14, 25, 8, 17, 20, 20, 28, 10, 10, 12, 16, 20, 12, 36];
        foreach ($widths as $idx => $width) {
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->getColumnDimension($col)->setWidth($width);
        }
    }
}
