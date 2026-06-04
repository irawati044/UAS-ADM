<?php
require_once 'config/db.php';
require_once 'includes/auth_check.php';

// Proteksi akses admin
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['action_error'] = "Anda tidak memiliki hak akses ke halaman tersebut.";
    header("Location: dashboard.php");
    exit();
}

$novel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($novel_id > 0) {
    try {
        // 1. Ambil data nama file cover untuk dihapus dari folder upload
        $stmt = $pdo->prepare("SELECT title, cover_image FROM novels WHERE id = ?");
        $stmt->execute([$novel_id]);
        $novel = $stmt->fetch();

        if ($novel) {
            $title = $novel['title'];
            $cover_image = $novel['cover_image'];

            // 2. Hapus file cover jika ada
            if (!empty($cover_image)) {
                $file_path = 'assets/uploads/' . $cover_image;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // 3. Hapus data di basis data (borrowings terkait otomatis terhapus karena foreign key ON DELETE CASCADE)
            $stmt = $pdo->prepare("DELETE FROM novels WHERE id = ?");
            $stmt->execute([$novel_id]);

            $_SESSION['action_success'] = "Novel '" . htmlspecialchars($title) . "' berhasil dihapus beserta berkas covernya!";
        } else {
            $_SESSION['action_error'] = "Novel tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $_SESSION['action_error'] = "Gagal menghapus novel dari database: " . $e->getMessage();
    }
} else {
    $_SESSION['action_error'] = "ID Novel tidak valid.";
}

header("Location: novels.php");
exit();
?>
