<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">

<a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">

<div class="sidebar-brand-icon rotate-n-15">
<i class="fas fa-laugh-wink"></i>
</div>

<div class="sidebar-brand-text mx-3">

<?= ($_SESSION['role']=="admin") ? "Admin" : "User Pengguna"; ?>

</div>

</a>

<hr class="sidebar-divider my-0">