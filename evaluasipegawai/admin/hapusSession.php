<?php
    session_start();          // Mulai session
    session_destroy();        // Hancurkan semua session
    header("Location:../");   // Redirect ke halaman login
    exit();
?>