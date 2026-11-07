<?php

include '../koneksi.php';
session_start();

// ==========================================================
// CEK LOGIN
// ==========================================================
if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login') {

    session_unset();
    session_destroy();

    header('location:../');
    exit();
}

// ==========================================================
// AMBIL ID EVALUASI
// ==========================================================
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Variabel awal
$evaluasi = null;
$nilai_kriteria = [];
$rule_aktif_detail = [];
$kode_rule_list = [];
$query = null;

// ==========================================================
// TAMPILKAN DETAIL EVALUASI
// ==========================================================
if ($id > 0) {

    // ------------------------------------------------------
    // Ambil data evaluasi berdasarkan ID
    // ------------------------------------------------------
    $query = mysqli_query(
        $koneksi,
        "SELECT 
            e.*,
            p.nama,
            p.nip,
            j.jabatan,
            b.nama_bidang
        FROM evaluasi e
        JOIN pegawai p ON e.id_pegawai = p.id
        JOIN jabatan j ON p.id_jabatan = j.id
        LEFT JOIN bidang b ON p.id_bidang = b.id
        WHERE e.id = " . $id
    );

    $evaluasi = mysqli_fetch_assoc($query);

    // ------------------------------------------------------
    // Jika data tidak ditemukan
    // ------------------------------------------------------
    if (!$evaluasi) {

        echo "<script>
                alert('Data tidak ditemukan!');
                document.location='hasil_evaluasi.php';
              </script>";

        exit();
    }

    // ------------------------------------------------------
    // Decode JSON nilai kriteria
    // ------------------------------------------------------
    $nilai_kriteria = json_decode(
        $evaluasi['nilai_kriteria'],
        true
    );

    if (!is_array($nilai_kriteria)) {
        $nilai_kriteria = [];
    }

    // ------------------------------------------------------
    // Ambil rule yang aktif
    // Contoh:
    // R06, R13, R21
    // ------------------------------------------------------
    $rule_aktif_detail = [];

    $kode_rule_list = array_filter(
        array_map(
            'trim',
            explode(
                ',',
                $evaluasi['rule_digunakan'] ?? ''
            )
        )
    );

    if (!empty($kode_rule_list)) {

        $placeholder = implode(
            ',',
            array_fill(
                0,
                count($kode_rule_list),
                '?'
            )
        );

        $stmt_rule = mysqli_prepare(
            $koneksi,
            "SELECT *
             FROM rule
             WHERE kode_rule IN ($placeholder)"
        );

        if ($stmt_rule) {

            $types = str_repeat(
                's',
                count($kode_rule_list)
            );

            mysqli_stmt_bind_param(
                $stmt_rule,
                $types,
                ...$kode_rule_list
            );

            mysqli_stmt_execute($stmt_rule);

            $result_rule = mysqli_stmt_get_result(
                $stmt_rule
            );

            while ($r = mysqli_fetch_assoc($result_rule)) {
                $rule_aktif_detail[] = $r;
            }

            mysqli_stmt_close($stmt_rule);
        }

        // --------------------------------------------------
        // Urutkan rule sesuai urutan pada rule_digunakan
        // --------------------------------------------------
        usort(
            $rule_aktif_detail,
            function ($a, $b) use ($kode_rule_list) {

                return array_search(
                    $a['kode_rule'],
                    $kode_rule_list
                ) <=> array_search(
                    $b['kode_rule'],
                    $kode_rule_list
                );
            }
        );
    }

} else {

    // ======================================================
    // TAMPILKAN SEMUA HASIL EVALUASI
    // ======================================================

    $query = mysqli_query(
        $koneksi,
        "SELECT
            e.*,
            p.nama,
            p.nip,
            j.jabatan
        FROM evaluasi e
        JOIN pegawai p ON e.id_pegawai = p.id
        JOIN jabatan j ON p.id_jabatan = j.id
        ORDER BY e.tanggal_evaluasi DESC"
    );
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport"
          content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Hasil Evaluasi</title>

    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->
    <link href="../assets/vendor/fontawesome-free/css/all.min.css"
          rel="stylesheet">

    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:200,300,400,600,700,800"
          rel="stylesheet">

    <!-- =====================================================
         SB ADMIN 2
    ====================================================== -->
    <link href="../assets/css/sb-admin-2.min.css"
          rel="stylesheet">

    <!-- =====================================================
         DATATABLES
    ====================================================== -->
    <link href="../assets/vendor/datatables/dataTables.bootstrap4.min.css"
          rel="stylesheet">


    <style>

        /* =====================================================
           STYLE UMUM
        ====================================================== */

        html,
        body {

            font-family: 'Poppins', sans-serif;

            background:
                linear-gradient(
                    120deg,
                    #f5f7fa,
                    #c3cfe2
                );

            color: #4a4a4a;
        }


        /* =====================================================
           SIDEBAR
        ====================================================== */

        .sidebar {

            background:
                linear-gradient(
                    45deg,
                    #6a11cb,
                    #2575fc
                );

            color: #ffffff;
        }

        .sidebar .nav-item .nav-link {

            color: #ffffff;

            font-weight: 500;
        }

        .sidebar .nav-item.active .nav-link,
        .sidebar .nav-item .nav-link:hover {

            background-color:
                rgba(255, 255, 255, 0.2);

            border-radius: 8px;
        }


        /* =====================================================
           NAVBAR
        ====================================================== */

        .navbar {

            background: #ffffff;

            border-bottom:
                2px solid rgba(0, 0, 0, 0.1);

            box-shadow:
                0 4px 6px rgba(0, 0, 0, 0.1);
        }


        /* =====================================================
           CARD
        ====================================================== */

        .card {

            border: none;

            border-radius: 16px;

            box-shadow:
                0 6px 20px rgba(0, 0, 0, 0.1);
        }


        /* =====================================================
           HASIL EVALUASI
        ====================================================== */

        .result-badge {

            font-size: 1.5rem;

            padding: 10px 25px;

            border-radius: 50px;
        }

        .result-sangat-baik {

            background: #28a745;

            color: white;
        }

        .result-baik {

            background: #17a2b8;

            color: white;
        }

        .result-cukup {

            background: #ffc107;

            color: #333;
        }

        .result-kurang {

            background: #dc3545;

            color: white;
        }

        .result-tidak-ada {

            background: #6c757d;

            color: white;
        }


        /* =====================================================
           KOP SURAT
           
           DEFAULT TIDAK DITAMPILKAN.
           HANYA MUNCUL SAAT PRINT.
        ====================================================== */

        .print-letterhead {

            display: none;
        }


        /* =====================================================
           JUDUL CETAK
        ====================================================== */

        .print-title {

            display: none;
        }


        /* =====================================================
           TANDA TANGAN
           
           DEFAULT TIDAK DITAMPILKAN.
           HANYA MUNCUL SAAT PRINT.
        ====================================================== */

        .print-signature {

            display: none;
        }


        /* =====================================================
           PRINT STYLE
        ====================================================== */

        @media print {

            /* -------------------------------------------------
               HALAMAN
            ------------------------------------------------- */

            @page {

                size: A4;

                margin:
                    15mm
                    15mm
                    15mm
                    15mm;
            }


            html,
            body {

                background: #ffffff !important;

                color: #000000 !important;

                font-family:
                    Arial,
                    Helvetica,
                    sans-serif !important;

                width: 100% !important;

                margin: 0 !important;

                padding: 0 !important;
            }


            /* -------------------------------------------------
               HILANGKAN KOMPONEN WEBSITE
            ------------------------------------------------- */

            .sidebar,
            .navbar,
            .sticky-footer,
            #logoutModal,
            .btn,
            .topbar-divider,
            .d-sm-flex.align-items-center.justify-content-between {

                display: none !important;
            }


            /* -------------------------------------------------
               CONTENT
            ------------------------------------------------- */

            #wrapper {

                display: block !important;

                width: 100% !important;
            }

            #content-wrapper {

                margin-left: 0 !important;

                width: 100% !important;
            }

            #content {

                margin: 0 !important;

                padding: 0 !important;

                width: 100% !important;
            }

            .container-fluid {

                padding: 0 !important;

                margin: 0 !important;

                width: 100% !important;
            }


            /* =================================================
               KOP SURAT
            ================================================== */

            .print-letterhead {

                display: block !important;

                width: 100%;

                margin-bottom: 12px;

                page-break-inside: avoid;
            }


            .print-letterhead table {

                width: 100%;

                border-collapse: collapse;

                border: none !important;
            }


            .print-letterhead td {

                border: none !important;

                padding: 0;

                vertical-align: middle;
            }


            /* -------------------------------------------------
               LOGO
            ------------------------------------------------- */

            .letterhead-logo {

                width: 90px;

                text-align: center;
            }


            .letterhead-logo img {

                width: 70px;

                height: auto;

                display: block;

                margin: auto;
            }


            /* -------------------------------------------------
               TEKS KOP
            ------------------------------------------------- */

            .letterhead-text {

                text-align: center;

                line-height: 1.25;

                padding-right: 70px;
            }


            .letterhead-text .ministry {

                font-size: 13px;

                font-weight: bold;
            }


            .letterhead-text .directorate {

                font-size: 12px;

                font-weight: bold;
            }


            .letterhead-text .region {

                font-size: 11px;

                font-weight: bold;
            }


            .letterhead-text .office {

                font-size: 13px;

                font-weight: bold;
            }


            .letterhead-text .address {

                font-size: 9px;

                margin-top: 2px;
            }


            .letterhead-text .contact {

                font-size: 8px;
            }


            /* -------------------------------------------------
               GARIS KOP
            ------------------------------------------------- */

            .letterhead-rule {

                border: 0;

                border-top:
                    3px solid #000000;

                margin:
                    7px 0
                    12px 0;
            }


            /* =================================================
               JUDUL LAPORAN
            ================================================== */

            .print-title {

                display: block !important;

                text-align: center;

                margin-bottom: 12px;

                page-break-inside: avoid;
            }


            .print-title h2 {

                font-size: 15px;

                font-weight: bold;

                margin: 0;

                text-transform: uppercase;
            }


            .print-title p {

                font-size: 9px;

                margin:
                    3px 0
                    0 0;
            }


            /* =================================================
               CARD PADA PRINT
            ================================================== */

            .card {

                box-shadow: none !important;

                border:
                    1px solid #cccccc !important;

                border-radius: 0 !important;

                margin-bottom: 10px !important;

                page-break-inside: avoid;
            }


            .card-header {

                background: #ffffff !important;

                border-bottom:
                    1px solid #999999 !important;

                padding: 7px 10px !important;
            }


            .card-header h6 {

                color: #000000 !important;

                font-size: 10px !important;
            }


            .card-body {

                padding: 9px 10px !important;

                font-size: 9px !important;
            }


            /* =================================================
               INFORMASI PEGAWAI
            ================================================== */

            .card-body p {

                margin-bottom: 4px !important;

                font-size: 9px !important;
            }


            /* =================================================
               TABEL
            ================================================== */

            table {

                font-size: 8px !important;
            }


            table th,
            table td {

                padding:
                    3px 5px !important;
            }


            .table {

                margin-bottom: 7px !important;
            }


            .table-bordered {

                border-color: #999999 !important;
            }


            .table-bordered th,
            .table-bordered td {

                border-color: #999999 !important;
            }


            /* =================================================
               BADGE
            ================================================== */

            .badge {

                border:
                    1px solid #777777 !important;

                color: #000000 !important;

                background: #ffffff !important;

                font-size: 7px !important;
            }


            .result-badge {

                border:
                    1px solid #777777 !important;

                color: #000000 !important;

                background: #ffffff !important;

                font-size: 13px !important;
            }


            /* =================================================
               HASIL EVALUASI
            ================================================== */

            .result-sangat-baik,
            .result-baik,
            .result-cukup,
            .result-kurang,
            .result-tidak-ada {

                color: #000000 !important;

                background: #ffffff !important;
            }


            /* =================================================
               TANDA TANGAN
            ================================================== */

            .print-signature {

                display: flex !important;

                justify-content: flex-end;

                width: 100%;

                margin-top: 35px;

                page-break-inside: avoid;
            }


            .signature-box {

                width: 260px;

                text-align: center;

                color: #000000;

                font-size: 10px;

                line-height: 1.4;
            }


            .signature-box .signature-space {

                height: 70px;
            }


            /* -------------------------------------------------
               JIKA NANTI MENGGUNAKAN GAMBAR TANDA TANGAN
            ------------------------------------------------- */

            .signature-box .signature-image {

                width: 180px;

                height: 60px;

                object-fit: contain;

                display: block;

                margin:
                    0 auto;
            }


            .signature-box .name {

                font-weight: bold;

                text-decoration: underline;

                margin-top: 3px;
            }


            /* =================================================
               JAGA AGAR BAGIAN PENTING TIDAK TERPOTONG
            ================================================== */

            .print-letterhead,
            .print-title,
            .print-signature {

                page-break-inside: avoid;
            }


            /* =================================================
               HILANGKAN ELEMEN YANG TIDAK PERLU
            ================================================== */

            .text-gray-800 {

                color: #000000 !important;
            }

            .shadow {

                box-shadow: none !important;
            }

        }

    </style>

