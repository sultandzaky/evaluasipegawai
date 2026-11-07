<?php
include '../koneksi.php';      // Panggil koneksi database
session_start();               // Mulai session

// Cek apakah user sudah login
if ($_SESSION['status'] != 'login') {
    session_unset();
    session_destroy();
    header('location:../');
    exit();
}

// ===== AMBIL DATA UNTUK FORM =====
// Ambil data pegawai
$pegawai = mysqli_query($koneksi, "SELECT * FROM pegawai ORDER BY nama");
// Ambil data kriteria
$kriteria_list = mysqli_query($koneksi, "SELECT * FROM kriteria ORDER BY kode_kriteria");
// Ambil data nilai kondisi
$nilai_list = mysqli_query($koneksi, "SELECT * FROM nilai_kondisi ORDER BY skor DESC");

// ===== FUNGSI FORWARD CHAINING =====
// Urutan prioritas kesimpulan: semakin kecil angka, semakin tinggi kesimpulan.
// Dipakai untuk menentukan kesimpulan akhir jika ada LEBIH DARI SATU rule aktif.
function prioritasKesimpulan($kesimpulan) {
    $urutan = [
        'Kinerja Sangat Baik' => 1,
        'Kinerja Baik'        => 2,
        'Kinerja Cukup'       => 3,
        'Kinerja Kurang'      => 4,
    ];
    return $urutan[$kesimpulan] ?? 99; // kesimpulan tak dikenal ditaruh paling akhir
}

function forwardChaining($fakta, $rules) {
    $rules_aktif    = [];   // menampung SEMUA rule yang kondisinya terpenuhi
    $log_iterasi    = [];

    // Fakta sudah dalam format ['nama_kriteria' => 'nama_nilai']
    $fakta_array = $fakta;

    foreach ($rules as $rule) {
        $kondisi_str     = $rule['kondisi'];
        $kondisi_cocok   = [];
        $kondisi_gagal   = [];

        // Cek apakah kondisi mengandung OR
        if (strpos($kondisi_str, ' OR ') !== false) {
            // Handle OR: cukup satu kondisi terpenuhi
            $kondisi_parts  = explode(' OR ', $kondisi_str);
            $semua_terpenuhi = false;

            foreach ($kondisi_parts as $kondisi) {
                $parts = explode('=', trim($kondisi));
                if (count($parts) == 2) {
                    $kriteria = trim($parts[0]);
                    $nilai    = trim($parts[1]);
                    if (isset($fakta_array[$kriteria]) && $fakta_array[$kriteria] == $nilai) {
                        $semua_terpenuhi = true;
                        $kondisi_cocok[] = trim($kondisi);
                    } else {
                        $kondisi_gagal[] = trim($kondisi);
                    }
                }
            }
        } else {
            // Handle AND: semua kondisi harus terpenuhi
            $kondisi_parts   = explode(' AND ', $kondisi_str);
            $semua_terpenuhi = true;

            foreach ($kondisi_parts as $kondisi) {
                $parts = explode('=', trim($kondisi));
                if (count($parts) == 2) {
                    $kriteria = trim($parts[0]);
                    $nilai    = trim($parts[1]);
                    if (isset($fakta_array[$kriteria]) && $fakta_array[$kriteria] == $nilai) {
                        $kondisi_cocok[] = trim($kondisi);
                    } else {
                        $semua_terpenuhi = false;
                        $kondisi_gagal[] = trim($kondisi);
                    }
                }
            }
        }

        // Log setiap rule untuk jejak proses inferensi (ditampilkan di hasil_evaluasi.php)
        $log_iterasi[] = [
            'kode_rule'  => $rule['kode_rule'],
            'kondisi'    => $kondisi_str,
            'terpenuhi'  => $semua_terpenuhi,
            'cocok'      => $kondisi_cocok,
            'gagal'      => $kondisi_gagal,
            'kesimpulan' => $rule['kesimpulan'],
        ];

        // Kumpulkan SEMUA rule yang terpenuhi (bukan cuma yang pertama)
        if ($semua_terpenuhi) {
            $rules_aktif[] = $rule;
        }
    }

    // ===== Tentukan kesimpulan akhir dari rule-rule yang aktif =====
    if (empty($rules_aktif)) {
        // Fallback eksplisit: tidak ada rule yang cocok sama sekali.
        // Ini kondisi nyata yang perlu terlihat oleh pengguna/penguji, bukan disembunyikan
        // jadi "Kinerja Cukup" seolah-olah itu hasil inferensi yang valid.
        return [
            'kesimpulan'     => 'Tidak dapat ditentukan',
            'rule_cocok'     => null,
            'rules_terpakai' => [],
            'rules_aktif'    => [],
            'log_iterasi'    => $log_iterasi,
            'fakta'          => $fakta_array,
            'penjelasan'     => 'Tidak ada rule pada basis pengetahuan yang cocok dengan kombinasi nilai kriteria yang dimasukkan. Basis aturan perlu ditambah agar mencakup kombinasi ini.',
        ];
    }

    // Urutkan rule aktif berdasarkan prioritas kesimpulan (tertinggi dulu)
    usort($rules_aktif, function ($a, $b) {
        return prioritasKesimpulan($a['kesimpulan']) <=> prioritasKesimpulan($b['kesimpulan']);
    });

    $kesimpulan_akhir = $rules_aktif[0]['kesimpulan'];
    $rules_terpakai   = array_column($rules_aktif, 'kode_rule');

    // Gabungkan deskripsi dari SEMUA rule yang aktif -> jadi penjelasan
    $daftar_penjelasan = [];
    foreach ($rules_aktif as $r) {
        $teks = trim($r['deskripsi'] ?? '');
        if ($teks !== '') {
            $daftar_penjelasan[] = '• [' . $r['kode_rule'] . '] ' . $teks;
        }
    }
    $penjelasan = !empty($daftar_penjelasan)
        ? implode("\n", $daftar_penjelasan)
        : 'Kesimpulan diambil dari rule ' . implode(', ', $rules_terpakai) . '.';

    return [
        'kesimpulan'     => $kesimpulan_akhir,
        'rule_cocok'     => $rules_aktif[0],
        'rules_terpakai' => $rules_terpakai,
        'rules_aktif'    => $rules_aktif,
        'log_iterasi'    => $log_iterasi,
        'fakta'          => $fakta_array,
        'penjelasan'     => $penjelasan,
    ];
}

