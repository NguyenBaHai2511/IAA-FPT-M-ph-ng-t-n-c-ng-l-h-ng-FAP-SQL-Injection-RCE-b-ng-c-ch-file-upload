<?php
session_start();
require_once __DIR__ . '/../src/db.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
$user = $_SESSION['user'];
// Lấy thông tin sinh viên theo username -> mã sinh viên
$pdo = getDB();
$st = $pdo->prepare('SELECT * FROM students WHERE student_code = ?');
$st->execute([$user['username']]);
$student = $st->fetch(PDO::FETCH_ASSOC);
// Load grades
$grades = [];
if ($student) {
    $grades = $pdo->prepare('SELECT g.*, u.fullname as teacher_name FROM grades g LEFT JOIN users u ON u.id = g.teacher_id WHERE g.student_id = ?');
    $grades->execute([$student['id']]);
    $grades = $grades->fetchAll(PDO::FETCH_ASSOC);
}

// Load schedule
$schedule = $pdo->prepare("SELECT * FROM schedule WHERE role='student' AND user_ref = ?");
$schedule->execute([$student['student_code'] ?? '']);
$schedule = $schedule->fetchAll(PDO::FETCH_ASSOC);

// Load payment
$payment = $pdo->prepare('SELECT * FROM payments WHERE student_id = ? LIMIT 1');
$payment->execute([$student['id'] ?? 0]);
$payment = $payment->fetch(PDO::FETCH_ASSOC);

// Handle complaint upload
$complaint_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $msg = $_POST['message'] ?? '';
    if (isset($_FILES['complaint_file']) && $_FILES['complaint_file']['error'] === UPLOAD_ERR_OK) {
        $updir = __DIR__ . '/uploads/complaints/';
        if (!is_dir($updir)) mkdir($updir, 0755, true);
        $name = basename($_FILES['complaint_file']['name']);
        // Simple protection: force .txt extension to avoid php execution
        $target = $updir . bin2hex(random_bytes(8)) . '-' . preg_replace('/[^a-zA-Z0-9._-]/','', $name) . '.txt';
        move_uploaded_file($_FILES['complaint_file']['tmp_name'], $target);
        $stmt = $pdo->prepare('INSERT INTO complaints(student_id,file_path,message,created_at) VALUES(?,?,?,datetime("now"))');
        $stmt->execute([$student['id'], 'uploads/complaints/' . basename($target), $msg]);
        $complaint_msg = 'Đã nộp đơn khiếu nại';
    } else {
        $complaint_msg = 'Vui lòng chọn file';
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sinh viên</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Sinh viên: <?php echo htmlspecialchars($user['fullname']); ?></h2>
<?php if ($student): ?>
    <p>Mã: <?php echo htmlspecialchars($student['student_code']); ?></p>
    <p>Lớp: <?php echo htmlspecialchars($student['class']); ?></p>
    <p>Thông tin: <?php echo htmlspecialchars($student['info']); ?></p>
<?php else: ?>
    <p>Không tìm thấy hồ sơ sinh viên.</p>
<?php endif; ?>
</br>
<h3>Điểm</h3>
<?php if (!empty($grades)): ?>
    <table>
        <tr><th>Môn</th><th>Điểm</th><th>Giảng viên</th><th>Ngày</th></tr>
        <?php foreach ($grades as $g): ?>
            <tr>
                <td><?php echo htmlspecialchars($g['course']); ?></td>
                <td><?php echo htmlspecialchars($g['grade']); ?></td>
                <td><?php echo htmlspecialchars($g['teacher_name']); ?></td>
                <td><?php echo htmlspecialchars($g['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Chưa có điểm.</p>
<?php endif; ?>

<h3>Lịch học</h3>
<?php if (!empty($schedule)): ?>
    <ul>
    <?php foreach ($schedule as $s): ?>
        <li><?php echo htmlspecialchars($s['course'] . ' - ' . $s['day'] . ' ' . $s['time']); ?></li>
    <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Chưa có lịch.</p>
<?php endif; ?>

<h3>Học phí</h3>
<?php if ($payment): ?>
    <p>Số tiền: <?php echo htmlspecialchars($payment['amount']); ?> - Trạng thái: <?php echo htmlspecialchars($payment['status']); ?></p>
    <p><a href="pay_qr_demo.php">Thanh toán / Xem chi tiết</a></p>
<?php else: ?>
    <p>Không có thông tin học phí.</p>
<?php endif; ?>

<h3>Nộp đơn khiếu nại / đơn từ</h3>
<div class="message"><?php echo htmlspecialchars($complaint_msg); ?></div>
<form method="post" enctype="multipart/form-data">
    <label>File đính kèm</label>
    <input type="file" name="complaint_file" />
    <label>Nội dung</label>
    <textarea name="message"></textarea>
    <button type="submit" name="submit_complaint">Nộp đơn</button>
<form>

<p><a href="pay_qr_demo.php">Thanh toán học phí</a></p>

<h3>Tài liệu học tập (ví dụ)</h3>
<ul>
    <li><a href="assets/docs/sample_syllabus.txt">Mô tả môn - Syllabus</a></li>
    <li><a href="assets/docs/sample_notes.txt">Ghi chú buổi học</a></li>
    <li><a href="#">Bài tập tuần 1 (ví dụ)</a></li>
    <li><a href="#">Tài liệu tham khảo (ví dụ)</a></li>
</ul>

<h3>Thông báo (ví dụ)</h3>
<ul>
    <li>2025-10-01: Khai giảng học kỳ mới</li>
    <li>2025-10-05: Phòng Đào tạo thông báo lịch thi</li>
</ul>

<h3>Lịch sử đơn khiếu nại (ví dụ)</h3>
<?php
    $compl_list = $pdo->prepare('SELECT * FROM complaints WHERE student_id = ? ORDER BY created_at DESC');
    $compl_list->execute([$student['id'] ?? 0]);
    $compl_list = $compl_list->fetchAll(PDO::FETCH_ASSOC);
?>
<?php if (!empty($compl_list)): ?>
    <ul>
    <?php foreach ($compl_list as $c): ?>
        <li><?php echo htmlspecialchars($c['created_at'] . ' - ' . $c['message'] . ' - ' . $c['status']); ?> - <?php if($c['file_path']): ?><a href="/<?php echo htmlspecialchars($c['file_path']); ?>">File</a><?php endif; ?></li>
    <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Chưa có đơn.</p>
<?php endif; ?>

<h3>Lịch sử thanh toán (ví dụ)</h3>
<?php
    $pay_list = $pdo->prepare('SELECT * FROM payments WHERE student_id = ? ORDER BY id DESC');
    $pay_list->execute([$student['id'] ?? 0]);
    $pay_list = $pay_list->fetchAll(PDO::FETCH_ASSOC);
?>
<?php if (!empty($pay_list)): ?>
    <table>
        <tr><th>TX</th><th>Amount</th><th>Status</th></tr>
        <?php foreach ($pay_list as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['tx_ref']); ?></td>
                <td><?php echo htmlspecialchars($p['amount']); ?></td>
                <td><?php echo htmlspecialchars($p['status']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Không có lịch sử thanh toán.</p>
<?php endif; ?>
</body>
</html>