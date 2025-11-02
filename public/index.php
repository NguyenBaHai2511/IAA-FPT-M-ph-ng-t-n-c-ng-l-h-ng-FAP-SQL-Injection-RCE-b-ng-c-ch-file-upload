<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user = $_SESSION['user'];
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard - <?php echo htmlspecialchars($user['fullname']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header>
    <h1>FAP - Hệ thống quản lý (Demo)</h1>
    <div class="user">Xin chào, <?php echo htmlspecialchars($user['fullname']); ?> (<?php echo $user['role']; ?>) | <a href="logout.php">Đăng xuất</a></div>
</header>
<main>
    <?php if ($user['role'] === 'admin'): ?>
        <h2>Trang Admin</h2>
        <ul>
            <li><a href="admin.php">Quản lý người dùng / Đơn / Học phí / Điểm</a></li>
            <li><a href="upload.php">Upload file</a></li>
            <li><a href="pay_qr_demo.php">Thanh toán hệ thống</a></li>
            <li><a href="profile.php">Thông tin cá nhân</a></li>
            <!-- DDoS demo removed -->
        </ul>
    <?php elseif ($user['role'] === 'teacher'): ?>
        <h2>Trang Giảng viên</h2>
        <ul>
            <li><a href="teacher.php">Tài liệu & chấm điểm / Điểm danh</a></li>
            <li><a href="upload.php">Upload tài liệu</a></li>
            <li><a href="profile.php">Thông tin cá nhân</a></li>
            <li><a href="student.php">Xem danh sách sinh viên</a></li>
        </ul>
    <?php else: ?>
        <h2>Trang Sinh viên</h2>
        <ul>
            <li><a href="student.php">Xem điểm / Lịch học / Học phí</a></li>
            <li><a href="pay_qr_demo.php">Thanh toán học phí</a></li>
            <li><a href="profile.php">Thông tin cá nhân</a></li>
            <li><a href="upload.php">Nộp đơn/Upload tài liệu</a></li>
        </ul>
    <?php endif; ?>
</main>
</body>
</html>