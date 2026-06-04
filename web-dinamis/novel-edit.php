<?php
$page_title = "Edit Detail Novel";
$page_desc = "Perbarui rincian novel dan ganti gambar sampul jika diperlukan.";
require_once 'includes/header.php';

// Proteksi akses admin
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['action_error'] = "Anda tidak memiliki hak akses ke halaman tersebut.";
    header("Location: dashboard.php");
    exit();
}

$error = '';
$novel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$novel = null;

// Ambil data novel yang akan diedit
try {
    $stmt = $pdo->prepare("SELECT * FROM novels WHERE id = ?");
    $stmt->execute([$novel_id]);
    $novel = $stmt->fetch();
    
    if (!$novel) {
        $_SESSION['action_error'] = "Novel dengan ID tersebut tidak ditemukan.";
        header("Location: novels.php");
        exit();
    }
} catch (PDOException $e) {
    die("Kesalahan database: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $publisher = trim($_POST['publisher']);
    $year = !empty($_POST['year']) ? intval($_POST['year']) : null;
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $stock = intval($_POST['stock']);
    $synopsis = trim($_POST['synopsis']);
    $cover_image = $novel['cover_image']; // Gunakan cover lama sebagai default

    if (empty($title) || empty($author) || $stock < 0) {
        $error = "Judul, penulis, dan stok minimal 0 wajib diisi!";
    } else {
        try {
            // PROSES UNGGAH GAMBAR COVER BARU
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['cover_image']['tmp_name'];
                $fileName = $_FILES['cover_image']['name'];
                $fileSize = $_FILES['cover_image']['size'];
                
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png'];

                if (in_array($fileExtension, $allowedExtensions)) {
                    if ($fileSize < 2 * 1024 * 1024) {
                        $uploadFileDir = 'assets/uploads/';
                        $newFileName = uniqid('cover_', true) . '.' . $fileExtension;
                        $dest_path = $uploadFileDir . $newFileName;

                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            // Hapus cover lama dari server jika ada
                            if (!empty($novel['cover_image'])) {
                                $old_file_path = $uploadFileDir . $novel['cover_image'];
                                if (file_exists($old_file_path)) {
                                    unlink($old_file_path);
                                }
                            }
                            $cover_image = $newFileName;
                        } else {
                            $error = "Terjadi kesalahan saat menyimpan file cover baru.";
                        }
                    } else {
                        $error = "Ukuran cover novel terlalu besar. Maksimal 2MB.";
                    }
                } else {
                    $error = "Format file cover tidak didukung. Harap gunakan format JPG, JPEG, atau PNG.";
                }
            }

            if (empty($error)) {
                $stmt = $pdo->prepare("
                    UPDATE novels 
                    SET title = ?, author = ?, publisher = ?, year = ?, category_id = ?, synopsis = ?, cover_image = ?, stock = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$title, $author, $publisher, $year, $category_id, $synopsis, $cover_image, $stock, $novel_id]);

                $_SESSION['action_success'] = "Novel '" . htmlspecialchars($title) . "' berhasil diperbarui!";
                header("Location: novels.php");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Gagal memperbarui database: " . $e->getMessage();
        }
    }
}
?>

<div class="card-section">
    <div style="margin-bottom: 20px;">
        <a href="novels.php" class="btn btn-secondary">⬅️ Kembali ke Daftar</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="novel-edit.php?id=<?php echo $novel['id']; ?>" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label for="title">Judul Novel *</label>
                <input type="text" id="title" name="title" class="input-control" placeholder="Masukkan judul" required value="<?php echo htmlspecialchars($novel['title']); ?>">
            </div>

            <div class="form-group">
                <label for="author">Penulis *</label>
                <input type="text" id="author" name="author" class="input-control" placeholder="Nama penulis" required value="<?php echo htmlspecialchars($novel['author']); ?>">
            </div>

            <div class="form-group">
                <label for="publisher">Penerbit</label>
                <input type="text" id="publisher" name="publisher" class="input-control" placeholder="Nama penerbit" value="<?php echo htmlspecialchars($novel['publisher'] ?? ''); ?>">
            </div>

            <div class="form-grid" style="padding: 0;">
                <div class="form-group">
                    <label for="year">Tahun Terbit</label>
                    <input type="number" id="year" name="year" class="input-control" placeholder="Tahun" min="1000" max="2100" value="<?php echo htmlspecialchars($novel['year'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="stock">Stok Buku *</label>
                    <input type="number" id="stock" name="stock" class="input-control" min="0" required value="<?php echo htmlspecialchars($novel['stock']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="category_id">Kategori Novel</label>
                <select id="category_id" name="category_id" class="input-control">
                    <option value="">-- Pilih Kategori --</option>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
                        while ($cat = $stmt->fetch()) {
                            $selected = ($novel['category_id'] == $cat['id']) ? 'selected' : '';
                            echo "<option value='{$cat['id']}' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option value=''>Gagal memuat kategori</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="cover_image">Ganti Cover Novel (Format: JPG/PNG, Max: 2MB)</label>
                <input type="file" id="cover_image" name="cover_image" class="input-control" accept="image/*">
                
                <div class="cover-preview-container">
                    <!-- Preview Cover Sekarang -->
                    <?php
                    $cover_src = 'assets/uploads/' . $novel['cover_image'];
                    $has_cover = !empty($novel['cover_image']) && file_exists($cover_src);
                    ?>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 5px;">Cover Saat Ini:</div>
                        <?php if ($has_cover): ?>
                            <img src="<?php echo $cover_src; ?>" alt="Cover Sekarang" style="width: 80px; height: 110px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                        <?php else: ?>
                            <div style="width: 80px; height: 110px; border-radius: 6px; border: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--text-muted);">📖</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Preview Cover Baru (melalui JS) -->
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 5px;">Cover Baru (Pratinjau):</div>
                        <img id="cover-preview-img" src="" alt="Pratinjau Cover" class="cover-preview" style="width: 80px; height: 110px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-top: 10px;">
            <label for="synopsis">Sinopsis Novel</label>
            <textarea id="synopsis" name="synopsis" class="input-control" placeholder="Tuliskan sinopsis singkat novel di sini..."><?php echo htmlspecialchars($novel['synopsis'] ?? ''); ?></textarea>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn btn-primary">
                💾 Simpan Perubahan
            </button>
            <a href="novels.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php
require_once 'includes/footer.php';
?>
