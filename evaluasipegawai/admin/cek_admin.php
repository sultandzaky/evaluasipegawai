<?php
include 'cek_admin.php';

// Cek apakah bukan admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "<script>
            alert('Maaf, Anda tidak memiliki hak akses ke halaman ini.');
            window.location='index.php';
          </script>";
    exit();
}
?>