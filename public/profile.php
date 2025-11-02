<?php
session_start();
require_once __DIR__ . '/../src/db.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
$user = $_SESSION['user'];
$pdo = getDB();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'] ?? '';
    $pdo->prepare('UPDATE users SET fullname = ? WHERE id = ?')->execute([$fullname, $user['id']]);
    // if student, update students.info
    if ($user['role'] === 'student') {
        $info = $_POST['info'] ?? '';
        $pdo->prepare('UPDATE students SET info = ? WHERE student_code = ?')->execute([$info, $user['username']]);
    }
    $message = 'Cập nhật thành công';
    // refresh session fullname
    $u = $pdo->prepare('SELECT * FROM users WHERE id = ?'); $u->execute([$user['id']]); $new = $u->fetch(PDO::FETCH_ASSOC);
    $_SESSION['user']['fullname'] = $new['fullname'];
}
// load profile info
$profile = $pdo->prepare('SELECT * FROM users WHERE id = ?'); $profile->execute([$user['id']]); $profile = $profile->fetch(PDO::FETCH_ASSOC);
$studentInfo = null;
if ($user['role'] === 'student') {
    $st = $pdo->prepare('SELECT * FROM students WHERE student_code = ?'); $st->execute([$user['username']]); $studentInfo = $st->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profile</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Profile: <?php echo htmlspecialchars($profile['username']); ?></h2>
<div class="message"><?php echo htmlspecialchars($message); ?></div>
<form method="post">
    <label>Fullname</label>
    <input name="fullname" value="<?php echo htmlspecialchars($profile['fullname']); ?>" />
    <?php if ($user['role'] === 'student'): ?>
        <label>Thông tin</label>
        <textarea name="info"><?php echo htmlspecialchars($studentInfo['info'] ?? ''); ?></textarea>
    <?php endif; ?>
    <button name="update_profile">Lưu</button>
</form>
</body>
</html>