// ===== PROSES EVALUASI =====
if (isset($_POST['evaluasi'])) {
    $id_pegawai = mysqli_real_escape_string($koneksi, $_POST['id_pegawai']);
    $tanggal_evaluasi = date('Y-m-d');
    
    // Mapping kode_kriteria -> nama_kriteria
    $map_query = mysqli_query($koneksi, "SELECT kode_kriteria, nama_kriteria FROM kriteria");
    $map_kriteria = [];
    while ($m = mysqli_fetch_assoc($map_query)) {
        $map_kriteria[$m['kode_kriteria']] = $m['nama_kriteria'];
    }

    // Kumpulkan fakta dari form
    $fakta = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'kriteria_') === 0) {
            $kode = str_replace('kriteria_', '', $key);
            if (isset($map_kriteria[$kode])) {
                $fakta[$map_kriteria[$kode]] = $value;
            }
        }
    }
    
    // Ambil semua rule
    $rules_query = mysqli_query($koneksi, "SELECT * FROM rule");
    $rules = [];
    while ($rule = mysqli_fetch_assoc($rules_query)) {
        $rules[] = $rule;
    }
    
    // Jalankan Forward Chaining
    $hasil = forwardChaining($fakta, $rules);
    
    // Simpan hasil evaluasi
    // Catatan: 'Tidak dapat ditentukan' adalah kondisi valid, bukan error yang perlu
    // ditutupi dengan default 'Kinerja Cukup' — ini menandakan basis aturan belum
    // mencakup kombinasi nilai kriteria tersebut, dan perlu terlihat oleh pengguna.
    $kesimpulan_akhir = $hasil['kesimpulan'];
    $rules_digunakan  = implode(', ', $hasil['rules_terpakai']);
    $penjelasan_akhir = $hasil['penjelasan'];
    $nilai_kriteria_json = json_encode($fakta);
    
    // Hitung skor total
    $skor_total = 0;
    $total_bobot = 0;
    
    // Ambil bobot kriteria
    $bobot_query = mysqli_query($koneksi, "SELECT * FROM kriteria");
    $bobot_kriteria = [];
    while ($b = mysqli_fetch_assoc($bobot_query)) {
        $bobot_kriteria[$b['nama_kriteria']] = $b['bobot'];
    }
    
    // Ambil skor nilai
    $skor_nilai = [];
    $nilai_skor_query = mysqli_query($koneksi, "SELECT * FROM nilai_kondisi");
    while ($n = mysqli_fetch_assoc($nilai_skor_query)) {
        $skor_nilai[$n['nama_nilai']] = $n['skor'];
    }
    
    // Hitung skor berdasarkan bobot dan nilai
    foreach ($fakta as $kriteria => $nilai) {
        if (isset($bobot_kriteria[$kriteria]) && isset($skor_nilai[$nilai])) {
            $skor_total += ($bobot_kriteria[$kriteria] * $skor_nilai[$nilai]) / 5;
            $total_bobot += $bobot_kriteria[$kriteria];
        }
    }
    
    if ($total_bobot > 0) {
        $skor_total = ($skor_total / $total_bobot) * 100;
    }
    
    // Buat rekomendasi berdasarkan hasil
    $rekomendasi = '';
    if ($kesimpulan_akhir == 'Kinerja Sangat Baik') {
        $rekomendasi = 'Pegawai layak dipertahankan dan dipertimbangkan untuk pengembangan karier.';
    } elseif ($kesimpulan_akhir == 'Kinerja Baik') {
        $rekomendasi = 'Pegawai memiliki kinerja baik, pertahankan dan berikan motivasi untuk peningkatan.';
    } elseif ($kesimpulan_akhir == 'Kinerja Cukup') {
        $rekomendasi = 'Pegawai perlu diberikan pelatihan dan pendampingan untuk meningkatkan kinerja.';
    } elseif ($kesimpulan_akhir == 'Kinerja Kurang') {
        $rekomendasi = 'Pegawai perlu evaluasi lebih lanjut dan perbaikan kinerja.';
    } else {
        // Kesimpulan == 'Tidak dapat ditentukan'
        $rekomendasi = 'Basis aturan belum mencakup kombinasi nilai kriteria ini. Disarankan menambah rule baru pada menu Basis Aturan (Rule) sebelum kesimpulan dapat ditarik.';
    }

    // Simpan ke database menggunakan prepared statement (mencegah SQL injection,
    // sekaligus aman menyimpan teks penjelasan yang mengandung tanda kutip / baris baru)
    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO evaluasi (id_pegawai, tanggal_evaluasi, hasil, rule_digunakan, penjelasan, rekomendasi, skor_total, nilai_kriteria)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param(
        $stmt, 'ssssssds',
        $id_pegawai, $tanggal_evaluasi, $kesimpulan_akhir, $rules_digunakan,
        $penjelasan_akhir, $rekomendasi, $skor_total, $nilai_kriteria_json
    );
    $simpan = mysqli_stmt_execute($stmt);

    if ($simpan) {
        echo "<script>
            alert('Evaluasi berhasil!');
            document.location='hasil_evaluasi.php?id=" . mysqli_insert_id($koneksi) . "';
        </script>";
    } else {
        echo "<script>
            alert('Evaluasi gagal!');
            document.location='evaluasi.php';
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Evaluasi Pegawai</title>

    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:200,300,400,600,700,800" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        body, html {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(120deg, #f5f7fa, #c3cfe2);
            color: #4a4a4a;
        }
        .sidebar {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: #ffffff;
        }
        .sidebar .nav-item .nav-link {
            color: #ffffff;
            font-weight: 500;
        }
        .sidebar .nav-item.active .nav-link,
        .sidebar .nav-item .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }
        .navbar {
            background: #ffffff;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- ===== SIDEBAR ===== -->
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-laugh-wink"></i>
                </div>
                <div class="sidebar-brand-text mx-3">
                     <?= ($_SESSION['role']=="admin") ? "Admin" : "User"; ?>
                </div>
            </a>
            <hr class="sidebar-divider my-0">
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
             <?php if($_SESSION['role']=="admin"){ ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Master Data</div>
            <?php } ?>
            
             <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#pegawaiMenu">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Pegawai</span>
                </a>
                <div id="pegawaiMenu" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="pegawai.php">Data Pegawai</a>
                        <a class="collapse-item" href="tambah_pegawai.php">Tambah Pegawai</a>
                    </div>
                </div>
            </li>

            <?php if($_SESSION['role']=="admin"){ ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Sistem Pakar</div>
            <?php } ?>

            <?php if($_SESSION['role'] == 'admin'){ ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#kriteriaMenu">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Kriteria</span>
                </a>
                <div id="kriteriaMenu" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="kriteria.php">Data Kriteria</a>
                        <a class="collapse-item" href="tambah_kriteria.php">Tambah Kriteria</a>
                    </div>
                </div>
            </li>
            <?php } ?>

            <?php if($_SESSION['role'] == 'admin'){ ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#nilaiMenu">
                    <i class="fas fa-fw fa-sliders-h"></i>
                    <span>Nilai Kondisi</span>
                </a>
                <div id="nilaiMenu" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="nilai_kondisi.php">Data Nilai</a>
                        <a class="collapse-item" href="tambah_nilai.php">Tambah Nilai</a>
                    </div>
                </div>
            </li>
            <?php } ?>
            

            <?php if($_SESSION['role'] == 'admin'){ ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#ruleMenu">
                    <i class="fas fa-fw fa-code-branch"></i>
                    <span>Basis Aturan (Rule)</span>
                </a>
                <div id="ruleMenu" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="rule.php">Data Rule</a>
                        <a class="collapse-item" href="tambah_rule.php">Tambah Rule</a>
                    </div>
                </div>
            </li>
            <?php } ?>

            <?php if($_SESSION['role']=="admin"){ ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Evaluasi</div>
            <?php } ?>

            <li class="nav-item active">
                <a class="nav-link" href="evaluasi.php">
                    <i class="fas fa-fw fa-check-circle"></i>
                    <span>Evaluasi Pegawai</span>
                </a>

            <li class="nav-item">
                <a class="nav-link" href="hasil_evaluasi.php">
                    <i class="fas fa-fw fa-chart-bar"></i>
                    <span>Hasil Evaluasi</span>
                </a>
            </li>

            <?php if($_SESSION['role']=="admin"){ ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Laporan</div>
            <?php } ?>

            <li class="nav-item">
                <a class="nav-link" href="laporan.php">
                    <i class="fas fa-fw fa-file-alt"></i>
                    <span>Laporan</span>
                </a>
            </li>

            <?php if($_SESSION['role']=="admin"){ ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Pengaturan</div>
            <?php } ?>

            <?php if($_SESSION['role']=='admin'){ ?>

            <li class="nav-item">
                <a class="nav-link" href="user.php">
                    <i class="fas fa-fw fa-user-cog"></i>
                    <span>Manajemen User</span>
                </a>
            </li>

            <?php } ?>

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= $_SESSION['nama_user'] ?></span>
                                <img class="img-profile rounded-circle" src="../assets/img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Evaluasi Kinerja Pegawai</h1>
                    <p class="mb-4">Metode Forward Chaining - Sistem Pakar Evaluasi Kinerja</p>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Form Evaluasi</h6>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <!-- Pilih Pegawai -->
                                <div class="form-group">
                                    <label>Pilih Pegawai</label>
                                    <select name="id_pegawai" class="form-control col-6" required>
                                        <option value="">-- Pilih Pegawai --</option>
                                        <?php 
                                        mysqli_data_seek($pegawai, 0);
                                        while($p = mysqli_fetch_array($pegawai)): ?>
                                        <option value="<?= $p['id'] ?>"><?= $p['nip'] ?> - <?= $p['nama'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <!-- Penilaian Kriteria -->
                                <div class="form-section">
                                    <h6 class="font-weight-bold">Penilaian Kriteria</h6>
                                    <p class="text-muted small">Pilih nilai untuk setiap kriteria kinerja pegawai</p>
                                    
                                    <div class="row">
                                        <?php 
                                        mysqli_data_seek($kriteria_list, 0);
                                        while($k = mysqli_fetch_array($kriteria_list)): ?>
                                        <div class="col-md-6 mb-3">
                                            <label><?= $k['kode_kriteria'] ?> - <?= $k['nama_kriteria'] ?></label>
                                            <select name="kriteria_<?= $k['kode_kriteria'] ?>" class="form-control" required>
                                                <option value="">-- Pilih --</option>
                                                <?php 
                                                mysqli_data_seek($nilai_list, 0);
                                                while($n = mysqli_fetch_array($nilai_list)): ?>
                                                <option value="<?= $n['nama_nilai'] ?>"><?= $n['nama_nilai'] ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                            <small class="text-muted"><?= $k['deskripsi'] ?></small>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>

                                <button type="submit" name="evaluasi" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Proses Evaluasi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Sistem Evaluasi Kinerja 2026</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- ===== LOGOUT MODAL ===== -->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Yakin Ingin Keluar?</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="hapusSession.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../assets/js/sb-admin-2.min.js"></script>
</body>
</html>