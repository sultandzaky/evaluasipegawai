<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Evaluasi Kinerja - Forward Chaining</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Navbar Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: white;
            font-size: 24px;
            font-weight: 700;
        }

        .logo span {
            color: #00ff88;
        }

        /* Hero Section */
        .hero {
            padding-top: 120px;
            padding-bottom: 60px;
            text-align: center;
            color: white;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            background: linear-gradient(to right, #ffffff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 40px;
            color: #e0e0e0;
        }

        .btn-login {
            background: linear-gradient(45deg, #00ff88, #00b8ff);
            color: #1e3c72;
            padding: 10px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 136, 0.4);
        }

        /* Features Section */
        .features {
            padding: 60px 0;
            text-align: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            color: white;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 40px;
            color: #00ff88;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            margin-bottom: 15px;
            font-size: 20px;
        }

        .feature-card p {
            color: #e0e0e0;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-content">
            <div class="logo">Sistem Evaluasi<span> Kinerja</span></div>
            <a class="btn-login" href="login.php">Login</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Sistem Evaluasi Kinerja Pegawai</h1>
            <p>Metode Forward Chaining - Sistem Pakar untuk Evaluasi Kinerja Pegawai Kantor Imigrasi</p>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Sistem Pakar</h3>
                    <p>Menggunakan metode forward chaining untuk evaluasi kinerja pegawai secara akurat</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Evaluasi Objektif</h3>
                    <p>Penilaian kinerja berdasarkan kriteria dan rule yang telah ditentukan</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Laporan Lengkap</h3>
                    <p>Hasil evaluasi disajikan dengan rekomendasi untuk pengembangan pegawai</p>
                </div>
            </div>
        </div>
    </section>
</body>
</html>