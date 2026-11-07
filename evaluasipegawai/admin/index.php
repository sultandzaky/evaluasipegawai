<?php
include '../koneksi.php';      // Panggil koneksi database
session_start();               // Mulai session

// Cek apakah user sudah login
if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login') {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// ===== HELPER: cek tabel/kolom agar query aman meski struktur DB berbeda =====
function tabelAda($koneksi, $namaTabel) {
    $hasil = mysqli_query($koneksi, "SHOW TABLES LIKE '" . mysqli_real_escape_string($koneksi, $namaTabel) . "'");
    return $hasil && mysqli_num_rows($hasil) > 0;
}
function kolomAda($koneksi, $namaTabel, $namaKolom) {
    $hasil = mysqli_query($koneksi, "SHOW COLUMNS FROM `$namaTabel` LIKE '" . mysqli_real_escape_string($koneksi, $namaKolom) . "'");
    return $hasil && mysqli_num_rows($hasil) > 0;
}

// ===== AMBIL STATISTIK =====
// Total Pegawai
$pegawai = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pegawai");
$totalPegawai = $pegawai ? mysqli_fetch_assoc($pegawai)['total'] : 0;

// Total Evaluasi
$evaluasi = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM evaluasi");
$totalEvaluasi = $evaluasi ? mysqli_fetch_assoc($evaluasi)['total'] : 0;

// Total Laporan
// Catatan: jika belum ada tabel 'laporan' terpisah, dihitung dari jumlah evaluasi yang sudah selesai.
if (tabelAda($koneksi, 'laporan')) {
    $laporan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM laporan");
    $totalLaporan = $laporan ? mysqli_fetch_assoc($laporan)['total'] : 0;
} else {
    $totalLaporan = $totalEvaluasi;
}

// Progress Evaluasi = persentase pegawai yang sudah dievaluasi
$progressEvaluasi = ($totalPegawai > 0) ? round(($totalEvaluasi / $totalPegawai) * 100) : 0;
if ($progressEvaluasi > 100) $progressEvaluasi = 100;

// ===== AKTIVITAS TERBARU =====
// Catatan: sesuaikan nama kolom id_pegawai/id_evaluasi jika berbeda di tabel Anda.
$aktivitasTerbaru = [];
if (tabelAda($koneksi, 'evaluasi') && kolomAda($koneksi, 'evaluasi', 'id_pegawai') && kolomAda($koneksi, 'pegawai', 'nama_pegawai')) {
    $kolomUrut = kolomAda($koneksi, 'evaluasi', 'id_evaluasi') ? 'e.id_evaluasi' : '1';
    $sqlAktivitas = "SELECT p.nama_pegawai
                      FROM evaluasi e
                      JOIN pegawai p ON p.id_pegawai = e.id_pegawai
                      ORDER BY $kolomUrut DESC
                      LIMIT 5";
    $resAktivitas = mysqli_query($koneksi, $sqlAktivitas);
    if ($resAktivitas) {
        while ($row = mysqli_fetch_assoc($resAktivitas)) {
            $aktivitasTerbaru[] = $row['nama_pegawai'];
        }
    }
}

