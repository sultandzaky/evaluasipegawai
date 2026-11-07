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

// Proses hapus data rule
if (isset($_GET['hal']) == 'hapus') {
    $hapus = mysqli_query($koneksi, "DELETE FROM rule WHERE id = '$_GET[id]'");
    if ($hapus) {
        echo "<script>
            alert('Hapus data sukses!');
            document.location='rule.php';
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
    <title>Data Rule</title>

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
        .rule-kondisi {
            font-family: monospace;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
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

            <li class="nav-item active">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#ruleMenu">
                    <i class="fas fa-fw fa-code-branch"></i>
                    <span>Basis Aturan (Rule)</span>
                </a>
                <div id="ruleMenu" class="collapse show" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item active" href="rule.php">Data Rule</a>
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
                    <h1 class="h3 mb-2 text-gray-800">Data Rule (Basis Aturan)</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <a href="tambah_rule.php" class="btn btn-primary btn-icon-split">
                                <span class="text">Tambah Data</span>
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode</th>
                                            <th>Kondisi</th>
                                            <th>Kesimpulan</th>
                                            <th>Deskripsi</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $no = 1;
                                    $tampil = mysqli_query($koneksi, "SELECT * FROM rule ORDER BY kode_rule");
                                    while($data = mysqli_fetch_array($tampil)):
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= $data['kode_rule'] ?></td>
                                        <td><span class="rule-kondisi"><?= $data['kondisi'] ?></span></td>
                                        <td><span class="badge badge-success"><?= $data['kesimpulan'] ?></span></td>
                                        <td><?= $data['deskripsi'] ?></td>
                                        <td>
                                            <a href="edit_rule.php?id=<?= $data['id'] ?>" class="btn btn-warning btn-circle btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a onclick="return confirm('Apakah Anda Yakin Ingin Menghapus Data?')" 
                                               href="rule.php?hal=hapus&id=<?= $data['id'] ?>" 
                                               class="btn btn-danger btn-circle btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </a>
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