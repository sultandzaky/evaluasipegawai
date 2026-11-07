<?php
include '../koneksi.php';
session_start();

if ($_SESSION['status'] != 'login') {
    session_unset();
    session_destroy();
    header('location:../');
    exit();
}

// Statistik
$total_pegawai = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pegawai"))['total'];
$total_evaluasi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM evaluasi"))['total'];
$total_rule = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM rule"))['total'];
$total_kriteria = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kriteria"))['total'];

// Distribusi hasil evaluasi
$resDistribusi = mysqli_query($koneksi, 
    "SELECT hasil, COUNT(*) as jumlah FROM evaluasi GROUP BY hasil"
);
$distribusi = [];
while ($row = mysqli_fetch_assoc($resDistribusi)) {
    $distribusi[] = $row;
}

// Warna badge per kategori hasil (dipakai juga untuk warna chart)
function kelasHasil($hasil) {
    if (strpos($hasil, 'Sangat Baik') !== false) return ['badge' => 'success', 'hex' => '#1cc88a'];
    if (strpos($hasil, 'Baik') !== false) return ['badge' => 'info', 'hex' => '#36b9cc'];
    if (strpos($hasil, 'Cukup') !== false) return ['badge' => 'warning', 'hex' => '#f6c23e'];
    if (strpos($hasil, 'Kurang') !== false) return ['badge' => 'danger', 'hex' => '#e74a3b'];
    return ['badge' => 'secondary', 'hex' => '#858796']; // "Tidak dapat ditentukan"
}

// 10 Pegawai terbaik berdasarkan skor
$terbaik = mysqli_query($koneksi,
    "SELECT e.*, p.nama, p.nip, j.jabatan 
    FROM evaluasi e
    JOIN pegawai p ON e.id_pegawai = p.id
    JOIN jabatan j ON p.id_jabatan = j.id
    ORDER BY e.skor_total DESC
    LIMIT 10"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan</title>

    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
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
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        .card-icon {
            font-size: 2.5rem;
            color: rgba(50, 115, 220, 0.8);
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
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

              <?php if($_SESSION['role']=="admin"){ ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Laporan</div>
            <?php } ?>

            <li class="nav-item active">
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
                    <h1 class="h3 mb-1 text-gray-800">Laporan Sistem Evaluasi Kinerja</h1>
                    <p class="text-muted mb-4">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        Laporan dibuat pada <?= date('d F Y, H:i') ?> WIB
                    </p>

                    <!-- Statistik -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="card-title text-primary text-uppercase mb-1">Total Pegawai</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_pegawai ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="card-title text-success text-uppercase mb-1">Total Evaluasi</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_evaluasi ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="card-title text-info text-uppercase mb-1">Total Rule</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_rule ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-code-branch card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="card-title text-warning text-uppercase mb-1">Total Kriteria</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_kriteria ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-list card-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Distribusi Hasil Evaluasi -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Distribusi Hasil Evaluasi</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($distribusi)): ?>
                                    <div class="row align-items-center">
                                        <div class="col-md-5 text-center mb-3 mb-md-0">
                                            <canvas id="chartDistribusi" width="180" height="180"></canvas>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="table-responsive">
                                                <table class="table table-borderless mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Kategori</th>
                                                            <th>Jumlah</th>
                                                            <th>Persentase</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $total = $total_evaluasi > 0 ? $total_evaluasi : 1;
                                                        foreach ($distribusi as $d):
                                                            $k = kelasHasil($d['hasil']);
                                                        ?>
                                                        <tr>
                                                            <td><span class="badge badge-<?= $k['badge'] ?>"><?= htmlspecialchars($d['hasil']) ?></span></td>
                                                            <td><?= $d['jumlah'] ?></td>
                                                            <td><?= number_format(($d['jumlah'] / $total) * 100, 1) ?>%</td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center mb-0 py-4">Belum ada data evaluasi untuk ditampilkan.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">10 Pegawai dengan Skor Terbaik</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nama</th>
                                                    <th>Jabatan</th>
                                                    <th>Skor</th>
                                                    <th>Hasil</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $rank = 1; while($t = mysqli_fetch_array($terbaik)): $k = kelasHasil($t['hasil']); ?>
                                                <tr>
                                                    <td class="font-weight-bold text-gray-600"><?= $rank++ ?></td>
                                                    <td><?= htmlspecialchars($t['nama']) ?></td>
                                                    <td><?= htmlspecialchars($t['jabatan']) ?></td>
                                                    <td style="min-width: 110px;">
                                                        <div class="d-flex align-items-center">
                                                            <span class="mr-2"><?= number_format($t['skor_total'], 2) ?>%</span>
                                                        </div>
                                                        <div class="progress" style="height: 6px; border-radius: 6px;">
                                                            <div class="progress-bar bg-<?= $k['badge'] ?>" role="progressbar"
                                                                 style="width: <?= min(100, $t['skor_total']) ?>%; border-radius: 6px;"></div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?= $k['badge'] ?>"><?= htmlspecialchars($t['hasil']) ?></span>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Cetak Laporan -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4" style="background: linear-gradient(45deg, #6a11cb, #2575fc);">
                                <div class="card-body text-center py-4">
                                    <h5 class="text-white font-weight-bold mb-1">Cetak Laporan</h5>
                                    <p class="text-white-50 mb-3">Cetak laporan dalam format PDF atau Excel</p>
                                    <a href="cetak_laporan.php" target="_blank" class="btn btn-light btn-lg mr-2 font-weight-bold text-danger">
                                        <i class="fas fa-file-pdf"></i> Cetak PDF
                                    </a>
                                    <a href="export_excel.php" class="btn btn-light btn-lg font-weight-bold text-success">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </a>
                                </div>
                            </div>
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

    <!-- Logout Modal -->
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

    <?php if (!empty($distribusi)): ?>
    <!-- Chart.js untuk Distribusi Hasil Evaluasi -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const ctxDistribusi = document.getElementById('chartDistribusi').getContext('2d');
        new Chart(ctxDistribusi, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($distribusi, 'hasil')) ?>,
                datasets: [{
                    data: <?= json_encode(array_map('intval', array_column($distribusi, 'jumlah'))) ?>,
                    backgroundColor: <?= json_encode(array_map(fn($d) => kelasHasil($d['hasil'])['hex'], $distribusi)) ?>,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: { legend: { display: false } }
            }
        });
    </script>
    <?php endif; ?>
</body>

</html>