<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.save_path", __DIR__ . "/sessions");
    session_start();
}
require_once "config/db.php"; // <--- WAJIB TAMBAHKAN INI DI ATAS HEADER
$page_title = "Tambah Novel Baru";
$page_desc = "Masukkan detail novel baru dan unggah gambar sampul.";
require_once 'includes/header.php';

// Proteksi halaman
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['action_error'] = "Anda tidak memiliki hak akses ke halaman tersebut.";
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $publisher = trim($_POST['publisher']);
    $year = !empty($_POST['year']) ? intval($_POST['year']) : null;
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $stock = intval($_POST['stock']);
    $synopsis = trim($_POST['synopsis']);
    $cover_image = null;

    if (empty($title) || empty($author) || $stock < 0) {
        $error = "Judul, penulis, dan stok minimal 0 wajib diisi!";
    } else {
        try {
            // PROSES UNGGAH GAMBAR COVER
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['cover_image']['tmp_name'];
                $fileName = $_FILES['cover_image']['name'];
                $fileSize = $_FILES['cover_image']['size'];
                $fileType = $_FILES['cover_image']['type'];
                
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                // Ekstensi yang diizinkan
                $allowedExtensions = ['jpg', 'jpeg', 'png'];

                if (in_array($fileExtension, $allowedExtensions)) {
                    // Batasi ukuran gambar (max 2MB)
                    if ($fileSize < 2 * 1024 * 1024) {
                        $uploadFileDir = 'assets/uploads/';
                        
                        // Buat direktori jika belum ada
                        if (!is_dir($uploadFileDir)) {
                            mkdir($uploadFileDir, 0755, true);
                        }

                        $newFileName = uniqid('cover_', true) . '.' . $fileExtension;
                        $dest_path = $uploadFileDir . $newFileName;

                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            $cover_image = $newFileName;
                        } else {
                            $error = "Terjadi kesalahan saat memindahkan file yang diunggah.";
                        }
                    } else {
                        $error = "Ukuran cover novel terlalu besar. Maksimal 2MB.";
                    }
                } else {
                    $error = "Format file tidak didukung. Harap unggah format JPG, JPEG, atau PNG.";
                }
            }

            if (empty($error)) {
                $stmt = $pdo->prepare("
                    INSERT INTO novels (title, author, publisher, year, category_id, synopsis, cover_image, stock) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $author, $publisher, $year, $category_id, $synopsis, $cover_image, $stock]);

                $_SESSION['action_success'] = "Novel '" . htmlspecialchars($title) . "' berhasil ditambahkan!";
                header("Location: novels.php");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Gagal menyimpan data ke database: " . $e->getMessage();
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

    <form action="novel-add.php" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label for="title">Judul Novel *</label>
                <input type="text" id="title" name="title" class="input-control" placeholder="Masukkan judul novel" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="author">Penulis *</label>
                <input type="text" id="author" name="author" class="input-control" placeholder="Nama penulis" required value="<?php echo isset($_POST['author']) ? htmlspecialchars($_POST['author']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="publisher">Penerbit</label>
                <input type="text" id="publisher" name="publisher" class="input-control" placeholder="Nama penerbit" value="<?php echo isset($_POST['publisher']) ? htmlspecialchars($_POST['publisher']) : ''; ?>">
            </div>

            <div class="form-grid" style="padding: 0;">
                <div class="form-group">
                    <label for="year">Tahun Terbit</label>
                    <input type="number" id="year" name="year" class="input-control" placeholder="Contoh: 2024" min="1000" max="2100" value="<?php echo isset($_POST['year']) ? htmlspecialchars($_POST['year']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="stock">Stok Buku *</label>
                    <input type="number" id="stock" name="stock" class="input-control" min="0" required value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '1'; ?>">
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
                            $selected = (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '';
                            echo "<option value='{$cat['id']}' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option value=''>Gagal memuat kategori</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="cover_image">Cover Novel (Format: JPG/PNG, Max: 2MB)</label>
                <input type="file" id="cover_image" name="cover_image" class="input-control" accept="image/*">
                <div class="cover-preview-container">
                    <img id="cover-preview-img" src="" alt="Pratinjau Cover" class="cover-preview">
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-top: 10px;">
            <label for="synopsis">Sinopsis Novel</label>
            <textarea id="synopsis" name="synopsis" class="input-control" placeholder="Tuliskan sinopsis singkat novel di sini..."><?php echo isset($_POST['synopsis']) ? htmlspecialchars($_POST['synopsis']) : ''; ?></textarea>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn btn-primary">
                💾 Simpan Novel
            </button>
            <a href="novels.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php
require_once 'includes/footer.php';
?>
