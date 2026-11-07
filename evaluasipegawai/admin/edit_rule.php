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

// Cek apakah ID rule ada
if (!isset($_GET['id'])) {
    echo "<script>alert('ID Rule tidak valid!'); document.location='rule.php';</script>";
    exit();
}

$id    = mysqli_real_escape_string($koneksi, $_GET['id']);
$query = mysqli_query($koneksi, "SELECT * FROM rule WHERE id = '$id'");
$rule  = mysqli_fetch_assoc($query);

// Cek apakah data rule ditemukan
if (!$rule) {
    echo "<script>alert('Rule tidak ditemukan!'); document.location='rule.php';</script>";
    exit();
}

// ===== AMBIL DATA UNTUK DROPDOWN =====
$kriteria_result = mysqli_query($koneksi, "SELECT * FROM kriteria ORDER BY kode_kriteria");
$kriteria_list   = [];
while ($k = mysqli_fetch_assoc($kriteria_result)) {
    $kriteria_list[] = $k;
}

$nilai_result = mysqli_query($koneksi, "SELECT * FROM nilai_kondisi ORDER BY skor DESC");
$nilai_list   = [];
while ($n = mysqli_fetch_assoc($nilai_result)) {
    $nilai_list[] = $n;
}

$kesimpulan_options = ['Kinerja Sangat Baik', 'Kinerja Baik', 'Kinerja Cukup', 'Kinerja Kurang'];

// Fungsi parse kondisi untuk edit.
// Mendeteksi SATU operator global (AND atau OR) yang berlaku untuk seluruh rule,
// karena mesin forward chaining tidak mendukung campuran AND/OR dalam satu rule.
function parseKondisi(string $kondisi): array {
    $operator_global = 'AND';
    if (strpos($kondisi, ' OR ') !== false) {
        $operator_global = 'OR';
        $parts = explode(' OR ', $kondisi);
    } else {
        $parts = explode(' AND ', $kondisi);
    }

    $rows = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if (strpos($part, '=') !== false) {
            [$krit, $nil] = explode('=', $part, 2);
            $rows[] = [
                'kriteria' => trim($krit),
                'nilai'    => trim($nil),
            ];
        }
    }
    return ['rows' => $rows, 'operator' => $operator_global];
}

$parsed = parseKondisi($rule['kondisi']);
$existing_rows = $parsed['rows'];
$existing_operator = $parsed['operator'];

// Proses update data rule
if (isset($_POST['update'])) {
    $kode_rule  = mysqli_real_escape_string($koneksi, $_POST['kode_rule']);
    $kondisi    = mysqli_real_escape_string($koneksi, $_POST['kondisi']);
    $kesimpulan = mysqli_real_escape_string($koneksi, $_POST['kesimpulan']);
    $deskripsi  = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    if (empty($kondisi)) {
        echo "<script>alert('Kondisi tidak boleh kosong!'); history.back();</script>";
        exit();
    }

    // Validasi kondisi tidak boleh mencampur AND dan OR dalam satu rule.
    if (strpos($kondisi, ' AND ') !== false && strpos($kondisi, ' OR ') !== false) {
        echo "<script>alert('Kondisi tidak boleh mencampur AND dan OR dalam satu rule. Pilih salah satu operator saja.'); history.back();</script>";
        exit();
    }

    // Cek kode rule duplikat kecuali dirinya sendiri
    $cek = mysqli_query($koneksi, "SELECT * FROM rule WHERE kode_rule = '$kode_rule' AND id != '$id'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Kode rule sudah digunakan!'); document.location='edit_rule.php?id=$id';</script>";
        exit();
    }

    $update = mysqli_query($koneksi,
        "UPDATE rule SET 
            kode_rule  = '$kode_rule',
            kondisi    = '$kondisi',
            kesimpulan = '$kesimpulan',
            deskripsi  = '$deskripsi'
         WHERE id = '$id'"
    );

    if ($update) {
        echo "<script>alert('Update data sukses!'); document.location='rule.php';</script>";
    } else {
        echo "<script>alert('Update data gagal!'); document.location='edit_rule.php?id=$id';</script>";
    }
}

