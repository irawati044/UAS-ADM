<?php
$page_title = "Dashboard Perpustakaan_Kholis Irawati iskandar_2388010025";
$page_desc = "Selamat datang kembali di panel perpustakaan digital NovelLib.";
require_once 'includes/header.php';

// Ambil data statistik untuk card (Admin & Member bisa lihat beberapa statistik)
try {
    // Total Novel
    $stmt = $pdo->query("SELECT COUNT(*) FROM novels");
    $total_novels = $stmt->fetchColumn();

    // Total Kategori
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $total_categories = $stmt->fetchColumn();

    // Total Sedang Dipinjam
    $stmt = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status = 'borrowed'");
    $total_borrowed = $stmt->fetchColumn();

    // Total Anggota
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'member'");
    $total_members = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Gagal mengambil data statistik: " . $e->getMessage());
}

// LOGIKA PROSES PINJAM NOVEL (KHUSUS MEMBER)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'borrow') {
    if ($_SESSION['role'] !== 'member') {
        $_SESSION['action_error'] = "Hanya anggota (member) yang dapat meminjam novel.";
    } else {
        $novel_id = intval($_POST['novel_id']);
        $user_id = $_SESSION['user_id'];
        $borrow_date = date('Y-m-d');

        try {
            // Mulai transaksi database
            $pdo->beginTransaction();

            // 1. Cek stok novel
            $stmt = $pdo->prepare("SELECT title, stock FROM novels WHERE id = ? FOR UPDATE");
            $stmt->execute([$novel_id]);
            $novel = $stmt->fetch();

            if (!$novel) {
                $_SESSION['action_error'] = "Novel tidak ditemukan!";
                $pdo->rollBack();
            } elseif ($novel['stock'] <= 0) {
                $_SESSION['action_error'] = "Maaf, stok novel '" . htmlspecialchars($novel['title']) . "' sedang habis!";
                $pdo->rollBack();
            } else {
                // 2. Cek apakah member sudah meminjam novel ini dan belum dikembalikan
                $stmt = $pdo->prepare("SELECT id FROM borrowings WHERE user_id = ? AND novel_id = ? AND status = 'borrowed'");
                $stmt->execute([$user_id, $novel_id]);
                
                if ($stmt->fetch()) {
                    $_SESSION['action_error'] = "Anda sedang meminjam novel ini. Kembalikan terlebih dahulu sebelum meminjam lagi!";
                    $pdo->rollBack();
                } else {
                    // 3. Catat peminjaman
                    $stmt = $pdo->prepare("INSERT INTO borrowings (user_id, novel_id, borrow_date, status) VALUES (?, ?, ?, 'borrowed')");
                    $stmt->execute([$user_id, $novel_id, $borrow_date]);

                    // 4. Kurangi stok novel
                    $stmt = $pdo->prepare("UPDATE novels SET stock = stock - 1 WHERE id = ?");
                    $stmt->execute([$novel_id]);

                    $pdo->commit();
                    $_SESSION['action_success'] = "Novel '" . htmlspecialchars($novel['title']) . "' berhasil dipinjam! Silakan ambil buku fisik di perpustakaan.";
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['action_error'] = "Gagal memproses peminjaman: " . $e->getMessage();
        }
    }
    // Redirect kembali ke halaman ini agar tidak ada post resubmission
    header("Location: dashboard.php");
    exit();
}
?>

<!-- STATS GRID -->
<div class="stats-grid">
    <div class="stats-card">
        <div class="stats-info">
            <h3>Total Koleksi Novel</h3>
            <div class="value"><?php echo $total_novels; ?></div>
        </div>
        <div class="stats-icon blue">📚</div>
    </div>
    
    <div class="stats-card">
        <div class="stats-info">
            <h3>Kategori Novel</h3>
            <div class="value"><?php echo $total_categories; ?></div>
        </div>
        <div class="stats-icon purple">🏷️</div>
    </div>

    <div class="stats-card">
        <div class="stats-info">
            <h3>Sedang Dipinjam</h3>
            <div class="value"><?php echo $total_borrowed; ?></div>
        </div>
        <div class="stats-icon warning">🔄</div>
    </div>

    <div class="stats-card">
        <div class="stats-info">
            <h3>Anggota Terdaftar</h3>
            <div class="value"><?php echo $total_members; ?></div>
        </div>
        <div class="stats-icon success">👥</div>
    </div>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
    <!-- ==================== TAMPILAN ADMIN ==================== -->
    <div class="card-section">
        <div class="section-title">
            <span>⏱️</span> Log Peminjaman Novel Terbaru (Aktif)
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Peminjam</th>
                        <th>Judul Novel</th>
                        <th>Tanggal Pinjam</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        // Ambil 5 transaksi peminjaman aktif terbaru
                        $stmt = $pdo->query("
                            SELECT b.id, u.fullname, n.title, b.borrow_date, b.status 
                            FROM borrowings b 
                            JOIN users u ON b.user_id = u.id 
                            JOIN novels n ON b.novel_id = n.id 
                            WHERE b.status = 'borrowed' 
                            ORDER BY b.borrow_date DESC 
                            LIMIT 5
                        ");
                        $recent_borrowings = $stmt->fetchAll();

                        if (count($recent_borrowings) > 0) {
                            foreach ($recent_borrowings as $row) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td>" . htmlspecialchars(date('d-m-Y', strtotime($row['borrow_date']))) . "</td>";
                                echo "<td><span class='badge badge-warning'>Dipinjam</span></td>";
                                echo "<td>
                                        <a href='borrowings.php' class='btn btn-secondary' style='padding: 6px 12px; font-size: 12px;'>
                                            Kelola Transaksi
                                        </a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align: center; color: var(--text-muted);'>Tidak ada peminjaman aktif saat ini.</td></tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='5'>Gagal memuat log terbaru: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- ==================== TAMPILAN MEMBER ==================== -->
    
    <!-- Bagian Pencarian / Filter -->
    <div class="card-section" style="padding: 20px 30px; margin-bottom: 24px;">
        <form method="GET" action="dashboard.php" style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" name="search" class="input-control" placeholder="Cari judul novel atau penulis..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </div>
            <div>
                <select name="category" class="input-control">
                    <option value="">Semua Kategori</option>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
                        while ($cat = $stmt->fetch()) {
                            $selected = (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : '';
                            echo "<option value='{$cat['id']}' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                        }
                    } catch (PDOException $e) {
                        // ignore
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">🔍 Cari</button>
            <?php if (isset($_GET['search']) || isset($_GET['category'])): ?>
                <a href="dashboard.php" class="btn btn-secondary" style="display: flex; align-items: center;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="novel-grid">
        <?php
        try {
            // Bangun query novel dengan filter
            $sql = "SELECT n.*, c.name AS category_name FROM novels n LEFT JOIN categories c ON n.category_id = c.id";
            $params = [];
            $conditions = [];

            if (!empty($_GET['search'])) {
                $conditions[] = "(n.title LIKE ? OR n.author LIKE ?)";
                $search_val = "%" . $_GET['search'] . "%";
                $params[] = $search_val;
                $params[] = $search_val;
            }

            if (!empty($_GET['category'])) {
                $conditions[] = "n.category_id = ?";
                $params[] = intval($_GET['category']);
            }

            if (count($conditions) > 0) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $sql .= " ORDER BY n.title ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $novels = $stmt->fetchAll();

            if (count($novels) > 0) {
                foreach ($novels as $novel) {
                    $cover_src = 'assets/uploads/' . $novel['cover_image'];
                    $has_cover = !empty($novel['cover_image']) && file_exists($cover_src);
                    ?>
                    <div class="novel-card">
                        <div class="novel-card-cover">
                            <?php if ($has_cover): ?>
                                <img src="<?php echo $cover_src; ?>" alt="<?php echo htmlspecialchars($novel['title']); ?>">
                            <?php else: ?>
                                <div class="placeholder">📖</div>
                            <?php endif; ?>
                            
                            <span class="novel-card-badge badge badge-primary">
                                <?php echo htmlspecialchars($novel['category_name'] ?? 'Umum'); ?>
                            </span>
                        </div>
                        
                        <div class="novel-card-content">
                            <h3 class="novel-card-title" title="<?php echo htmlspecialchars($novel['title']); ?>">
                                <?php echo htmlspecialchars($novel['title']); ?>
                            </h3>
                            <div class="novel-card-author">
                                Oleh: <strong><?php echo htmlspecialchars($novel['author']); ?></strong>
                            </div>
                            <p class="novel-card-synopsis">
                                <?php echo htmlspecialchars($novel['synopsis'] ?: 'Tidak ada sinopsis untuk novel ini.'); ?>
                            </p>
                            
                            <div class="novel-card-footer">
                                <span class="stock">Stok: <strong><?php echo $novel['stock']; ?></strong></span>
                                <span class="year"><?php echo $novel['year'] ?: '-'; ?></span>
                            </div>
                            
                            <div style="margin-top: 15px;">
                                <?php if ($novel['stock'] > 0): ?>
                                    <form action="dashboard.php" method="POST" onsubmit="return confirm('Apakah Anda ingin meminjam novel ini?');">
                                        <input type="hidden" name="action" value="borrow">
                                        <input type="hidden" name="novel_id" value="<?php echo $novel['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-block" style="padding: 8px 16px; font-size: 13px;">
                                            📥 Pinjam Novel
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-block" disabled style="padding: 8px 16px; font-size: 13px; cursor: not-allowed; opacity: 0.6;">
                                        ❌ Stok Habis
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<div style='grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted); background-color: var(--bg-secondary); border-radius: 16px; border: 1px dashed var(--border-color);'>
                        Tidak ada novel yang cocok dengan kriteria pencarian Anda.
                      </div>";
            }
        } catch (PDOException $e) {
            echo "<div style='grid-column: 1/-1;' class='alert alert-danger'>Gagal memuat novel: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>

<?php endif; ?>

<?php
require_once 'includes/footer.php';
?>
