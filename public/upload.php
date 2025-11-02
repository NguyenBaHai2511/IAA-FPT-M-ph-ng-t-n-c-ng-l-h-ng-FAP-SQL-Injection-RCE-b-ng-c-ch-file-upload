<?php
// public/upload.php - Demo upload (allow php) => RCE
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
$uploadDir = __DIR__ . '/uploads/';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $name = $_FILES['file']['name'];
    $tmp = $_FILES['file']['tmp_name'];
    // VULN: Không kiểm tra extension, cho phép upload PHP => RCE
    $dest = $uploadDir . basename($name);
    if (move_uploaded_file($tmp, $dest)) {
        $message = 'Uploaded to: uploads/' . basename($name) . '. You can access it via /uploads/' . basename($name);
    } else {
        $message = 'Upload failed';
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Upload (RCE Demo)</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Upload file (Demo RCE)</h2>
<div class="message"><?php echo htmlspecialchars($message); ?></div>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="file">
    <button type="submit">Upload</button>
</form>
<p>Lưu ý: trang này CHO PHÉP upload file .php để minh họa lỗ hổng RCE. Đừng chạy trên môi trường production.</p>
</body>
</html>