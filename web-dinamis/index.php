<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika ada session user, arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
} else {
    // Jika tidak ada, arahkan ke login
    header("Location: login.php");
}
exit();
?>
