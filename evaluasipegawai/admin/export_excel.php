<?php
/**
 * export_excel.php
 * Export laporan evaluasi kinerja ke format Excel (.xls)
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


// Filter opsional (bulan/tahun)
$bulan  = isset($_GET['bulan']) ? (int) $_GET['bulan'] : null;
$tahun  = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');

// Bangun klausa WHERE
$where = '';
$params = [];

if ($bulan && $tahun) {
    $where = "WHERE MONTH(e.tanggal_evaluasi) = ? AND YEAR(e.tanggal_evaluasi) = ?";
    $params = [$bulan, $tahun];
} elseif ($tahun) {
    $where = "WHERE YEAR(e.tanggal_evaluasi) = ?";
    $params = [$tahun];
}

// Gunakan prepared statement untuk keamanan
$sql = "
    SELECT 
        e.id,
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
    ORDER BY e.tanggal_evaluasi DESC, p.nama ASC
";

$stmt = mysqli_prepare($koneksi, $sql);

if (!empty($params)) {
    $types = str_repeat('i', count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Label periode untuk nama file
$periode = $bulan && $tahun
    ? date('Y-m', mktime(0, 0, 0, $bulan, 1, $tahun))
    : $tahun;

$filename = 'laporan_evaluasi_' . $periode . '_' . date('Ymd') . '.xls';

// Header HTTP untuk download Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');

// BOM agar karakter UTF-8 terbaca dengan benar di Excel
echo "\xEF\xBB\xBF";

/**
 * Escape nilai HTML untuk keamanan output
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Mapping skor ke label warna sel (Excel conditional style via inline style)
 */
function skorStyle(float $skor): string {
    if ($skor >= 85) return 'background:#d4edda; color:#155724;'; // Sangat Baik
    if ($skor >= 70) return 'background:#d1ecf1; color:#0c5460;'; // Baik
    if ($skor >= 55) return 'background:#fff3cd; color:#856404;'; // Cukup
    return 'background:#f8d7da; color:#721c24;';                   // Kurang
}

$judulPeriode = $bulan && $tahun
    ? 'Periode: ' . date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun))
    : 'Tahun: ' . $tahun;
?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta charset="UTF-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Evaluasi Kinerja</x:Name>
                    <x:WorksheetOptions>
                        <x:Print>
                            <x:FitWidth>1</x:FitWidth>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #bdbdbd; padding: 6px 10px; vertical-align: middle; }
        th { background-color: #1a3c6e; color: #ffffff; font-weight: bold; text-align: center; }
        td { text-align: left; }
        td.center { text-align: center; }
        td.right  { text-align: right; }
        .judul { font-size: 16pt; font-weight: bold; text-align: center; border: none; }
        .subjudul { font-size: 11pt; text-align: center; border: none; color: #555555; }
        .section-header { background-color: #e8edf5; font-weight: bold; border: none; padding: 10px 0 4px 0; }
        .meta { border: none; font-size: 9pt; color: #888888; text-align: right; }
        .no { text-align: center; width: 40px; }
    </style>
</head>
<body>
<table>
    <!-- Judul -->
    <tr>
        <td colspan="8" class="judul">LAPORAN EVALUASI KINERJA PEGAWAI</td>
    </tr>
    <tr>
        <td colspan="8" class="subjudul"><?= e($judulPeriode) ?> &nbsp;|&nbsp; Diekspor: <?= date('d F Y H:i') ?></td>
    </tr>
    <tr><td colspan="8" style="border:none; height:10px;"></td></tr>

    <!-- Header tabel -->
    <tr>
        <th class="no">No</th>
        <th style="width:100px;">NIP</th>
        <th style="width:180px;">Nama Pegawai</th>
        <th style="width:140px;">Jabatan</th>
        <th style="width:110px;">Hasil Evaluasi</th>
        <th style="width:80px;">Skor (%)</th>
        <th style="width:110px;">Tanggal Evaluasi</th>
        <th>Rekomendasi</th>
    </tr>

    <?php
    $no = 1;
    $totalSkor = 0;
    $jumlah    = 0;

    if (mysqli_num_rows($result) === 0):
    ?>
    <tr>
        <td colspan="8" class="center" style="color:#888; font-style:italic;">
            Tidak ada data evaluasi untuk periode ini.
        </td>
    </tr>
    <?php else: while ($row = mysqli_fetch_assoc($result)): $jumlah++; $totalSkor += (float) $row['skor_total']; ?>
    <tr>
        <td class="no"><?= $no++ ?></td>
        <td class="center"><?= e($row['nip']) ?></td>
        <td><?= e($row['nama']) ?></td>
        <td><?= e($row['jabatan']) ?></td>
        <td class="center"><?= e($row['hasil']) ?></td>
        <td class="center" style="<?= skorStyle((float) $row['skor_total']) ?>">
            <?= number_format((float) $row['skor_total'], 2) ?>
        </td>
        <td class="center"><?= date('d-m-Y', strtotime($row['tanggal_evaluasi'])) ?></td>
        <td><?= e($row['rekomendasi'] ?? '-') ?></td>
    </tr>
    <?php endwhile; endif; ?>

    <!-- Baris ringkasan -->
    <?php if ($jumlah > 0): $rataRata = $totalSkor / $jumlah; ?>
    <tr>
        <td colspan="5" style="font-weight:bold; background:#f5f5f5; text-align:right;">
            Rata-rata Skor (<?= $jumlah ?> pegawai)
        </td>
        <td class="center" style="font-weight:bold; background:#f5f5f5; <?= skorStyle($rataRata) ?>">
            <?= number_format($rataRata, 2) ?>
        </td>
        <td colspan="2" style="background:#f5f5f5;"></td>
    </tr>
    <?php endif; ?>

    <!-- Footer -->
    <tr><td colspan="8" style="border:none; height:12px;"></td></tr>
    <tr>
        <td colspan="8" class="meta">
            *) Dokumen ini digenerate otomatis oleh Sistem Evaluasi Kinerja.
        </td>
    </tr>
</table>
</body>
</html>