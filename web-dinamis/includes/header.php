<?php
require_once 'config/db.php';
require_once 'includes/auth_check.php';

// Mendapatkan nama file saat ini untuk menentukan class active pada menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?> - Perpustakaan Novel</title>
    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="logo-icon">📖</div>
                <span>NovelLib</span>
            </div>
            
            <ul class="sidebar-menu">
                <!-- Dashboard untuk semua -->
                <li class="sidebar-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="dashboard.php">
                        <span>📊</span> <span>Dashboard</span>
                    </a>
                </li>
                
                <!-- Menu Khusus Admin -->
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li class="sidebar-item <?php echo ($current_page === 'novels.php' || $current_page === 'novel-add.php' || $current_page === 'novel-edit.php') ? 'active' : ''; ?>">
                        <a href="novels.php">
                            <span>📚</span> <span>Data Novel</span>
                        </a>
                    </li>
                    <li class="sidebar-item <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
                        <a href="categories.php">
                            <span>🏷️</span> <span>Kategori Novel</span>
                        </a>
                    </li>
                    <li class="sidebar-item <?php echo $current_page === 'borrowings.php' ? 'active' : ''; ?>">
                        <a href="borrowings.php">
                            <span>🔄</span> <span>Transaksi Pinjam</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Menu Khusus Member -->
                <?php if ($_SESSION['role'] === 'member'): ?>
                    <li class="sidebar-item <?php echo $current_page === 'my-borrowings.php' ? 'active' : ''; ?>">
                        <a href="my-borrowings.php">
                            <span>📖</span> <span>Pinjaman Saya</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Logout -->
                <li class="sidebar-item">
                    <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                        <span>🚪</span> <span>Keluar</span>
                    </a>
                </li>
            </ul>
            
            <!-- User Profile di bawah sidebar -->
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name" title="<?php echo htmlspecialchars($_SESSION['fullname']); ?>">
                        <?php echo htmlspecialchars($_SESSION['fullname']); ?>
                    </div>
                    <div class="sidebar-user-role">
                        <?php echo htmlspecialchars($_SESSION['role']); ?>
                    </div>
                </div>
                <a href="logout.php" class="sidebar-user-logout" onclick="return confirm('Apakah Anda yakin ingin keluar?');" title="Keluar">
                    <span>🚪</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT CONTAINER -->
        <main class="main-content">
            <div class="content-header">
                <div class="content-title">
                    <h1><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                    <p><?php echo isset($page_desc) ? $page_desc : 'Ringkasan aktivitas dan informasi perpustakaan.'; ?></p>
                </div>
                <div class="header-date">
                    <span style="color: var(--text-secondary); font-size: 14px; font-weight: 500;">
                        📅 <?php echo date('d M Y'); ?>
                    </span>
                </div>
            </div>
            
            <!-- Tempat notifikasi status (sukses/gagal) di dashboard -->
            <?php if (isset($_SESSION['action_success'])): ?>
                <div class="alert alert-success">
                    <span>✅</span> <?php echo $_SESSION['action_success']; unset($_SESSION['action_success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['action_error'])): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?php echo $_SESSION['action_error']; unset($_SESSION['action_error']); ?>
                </div>
            <?php endif; ?>
?> ```

#### 2. Cek file `novels.php` di VS Code laptop kamu.
Pastikan bagian atas file `novels.php` tersusun bersih seperti ini (tidak ada potongan teks sisa editan `sed` kemarin):

```php
<?php
$page_title = "Data Novel";
require_once 'config/db.php';
require_once 'includes/header.php';

// Proteksi halaman: hanya admin yang diperbolehkan
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['action_error'] = "Anda tidak memiliki hak akses ke halaman tersebut.";
    header("Location: dashboard.php");
    exit();
}

// Pencarian Novel
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
?>