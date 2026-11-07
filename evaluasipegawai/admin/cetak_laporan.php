<?php
/**
 * cetak_laporan.php
 * Cetak laporan evaluasi kinerja pegawai (tampilan web + print)
 */

include '../koneksi.php';
session_start();

// Cek login
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    session_unset();
    session_destroy();
    header('Location: ../');
    exit();
}

// Filter periode
$bulan = isset($_GET['bulan']) ? (int) $_GET['bulan'] : (int) date('m');
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');

// Helper escape
function e(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Bangun filter WHERE
$where  = "WHERE MONTH(e.tanggal_evaluasi) = ? AND YEAR(e.tanggal_evaluasi) = ?";

// Query: ringkasan statistik (subquery)
$sqlStats = "
    SELECT 
        COUNT(*) AS total,
        ROUND(AVG(e.skor_total), 2) AS rata_rata,
        MAX(e.skor_total) AS tertinggi,
        MIN(e.skor_total) AS terendah,
        SUM(e.skor_total >= 85) AS sangat_baik,
        SUM(e.skor_total >= 70 AND e.skor_total < 85) AS baik,
        SUM(e.skor_total >= 55 AND e.skor_total < 70) AS cukup,
        SUM(e.skor_total < 55) AS kurang
    FROM evaluasi e $where
";
$stmtStats = mysqli_prepare($koneksi, $sqlStats);
mysqli_stmt_bind_param($stmtStats, 'ii', $bulan, $tahun);
mysqli_stmt_execute($stmtStats);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtStats));

// Query: daftar evaluasi
$sqlEval = "
    SELECT 
        p.nip,
        p.nama,
        j.jabatan,
        e.hasil,
        e.skor_total,
        e.tanggal_evaluasi,
        e.rekomendasi
    FROM evaluasi e
    JOIN pegawai p ON e.id_pegawai = p.id
    JOIN jabatan j ON p.id_jabatan = j.id
    $where
    ORDER BY e.skor_total DESC, p.nama ASC
";
$stmtEval = mysqli_prepare($koneksi, $sqlEval);
mysqli_stmt_bind_param($stmtEval, 'ii', $bulan, $tahun);
mysqli_stmt_execute($stmtEval);
$evalResult = mysqli_stmt_get_result($stmtEval);

$judulBulan = date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun));

