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

// Cek apakah ID nilai ada
if (!isset($_GET['id'])) {
    echo "<script>
        alert('ID Nilai tidak valid!');
        document.location='nilai_kondisi.php';
    </script>";
    exit();
}

$id = mysqli_real_escape_string($koneksi, $_GET['id']);
$query = mysqli_query($koneksi, "SELECT * FROM nilai_kondisi WHERE id = '$id'");
$nilai = mysqli_fetch_assoc($query);

// Cek apakah data nilai ditemukan
if (!$nilai) {
    echo "<script>
        alert('Nilai tidak ditemukan!');
        document.location='nilai_kondisi.php';
    </script>";
    exit();
}

// Proses update data nilai
if (isset($_POST['update'])) {
    $update = mysqli_query($koneksi,
        "UPDATE nilai_kondisi SET 
        nama_nilai = '$_POST[nama_nilai]',
        keterangan = '$_POST[keterangan]',
        skor = '$_POST[skor]'
        WHERE id = '$id'"
    );

    if ($update) {
        echo "<script>
            alert('Update data sukses!');
            document.location='nilai_kondisi.php';
        </script>";
    } else {
        echo "<script>
            alert('Update data gagal!');
            document.location='edit_nilai.php?id=$id';
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
    <title>Edit Nilai Kondisi</title>

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
                <div class="sidebar-brand-text mx-3">Admin</div>
            </a>
            <hr class="sidebar-divider my-0">
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Master Data</div>
            
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

            <hr class="sidebar-divider">
            <div class="sidebar-heading">Sistem Pakar</div>

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

            <li class="nav-item active">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#nilaiMenu">
                    <i class="fas fa-fw fa-sliders-h"></i>
                    <span>Nilai Kondisi</span>
                </a>
                <div id="nilaiMenu" class="collapse show" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="nilai_kondisi.php">Data Nilai</a>
                        <a class="collapse-item" href="tambah_nilai.php">Tambah Nilai</a>
                    </div>
                </div>
            </li>

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

            <hr class="sidebar-divider">
            <div class="sidebar-heading">Evaluasi</div>

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

            <hr class="sidebar-divider">
            <div class="sidebar-heading">Laporan</div>

            <li class="nav-item">
                <a class="nav-link" href="laporan.php">
                    <i class="fas fa-fw fa-file-alt"></i>
                    <span>Laporan</span>
                </a>
            </li>

            <hr class="sidebar-divider">
            <div class="sidebar-heading">Pengaturan</div>

            <li class="nav-item">
                <a class="nav-link" href="user.php">
                    <i class="fas fa-fw fa-user-cog"></i>
                    <span>Manajemen User</span>
                </a>
            </li>

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
                    <h1 class="h3 mb-2 text-gray-800">Edit Nilai Kondisi</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <a href="nilai_kondisi.php" class="btn btn-success btn-icon-split">
                                <span class="text">Kembali</span>
                            </a>
                        </div>
                        <div class="card-body">
                            <form method="post" class="user">
                                <div class="form-group">
                                    <input type="text" name="nama_nilai" class="form-control form-control-user col-6" 
                                           placeholder="Nama Nilai" value="<?= $nilai['nama_nilai'] ?>" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" name="keterangan" class="form-control form-control-user col-6" 
                                           placeholder="Keterangan" value="<?= $nilai['keterangan'] ?>" required>
                                </div>
                                <div class="form-group">
                                    <input type="number" name="skor" class="form-control form-control-user col-6" 
                                           placeholder="Skor" step="0.01" min="0" max="100" value="<?= $nilai['skor'] ?>" required>
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="update" class="btn btn-primary btn-icon-split">
                                        <span class="text">Update</span>
                                    </button>
                                </div>
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