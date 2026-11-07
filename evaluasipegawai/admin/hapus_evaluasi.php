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


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Hapus data evaluasi berdasarkan ID
if ($id > 0) {
    $hapus = mysqli_query($koneksi, "DELETE FROM evaluasi WHERE id = $id");
    if ($hapus) {
        echo "<script>alert('Data evaluasi berhasil dihapus!'); document.location='hasil_evaluasi.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data!'); document.location='hasil_evaluasi.php';</script>";
    }
} else {
    header('location:hasil_evaluasi.php');
}
?>