// Helper warna badge berdasarkan skor
function skorBadge(float $skor): array {
    if ($skor >= 85) return ['label' => 'Sangat Baik', 'class' => 'badge-success'];
    if ($skor >= 70) return ['label' => 'Baik',        'class' => 'badge-info'];
    if ($skor >= 55) return ['label' => 'Cukup',       'class' => 'badge-warning'];
    return                  ['label' => 'Kurang',      'class' => 'badge-danger'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Evaluasi Kinerja – <?= e($judulBulan) ?></title>
    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:   #1a3c6e;
            --primary-lt:#e8edf5;
            --accent:    #2563eb;
            --success:   #16a34a;
            --info:      #0284c7;
            --warning:   #ca8a04;
            --danger:    #dc2626;
            --gray-50:   #f9fafb;
            --gray-100:  #f3f4f6;
            --gray-200:  #e5e7eb;
            --gray-400:  #9ca3af;
            --gray-600:  #4b5563;
            --gray-800:  #1f2937;
            --border:    #d1d5db;
            --radius:    6px;
            --font:      'Segoe UI', Arial, sans-serif;
        }

        body {
            font-family: var(--font);
            font-size: 13px;
            color: var(--gray-800);
            background: var(--gray-100);
            padding: 24px;
            line-height: 1.5;
        }

        /* ===== KONTAINER DOKUMEN ===== */
        .document {
            max-width: 960px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
        }

        /* ===== KOP SURAT RESMI ===== */
        .official-letterhead {
            padding: 24px 36px 10px;
            background: #fff;
        }
        .letterhead-table {
            width: 100%;
            border-collapse: collapse;
        }
        .letterhead-logo {
            width: 92px;
            text-align: center;
            vertical-align: middle;
        }
        .letterhead-logo img {
            width: 78px;
            height: 78px;
            object-fit: contain;
        }
        .letterhead-text {
            text-align: center;
            vertical-align: middle;
            line-height: 1.18;
            color: #111;
        }
        .letterhead-text .ministry {
            font-size: 15px;
            font-weight: 500;
        }
        .letterhead-text .directorate,
        .letterhead-text .region {
            font-size: 14px;
            font-weight: 500;
        }
        .letterhead-text .office {
            font-size: 16px;
            font-weight: 700;
        }
        .letterhead-text .address {
            font-size: 12px;
            margin-top: 2px;
        }
        .letterhead-text .contact {
            font-size: 11.5px;
        }
        .letterhead-rule {
            border: 0;
            border-top: 1.5px solid #555;
            margin: 8px 0 0;
        }
        .report-title-box {
            padding: 12px 36px 0;
            text-align: center;
        }
        .report-title-box h1 {
            font-size: 18px;
            margin: 0;
            color: #111;
            font-weight: 700;
        }
        .report-title-box .meta {
            margin-top: 4px;
            font-size: 11.5px;
            color: #444;
        }

        /* ===== BODY KONTEN ===== */
        .doc-body { padding: 28px 36px; }

        /* ===== SECTION ===== */
        .section { margin-bottom: 32px; }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 6px;
            margin-bottom: 14px;
        }

        /* ===== STAT CARDS ===== */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-card {
            background: var(--gray-50);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 16px;
            text-align: center;
        }
        .stat-card .stat-val {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            display: block;
            line-height: 1.1;
        }
        .stat-card .stat-label {
            font-size: 11px;
            color: var(--gray-600);
            margin-top: 4px;
        }
        .stat-card.success .stat-val { color: var(--success); }
        .stat-card.info    .stat-val { color: var(--info); }
        .stat-card.warning .stat-val { color: var(--warning); }
        .stat-card.danger  .stat-val { color: var(--danger); }

        /* Distribusi hasil */
        .dist-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        .dist-item {
            border-radius: var(--radius);
            padding: 10px 12px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
        }
        .dist-item .val { font-size: 20px; font-weight: 700; display: block; }
        .dist-item.success { background: #dcfce7; color: var(--success); }
        .dist-item.info    { background: #e0f2fe; color: var(--info); }
        .dist-item.warning { background: #fef9c3; color: var(--warning); }
        .dist-item.danger  { background: #fee2e2; color: var(--danger); }

        /* ===== TABEL ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }
        .data-table thead th {
            background: var(--primary);
            color: #fff;
            padding: 9px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 11.5px;
            white-space: nowrap;
        }
        .data-table thead th.center { text-align: center; }
        .data-table tbody tr:nth-child(even) { background: var(--gray-50); }
        .data-table tbody tr:hover { background: var(--primary-lt); }
        .data-table td {
            padding: 8px 12px;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }
        .data-table td.center { text-align: center; }
        .data-table .no { color: var(--gray-400); font-size: 11px; text-align: center; width: 36px; }
        .data-table tfoot td {
            background: var(--primary-lt);
            font-weight: 600;
            padding: 9px 12px;
            border-top: 2px solid var(--primary);
        }

        /* ===== BADGE ===== */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: var(--success); }
        .badge-info    { background: #e0f2fe; color: var(--info); }
        .badge-warning { background: #fef9c3; color: var(--warning); }
        .badge-danger  { background: #fee2e2; color: var(--danger); }
        .badge-active  { background: #dcfce7; color: var(--success); }

        /* Skor bar mini */
        .skor-cell { display: flex; align-items: center; gap: 8px; }
        .skor-bar {
            flex: 1;
            height: 6px;
            background: var(--gray-200);
            border-radius: 3px;
            overflow: hidden;
            min-width: 50px;
        }
        .skor-fill {
            height: 100%;
            border-radius: 3px;
            background: var(--accent);
        }
        .skor-fill.success { background: var(--success); }
        .skor-fill.info    { background: var(--info); }
        .skor-fill.warning { background: var(--warning); }
        .skor-fill.danger  { background: var(--danger); }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 32px;
            color: var(--gray-400);
            font-style: italic;
        }

        /* ===== FOOTER DOKUMEN ===== */
        .doc-footer {
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
            padding: 16px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--gray-400);
        }

        /* ===== TOOLBAR (no-print) ===== */
        .toolbar {
            max-width: 960px;
            margin: 0 auto 16px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border: none;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .85; }
        .btn-primary   { background: var(--accent); color: #fff; }
        .btn-secondary { background: var(--gray-200); color: var(--gray-800); }
        .btn-success   { background: #16a34a; color: #fff; }


        /* ===== TANDA TANGAN PIMPINAN ===== */
        .signature-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 28px;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 280px;
            text-align: center;
            color: #111;
            font-size: 12px;
            line-height: 1.35;
        }
        .signature-box .date-line {
            margin-bottom: 2px;
        }
        .signature-box .position {
            margin-bottom: 2px;
        }
        .signature-box .signature-image {
            width: 220px;
            height: 68px;
            object-fit: contain;
            display: block;
            margin: 2px auto 2px;
        }
        .signature-box .name {
            font-weight: 700;
            text-decoration: underline;
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            body { background: #fff; padding: 0; font-size: 12px; }
            .toolbar, .no-print { display: none !important; }
            .document { box-shadow: none; border: none; max-width: 100%; }
            .official-letterhead, .report-title-box { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .data-table thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .dist-item, .badge, .stat-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .data-table tbody tr { page-break-inside: avoid; }
            .section { page-break-inside: avoid; }
            .skor-bar { display: none; }
        }
    </style>
</head>
<body>

<!-- TOOLBAR (tidak ikut tercetak) -->
<div class="toolbar no-print">
    <a href="export_excel.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>"
       class="btn btn-success">
        ⬇ Export Excel
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        🖨️ Cetak
    </button>
    <button onclick="history.back()" class="btn btn-secondary">
        ← Kembali
    </button>
</div>

<!-- DOKUMEN UTAMA -->
<div class="document">

    <!-- KOP SURAT RESMI -->
    <div class="official-letterhead">
        <table class="letterhead-table">
            <tr>
                 <td class="letterhead-logo">
                    <img src="../assets/img/logo_kemenimipas.png" 
                     alt="Logo Kementerian Imigrasi dan Pemasyarakatan">
                </td>
                <td class="letterhead-text">
                    <div class="ministry">KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</div>
                    <div class="directorate">DIREKTORAT JENDERAL IMIGRASI</div>
                    <div class="region">KANTOR WILAYAH SUMATERA BARAT</div>
                    <div class="office">KANTOR IMIGRASI KELAS I TPI PADANG</div>
                    <div class="address">Jl. Khatib Sulaiman No. 50, Kota Padang, Sumatera Barat</div>
                    <div class="contact">Telepon: (0751) 7055113/ (0751) 419000</div>
                    <div class="contact">Laman: padang.imigrasi.go.id, &nbsp;Pos-el: kanim_padang@imigrasi.go.id</div>
                </td>
            </tr>
        </table>
        <hr class="letterhead-rule">
    </div>

    <div class="report-title-box">
        <h1>LAPORAN EVALUASI KINERJA PEGAWAI</h1>
        <div class="meta">
            Periode: <?= e($judulBulan) ?> &nbsp; | &nbsp; Dicetak: <?= date('d F Y, H:i') ?> WIB
        </div>
    </div>

    <div class="doc-body">

        <!-- ===== RINGKASAN STATISTIK ===== -->
        <div class="section">
            <div class="section-title">Ringkasan</div>

            <div class="stat-grid">
                <div class="stat-card">
                    <span class="stat-val"><?= (int) ($stats['total'] ?? 0) ?></span>
                    <div class="stat-label">Total Evaluasi</div>
                </div>
                <div class="stat-card info">
                    <span class="stat-val"><?= number_format((float)($stats['rata_rata'] ?? 0), 1) ?></span>
                    <div class="stat-label">Rata-rata Skor</div>
                </div>
                <div class="stat-card success">
                    <span class="stat-val"><?= number_format((float)($stats['tertinggi'] ?? 0), 1) ?></span>
                    <div class="stat-label">Skor Tertinggi</div>
                </div>
                <div class="stat-card danger">
                    <span class="stat-val"><?= number_format((float)($stats['terendah'] ?? 0), 1) ?></span>
                    <div class="stat-label">Skor Terendah</div>
                </div>
            </div>

            <!-- Distribusi predikat -->
            <div class="dist-grid">
                <div class="dist-item success">
                    <span class="val"><?= (int)($stats['sangat_baik'] ?? 0) ?></span>
                    Sangat Baik (≥85)
                </div>
                <div class="dist-item info">
                    <span class="val"><?= (int)($stats['baik'] ?? 0) ?></span>
                    Baik (70–84)
                </div>
                <div class="dist-item warning">
                    <span class="val"><?= (int)($stats['cukup'] ?? 0) ?></span>
                    Cukup (55–69)
                </div>
                <div class="dist-item danger">
                    <span class="val"><?= (int)($stats['kurang'] ?? 0) ?></span>
                    Kurang (&lt;55)
                </div>
            </div>
        </div>

        <!-- ===== HASIL EVALUASI ===== -->
        <div class="section">
            <div class="section-title">Hasil Evaluasi – <?= e($judulBulan) ?></div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th class="no">#</th>
                        <th>NIP</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan</th>
                        <th class="center">Predikat</th>
                        <th>Skor</th>
                        <th class="center">Tanggal</th>
                        <th>Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                $totalSkor = 0;
                $jumlah    = 0;

                if (mysqli_num_rows($evalResult) === 0): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            Tidak ada data evaluasi untuk periode <?= e($judulBulan) ?>.
                        </td>
                    </tr>
                <?php else:
                    while ($row = mysqli_fetch_assoc($evalResult)):
                        $skor   = (float) $row['skor_total'];
                        $badge  = skorBadge($skor);
                        $pct    = min(100, $skor);
                        $totalSkor += $skor;
                        $jumlah++;
                ?>
                    <tr>
                        <td class="no"><?= $no++ ?></td>
                        <td><?= e($row['nip']) ?></td>
                        <td><strong><?= e($row['nama']) ?></strong></td>
                        <td><?= e($row['jabatan']) ?></td>
                        <td class="center">
                            <span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                        </td>
                        <td>
                            <div class="skor-cell">
                                <span style="min-width:38px; font-weight:600;">
                                    <?= number_format($skor, 1) ?>
                                </span>
                                <div class="skor-bar">
                                    <div class="skor-fill <?= str_replace('badge-', '', $badge['class']) ?>"
                                         style="width:<?= $pct ?>%;"></div>
                                </div>
                            </div>
                        </td>
                        <td class="center"><?= date('d M Y', strtotime($row['tanggal_evaluasi'])) ?></td>
                        <td><?= e($row['rekomendasi'] ?? '—') ?></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
                <?php if ($jumlah > 0): ?>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align:right;">
                            Rata-rata dari <?= $jumlah ?> pegawai:
                        </td>
                        <td colspan="3">
                            <?= number_format($totalSkor / $jumlah, 2) ?>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- ===== TANDA TANGAN PIMPINAN ===== -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="date-line">Padang, <?= date('d F Y') ?></div>
                <div class="position">Kepala Kantor,</div>
                <img class="signature-image"
                  
                <div class="name">Murdo Danang Laksono</div>
            </div>
        </div>

    </div><!-- /doc-body -->

    <!-- Footer dokumen -->
    <div class="doc-footer">
        <span>Sistem Evaluasi Kinerja Pegawai</span>
        <span>Dokumen ini digenerate otomatis · <?= date('d F Y H:i:s') ?></span>
    </div>

</div><!-- /document -->

</body>
</html>