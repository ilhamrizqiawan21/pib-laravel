<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentTemplateController extends Controller
{
    public function download(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-impor-siswa.csv"',
        ];

        return response()->stream(function (): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM agar terbaca sempurna di Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['nama', 'kelas', 'nis', 'nisn', 'jenis_kelamin']);
            fputcsv($handle, ['Ahmad Fauzan', 'VII-A', '20240101', '0012345678', 'L']);
            fputcsv($handle, ['Fatimah Az-Zahra', 'VII-A', '20240102', '0012345679', 'P']);

            fclose($handle);
        }, 200, $headers);
    }
}
