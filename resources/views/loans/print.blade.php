<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Peminjaman Barang - {{ config('app.name', 'Inventaris Sekolah') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
            background-color: #f1f5f9;
            margin: 0;
            padding: 24px 16px;
            font-size: 12px;
            line-height: 1.45;
        }
        .report-toolbar {
            max-width: 1120px;
            margin: 0 auto 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
        }
        .toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-outline {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #334155;
        }
        .btn-success {
            background: #16a34a;
            color: #ffffff;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
        }
        .report-sheet {
            max-width: 1120px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px 32px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
        }
        .report-header {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }
        .report-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .report-title-group h1 {
            font-size: 18px;
            font-weight: 800;
            margin: 0 0 2px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .report-title-group h2 {
            font-size: 13px;
            font-weight: 700;
            color: #2563eb;
            margin: 0 0 4px;
        }
        .report-title-group p {
            font-size: 11px;
            color: #475569;
            margin: 0;
        }
        .report-summary {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }
        .summary-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            background: #f8fafc;
        }
        .summary-box .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            display: block;
        }
        .summary-box .value {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        table.report-table th,
        table.report-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            vertical-align: top;
            text-align: left;
            font-size: 11px;
        }
        table.report-table th {
            background-color: #f1f5f9;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            color: #334155;
        }
        .text-center { text-align: center !important; }
        .text-muted { color: #64748b; font-size: 10px; display: block; }
        .status-pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: capitalize;
        }
        .status-dipinjam { background: #dbeafe; color: #1d4ed8; }
        .status-dikembalikan { background: #dcfce7; color: #15803d; }
        .status-terlambat { background: #fee2e2; color: #b91c1c; }
        .status-menunggu { background: #fef3c7; color: #b45309; }
        .status-ditolak { background: #e2e8f0; color: #475569; }
        .report-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 240px;
            text-align: center;
            font-size: 11px;
        }
        .signature-space {
            height: 56px;
        }
        @media (max-width: 767.98px) {
            .report-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .report-sheet {
                padding: 16px;
                overflow-x: auto;
            }
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .report-sheet {
                max-width: none;
                border: 0;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    @php
        $queryFilter = array_filter(['search' => $search, 'status' => $status], fn ($v) => $v !== '');
        $totalUnits = $loans->sum('quantity');
        $activeCount = $loans->where('status', 'dipinjam')->count();
        $overdueCount = $loans->where('status', 'terlambat')->count();
        $returnedCount = $loans->where('status', 'dikembalikan')->count();
    @endphp

    <div class="report-toolbar no-print">
        <div>
            <strong>Pratinjau Cetak / PDF Laporan Peminjaman</strong>
            <span class="text-muted">Gunakan tombol "Cetak / Simpan PDF" lalu pilih tujuan "Save as PDF" untuk mengunduh file PDF.</span>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('loans.index', $queryFilter) }}" class="btn btn-outline">&larr; Kembali</a>
            <a href="{{ route('loans.export.excel', $queryFilter) }}" class="btn btn-success">Unduh Excel (.xlsx)</a>
            <button type="button" onclick="window.print()" class="btn btn-primary">Cetak / Simpan PDF</button>
        </div>
    </div>

    <div class="report-sheet">
        <div class="report-header">
            <img src="{{ asset('assets/img/logo-sekolah.png') }}" alt="Logo Sekolah" class="report-logo">
            <div class="report-title-group">
                <h1>Sistem Inventaris &amp; Peminjaman Barang Sekolah</h1>
                <h2>Laporan Rekapitulasi Peminjaman &amp; Pengembalian Barang</h2>
                <p>
                    Dicetak pada: <strong>{{ $printedAt->translatedFormat('d F Y, H:i') }} WIB</strong>
                    &bull; Filter Status: <strong>{{ $status !== '' ? ucfirst($status) : 'Semua Status' }}</strong>
                    @if ($search !== '')
                        &bull; Kata Kunci: <strong>"{{ $search }}"</strong>
                    @endif
                </p>
            </div>
        </div>

        <div class="report-summary">
            <div class="summary-box">
                <span class="label">Total Transaksi</span>
                <span class="value">{{ number_format($loans->count(), 0, ',', '.') }}</span>
            </div>
            <div class="summary-box">
                <span class="label">Total Unit Barang</span>
                <span class="value">{{ number_format($totalUnits, 0, ',', '.') }} unit</span>
            </div>
            <div class="summary-box">
                <span class="label">Sedang Dipinjam</span>
                <span class="value">{{ number_format($activeCount, 0, ',', '.') }}</span>
            </div>
            <div class="summary-box">
                <span class="label">Terlambat</span>
                <span class="value">{{ number_format($overdueCount, 0, ',', '.') }}</span>
            </div>
            <div class="summary-box">
                <span class="label">Sudah Dikembalikan</span>
                <span class="value">{{ number_format($returnedCount, 0, ',', '.') }}</span>
            </div>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 34px;">No</th>
                    <th>Peminjam</th>
                    <th>Barang &amp; Kode</th>
                    <th>Kategori</th>
                    <th class="text-center" style="width: 55px;">Jumlah</th>
                    <th class="text-center" style="width: 82px;">Tgl Pinjam</th>
                    <th class="text-center" style="width: 82px;">Jatuh Tempo</th>
                    <th class="text-center" style="width: 84px;">Status</th>
                    <th>Diverifikasi Oleh</th>
                    <th>Pengembalian &amp; Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $index => $loan)
                    <tr>
                        <td class="text-center">{{ number_format($index + 1, 0, ',', '.') }}</td>
                        <td>
                            <strong>{{ $loan->user?->name ?? '-' }}</strong>
                            <span class="text-muted">{{ $loan->user?->email ?? '-' }}</span>
                        </td>
                        <td>
                            <strong>{{ $loan->item?->name ?? '-' }}</strong>
                            <span class="text-muted">{{ $loan->item?->code ?? '-' }}</span>
                        </td>
                        <td>{{ $loan->item?->category?->name ?? '-' }}</td>
                        <td class="text-center">{{ number_format($loan->quantity, 0, ',', '.') }} unit</td>
                        <td class="text-center">{{ $loan->loan_date?->translatedFormat('d M Y') ?? '-' }}</td>
                        <td class="text-center">{{ $loan->due_date?->translatedFormat('d M Y') ?? '-' }}</td>
                        <td class="text-center">
                            <span class="status-pill status-{{ $loan->status }}">{{ ucfirst($loan->status) }}</span>
                        </td>
                        <td>{{ $loan->approver?->name ?? '-' }}</td>
                        <td>
                            @if ($loan->returnLog)
                                <strong>{{ $loan->returnLog->returned_at?->translatedFormat('d M Y') }}</strong>
                                <span class="text-muted">Kondisi: {{ ucwords($loan->returnLog->condition_on_return) }}</span>
                            @else
                                <span class="text-muted">Belum dikembalikan</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center" style="padding: 20px;">
                            Tidak ada data peminjaman yang sesuai dengan filter laporan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="report-footer">
            <div class="signature-box">
                <div>Mengetahui,</div>
                <div>Petugas Inventaris Sekolah</div>
                <div class="signature-space"></div>
                <div><strong>{{ $printedBy?->name ?? 'Admin Inventaris' }}</strong></div>
            </div>
        </div>
    </div>
</body>
</html>

