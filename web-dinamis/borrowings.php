<?php
$page_title = "Transaksi Peminjaman";
$page_desc = "Pencatatan peminjaman novel oleh anggota dan pemrosesan pengembalian.";
require_once 'includes/header.php';

// Proteksi admin
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['action_error'] = "Anda tidak memiliki hak akses ke halaman tersebut.";
    header("Location: dashboard.php");
    exit();
}

$error = '';

// 1. PROSES PENGEMBALIAN BUKU
if (isset($_POST['action']) && $_POST['action'] === 'return') {
    $borrow_id = intval($_POST['borrow_id']);
    $return_date = date('Y-m-d');

    try {
        $pdo->beginTransaction();

        // Ambil data peminjaman untuk mencari novel_id dan verifikasi statusnya
        $stmt = $pdo->prepare("SELECT novel_id, status FROM borrowings WHERE id = ? FOR UPDATE");
        $stmt->execute([$borrow_id]);
        $borrow = $stmt->fetch();

        if (!$borrow) {
            $error = "Data transaksi peminjaman tidak ditemukan.";
            $pdo->rollBack();
        } elseif ($borrow['status'] === 'returned') {
            $error = "Novel ini sudah dikembalikan sebelumnya.";
            $pdo->rollBack();
        } else {
            // Update status peminjaman ke 'returned' dan simpan tanggal kembali
            $stmt = $pdo->prepare("UPDATE borrowings SET status = 'returned', return_date = ? WHERE id = ?");
            $stmt->execute([$return_date, $borrow_id]);

            // Tambahkan stok novel kembali
            $stmt = $pdo->prepare("UPDATE novels SET stock = stock + 1 WHERE id = ?");
            $stmt->execute([$borrow['novel_id']]);

            $pdo->commit();
            $_SESSION['action_success'] = "Novel berhasil dikembalikan! Stok buku bertambah.";
            header("Location: borrowings.php");
            exit();
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Gagal memproses pengembalian: " . $e->getMessage();
    }
}

// 2. PROSES TAMBAH PEMINJAMAN MANUAL OLEH ADMIN
if (isset($_POST['action']) && $_POST['action'] === 'add_borrow') {
    $user_id = intval($_POST['user_id']);
    $novel_id = intval($_POST['novel_id']);
    $borrow_date = trim($_POST['borrow_date']);

    if ($user_id <= 0 || $novel_id <= 0 || empty($borrow_date)) {
        $error = "Anggota, Novel, dan Tanggal Pinjam wajib diisi!";
    } else {
        try {
            $pdo->beginTransaction();

            // Cek stok novel
            $stmt = $pdo->prepare("SELECT title, stock FROM novels WHERE id = ? FOR UPDATE");
            $stmt->execute([$novel_id]);
            $novel = $stmt->fetch();

            if (!$novel) {
                $error = "Novel tidak ditemukan!";
                $pdo->rollBack();
            } elseif ($novel['stock'] <= 0) {
                $error = "Stok novel '" . htmlspecialchars($novel['title']) . "' sedang habis!";
                $pdo->rollBack();
            } else {
                // Cek apakah user sedang meminjam novel ini
                $stmt = $pdo->prepare("SELECT id FROM borrowings WHERE user_id = ? AND novel_id = ? AND status = 'borrowed'");
                $stmt->execute([$user_id, $novel_id]);

                if ($stmt->fetch()) {
                    $error = "Anggota tersebut sedang meminjam novel ini.";
                    $pdo->rollBack();
                } else {
                    // Catat peminjaman
                    $stmt = $pdo->prepare("INSERT INTO borrowings (user_id, novel_id, borrow_date, status) VALUES (?, ?, ?, 'borrowed')");
                    $stmt->execute([$user_id, $novel_id, $borrow_date]);

                    // Kurangi stok novel
                    $stmt = $pdo->prepare("UPDATE novels SET stock = stock - 1 WHERE id = ?");
                    $stmt->execute([$novel_id]);

                    $pdo->commit();
                    $_SESSION['action_success'] = "Peminjaman berhasil dicatat!";
                    header("Location: borrowings.php");
                    exit();
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal mencatat peminjaman: " . $e->getMessage();
        }
    }
}
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="form-grid">
    <!-- PANEL KIRI: DAFTAR TRANSAKSI PEMINJAMAN -->
    <div class="card-section" style="grid-column: span 2;">
        <div class="section-title">
            <span>🔄</span> Riwayat & Status Peminjaman Novel
        </div>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Peminjam</th>
                        <th>Judul Novel</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt = $pdo->query("
                            SELECT b.id, u.fullname, n.title, b.borrow_date, b.return_date, b.status 
                            FROM borrowings b 
                            JOIN users u ON b.user_id = u.id 
                            JOIN novels n ON b.novel_id = n.id 
                            ORDER BY b.status ASC, b.borrow_date DESC
                        ");
                        $borrowings = $stmt->fetchAll();

                        if (count($borrowings) > 0) {
                            foreach ($borrowings as $row) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['fullname']) . "</td>";
                                echo "<td><strong>" . htmlspecialchars($row['title']) . "</strong></td>";
                                echo "<td>" . htmlspecialchars(date('d-m-Y', strtotime($row['borrow_date']))) . "</td>";
                                echo "<td>" . ($row['return_date'] ? htmlspecialchars(date('d-m-Y', strtotime($row['return_date']))) : '-') . "</td>";
                                echo "<td>";
                                if ($row['status'] === 'borrowed') {
                                    echo "<span class='badge badge-warning'>Dipinjam</span>";
                                } else {
                                    echo "<span class='badge badge-success'>Dikembalikan</span>";
                                }
                                echo "</td>";
                                echo "<td>";
                                if ($row['status'] === 'borrowed') {
                                    echo "<form action='borrowings.php' method='POST' style='display:inline;' onsubmit=\"return confirm('Apakah Anda ingin memproses pengembalian novel ini?');\">
                                            <input type='hidden' name='action' value='return'>
                                            <input type='hidden' name='borrow_id' value='{$row['id']}'>
                                            <button type='submit' class='btn btn-success' style='padding: 6px 12px; font-size:12px;'>📥 Kembalikan</button>
                                          </form>";
                                } else {
                                    echo "<span style='color: var(--text-muted); font-size: 12px;'>Selesai</span>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align: center; color: var(--text-muted);'>Belum ada riwayat peminjaman buku.</td></tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='6' class='alert alert-danger'>Gagal memuat transaksi: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PANEL KANAN: FORM PINJAM BUKU MANUAL -->
    <div class="card-section" style="grid-column: span 2;">
        <div class="section-title">
            <span>➕</span> Catat Peminjaman Baru (Manual)
        </div>

        <form action="borrowings.php" method="POST">
            <input type="hidden" name="action" value="add_borrow">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="user_id">Pilih Anggota (Member)</label>
                    <select id="user_id" name="user_id" class="input-control" required>
                        <option value="">-- Pilih Anggota --</option>
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT id, fullname, username FROM users WHERE role = 'member' ORDER BY fullname ASC");
                            while ($user = $stmt->fetch()) {
                                echo "<option value='{$user['id']}'>" . htmlspecialchars($user['fullname']) . " (" . htmlspecialchars($user['username']) . ")</option>";
                            }
                        } catch (PDOException $e) {
                            echo "<option value=''>Gagal memuat anggota</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="novel_id">Pilih Novel Yang Tersedia</label>
                    <select id="novel_id" name="novel_id" class="input-control" required>
                        <option value="">-- Pilih Novel --</option>
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT id, title, stock FROM novels WHERE stock > 0 ORDER BY title ASC");
                            while ($novel = $stmt->fetch()) {
                                echo "<option value='{$novel['id']}'>" . htmlspecialchars($novel['title']) . " (Stok: {$novel['stock']})</option>";
                            }
                        } catch (PDOException $e) {
                            echo "<option value=''>Gagal memuat novel</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="max-width: 300px;">
                <label for="borrow_date">Tanggal Peminjaman</label>
                <input type="date" id="borrow_date" name="borrow_date" class="input-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    🚀 Catat Peminjaman
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
