<?php
session_start();
require_once __DIR__ . '/../src/db.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
$user = $_SESSION['user'];
// Demo: danh sách sinh viên và chấm điểm (dùng input không kiểm soát)
$students = getDB()->query('SELECT * FROM students')->fetchAll(PDO::FETCH_ASSOC);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['grade_student'])) {
        // Insert grade
        $stmt = getDB()->prepare('INSERT INTO grades(student_id,course,grade,teacher_id,created_at) VALUES(?,?,?,?,datetime("now"))');
        $stmt->execute([$_POST['student_id'], $_POST['course'], $_POST['grade'], $_SESSION['user']['id']]);
        $message = 'Đã chấm điểm cho ID ' . intval($_POST['student_id']) . ' môn ' . htmlspecialchars($_POST['course']);
    }
    if (isset($_POST['attendance_submit'])) {
        $stmt = getDB()->prepare('INSERT INTO attendance(student_id,course,date,status,teacher_id) VALUES(?,?,?,?,?)');
        $stmt->execute([$_POST['student_id'], $_POST['course_att'], $_POST['date'], $_POST['status'], $_SESSION['user']['id']]);
        $message = 'Đã lưu điểm danh cho ID ' . intval($_POST['student_id']);
    }
}

// Load schedule for teacher
$schedule = getDB()->prepare("SELECT * FROM schedule WHERE role='teacher' AND user_ref = ?");
$schedule->execute([$_SESSION['user']['username']]);
$schedule = $schedule->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Giảng viên</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Giảng viên - <?php echo htmlspecialchars($user['fullname']); ?></h2>
<p>Tài khoản: <?php echo htmlspecialchars($user['username']); ?> | Vai trò: <?php echo htmlspecialchars($user['role']); ?></p>
<div class="message"><?php echo htmlspecialchars($message); ?></div>
<h3>Danh sách sinh viên</h3>
<ul>
    <?php foreach ($students as $s): ?>
        <li><?php echo htmlspecialchars($s['student_code'] . ' - ' . $s['fullname']); ?></li>
    <?php endforeach; ?>
</ul>

<h3>Chấm điểm (demo)</h3>
<form method="post">
    <select name="student_id">
        <?php foreach ($students as $s): ?>
            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['fullname']); ?></option>
        <?php endforeach; ?>
    </select>
    <input name="course" placeholder="Môn">
    <input name="grade" placeholder="Điểm">
    <button name="grade_student">Lưu</button>
</form>

<h3>Điểm danh</h3>
<form method="post">
    <select name="student_id">
        <?php foreach ($students as $s): ?>
            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['fullname']); ?></option>
        <?php endforeach; ?>
    </select>
    <input name="course_att" placeholder="Môn">
    <input name="date" type="date" />
    <select name="status"><option value="present">Present</option><option value="absent">Absent</option></select>
    <button name="attendance_submit">Gửi</button>
</form>

<h3>Lịch dạy</h3>
<?php if (!empty($schedule)): ?>
    <ul>
    <?php foreach ($schedule as $s): ?>
        <li><?php echo htmlspecialchars($s['course'] . ' - ' . $s['day'] . ' ' . $s['time']); ?></li>
    <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Chưa có lịch dạy.</p>
<?php endif; ?>

