<?php
// public/login.php
session_start();
require_once __DIR__ . '/../src/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // VULN: Tìm user bằng hàm vulnerable (SQL Injection có thể xảy ra nếu username chứa ký tự đặc biệt)
    $user = findUserByUsername($username);
    if ($user ) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'fullname' => $user['fullname']
        ];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Sai username hoặc password';
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Đăng nhập - FAP Demo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-box">
    <h2>Đăng nhập hệ thống FAP (Demo)</h2>
    <form method="post">
        <label>Username</label>
        <input name="username" required>
        <label>Password</label>
        <input name="password" type="password" required>
        <button type="submit">Đăng nhập</button>
    </form>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
</div>
</body>
</html>