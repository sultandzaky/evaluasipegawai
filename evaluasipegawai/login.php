<?php
include 'koneksi.php';          // Panggil file koneksi database
session_start();                // Mulai session

// Cek jika sudah login, redirect ke halaman sesuai role
if(isset($_SESSION['status']) == 'login'){
    if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
        header("location:admin");
    } else if(isset($_SESSION['role']) && $_SESSION['role'] == 'pakar'){
        header("location:admin");
    } else {
        header("location:admin");
    }
}

// Proses login jika tombol login ditekan
if(isset($_POST['login'])){
    $username = $_POST['username'];          // Ambil username dari form
    $password = md5($_POST['password']);     // Enkripsi password dengan MD5

    // Cek login di tabel user
    $login = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username' and password='$password'");
    $cek = mysqli_num_rows($login);
  

    // Jika ditemukan di tabel user
    if($cek > 0) {
        $user_data = mysqli_fetch_assoc($login);
        // Simpan data user ke session
        $_SESSION['id_user'] = $user_data['id'];
        $_SESSION['nama_user'] = $user_data['nama'];
        $_SESSION['username_user'] = $username;
        $_SESSION['role'] = $user_data['role'];
        $_SESSION['status'] = "login";
        
        // Redirect sesuai role
        if($user_data['role'] == 'admin'){
            header('location:admin');
        } else if($user_data['role'] == 'pakar'){
            header('location:admin');
        } else {
            header('location:admin');
        }
    } 
    // Jika ditemukan di tabel pegawai
    else if ($cekPegawai > 0) {
        $pegawai_data = mysqli_fetch_assoc($loginPegawai);
        // Simpan data pegawai ke session
        $_SESSION['id_pegawai'] = $pegawai_data['id'];
        $_SESSION['nama_pegawai'] = $pegawai_data['nama'];
        $_SESSION['username_pegawai'] = $username;
        $_SESSION['status'] = "login";
        header('location:admin');
    } 
    // Jika tidak ditemukan di kedua tabel
    else {
        echo "<script>
        alert('Login Gagal, Periksa Username dan Password Anda!');
        header('location:login.php');
             </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta tags untuk responsif -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - Sistem Evaluasi Kinerja</title>

    <!-- CSS Libraries -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Custom CSS untuk tampilan login -->
    <style>
        body {
            background-image: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 400px;
        }
        .login-card h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #4e73df;
            font-weight: 600;
        }
        .login-card .form-control {
            border-radius: 50px;
            padding: 12px 20px;
            font-size: 16px;
        }
        .login-card .btn-primary {
            border-radius: 50px;
            font-size: 16px;
            padding: 12px 30px;
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .login-card .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <h1>Sistem Evaluasi Kinerja</h1>
            <!-- Form Login -->
            <form class="user" method="POST">
                <div class="form-group">
                    <input type="text" class="form-control form-control-user" id="username" name="username" placeholder="Enter Username...">
                </div>
                <div class="form-group">
                    <input type="password" class="form-control form-control-user" id="password" name="password" placeholder="Password">
                </div>
                <button type="submit" name="login" class="btn btn-primary btn-user btn-block">Login</button>
                <hr>
            </form>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="assets/js/sb-admin-2.min.js"></script>
</body>
</html>