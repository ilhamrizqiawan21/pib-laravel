<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rapor Penilaian Praktik Ibadah - {{ $student->name }}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
            size: a4 portrait;
        }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #17202D;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .top-stripe {
            height: 4px;
            background-color: #176B57;
            margin-bottom: 12px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .title {
            font-size: 14pt;
            font-weight: bold;
            color: #176B57;
            margin: 0 0 2px 0;
        }
        .school-name {
            font-size: 9.5pt;
            font-weight: bold;
            color: #17202D;
        }
        .school-address {
            font-size: 8pt;
            color: #657186;
        }
        .meta-text {
            font-size: 8pt;
            color: #657186;
            text-align: right;
        }
        .divider {
            border-top: 1px solid #DCE3DF;
            margin: 6px 0 10px 0;
        }
        .student-info {
            margin-bottom: 12px;
        }
        .student-name {
            font-size: 12pt;
            font-weight: bold;
            color: #17202D;
            margin-bottom: 2px;
        }
        .student-details {
            font-size: 8.5pt;
            color: #657186;
        }
        .stats-badge {
            background-color: #EAF5F1;
            border: 1px solid #C2DFD4;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 14px;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }
        .stats-table td {
            padding: 0;
            font-size: 8.5pt;
            font-weight: bold;
            color: #176B57;
        }
        .stats-table td.right {
            text-align: right;
        }
        table.materials-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.materials-table th {
            background-color: #176B57;
            color: #FFFFFF;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 6px;
            border-bottom: 1px solid #DCE3DF;
        }
        table.materials-table th.center, table.materials-table td.center {
            text-align: center;
        }
        table.materials-table th.right, table.materials-table td.right {
            text-align: right;
        }
        .group-header {
            background-color: #F7FAF8;
            font-weight: bold;
            color: #176B57;
            font-size: 8.5pt;
            padding: 6px 8px 4px 8px;
            border-bottom: 1px solid #DCE3DF;
        }
        table.materials-table td {
            padding: 5px 6px;
            font-size: 8pt;
            border-bottom: 0.5pt solid #E5E9E7;
            vertical-align: middle;
        }
        .signature-section {
            width: 100%;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signature-box {
            float: right;
            width: 200px;
            text-align: center;
            font-size: 8.5pt;
        }
        .signature-space {
            height: 48px;
        }
        .signature-name {
            font-weight: bold;
            border-bottom: 1px solid #17202D;
            padding-bottom: 2px;
        }
    </style>
</head>
<body>
    <div class="top-stripe"></div>

    <table class="header-table">
        <tr>
            <td>
                <div class="title">Rapor Penilaian Praktik Ibadah</div>
                <div class="school-name">{{ $schoolName }}</div>
                @if($schoolAddress)
                    <div class="school-address">{{ $schoolAddress }}</div>
                @endif
            </td>
            <td class="meta-text">
                <div>Dicetak: {{ now()->translatedFormat('d F Y, H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="student-info">
        <div class="student-name">{{ $student->name }}</div>
        <div class="student-details">
            Kelas {{ $student->schoolClass?->name ?? '-' }}
            @if($student->schoolClass?->academicYear)
                · {{ $student->schoolClass->academicYear->name }} · Semester {{ $student->schoolClass->academicYear->semester->value }}
            @endif
            @if($student->nis)
                · NIS {{ $student->nis }}
            @endif
        </div>
    </div>

    <div class="stats-badge">
        <table class="stats-table">
            <tr>
                <td>Dinilai: {{ $stats['assessed'] }} dari {{ $stats['expected'] }} materi ({{ $stats['percent'] }}%)</td>
                <td class="right">Rata-rata berbobot: {{ $stats['average'] !== null ? number_format($stats['average'], 1) : '-' }}</td>
            </tr>
        </table>
    </div>

    <table class="materials-table">
        <thead>
            <tr>
                <th style="text-align: left;">Materi</th>
                <th style="width: 50px;" class="right">Bobot</th>
                <th style="width: 70px;" class="right">Kesalahan</th>
                <th style="width: 80px;" class="right">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @php $currentGroup = null; @endphp
            @forelse($rows as $row)
                @if($row['assessment_id'] === null)
                    @continue
                @endif
                @php
                    $groupLabel = implode(' › ', array_filter([$row['chapter'], $row['subchapter']]));
                @endphp
                @if($groupLabel !== $currentGroup)
                    @php $currentGroup = $groupLabel; @endphp
                    <tr>
                        <td colspan="4" class="group-header">{{ $groupLabel }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding-left: 12px;">{{ $row['assessment'] }}</td>
                    <td class="right">{{ number_format((float) ($row['weight'] ?? 1.0), 1) }}</td>
                    <td class="right">{{ $row['mistakes'] !== null ? $row['mistakes'] : '-' }}</td>
                    <td class="right">
                        @if($row['score'] !== null)
                            <strong>{{ $row['score'] }}</strong>
                        @else
                            <span style="color: #657186; font-style: italic;">Belum dinilai</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #657186; padding: 16px;">
                        Materi belum tersedia untuk kelas ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <div>Mengetahui,</div>
            <div>Guru / penilai</div>
            <div class="signature-space"></div>
            <div class="signature-name">{{ $teacherName ?: '..............................' }}</div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "PIB Penilaian • Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $size = 7.5;
            $color = array(0.4, 0.45, 0.52);
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $pdf->page_text(297.5 - ($width / 2), 820, $text, $font, $size, $color);
        }
    </script>
</body>
</html>