$kriteria_json     = json_encode($kriteria_list);
$nilai_json        = json_encode($nilai_list);
$existing_rows_json = json_encode($existing_rows);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Rule</title>

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
        .condition-builder {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .condition-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .condition-row select {
            flex: 1;
            min-width: 150px;
        }
        .condition-row .operator-select {
            flex: 0 0 90px;
        }
        .kondisi-preview {
            font-family: monospace;
            background: #e9ecef;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            min-height: 36px;
            word-break: break-all;
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
                    <h1 class="h3 mb-2 text-gray-800">Edit Rule &mdash; <?= htmlspecialchars($rule['kode_rule']) ?></h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <a href="rule.php" class="btn btn-success btn-icon-split">
                                <span class="text">Kembali</span>
                            </a>
                        </div>
                        <div class="card-body">
                            <form method="post" id="formRule">

                                <!-- Kode Rule -->
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label font-weight-bold">Kode Rule</label>
                                    <div class="col-sm-4">
                                        <input type="text" name="kode_rule" class="form-control"
                                               value="<?= htmlspecialchars($rule['kode_rule']) ?>" required>
                                    </div>
                                </div>

                                <!-- Condition Builder -->
                                <div class="form-group">
                                    <label class="font-weight-bold">Kondisi Rule</label>
                                    <div class="condition-builder">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label small text-muted">Operator antar kondisi</label>
                                            <div class="col-sm-3">
                                                <select id="global-operator" class="form-control form-control-sm" onchange="updatePreview()">
                                                    <option value="AND">AND (semua harus terpenuhi)</option>
                                                    <option value="OR">OR (cukup salah satu)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div id="condition-rows"></div>
                                        <button type="button" id="btn-tambah-kondisi" class="btn btn-sm btn-outline-primary mt-2" onclick="tambahKondisi()">
                                            <i class="fas fa-plus"></i> Tambah Kondisi
                                        </button>
                                        <div class="mt-3">
                                            <label class="small text-muted">Preview Kondisi:</label>
                                            <div class="kondisi-preview" id="kondisi-preview">-</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="kondisi" id="kondisi-hidden">
                                </div>

                                <!-- Kesimpulan -->
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label font-weight-bold">Kesimpulan</label>
                                    <div class="col-sm-4">
                                        <select name="kesimpulan" class="form-control" required>
                                            <?php foreach ($kesimpulan_options as $opt): ?>
                                                <option value="<?= $opt ?>" <?= ($rule['kesimpulan'] === $opt) ? 'selected' : '' ?>>
                                                    <?= $opt ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Deskripsi -->
                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label font-weight-bold">Deskripsi</label>
                                    <div class="col-sm-6">
                                        <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($rule['deskripsi']) ?></textarea>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" name="update" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update
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

    <!-- ===== JAVASCRIPT CONDITION BUILDER ===== -->
    <script>
        const kriteriaList     = <?= $kriteria_json ?>;
        const nilaiList        = <?= $nilai_json ?>;
        const existingRows     = <?= $existing_rows_json ?>;
        const existingOperator = <?= json_encode($existing_operator) ?>;

        let rowCount = 0;

        function getUsedKriteria(excludeSelect = null) {
            const used = [];
            document.querySelectorAll('.kondisi-kriteria').forEach(sel => {
                if (sel !== excludeSelect) used.push(sel.value);
            });
            return used;
        }

        function updateUsedKriteria() {
            const allSelects = document.querySelectorAll('.kondisi-kriteria');
            allSelects.forEach(sel => {
                const used = getUsedKriteria(sel);
                Array.from(sel.options).forEach(opt => {
                    opt.disabled = used.includes(opt.value);
                });
            });

            const btnTambah = document.getElementById('btn-tambah-kondisi');
            if (btnTambah) {
                const totalRows    = document.querySelectorAll('.condition-row').length;
                btnTambah.disabled = totalRows >= kriteriaList.length;
            }
        }

        function buatSelectKriteria(selectedVal = '') {
            let html = '<select class="form-control kondisi-kriteria" onchange="updateUsedKriteria(); updatePreview()">';
            kriteriaList.forEach(k => {
                let sel = (k.nama_kriteria === selectedVal) ? 'selected' : '';
                html += `<option value="${k.nama_kriteria}" ${sel}>${k.nama_kriteria}</option>`;
            });
            html += '</select>';
            return html;
        }

        function buatSelectNilai(selectedVal = '') {
            let html = '<select class="form-control kondisi-nilai" onchange="updatePreview()">';
            nilaiList.forEach(n => {
                let sel = (n.nama_nilai === selectedVal) ? 'selected' : '';
                html += `<option value="${n.nama_nilai}" ${sel}>${n.nama_nilai}</option>`;
            });
            html += '</select>';
            return html;
        }

        function tambahKondisi(kriteriaVal = '', nilaiVal = '') {
            if (!kriteriaVal) {
                const used = getUsedKriteria();
                const available = kriteriaList.find(k => !used.includes(k.nama_kriteria));
                if (!available) {
                    alert('Semua kriteria sudah digunakan!');
                    return;
                }
                kriteriaVal = available.nama_kriteria;
            }

            rowCount++;
            const div = document.createElement('div');
            div.className = 'condition-row';
            div.id = 'row-' + rowCount;

            const numLabel = document.createElement('span');
            numLabel.className = 'badge badge-secondary';
            numLabel.style.minWidth = '24px';
            numLabel.textContent = document.querySelectorAll('.condition-row').length + 1;

            const wKriteria = document.createElement('div');
            wKriteria.style.flex = '2';
            wKriteria.innerHTML = buatSelectKriteria(kriteriaVal);

            const eq = document.createElement('span');
            eq.className = 'font-weight-bold text-primary';
            eq.textContent = '=';

            const wNilai = document.createElement('div');
            wNilai.style.flex = '2';
            wNilai.innerHTML = buatSelectNilai(nilaiVal);

            const btnHapus = document.createElement('button');
            btnHapus.type = 'button';
            btnHapus.className = 'btn btn-sm btn-danger';
            btnHapus.innerHTML = '<i class="fas fa-trash"></i>';
            btnHapus.onclick = function () {
                div.remove();
                renumberRows();
                updateUsedKriteria();
                updatePreview();
            };

            div.appendChild(numLabel);
            div.appendChild(wKriteria);
            div.appendChild(eq);
            div.appendChild(wNilai);
            div.appendChild(btnHapus);

            document.getElementById('condition-rows').appendChild(div);
            updateUsedKriteria();
            updatePreview();
        }

        function renumberRows() {
            document.querySelectorAll('.condition-row').forEach((r, i) => {
                const badge = r.querySelector('.badge');
                if (badge) badge.textContent = i + 1;
            });
        }

        function updatePreview() {
            const rows = document.querySelectorAll('.condition-row');
            const operator = document.getElementById('global-operator').value;
            let parts = [];
            rows.forEach(row => {
                const kriteria = row.querySelector('.kondisi-kriteria').value;
                const nilai    = row.querySelector('.kondisi-nilai').value;
                parts.push(`${kriteria}=${nilai}`);
            });
            const kondisiStr = parts.join(` ${operator} `);
            document.getElementById('kondisi-preview').textContent = kondisiStr || '-';
            document.getElementById('kondisi-hidden').value = kondisiStr;
        }

        document.getElementById('formRule').addEventListener('submit', function (e) {
            if (!document.getElementById('kondisi-hidden').value.trim()) {
                e.preventDefault();
                alert('Tambahkan minimal 1 kondisi rule!');
            }
        });

        // Set operator global sesuai rule yang sedang diedit, lalu muat baris kondisinya
        document.getElementById('global-operator').value = existingOperator;
        if (existingRows.length > 0) {
            existingRows.forEach(row => tambahKondisi(row.kriteria, row.nilai));
        } else {
            tambahKondisi();
        }
    </script>
</body>
</html>