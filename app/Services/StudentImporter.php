<?php

namespace App\Services;

use App\Enums\Gender;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class StudentImporter
{
    private const MAX_ROWS = 1000;

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function import(UploadedFile $file, ?int $userId = null): StudentImportResult
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        /** @var array<int, array<int, mixed>> $rows */
        $rows = $worksheet->toArray();

        if (count($rows) <= 1) {
            return new StudentImportResult(0);
        }

        // Header + data rows
        $dataRowsCount = count($rows) - 1;
        if ($dataRowsCount > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'file' => 'Berkas impor maksimal berisi '.self::MAX_ROWS.' baris data siswa.',
            ]);
        }

        /** @var array<int, mixed> $headerRow */
        $headerRow = array_shift($rows);
        $headerMap = $this->resolveHeaderMap($headerRow);

        if (! isset($headerMap['name']) || ! isset($headerMap['class'])) {
            throw ValidationException::withMessages([
                'file' => 'Berkas harus memiliki kolom header "nama" dan "kelas".',
            ]);
        }

        /** @var array<string, array<int, array{id: int, active_year: bool}>> $classesMap */
        $classesMap = [];
        foreach (SchoolClass::query()->with('academicYear')->where('is_active', true)->get() as $class) {
            $classesMap[$class->name][] = [
                'id' => $class->id,
                'active_year' => $class->academicYear->is_active,
            ];
        }

        $existingNis = Student::query()
            ->whereNotNull('nis')
            ->pluck('nis')
            ->flip()
            ->all();

        $seenNisInImport = [];
        $validStudents = [];
        $failed = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 untuk 1-based, +1 karena header sudah di-shift

            // Abaikan baris yang seluruhnya kosong
            if (empty(array_filter($row, fn ($val) => ! is_null($val) && trim((string) $val) !== ''))) {
                continue;
            }

            $name = trim((string) ($row[$headerMap['name']] ?? ''));
            $className = trim((string) ($row[$headerMap['class']] ?? ''));
            $nis = isset($headerMap['nis']) ? trim((string) ($row[$headerMap['nis']] ?? '')) : '';
            $nisn = isset($headerMap['nisn']) ? trim((string) ($row[$headerMap['nisn']] ?? '')) : '';
            $genderRaw = isset($headerMap['gender']) ? trim((string) ($row[$headerMap['gender']] ?? '')) : '';

            $normalizedNis = $nis === '' ? null : $nis;

            if ($name === '') {
                $failed[] = [
                    'row' => $rowNumber,
                    'error' => 'Nama siswa tidak boleh kosong.',
                    'data' => ['name' => $name, 'class' => $className],
                ];

                continue;
            }

            if (mb_strlen($name) > 120) {
                $failed[] = [
                    'row' => $rowNumber,
                    'error' => 'Nama siswa melebihi 120 karakter.',
                    'data' => ['name' => $name, 'class' => $className],
                ];

                continue;
            }

            if ($className === '' || ! isset($classesMap[$className])) {
                $failed[] = [
                    'row' => $rowNumber,
                    'error' => "Kelas '{$className}' tidak ditemukan di sistem.",
                    'data' => ['name' => $name, 'class' => $className],
                ];

                continue;
            }

            $classOptions = $classesMap[$className];
            $activeClass = array_find($classOptions, fn (array $option): bool => $option['active_year']);
            if (count($classOptions) > 1 && $activeClass === null) {
                $failed[] = [
                    'row' => $rowNumber,
                    'error' => "Kelas '{$className}' ada pada beberapa tahun ajaran. Aktifkan tahun ajaran tujuan sebelum mengimpor.",
                    'data' => ['name' => $name, 'class' => $className],
                ];

                continue;
            }

            $classId = $activeClass['id'] ?? $classOptions[0]['id'];

            if ($normalizedNis !== null) {
                if (isset($seenNisInImport[$normalizedNis])) {
                    $failed[] = [
                        'row' => $rowNumber,
                        'error' => "NIS '{$normalizedNis}' duplikat dalam berkas impor.",
                        'data' => ['name' => $name, 'class' => $className, 'nis' => $normalizedNis],
                    ];

                    continue;
                }

                if (isset($existingNis[$normalizedNis])) {
                    $failed[] = [
                        'row' => $rowNumber,
                        'error' => "NIS '{$normalizedNis}' sudah terdaftar di sistem.",
                        'data' => ['name' => $name, 'class' => $className, 'nis' => $normalizedNis],
                    ];

                    continue;
                }

                $seenNisInImport[$normalizedNis] = true;
            }

            $resolvedGender = $this->parseGender($genderRaw);
            if ($genderRaw !== '' && $resolvedGender === null) {
                $failed[] = [
                    'row' => $rowNumber,
                    'error' => "Jenis kelamin '{$genderRaw}' tidak valid (gunakan L atau P).",
                    'data' => ['name' => $name, 'class' => $className],
                ];

                continue;
            }

            $validStudents[] = [
                'school_class_id' => $classId,
                'name' => $name,
                'nis' => $normalizedNis,
                'nisn' => mb_substr($nisn, 0, 30),
                'gender' => $resolvedGender,
                'is_active' => true,
            ];
        }

        $importedCount = count($validStudents);

        if ($importedCount > 0) {
            DB::transaction(function () use ($validStudents, $importedCount, $failed, $userId): void {
                foreach ($validStudents as $data) {
                    Student::create($data);
                }

                $this->auditLogger->record(
                    entity: 'import',
                    entityId: 'student',
                    action: 'IMPORT',
                    summary: "Impor {$importedCount} siswa (".count($failed).' gagal)',
                    meta: [
                        'imported_count' => $importedCount,
                        'failed_count' => count($failed),
                    ],
                    userId: $userId
                );
            });
        }

        return new StudentImportResult($importedCount, $failed);
    }

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array<string, int>
     */
    private function resolveHeaderMap(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $col) {
            $colName = mb_strtolower(trim((string) $col));

            if (in_array($colName, ['nama', 'name', 'nama siswa', 'nama lengkap'], true)) {
                $map['name'] = $index;
            } elseif (in_array($colName, ['kelas', 'class', 'nama kelas'], true)) {
                $map['class'] = $index;
            } elseif (in_array($colName, ['nis', 'no induk', 'nomor induk'], true)) {
                $map['nis'] = $index;
            } elseif (in_array($colName, ['nisn'], true)) {
                $map['nisn'] = $index;
            } elseif (in_array($colName, ['jenis_kelamin', 'gender', 'jk', 'l/p', 'jenis kelamin'], true)) {
                $map['gender'] = $index;
            }
        }

        return $map;
    }

    private function parseGender(string $raw): ?Gender
    {
        $upper = mb_strtoupper(trim($raw));

        if (in_array($upper, ['L', 'LAKI-LAKI', 'LAKI', 'PRIA', 'M'], true)) {
            return Gender::L;
        }

        if (in_array($upper, ['P', 'PEREMPUAN', 'WANITA', 'F'], true)) {
            return Gender::P;
        }

        return null;
    }
}