</head>


<body id="page-top">


<div id="wrapper">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <ul class="navbar-nav sidebar sidebar-dark accordion"
        id="accordionSidebar">


        <!-- BRAND -->

        <a class="sidebar-brand d-flex align-items-center justify-content-center"
           href="index.php">

            <div class="sidebar-brand-icon rotate-n-15">

                <i class="fas fa-laugh-wink"></i>

            </div>

            <div class="sidebar-brand-text mx-3">

                <?= ($_SESSION['role'] == "admin")
                    ? "ADMIN"
                    : "USER";
                ?>

            </div>

        </a>


        <hr class="sidebar-divider my-0">


        <!-- DASHBOARD -->

        <li class="nav-item">

            <a class="nav-link"
               href="index.php">

                <i class="fas fa-fw fa-tachometer-alt"></i>

                <span>Dashboard</span>

            </a>

        </li>


        <!-- =================================================
             MASTER DATA
        ================================================== -->

        <?php if ($_SESSION['role'] == "admin") { ?>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Master Data
            </div>

        <?php } ?>


        <!-- PEGAWAI -->

        <li class="nav-item">

            <a class="nav-link collapsed"
               href="#"
               data-toggle="collapse"
               data-target="#pegawaiMenu">

                <i class="fas fa-fw fa-users"></i>

                <span>Pegawai</span>

            </a>


            <div id="pegawaiMenu"
                 class="collapse"
                 data-parent="#accordionSidebar">

                <div class="bg-white py-2 collapse-inner rounded">

                    <a class="collapse-item"
                       href="pegawai.php">
                        Data Pegawai
                    </a>

                    <a class="collapse-item"
                       href="tambah_pegawai.php">
                        Tambah Pegawai
                    </a>

                </div>

            </div>

        </li>


        <!-- =================================================
             SISTEM PAKAR
        ================================================== -->

        <?php if ($_SESSION['role'] == "admin") { ?>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Sistem Pakar
            </div>

        <?php } ?>


        <!-- KRITERIA -->

        <?php if ($_SESSION['role'] == 'admin') { ?>

            <li class="nav-item">

                <a class="nav-link collapsed"
                   href="#"
                   data-toggle="collapse"
                   data-target="#kriteriaMenu">

                    <i class="fas fa-fw fa-list"></i>

                    <span>Kriteria</span>

                </a>


                <div id="kriteriaMenu"
                     class="collapse"
                     data-parent="#accordionSidebar">

                    <div class="bg-white py-2 collapse-inner rounded">

                        <a class="collapse-item"
                           href="kriteria.php">
                            Data Kriteria
                        </a>

                        <a class="collapse-item"
                           href="tambah_kriteria.php">
                            Tambah Kriteria
                        </a>

                    </div>

                </div>

            </li>

        <?php } ?>


        <!-- NILAI KONDISI -->

        <?php if ($_SESSION['role'] == 'admin') { ?>

            <li class="nav-item">

                <a class="nav-link collapsed"
                   href="#"
                   data-toggle="collapse"
                   data-target="#nilaiMenu">

                    <i class="fas fa-fw fa-sliders-h"></i>

                    <span>Nilai Kondisi</span>

                </a>


                <div id="nilaiMenu"
                     class="collapse"
                     data-parent="#accordionSidebar">

                    <div class="bg-white py-2 collapse-inner rounded">

                        <a class="collapse-item"
                           href="nilai_kondisi.php">
                            Data Nilai
                        </a>

                        <a class="collapse-item"
                           href="tambah_nilai.php">
                            Tambah Nilai
                        </a>

                    </div>

                </div>

            </li>

        <?php } ?>


        <!-- BASIS ATURAN -->

        <?php if ($_SESSION['role'] == 'admin') { ?>

            <li class="nav-item">

                <a class="nav-link collapsed"
                   href="#"
                   data-toggle="collapse"
                   data-target="#ruleMenu">

                    <i class="fas fa-fw fa-code-branch"></i>

                    <span>Basis Aturan (Rule)</span>

                </a>


                <div id="ruleMenu"
                     class="collapse"
                     data-parent="#accordionSidebar">

                    <div class="bg-white py-2 collapse-inner rounded">

                        <a class="collapse-item"
                           href="rule.php">
                            Data Rule
                        </a>

                        <a class="collapse-item"
                           href="tambah_rule.php">
                            Tambah Rule
                        </a>

                    </div>

                </div>

            </li>

        <?php } ?>


        <!-- =================================================
             EVALUASI
        ================================================== -->

        <?php if ($_SESSION['role'] == "admin") { ?>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Evaluasi
            </div>

        <?php } ?>


        <!-- EVALUASI PEGAWAI -->

        <li class="nav-item">

            <a class="nav-link"
               href="evaluasi.php">

                <i class="fas fa-fw fa-check-circle"></i>

                <span>Evaluasi Pegawai</span>

            </a>

        </li>


        <!-- HASIL EVALUASI -->

        <li class="nav-item active">

            <a class="nav-link"
               href="hasil_evaluasi.php">

                <i class="fas fa-fw fa-chart-bar"></i>

                <span>Hasil Evaluasi</span>

            </a>

        </li>


        <!-- =================================================
             LAPORAN
        ================================================== -->

        <?php if ($_SESSION['role'] == "admin") { ?>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Laporan
            </div>

        <?php } ?>


        <li class="nav-item">

            <a class="nav-link"
               href="laporan.php">

                <i class="fas fa-fw fa-file-alt"></i>

                <span>Laporan</span>

            </a>

        </li>


        <!-- =================================================
             PENGATURAN
        ================================================== -->

        <?php if ($_SESSION['role'] == "admin") { ?>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Pengaturan
            </div>

        <?php } ?>


        <?php if ($_SESSION['role'] == 'admin') { ?>

            <li class="nav-item">

                <a class="nav-link"
                   href="user.php">

                    <i class="fas fa-fw fa-user-cog"></i>

                    <span>Manajemen User</span>

                </a>

            </li>

        <?php } ?>


        <!-- SIDEBAR TOGGLE -->

        <div class="text-center d-none d-md-inline">

            <button class="rounded-circle border-0"
                    id="sidebarToggle">
            </button>

        </div>


    </ul>


    <!-- =====================================================
         CONTENT WRAPPER
    ====================================================== -->

    <div id="content-wrapper"
         class="d-flex flex-column">


        <div id="content">


            <!-- =================================================
                 NAVBAR
            ================================================== -->

            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">


                <button id="sidebarToggleTop"
                        class="btn btn-link d-md-none rounded-circle mr-3">

                    <i class="fa fa-bars"></i>

                </button>


                <ul class="navbar-nav ml-auto">


                    <div class="topbar-divider d-none d-sm-block"></div>


                    <li class="nav-item dropdown no-arrow">


                        <a class="nav-link dropdown-toggle"
                           href="#"
                           id="userDropdown"
                           role="button"
                           data-toggle="dropdown">


                            <span class="mr-2 d-none d-lg-inline text-gray-600 small">

                                <?= htmlspecialchars($_SESSION['nama_user'] ?? 'User'); ?>

                            </span>


                            <img class="img-profile rounded-circle"
                                 src="../assets/img/undraw_profile.svg">


                        </a>


                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">


                            <a class="dropdown-item"
                               href="#"
                               data-toggle="modal"
                               data-target="#logoutModal">

                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>

                                Logout

                            </a>


                        </div>


                    </li>


                </ul>


            </nav>


            <!-- =================================================
                 MAIN CONTAINER
            ================================================== -->

            <div class="container-fluid">


                <?php if ($id > 0 && isset($evaluasi)) { ?>


                    <!-- =================================================
                         KOP SURAT KHUSUS CETAK
                    ================================================== -->

                    <div class="print-letterhead">


                        <table>

                            <tr>


                                <td class="letterhead-logo">

                                    <img src="../assets/img/logo_kemenimipas.png"
                                         alt="Logo Kementerian Imigrasi dan Pemasyarakatan">

                                </td>


                                <td class="letterhead-text">


                                    <div class="ministry">

                                        KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN
                                        REPUBLIK INDONESIA

                                    </div>


                                    <div class="directorate">

                                        DIREKTORAT JENDERAL IMIGRASI

                                    </div>


                                    <div class="region">

                                        KANTOR WILAYAH SUMATERA BARAT

                                    </div>


                                    <div class="office">

                                        KANTOR IMIGRASI KELAS I TPI PADANG

                                    </div>


                                    <div class="address">

                                        Jl. Khatib Sulaiman No. 50,
                                        Kota Padang, Sumatera Barat

                                    </div>


                                    <div class="contact">

                                        Telepon: (0751) 7055113 /
                                        (0751) 419000

                                        &nbsp; | &nbsp;

                                        Laman: padang.imigrasi.go.id

                                    </div>


                                </td>


                            </tr>

                        </table>


                        <hr class="letterhead-rule">


                    </div>


                    <!-- =================================================
                         JUDUL CETAK
                    ================================================== -->

                    <div class="print-title">

                        <h2>
                            DETAIL HASIL EVALUASI KINERJA PEGAWAI
                        </h2>

                        <p>
                            Sistem Evaluasi Kinerja Pegawai
                        </p>

                    </div>


                    <!-- =================================================
                         HEADER HALAMAN
                    ================================================== -->

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">


                        <h1 class="h3 mb-0 text-gray-800">
                            Detail Hasil Evaluasi
                        </h1>


                        <div>


                            <a href="hasil_evaluasi.php"
                               class="btn btn-secondary btn-sm">

                                <i class="fas fa-arrow-left"></i>

                                Kembali

                            </a>


                            <button onclick="window.print()"
                                    class="btn btn-info btn-sm ml-1">

                                <i class="fas fa-print"></i>

                                Cetak

                            </button>


                            <a href="hapus_evaluasi.php?id=<?= $id ?>"
                               onclick="return confirm('Yakin ingin menghapus data evaluasi ini?')"
                               class="btn btn-danger btn-sm ml-1">

                                <i class="fas fa-trash"></i>

                                Hapus

                            </a>


                        </div>


                    </div>


                    <!-- =================================================
                         DETAIL EVALUASI
                    ================================================== -->

                    <div class="row">


                        <!-- =================================================
                             INFORMASI + FORWARD CHAINING
                        ================================================== -->

                        <div class="col-lg-8">


                            <!-- INFORMASI PEGAWAI -->

                            <div class="card shadow mb-4">


                                <div class="card-header py-3">

                                    <h6 class="m-0 font-weight-bold text-primary">

                                        Informasi Pegawai

                                    </h6>

                                </div>


                                <div class="card-body">


                                    <div class="row">


                                        <div class="col-md-6">

                                            <p>

                                                <strong>NIP:</strong>

                                                <?= htmlspecialchars(
                                                    $evaluasi['nip']
                                                ); ?>

                                            </p>


                                            <p>

                                                <strong>Nama:</strong>

                                                <?= htmlspecialchars(
                                                    $evaluasi['nama']
                                                ); ?>

                                            </p>

                                        </div>


                                        <div class="col-md-6">

                                            <p>

                                                <strong>Jabatan:</strong>

                                                <?= htmlspecialchars(
                                                    $evaluasi['jabatan']
                                                ); ?>

                                            </p>


                                            <p>

                                                <strong>Bidang:</strong>

                                                <?= htmlspecialchars(
                                                    $evaluasi['nama_bidang'] ?? '-'
                                                ); ?>

                                            </p>

                                        </div>


                                    </div>


                                </div>


                            </div>


                            <!-- =================================================
                                 PROSES FORWARD CHAINING
                            ================================================== -->

                            <div class="card shadow mb-4">


                                <div class="card-header py-3">

                                    <h6 class="m-0 font-weight-bold text-primary">

                                        Proses Forward Chaining

                                    </h6>

                                </div>


                                <div class="card-body">


                                    <h6>
                                        Fakta yang Dimasukkan:
                                    </h6>


                                    <div class="table-responsive">


                                        <table class="table table-sm">


                                            <thead>

                                                <tr>

                                                    <th>
                                                        Kriteria
                                                    </th>

                                                    <th>
                                                        Nilai
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody>


                                                <?php
                                                foreach (
                                                    $nilai_kriteria
                                                    as $kriteria => $nilai
                                                ) {
                                                ?>

                                                    <tr>

                                                        <td>

                                                            <?= htmlspecialchars(
                                                                $kriteria
                                                            ); ?>

                                                        </td>


                                                        <td>

                                                            <span class="badge badge-info">

                                                                <?= htmlspecialchars(
                                                                    $nilai
                                                                ); ?>

                                                            </span>

                                                        </td>

                                                    </tr>

                                                <?php } ?>


                                            </tbody>


                                        </table>


                                    </div>


                                    <!-- =================================================
                                         RULE AKTIF
                                    ================================================== -->

                                    <h6>
                                        Rule yang Aktif:
                                    </h6>


                                    <?php if (!empty($rule_aktif_detail)) { ?>


                                        <div class="table-responsive mb-3">


                                            <table class="table table-sm table-bordered">


                                                <thead class="thead-light">


                                                    <tr>

                                                        <th style="width:90px">
                                                            Rule
                                                        </th>

                                                        <th>
                                                            Kondisi (IF)
                                                        </th>

                                                        <th style="width:170px">
                                                            Kesimpulan (THEN)
                                                        </th>

                                                    </tr>


                                                </thead>


                                                <tbody>


                                                    <?php
                                                    foreach (
                                                        $rule_aktif_detail
                                                        as $r
                                                    ) {
                                                    ?>

                                                        <tr>


                                                            <td>

                                                                <span class="badge badge-success">

                                                                    <?= htmlspecialchars(
                                                                        $r['kode_rule']
                                                                    ); ?>

                                                                </span>

                                                            </td>


                                                            <td>

                                                                <small>

                                                                    <?= htmlspecialchars(
                                                                        $r['kondisi']
                                                                    ); ?>

                                                                </small>

                                                            </td>


                                                            <td>

                                                                <?= htmlspecialchars(
                                                                    $r['kesimpulan']
                                                                ); ?>

                                                            </td>


                                                        </tr>

                                                    <?php } ?>


                                                </tbody>


                                            </table>


                                        </div>


                                    <?php } else { ?>


                                        <p class="text-muted mb-3">

                                            <i class="fas fa-info-circle"></i>

                                            Tidak ada rule pada basis pengetahuan
                                            yang cocok dengan kombinasi nilai
                                            kriteria di atas.

                                        </p>


                                    <?php } ?>


                                    <!-- =================================================
                                         PENJELASAN
                                    ================================================== -->

                                    <h6>
                                        Penjelasan:
                                    </h6>


                                    <p style="white-space: pre-line;">

                                        <?= htmlspecialchars(
                                            $evaluasi['penjelasan'] ?? ''
                                        ); ?>

                                    </p>


                                </div>


                            </div>


                        </div>


                        <!-- =================================================
                             HASIL EVALUASI
                        ================================================== -->

                        <div class="col-lg-4">


                            <div class="card shadow mb-4">


                                <div class="card-header py-3">

                                    <h6 class="m-0 font-weight-bold text-primary">

                                        Hasil Evaluasi

                                    </h6>

                                </div>


                                <div class="card-body text-center">


                                    <?php

                                    $class = '';

                                    if (
                                        strpos(
                                            $evaluasi['hasil'],
                                            'Sangat Baik'
                                        ) !== false
                                    ) {

                                        $class =
                                            'result-sangat-baik';

                                    } elseif (
                                        strpos(
                                            $evaluasi['hasil'],
                                            'Baik'
                                        ) !== false
                                    ) {

                                        $class =
                                            'result-baik';

                                    } elseif (
                                        strpos(
                                            $evaluasi['hasil'],
                                            'Cukup'
                                        ) !== false
                                    ) {

                                        $class =
                                            'result-cukup';

                                    } elseif (
                                        strpos(
                                            $evaluasi['hasil'],
                                            'Kurang'
                                        ) !== false
                                    ) {

                                        $class =
                                            'result-kurang';

                                    } else {

                                        $class =
                                            'result-tidak-ada';
                                    }

                                    ?>


                                    <span class="result-badge <?= $class ?>">

                                        <?= htmlspecialchars(
                                            $evaluasi['hasil']
                                        ); ?>

                                    </span>


                                    <hr>


                                    <h6>
                                        Skor Total
                                    </h6>


                                    <h2>

                                        <?= number_format(
                                            $evaluasi['skor_total'],
                                            2
                                        ); ?>%

                                    </h2>


                                    <hr>


                                    <h6>
                                        Tanggal Evaluasi
                                    </h6>


                                    <p>

                                        <?= date(
                                            'd F Y',
                                            strtotime(
                                                $evaluasi['tanggal_evaluasi']
                                            )
                                        ); ?>

                                    </p>


                                    <hr>


                                    <h6>
                                        Rekomendasi
                                    </h6>


                                    <p class="text-left">

                                        <?= htmlspecialchars(
                                            $evaluasi['rekomendasi'] ?? ''
                                        ); ?>

                                    </p>


                                </div>


                            </div>


                        </div>


                    </div>


                    <!-- =================================================
                         TANDA TANGAN KEPALA KANTOR
                         
                         HANYA MUNCUL SAAT PRINT
                    ================================================== -->

                    <div class="print-signature">


                        <div class="signature-box">


                            <div>

                                Padang,
                                <?= date('d F Y'); ?>

                            </div>


                            <div>

                                Kepala Kantor Imigrasi
                                Kelas I TPI Padang

                            </div>


                            <!-- =========================================
                                 RUANG TANDA TANGAN
                                 TANPA KOTAK / BORDER
                            ========================================== -->

                            <div class="signature-space"></div>


                            <!-- =========================================
                                 JIKA NANTI SUDAH ADA GAMBAR TTD
                                 HAPUS KOMENTAR DI BAWAH
                            ========================================== -->

                            <!--

                            <img class="signature-image"
                                 src="../assets/img/ttd_kepala.png"
                                 alt="Tanda Tangan Kepala Kantor">

                            -->


                            <div class="name">

                                Murdo Danang Laksono

                            </div>


                        </div>


                    </div>


                <?php } else { ?>


                    <!-- =================================================
                         DAFTAR HASIL EVALUASI
                    ================================================== -->

                    <h1 class="h3 mb-2 text-gray-800">

                        Hasil Evaluasi

                    </h1>


                    <div class="card shadow mb-4">


                        <div class="card-header py-3">


                            <a href="evaluasi.php"
                               class="btn btn-primary btn-icon-split">


                                <span class="text">

                                    <i class="fas fa-plus"></i>

                                    Evaluasi Baru

                                </span>


                            </a>


                        </div>


                        <div class="card-body">


                            <div class="table-responsive">


                                <table class="table table-bordered"
                                       id="dataTable"
                                       width="100%"
                                       cellspacing="0">


                                    <thead>

                                        <tr>

                                            <th>No</th>

                                            <th>NIP</th>

                                            <th>Nama</th>

                                            <th>Jabatan</th>

                                            <th>Hasil</th>

                                            <th>Skor</th>

                                            <th>Tanggal</th>

                                            <th>Aksi</th>

                                        </tr>

                                    </thead>


                                    <tbody>


                                        <?php

                                        $no = 1;

                                        while (
                                            $data =
                                            mysqli_fetch_array($query)
                                        ) {

                                        ?>


                                            <tr>


                                                <td>
                                                    <?= $no++; ?>
                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        $data['nip']
                                                    ); ?>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        $data['nama']
                                                    ); ?>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        $data['jabatan']
                                                    ); ?>

                                                </td>


                                                <td>


                                                    <?php

                                                    if (
                                                        strpos(
                                                            $data['hasil'],
                                                            'Sangat Baik'
                                                        ) !== false
                                                    ) {

                                                        $class =
                                                            'badge-success';

                                                    } elseif (
                                                        strpos(
                                                            $data['hasil'],
                                                            'Baik'
                                                        ) !== false
                                                    ) {

                                                        $class =
                                                            'badge-info';

                                                    } elseif (
                                                        strpos(
                                                            $data['hasil'],
                                                            'Cukup'
                                                        ) !== false
                                                    ) {

                                                        $class =
                                                            'badge-warning';

                                                    } elseif (
                                                        strpos(
                                                            $data['hasil'],
                                                            'Kurang'
                                                        ) !== false
                                                    ) {

                                                        $class =
                                                            'badge-danger';

                                                    } else {

                                                        $class =
                                                            'badge-secondary';
                                                    }

                                                    ?>


                                                    <span class="badge <?= $class ?>">

                                                        <?= htmlspecialchars(
                                                            $data['hasil']
                                                        ); ?>

                                                    </span>


                                                </td>


                                                <td>

                                                    <?= number_format(
                                                        $data['skor_total'],
                                                        2
                                                    ); ?>%

                                                </td>


                                                <td>

                                                    <?= date(
                                                        'd-m-Y',
                                                        strtotime(
                                                            $data['tanggal_evaluasi']
                                                        )
                                                    ); ?>

                                                </td>


                                                <td>


                                                    <a href="hasil_evaluasi.php?id=<?= $data['id'] ?>"
                                                       class="btn btn-info btn-sm">

                                                        <i class="fas fa-eye"></i>

                                                        Detail

                                                    </a>


                                                </td>


                                            </tr>


                                        <?php } ?>


                                    </tbody>


                                </table>


                            </div>


                        </div>


                    </div>


                <?php } ?>


            </div>


        </div>


        <!-- =====================================================
             FOOTER
        ====================================================== -->

        <footer class="sticky-footer bg-white">


            <div class="container my-auto">


                <div class="copyright text-center my-auto">

                    <span>
                        Copyright &copy; Sistem Evaluasi Kinerja 2026
                    </span>

                </div>


            </div>


        </footer>


    </div>