// ===== DATA GRAFIK EVALUASI =====
// Catatan: sesuaikan nama kolom 'hasil' dengan kolom status/predikat hasil evaluasi pada tabel Anda.
$grafikLabel = [];
$grafikData = [];
if (tabelAda($koneksi, 'evaluasi') && kolomAda($koneksi, 'evaluasi', 'hasil')) {
    $resGrafik = mysqli_query($koneksi, "SELECT hasil, COUNT(*) as total FROM evaluasi GROUP BY hasil");
    if ($resGrafik) {
        while ($row = mysqli_fetch_assoc($resGrafik)) {
            $grafikLabel[] = $row['hasil'];
            $grafikData[] = (int) $row['total'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard <?= ($_SESSION['role']=='admin') ? 'Admin' : 'Pakar' ?> - Evaluasi Kinerja</title>

    <!-- CSS Libraries -->
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:200,300,400,600,700,800" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body, html {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(120deg, #f5f7fa, #c3cfe2);
            height: 100%;
            color: #4a4a4a;
        }
        .sidebar {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: #ffffff;
        }
        .sidebar .nav-item .nav-link {
            color: #ffffff;
            font-weight: 500;
            transition: all 0.3s ease-in-out;
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #4a4a4a;
        }
        .card-icon {
            font-size: 2.5rem;
            color: rgba(50, 115, 220, 0.8);
        }
        .quick-action-card {
            cursor: pointer;
        }
        .quick-action-icon {
            font-size: 2rem;
        }
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
            color: #4a4a4a;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .bg-gradient-primary {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- ===== SIDEBAR ===== -->
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
            <!-- Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-laugh-wink"></i>
                </div>
                <div class="sidebar-brand-text mx-3">
                <?= ($_SESSION['role']=='admin') ? 'ADMIN' : 'USER'; ?>
                </div>
            </a>
            <hr class="sidebar-divider my-0">
            
            <!-- Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Master Data -->
            <?php if($_SESSION['role']=="admin"){ ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Master Data</div>
            <?php } ?>
            
            <!-- Menu Pegawai -->
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
            
            <!-- Sistem Pakar -->
              <?php if($_SESSION['role']=="admin"){ ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Sistem Pakar</div>
            <?php } ?>

        <?php if($_SESSION['role']=='admin'){ ?>
            <!-- Menu Kriteria -->
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

            <?php if($_SESSION['role']=='admin'){ ?>

            <!-- Menu Nilai Kondisi -->
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

            <?php if($_SESSION['role']=='admin'){ ?>

            <!-- Menu Rule -->
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
            
            <!-- Evaluasi -->
            <?php if($_SESSION['role']=="admin"){ ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Evaluasi</div>
            <?php } ?>
            <li class="nav-item">
                <a class="nav-link" href="evaluasi.php">
                    <i class="fas fa-fw fa-check-circle"></i>
                    <span>Evaluasi Pegawai</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="hasil_evaluasi.php">
                    <i class="fas fa-fw fa-chart-bar"></i>
                    <span>Hasil Evaluasi</span>
                </a>
            </li>
            
            <!-- Laporan -->
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
            
            <!-- Pengaturan -->
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

            <!-- Toggle Sidebar -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>

        <!-- ===== CONTENT WRAPPER ===== -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
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

                <!-- Main Content -->
                <div class="container-fluid">

                    <!-- ===== HEADER DASHBOARD ===== -->
                    <div class="card shadow border-0 mb-4">
                        <div class="card-body">
                            <h3 class="font-weight-bold text-primary mb-1">
                                Selamat Datang, <?= htmlspecialchars($_SESSION['nama_user']) ?> 👋
                            </h3>
                            <h5 class="text-dark mb-2">Dashboard Sistem Pakar Evaluasi Kinerja Pegawai</h5>
                            <p class="text-muted mb-0">
                                Login sebagai <b><?= ($_SESSION['role']=='admin') ? 'Administrator' : 'User' ?></b>.
                                Sistem ini membantu proses evaluasi kinerja pegawai menggunakan metode <i>Forward Chaining</i>.
                            </p>
                        </div>
                    </div>

                    <!-- ===== STATISTIK CARDS (sama untuk semua role) ===== -->
                    <div class="row">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="card-title text-primary text-uppercase mb-1">Total Pegawai</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalPegawai ?></div>
                                            <div class="text-xs text-muted mt-1">Terdaftar</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="card-title text-success text-uppercase mb-1">Total Evaluasi</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalEvaluasi ?></div>
                                            <div class="text-xs text-muted mt-1">Selesai</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="card-title text-info text-uppercase mb-1">Total Laporan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalLaporan ?></div>
                                            <div class="text-xs text-muted mt-1">Siap Cetak</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-file-alt card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== PROGRESS EVALUASI (sama untuk semua role) ===== -->
                    <div class="card shadow border-0 mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="font-weight-bold text-gray-800">Progress Evaluasi</span>
                                <span class="font-weight-bold text-primary"><?= $progressEvaluasi ?>%</span>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 10px;">
                                <div class="progress-bar bg-gradient-primary" role="progressbar"
                                     style="width: <?= $progressEvaluasi ?>%; border-radius: 10px;"
                                     aria-valuenow="<?= $progressEvaluasi ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== AKSI CEPAT (berbeda sesuai role) ===== -->
                    <h6 class="text-gray-800 font-weight-bold mb-3">Aksi Cepat</h6>
                    <div class="row">
                        <?php if ($_SESSION['role'] == 'admin'): ?>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="pegawai.php" class="text-decoration-none">
                                    <div class="card quick-action-card shadow h-100 py-3">
                                        <div class="card-body text-center">
                                            <i class="fas fa-user-friends quick-action-icon text-primary"></i>
                                            <div class="font-weight-bold text-gray-800 mt-2">Kelola Pegawai</div>
                                            <div class="text-xs text-muted">Data Pegawai</div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="evaluasi.php" class="text-decoration-none">
                                    <div class="card quick-action-card shadow h-100 py-3">
                                        <div class="card-body text-center">
                                            <i class="fas fa-user-check quick-action-icon text-success"></i>
                                            <div class="font-weight-bold text-gray-800 mt-2">Evaluasi Pegawai</div>
                                            <div class="text-xs text-muted">Mulai Evaluasi</div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="hasil_evaluasi.php" class="text-decoration-none">
                                    <div class="card quick-action-card shadow h-100 py-3">
                                        <div class="card-body text-center">
                                            <i class="fas fa-chart-bar quick-action-icon text-info"></i>
                                            <div class="font-weight-bold text-gray-800 mt-2">Hasil Evaluasi</div>
                                            <div class="text-xs text-muted">Lihat Detail</div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="laporan.php" class="text-decoration-none">
                                    <div class="card quick-action-card shadow h-100 py-3">
                                        <div class="card-body text-center">
                                            <i class="fas fa-file-alt quick-action-icon text-warning"></i>
                                            <div class="font-weight-bold text-gray-800 mt-2">Laporan</div>
                                            <div class="text-xs text-muted">Cetak Laporan</div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                        <?php else: ?>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="evaluasi.php" class="text-decoration-none">
                                    <div class="card quick-action-card shadow h-100 py-3">
                                        <div class="card-body text-center">
                                            <i class="fas fa-user-check quick-action-icon text-primary"></i>
                                            <div class="font-weight-bold text-gray-800 mt-2">Evaluasi Pegawai</div>
                                            <div class="text-xs text-muted">Mulai Evaluasi</div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="hasil_evaluasi.php" class="text-decoration-none">
                                    <div class="card quick-action-card shadow h-100 py-3">
                                        <div class="card-body text-center">
                                            <i class="fas fa-chart-bar quick-action-icon text-success"></i>
                                            <div class="font-weight-bold text-gray-800 mt-2">Hasil Evaluasi</div>
                                            <div class="text-xs text-muted">Lihat Detail</div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="laporan.php" class="text-decoration-none">
                                    <div class="card quick-action-card shadow h-100 py-3">
                                        <div class="card-body text-center">
                                            <i class="fas fa-file-alt quick-action-icon text-info"></i>
                                            <div class="font-weight-bold text-gray-800 mt-2">Laporan</div>
                                            <div class="text-xs text-muted">Cetak Laporan</div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-4">
                                <a href="hasil_evaluasi.php?riwayat=1" class="text-decoration-none">
                                    <div class="card quick-action-card shadow h-100 py-3">
                                        <div class="card-body text-center">
                                            <i class="fas fa-history quick-action-icon text-warning"></i>
                                            <div class="font-weight-bold text-gray-800 mt-2">Riwayat Evaluasi</div>
                                            <div class="text-xs text-muted">Histori Evaluasi</div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                        <?php endif; ?>
                    </div>

                    <!-- ===== GRAFIK & AKTIVITAS TERBARU (sama untuk semua role) ===== -->
                    <div class="row">
                        <div class="col-lg-7 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header bg-white font-weight-bold text-gray-800">
                                    <i class="fas fa-chart-line mr-1 text-primary"></i> Grafik Evaluasi
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($grafikData)): ?>
                                        <canvas id="grafikEvaluasi" height="220"></canvas>
                                    <?php else: ?>
                                        <p class="text-muted text-center mb-0 py-5">Belum ada data evaluasi untuk ditampilkan pada grafik.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header bg-white font-weight-bold text-gray-800">
                                    <i class="fas fa-clipboard-list mr-1 text-primary"></i> Aktivitas Terbaru
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($aktivitasTerbaru)): ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($aktivitasTerbaru as $namaPegawai): ?>
                                                <li class="activity-item">
                                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                                    <?= htmlspecialchars($namaPegawai) ?> selesai dievaluasi
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted text-center mb-0 py-5">Belum ada aktivitas terbaru.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer -->
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

    <!-- JavaScript -->
    <script src="../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../assets/js/sb-admin-2.min.js"></script>

    <?php if (!empty($grafikData)): ?>
    <!-- Chart.js untuk Grafik Evaluasi -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const ctxGrafikEvaluasi = document.getElementById('grafikEvaluasi').getContext('2d');
        new Chart(ctxGrafikEvaluasi, {
            type: 'bar',
            data: {
                labels: <?= json_encode($grafikLabel) ?>,
                datasets: [{
                    label: 'Jumlah Pegawai',
                    data: <?= json_encode($grafikData) ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>