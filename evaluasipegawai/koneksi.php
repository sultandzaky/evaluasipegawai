<?php
// ===== KONFIGURASI DATABASE =====
$server = "localhost";          // Alamat server database
$user = "root";                 // Username MySQL
$pass = "";                     // Password MySQL
$database = "database_evaluasi"; // Nama database

// ===== MEMBUAT KONEKSI =====
$koneksi = mysqli_connect($server, $user, $pass, $database) 
    or die(mysqli_error($koneksi)); // Jika gagal, tampilkan error
?>