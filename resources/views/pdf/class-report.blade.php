<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penilaian Praktik Ibadah</title>
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
            margin: 6px 0 8px 0;
        }
        .scope-box {
            font-size: 8pt;
            color: #657186;
            background-color: #F7FAF8;
            padding: 4px 8px;
            border-radius: 4px;
            margin-bottom: 12px;
            border: 1px solid #EAF5F1;
        }
        table.data-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 12px;
            page-break-inside: auto;
        }
        table.data-table tr {
            page-break-inside: auto;
            page-break-after: auto;
        }
        .chapter-band {
            background-color: #176B57;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 9pt;
            padding: 5px 8px;
            text-align: left;
        }
        .class-band {
            background-color: #EAF5F1;
            color: #176B57;
            font-weight: bold;
            font-size: 8.5pt;
            padding: 4px 8px;
            text-align: left;
        }
        table.data-table th {
            background-color: #176B57;
            color: #FFFFFF;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 6px;
            text-align: left;
            border-bottom: 1px solid #DCE3DF;
        }
        table.data-table th.center, table.data-table td.center {
            text-align: center;
        }
        table.data-table th.right, table.data-table td.right {
            text-align: right;
        }
        table.data-table td {
            padding: 4px 6px;
            font-size: 8pt;
            border-bottom: 0.5pt solid #E5E9E7;
        }
        table.data-table tr.zebra {
            background-color: #F7FAF8;
        }
        .summary-row {
            font-weight: bold;
            color: #176B57;
            background-color: #F7FAF8;
            border-top: 1px solid #DCE3DF;
        }
        .summary-card {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 16px;
        }
        .summary-card td {
            padding: 4px 8px;
            font-size: 8pt;
            border-bottom: 0.5pt solid #E5E9E7;
        }
        .summary-card tr.zebra {
            background-color: #F7FAF8;
        }
        .footer {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
            height: 6mm;
            border-top: 0.5pt solid #DCE3DF;
            font-size: 7.5pt;
            color: #657186;
            padding-top: 2mm;
        }
    </style>
</head>
<body>
    <div class="top-stripe"></div>

    <table class="header-table">
        <tr>
            <td>
                <div class="title">Laporan Penilaian Praktik Ibadah</div>
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

    <div class="scope-box">
        <strong>Cakupan:</strong> {{ $scope }}
    </div>

    @forelse($groups as $group)
        <table class="data-table">
            <thead>
                <tr>
                    <td colspan="5" class="chapter-band">BAB/MATERI: {{ $group['label'] }}</td>
                </tr>
            </thead>
            <tbody>
                @foreach($group['classes'] as $cls)
                    <tr>
                        <td colspan="5" class="class-band">KELAS: {{ $cls['name'] }}</td>
                    </tr>
                    <tr>
                        <th style="width: 28px;" class="center">No</th>
                        <th>Nama Siswa</th>
                        <th style="width: 35px;" class="center">JK</th>
                        <th style="width: 50px;" class="right">Nilai</th>
                        <th style="width: 100px;">Status</th>
                    </tr>
                    @foreach($cls['rows'] as $idx => $row)
                        <tr class="{{ $idx % 2 === 1 ? 'zebra' : '' }}">
                            <td class="center">{{ $idx + 1 }}</td>
                            <td><strong>{{ $row['name'] }}</strong></td>
                            <td class="center">{{ $row['gender'] }}</td>
                            <td class="right">
                                <strong>{{ $row['average'] !== null ? number_format($row['average'], 1) : '-' }}</strong>
                            </td>
                            <td>{{ $row['status'] }}</td>
                        </tr>
                    @endforeach
                    @php
                        $clsStats = $grouper->averageStats($cls['rows']);
                    @endphp
                    <tr class="summary-row">
                        <td></td>
                        <td colspan="2">Ringkasan kelas</td>
                        <td class="right">{{ number_format($clsStats['total'], 1) }}</td>
                        <td>Rata-rata: {{ $clsStats['average'] !== null ? number_format($clsStats['average'], 1) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <div style="padding: 16px; text-align: center; color: #657186; background-color: #F7FAF8; border-radius: 4px;">
            Tidak ada data penilaian dalam cakupan ini.
        </div>
    @endforelse

    <table class="summary-card" style="page-break-inside: avoid;">
        <thead>
            <tr>
                <td colspan="2" class="chapter-band">RINGKASAN AKHIR LAPORAN</td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 50%;">Jumlah data penilaian</td>
                <td><strong>{{ $assessmentsCount }}</strong></td>
            </tr>
            <tr class="zebra">
                <td>Sudah dinilai</td>
                <td><strong>{{ $overall['scored'] }}</strong></td>
            </tr>
            <tr>
                <td>Belum dinilai</td>
                <td><strong>{{ $assessmentsCount - $overall['scored'] }}</strong></td>
            </tr>
            <tr class="zebra">
                <td>Jumlah nilai</td>
                <td><strong>{{ number_format($overall['total'], 1) }}</strong></td>
            </tr>
            <tr>
                <td>Rata-rata berbobot</td>
                <td><strong>{{ $overall['average'] !== null ? number_format($overall['average'], 1) : '-' }}</strong></td>
            </tr>
        </tbody>
    </table>

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

