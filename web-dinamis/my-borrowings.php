<?php
$page_title = "Pinjaman Saya";
$page_desc = "Daftar novel yang sedang Anda pinjam atau telah Anda kembalikan.";
require_once 'includes/header.php';

// Memastikan hanya member yang bisa mengakses
if ($_SESSION['role'] !== 'member') {
    $_SESSION['action_error'] = "Halaman ini hanya untuk anggota perpustakaan.";
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<div class="card-section">
    <div class="section-title">
        <span>📖</span> Riwayat Peminjaman Novel Anda
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cover</th>
                    <th>Judul Novel</th>
                    <th>Penulis</th>
                    <th>Tanggal Pinjam</th>
                    <th>Tanggal Kembali</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT b.id, n.title, n.author, n.cover_image, b.borrow_date, b.return_date, b.status 
                        FROM borrowings b 
                        JOIN novels n ON b.novel_id = n.id 
                        WHERE b.user_id = ? 
                        ORDER BY b.borrow_date DESC
                    ");
                    $stmt->execute([$user_id]);
                    $my_borrowings = $stmt->fetchAll();

                    if (count($my_borrowings) > 0) {
                        foreach ($my_borrowings as $row) {
                            $cover_src = 'assets/uploads/' . $row['cover_image'];
                            $has_cover = !empty($row['cover_image']) && file_exists($cover_src);

                            echo "<tr>";
                            echo "<td>";
                            if ($has_cover) {
                                echo "<img src='{$cover_src}' alt='Cover' class='novel-cover-img'>";
                            } else {
                                echo "<div class='cover-placeholder' style='width:40px; height:56px; font-size:14px;'>📖</div>";
                            }
                            echo "</td>";
                            echo "<td><strong>" . htmlspecialchars($row['title']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['author']) . "</td>";
                            echo "<td>" . htmlspecialchars(date('d-m-Y', strtotime($row['borrow_date']))) . "</td>";
                            echo "<td>" . ($row['return_date'] ? htmlspecialchars(date('d-m-Y', strtotime($row['return_date']))) : '-') . "</td>";
                            echo "<td>";
                            if ($row['status'] === 'borrowed') {
                                echo "<span class='badge badge-warning'>Dipinjam</span>";
                            } else {
                                echo "<span class='badge badge-success'>Dikembalikan</span>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center; color: var(--text-muted);'>Anda belum pernah meminjam novel apapun.</td></tr>";
                    }
                } catch (PDOException $e) {
                    echo "<tr><td colspan='6' class='alert alert-danger'>Gagal mengambil data: " . $e->getMessage() . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
