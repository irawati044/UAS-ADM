<?php
$page_title = "Manajemen Novel";
$page_desc = "Kelola data novel di perpustakaan (tambah, edit, dan hapus data).";
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

<div class="card-section">
    <!-- Header Tabel & Aksi Tambah -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <form method="GET" action="novels.php" style="display: flex; gap: 10px;">
            <input type="text" name="search" class="input-control" placeholder="Cari judul atau penulis..." value="<?php echo htmlspecialchars($search); ?>" style="max-width: 250px; padding: 8px 12px; font-size: 14px;">
            <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">🔍 Cari</button>
            <?php if (!empty($search)): ?>
                <a href="novels.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 14px;">Reset</a>
            <?php endif; ?>
        </form>

        <a href="novel-add.php" class="btn btn-success" style="padding: 10px 20px; font-size: 14px;">
            ➕ Tambah Novel Baru
        </a>
    </div>

    <!-- Tabel Data Novel -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cover</th>
                    <th>Judul Novel</th>
                    <th>Penulis</th>
                    <th>Penerbit</th>
                    <th>Tahun</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $sql = "SELECT n.*, c.name AS category_name FROM novels n LEFT JOIN categories c ON n.category_id = c.id";
                    $params = [];

                    if (!empty($search)) {
                        $sql .= " WHERE n.title LIKE ? OR n.author LIKE ? OR n.publisher LIKE ?";
                        $params = ["%$search%", "%$search%", "%$search%"];
                    }

                    $sql .= " ORDER BY n.created_at DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $novels = $stmt->fetchAll();

                    if (count($novels) > 0) {
                        foreach ($novels as $row) {
                            $cover_src = 'assets/uploads/' . $row['cover_image'];
                            $has_cover = !empty($row['cover_image']) && file_exists($cover_src);

                            echo "<tr>";
                            echo "<td>";
                            if ($has_cover) {
                                echo "<img src='{$cover_src}' alt='Cover' class='novel-cover-img'>";
                            } else {
                                echo "<div class='cover-placeholder'>📖</div>";
                            }
                            echo "</td>";
                            echo "<td><strong>" . htmlspecialchars($row['title']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['author']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['publisher'] ?? '-') . "</td>";
                            echo "<td>" . htmlspecialchars($row['year'] ?? '-') . "</td>";
                            echo "<td><span class='badge badge-primary'>" . htmlspecialchars($row['category_name'] ?? 'Umum') . "</span></td>";
                            echo "<td><strong>" . htmlspecialchars($row['stock']) . "</strong></td>";
                            echo "<td>
                                    <div class='actions-btn-group'>
                                        <a href='novel-edit.php?id={$row['id']}' class='btn-icon edit' title='Edit'>✏️</a>
                                        <a href='novel-delete.php?id={$row['id']}' class='btn-icon delete' title='Hapus' onclick=\"return confirm('Apakah Anda yakin ingin menghapus novel ini?');\">🗑️</a>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' style='text-align: center; color: var(--text-muted);'>Belum ada data novel.</td></tr>";
                    }
                } catch (PDOException $e) {
                    echo "<tr><td colspan='8' class='alert alert-danger'>Terjadi kesalahan database: " . $e->getMessage() . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