</div>


<!-- ==========================================================
     LOGOUT MODAL
=========================================================== -->

<div class="modal fade"
     id="logoutModal"
     tabindex="-1"
     role="dialog">


    <div class="modal-dialog"
         role="document">


        <div class="modal-content">


            <div class="modal-header">


                <h5 class="modal-title">

                    Ready to Leave?

                </h5>


                <button class="close"
                        type="button"
                        data-dismiss="modal">

                    <span aria-hidden="true">
                        ×
                    </span>

                </button>


            </div>


            <div class="modal-body">

                Yakin Ingin Keluar?

            </div>


            <div class="modal-footer">


                <button class="btn btn-secondary"
                        type="button"
                        data-dismiss="modal">

                    Cancel

                </button>


                <a class="btn btn-primary"
                   href="hapusSession.php">

                    Logout

                </a>


            </div>


        </div>


    </div>


</div>


<!-- ==========================================================
     JAVASCRIPT
=========================================================== -->

<script src="../assets/vendor/jquery/jquery.min.js"></script>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>

<script src="../assets/vendor/datatables/jquery.dataTables.min.js"></script>

<script src="../assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<script src="../assets/js/sb-admin-2.min.js"></script>


<script>

    $(document).ready(function () {

        if ($('#dataTable').length) {

            $('#dataTable').DataTable({

                "language": {

                    "search": "Cari:",

                    "lengthMenu":
                        "Tampilkan _MENU_ data",

                    "info":
                        "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",

                    "infoEmpty":
                        "Tidak ada data",

                    "zeroRecords":
                        "Data tidak ditemukan",

                    "paginate": {

                        "first":
                            "Pertama",

                        "last":
                            "Terakhir",

                        "next":
                            "Berikutnya",

                        "previous":
                            "Sebelumnya"
                    }
                },

                "order": [
                    [6, "desc"]
                ]

            });

        }

    });

</script>


</body>

</html>