<?php
// Show salary info for teacher (visible only to teacher role)
$salaryInfo = null;
if ($_SESSION['user']['role'] === 'teacher') {
    $pdo = getDB();
    // try to find user id by username (case-insensitive) or fullname as fallback
    $uidStmt = $pdo->prepare('SELECT id FROM users WHERE lower(username) = lower(?) OR lower(fullname) = lower(?) LIMIT 1');
    $uidStmt->execute([$_SESSION['user']['username'], $_SESSION['user']['fullname']]);
    $row = $uidStmt->fetch(PDO::FETCH_ASSOC);
    $uid = $row['id'] ?? null;
    if ($uid) {
        $sstmt = $pdo->prepare('SELECT * FROM teacher_salary WHERE teacher_id = ? LIMIT 1');
        $sstmt->execute([$uid]);
        $salaryInfo = $sstmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<?php if ($salaryInfo): ?>
    <h3>Thông tin lương</h3>
    <p>Số tiền lương: <span id="salary_amount"><?php echo number_format($salaryInfo['amount']); ?></span> VND</p>
    <p>Tài khoản ngân hàng: <span id="salary_bank"><?php echo htmlspecialchars($salaryInfo['bank_account']); ?></span></p>
<?php else: ?>
    <h3>Thông tin lương</h3>
    <p>Không có thông tin lương (admin có thể cấu hình).</p>
    <p>Số tiền lương: <span id="salary_amount">-</span> VND</p>
    <p>Tài khoản ngân hàng: <span id="salary_bank">-</span></p>
<?php endif; ?>

<h3>Bảng điểm (ví dụ tổng hợp)</h3>
<?php
    $allgrades = getDB()->query("SELECT g.*, s.fullname as student_name FROM grades g LEFT JOIN students s ON s.id = g.student_id WHERE g.teacher_id = " . intval($_SESSION['user']['id']))->fetchAll(PDO::FETCH_ASSOC);
?>
<?php if (!empty($allgrades)): ?>
    <table>
        <tr><th>Student</th><th>Course</th><th>Grade</th><th>Ngày</th></tr>
        <?php foreach ($allgrades as $ag): ?>
            <tr>
                <td><?php echo htmlspecialchars($ag['student_name']); ?></td>
                <td><?php echo htmlspecialchars($ag['course']); ?></td>
                <td><?php echo htmlspecialchars($ag['grade']); ?></td>
                <td><?php echo htmlspecialchars($ag['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Chưa có bản ghi chấm điểm do bạn thực hiện.</p>
<?php endif; ?>

<h3>Record điểm danh gần đây (ví dụ)</h3>
<?php
    $recent = getDB()->query("SELECT a.*, s.fullname as student_name FROM attendance a LEFT JOIN students s ON s.id = a.student_id WHERE a.teacher_id = " . intval($_SESSION['user']['id']) . " ORDER BY a.date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php if (!empty($recent)): ?>
    <table>
        <tr><th>Student</th><th>Course</th><th>Date</th><th>Status</th></tr>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                <td><?php echo htmlspecialchars($r['course']); ?></td>
                <td><?php echo htmlspecialchars($r['date']); ?></td>
                <td><?php echo htmlspecialchars($r['status']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Không có dữ liệu điểm danh gần đây.</p>
<?php endif; ?>

<h3>Tài liệu dạy học (ví dụ)</h3>
<ul>
    <li><a href="assets/docs/sample_syllabus.txt">Syllabus - Toán</a></li>
    <li><a href="assets/docs/sample_notes.txt">Ghi chú bài giảng</a></li>
    <li><a href="#">Bài tập & Đáp án (ví dụ)</a></li>
</ul>

<!-- Removed hardcoded example salary to prefer DB-driven values -->

<h3>Thông báo giảng viên</h3>
<ul>
    <li>2025-10-03: Họp bộ môn lúc 10:00</li>
    <li>2025-10-10: Gửi điểm giữa kỳ</li>
</ul>
</body>
<script>
// Poll salary info periodically and update DOM if changed.
function formatNumber(n){
    if (n === null || n === undefined || n === 0) return '0';
    return new Intl.NumberFormat('en-US').format(n);
}
(function pollSalary(){
    const url = '/api.php?action=get_salary';
    fetch(url, {credentials: 'same-origin'})
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const salary = data.salary;
            const amtEl = document.getElementById('salary_amount');
            const bankEl = document.getElementById('salary_bank');
            if (salary && salary.amount){
                const text = formatNumber(salary.amount);
                if (amtEl && amtEl.textContent.replace(/[, ]/g,'') != String(salary.amount)) amtEl.textContent = text;
                if (bankEl && bankEl.textContent !== (salary.bank_account || '')) bankEl.textContent = salary.bank_account || '';
            } else {
                if (amtEl) amtEl.textContent = '-';
                if (bankEl) bankEl.textContent = '-';
            }
        }).catch(()=>{});
    // poll every 5 seconds
    setTimeout(pollSalary, 5000);
})();
</script>
</html>