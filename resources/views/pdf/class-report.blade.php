<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penilaian Praktik Ibadah</title>
    <style>
        @page {
            margin: 17mm 15mm 20mm 15mm;
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
            table-layout: auto;
            border-collapse: collapse;
            margin-bottom: 12px;
            page-break-inside: auto;
        }
        table.data-table thead { display: table-header-group; }
        table.data-table tr {
            page-break-inside: avoid;
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
        table.data-table th.chapter-band {
            background-color: #176B57;
            color: #FFFFFF;
            font-size: 9pt;
            text-align: left;
            padding: 6px 8px;
        }
        table.data-table th.class-band {
            background-color: #EAF5F1;
            color: #176B57;
            font-size: 8.5pt;
            text-align: left;
            padding: 5px 8px;
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
        .summary-heading {
            color: #176B57;
            font-size: 10pt;
            font-weight: bold;
            margin: 8px 0 5px;
        }
        .report-note {
            color: #657186;
            font-size: 7.5pt;
            margin: 0 0 9px;
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
                @if($teacherName)
                    <div>Penilai: {{ $teacherName }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="scope-box">
        <strong>Cakupan:</strong> {{ $scope }}
    </div>
    <p class="report-note">Nilai rata-rata berbobot dihitung dari materi yang sudah dinilai. Tanda — berarti belum ada nilai.</p>

    @forelse($groups as $group)
        @foreach($group['classes'] as $cls)
            <table class="data-table">
                <colgroup>
                    <col style="width: 7%;">
                    <col style="width: 48%;">
                    <col style="width: 7%;">
                    <col style="width: 11%;">
                    <col style="width: 27%;">
                </colgroup>
                <thead>
                    <tr>
                        <th colspan="5" class="chapter-band">BAB/MATERI: {{ $group['label'] }}</th>
                    </tr>
                    <tr>
                        <th colspan="5" class="class-band">KELAS: {{ $cls['name'] }}</th>
                    </tr>
                    <tr>
                        <th class="center">No</th>
                        <th>Nama Siswa</th>
                        <th class="center">JK</th>
                        <th class="right">Nilai</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
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
                </tbody>
            </table>
        @endforeach
    @empty
        <div style="padding: 16px; text-align: center; color: #657186; background-color: #F7FAF8; border-radius: 4px;">
            Tidak ada data penilaian dalam cakupan ini.
        </div>
    @endforelse

    <div style="page-break-inside: avoid;">
    <div class="summary-heading">Ringkasan akhir laporan</div>
    <table class="summary-card">
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
    </div>

</body>
</html>

