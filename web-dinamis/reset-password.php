<?php
require_once 'config/db.php';

try {
    // 1. Buat hash password secara dinamis menggunakan server PHP Anda
    $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $member_hash = password_hash('member123', PASSWORD_DEFAULT);

    // 2. Pastikan tabel users ada (jika belum dibuat)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `fullname` VARCHAR(100) NOT NULL,
          `role` ENUM('admin', 'member') DEFAULT 'member',
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3. Masukkan atau perbarui password admin dan member dengan hash baru
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, fullname = 'admin', role = 'admin' WHERE username = 'admin'");
        $stmt->execute([$admin_hash]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role) VALUES ('admin', ?, 'Administrator Perpustakaan', 'admin')");
        $stmt->execute([$admin_hash]);
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'member'");
    $stmt->execute();
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, fullname = 'Budi Santoso (Member)', role = 'member' WHERE username = 'member'");
        $stmt->execute([$member_hash]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role) VALUES ('member', ?, 'Budi Santoso (Member)', 'member')");
        $stmt->execute([$member_hash]);
    }

    echo "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; border: 1px solid #10b981; border-radius: 12px; background-color: #ecfdf5; color: #065f46;'>";
    echo "<h2 style='margin-top: 0;'>✅ Sinkronisasi Akun Berhasil!</h2>";
    echo "<p>Password telah berhasil di-hash ulang menggunakan server PHP Anda dan disimpan ke database MySQL.</p>";
    echo "<hr style='border: 0; border-top: 1px solid #a7f3d0; margin: 20px 0;'>";
    echo "<p>Sekarang silakan kembali ke halaman login dan gunakan akun berikut:</p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username: <code>admin</code> | password: <code>admin123</code></li>";
    echo "<li><strong>Member:</strong> username: <code>member</code> | password: <code>member123</code></li>";
    echo "</ul>";
    echo "<a href='login.php' style='display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #10b981; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>Buka Halaman Login ➡️</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; border: 1px solid #ef4444; border-radius: 12px; background-color: #fef2f2; color: #991b1b;'>";
    echo "<h2 style='margin-top: 0;'>⚠️ Koneksi Database Gagal</h2>";
    echo "<p>Aplikasi tidak dapat terhubung ke MySQL Anda. Harap pastikan XAMPP/Apache/MySQL Anda aktif dan database telah di-import.</p>";
    echo "<p><strong>Detail Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
