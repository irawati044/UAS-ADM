<?php
$page_title = "Kategori Novel";
$page_desc = "Kelola daftar kategori novel untuk pengelompokan koleksi.";
require_once 'includes/header.php';

// Proteksi akses admin
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['action_error'] = "Anda tidak memiliki hak akses ke halaman tersebut.";
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// 1. PROSES HAPUS KATEGORI
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        // Ambil nama kategori untuk notifikasi
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt->execute([$delete_id]);
        $cat = $stmt->fetch();
        
        if ($cat) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$delete_id]);
            $_SESSION['action_success'] = "Kategori '" . htmlspecialchars($cat['name']) . "' berhasil dihapus!";
        }
        header("Location: categories.php");
        exit();
    } catch (PDOException $e) {
        $error = "Gagal menghapus kategori (Kategori ini mungkin sedang digunakan oleh beberapa novel): " . $e->getMessage();
    }
}

// 2. PROSES TAMBAH & EDIT KATEGORI VIA POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = "Nama kategori wajib diisi!";
    } else {
        if ($action === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $_SESSION['action_success'] = "Kategori '" . htmlspecialchars($name) . "' berhasil ditambahkan!";
                header("Location: categories.php");
                exit();
            } catch (PDOException $e) {
                $error = "Gagal menambah kategori: Nama kategori sudah digunakan atau error DB.";
            }
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $id]);
                    $_SESSION['action_success'] = "Kategori '" . htmlspecialchars($name) . "' berhasil diperbarui!";
                    header("Location: categories.php");
                    exit();
                } catch (PDOException $e) {
                    $error = "Gagal memperbarui kategori: Nama kategori sudah digunakan atau error DB.";
                }
            }
        }
    }
}

// 3. LOGIKA MEMUAT DATA UNTUK FORM EDIT (BILA EDIT DIKLIK)
$edit_mode = false;
$edit_id = 0;
$edit_name = '';
$edit_desc = '';

if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$edit_id]);
        $cat_to_edit = $stmt->fetch();
        if ($cat_to_edit) {
            $edit_mode = true;
            $edit_name = $cat_to_edit['name'];
            $edit_desc = $cat_to_edit['description'];
        }
    } catch (PDOException $e) {
        $error = "Gagal memuat data kategori untuk diedit.";
    }
}
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="form-grid">
    <!-- PANEL KIRI: DAFTAR KATEGORI -->
    <div class="card-section">
        <div class="section-title">
            <span>🏷️</span> Daftar Kategori Aktif
        </div>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
                        $categories = $stmt->fetchAll();

                        if (count($categories) > 0) {
                            foreach ($categories as $row) {
                                echo "<tr>";
                                echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                                echo "<td style='color: var(--text-secondary); font-size:13px;'>" . htmlspecialchars($row['description'] ?? '-') . "</td>";
                                echo "<td>
                                        <div class='actions-btn-group'>
                                            <a href='categories.php?edit_id={$row['id']}' class='btn-icon edit' title='Edit'>✏️</a>
                                            <a href='categories.php?delete_id={$row['id']}' class='btn-icon delete' title='Hapus' onclick=\"return confirm('Apakah Anda yakin ingin menghapus kategori ini? Novel dengan kategori ini akan dikosongkan kategorinya.');\">🗑️</a>
                                        </div>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align: center; color: var(--text-muted);'>Belum ada data kategori.</td></tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='3' class='alert alert-danger'>Gagal memuat data.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PANEL KANAN: FORM TAMBAH / EDIT KATEGORI -->
    <div class="card-section">
        <div class="section-title">
            <span><?php echo $edit_mode ? '✏️' : '➕'; ?></span> 
            <?php echo $edit_mode ? 'Edit Kategori' : 'Tambah Kategori Baru'; ?>
        </div>

        <form action="categories.php" method="POST">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Nama Kategori *</label>
                <input type="text" id="name" name="name" class="input-control" placeholder="Contoh: Fantasi, Romance..." required value="<?php echo htmlspecialchars($edit_name); ?>">
            </div>

            <div class="form-group">
                <label for="description">Deskripsi Singkat</label>
                <textarea id="description" name="description" class="input-control" placeholder="Tulis penjelasan kategori di sini..." style="min-height: 120px;"><?php echo htmlspecialchars($edit_desc); ?></textarea>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_mode ? '💾 Perbarui Kategori' : '💾 Tambah Kategori'; ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="categories.php" class="btn btn-secondary">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
