<?php
// public/admin.php
session_start();
require_once __DIR__ . '/../src/db.php';
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
// VULN: Không kiểm tra role chặt chẽ ở nhiều chỗ => Broken Access Control demo
$user = $_SESSION['user'];

// Hành động thêm/xóa user (demo)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $pdo = getDB();
    $stmt = $pdo->prepare('INSERT INTO users(username,password,role,fullname) VALUES(?,?,?,?)');
    $stmt->execute([$_POST['username'], $_POST['password'], $_POST['role'], $_POST['fullname']]);
    $message = 'Đã thêm user';
}
// handle complaint status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_action'])) {
    $pdo = getDB();
    $stmt = $pdo->prepare('UPDATE complaints SET status = ? WHERE id = ?');
    $stmt->execute([$_POST['status'], $_POST['complaint_id']]);
    $message = 'Updated complaint';
}
// handle payment status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_action'])) {
    $pdo = getDB();
    $stmt = $pdo->prepare('UPDATE payments SET status = ? WHERE id = ?');
    $stmt->execute([$_POST['status'], $_POST['payment_id']]);
    $message = 'Updated payment';
}
// handle assign teacher form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_teacher'])) {
    $pdo = getDB();
    $stmt = $pdo->prepare('INSERT INTO schedule(role,user_ref,course,day,time) VALUES(?,?,?,?,?)');
    $stmt->execute(['teacher', $_POST['teacher_user'], $_POST['course'], $_POST['day'], $_POST['time']]);
    $message = 'Assigned teacher to course';
}

// Handle salary update/create (only admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_salary'])) {
    if ($_SESSION['user']['role'] !== 'admin') {
        $message = 'Permission denied';
    } else {
        $teacher_user = $_POST['teacher_user_salary'] ?? '';
        $amount = intval($_POST['amount']);
        $bank = $_POST['bank_account'] ?? '';
        // find teacher id
        $uid = getDB()->prepare('SELECT id FROM users WHERE username = ? AND role = "teacher"');
        $uid->execute([$teacher_user]);
        $row = $uid->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $tid = $row['id'];
            // upsert: update if exists, else insert
            $exists = getDB()->prepare('SELECT id FROM teacher_salary WHERE teacher_id = ?');
            $exists->execute([$tid]);
            if ($exists->fetch()) {
                $upd = getDB()->prepare('UPDATE teacher_salary SET amount = ?, bank_account = ? WHERE teacher_id = ?');
                $upd->execute([$amount, $bank, $tid]);
                $message = 'Updated salary for ' . htmlspecialchars($teacher_user);
            } else {
                $ins = getDB()->prepare('INSERT INTO teacher_salary(teacher_id,amount,bank_account) VALUES(?,?,?)');
                $ins->execute([$tid, $amount, $bank]);
                $message = 'Created salary for ' . htmlspecialchars($teacher_user);
            }
        } else {
            $message = 'Teacher not found';
        }
    }
}

// Handle bulk inline salary updates from the editable table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_salary_update'])) {
    if ($_SESSION['user']['role'] !== 'admin') {
        $message = 'Permission denied';
    } else {
        $pdo = getDB();
        $teacher_users = $_POST['teacher_user'] ?? [];
        $amounts = $_POST['amount'] ?? [];
        $banks = $_POST['bank_account'] ?? [];
        // iterate and upsert each
        for ($i = 0; $i < count($teacher_users); $i++) {
            $tuser = trim($teacher_users[$i]);
            if ($tuser === '') continue;
            $amt = intval($amounts[$i] ?? 0);
            $bank = $banks[$i] ?? '';
            // get teacher id
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND role = "teacher"');
            $stmt->execute([$tuser]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$r) continue; // skip if not a teacher
            $tid = $r['id'];
            $exists = $pdo->prepare('SELECT id FROM teacher_salary WHERE teacher_id = ?');
            $exists->execute([$tid]);
            if ($exists->fetch()) {
                $upd = $pdo->prepare('UPDATE teacher_salary SET amount = ?, bank_account = ? WHERE teacher_id = ?');
                $upd->execute([$amt, $bank, $tid]);
            } else {
                $ins = $pdo->prepare('INSERT INTO teacher_salary(teacher_id,amount,bank_account) VALUES(?,?,?)');
                $ins->execute([$tid, $amt, $bank]);
            }
        }
        $message = 'Updated salaries';
    }
}

$users = getDB()->query('SELECT id,username,role,fullname FROM users')->fetchAll(PDO::FETCH_ASSOC);
$complaints = getDB()->query('SELECT c.*, s.student_code, s.fullname FROM complaints c LEFT JOIN students s ON s.id = c.student_id ORDER BY c.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$payments = getDB()->query('SELECT p.*, s.student_code, s.fullname FROM payments p LEFT JOIN students s ON s.id = p.student_id')->fetchAll(PDO::FETCH_ASSOC);
$grades = getDB()->query('SELECT g.*, s.student_code, s.fullname FROM grades g LEFT JOIN students s ON s.id = g.student_id')->fetchAll(PDO::FETCH_ASSOC);

// quick stats
$total_users = getDB()->query('SELECT COUNT(*) as c FROM users')->fetch(PDO::FETCH_ASSOC)['c'];
$total_students = getDB()->query('SELECT COUNT(*) as c FROM students')->fetch(PDO::FETCH_ASSOC)['c'];
$total_complaints = getDB()->query('SELECT COUNT(*) as c FROM complaints')->fetch(PDO::FETCH_ASSOC)['c'];
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin - Quản lý</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Admin - Quản lý người dùng</h2>
<div class="message"><?php echo htmlspecialchars($message); ?></div>
<table>
    <tr><th>ID</th><th>Username</th><th>Role</th><th>Fullname</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?php echo $u['id']; ?></td>
        <td><?php echo htmlspecialchars($u['username']); ?></td>
        <td><?php echo $u['role']; ?></td>
        <td><?php echo htmlspecialchars($u['fullname']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h3>Thêm user mới (demo)</h3>
<form method="post">
    <input name="username" placeholder="username">
    <input name="password" placeholder="password">
    <select name="role"><option value="student">student</option><option value="teacher">teacher</option><option value="admin">admin</option></select>
    <input name="fullname" placeholder="fullname">
    <button name="add_user">Thêm</button>
</form>

<h3>Đơn khiếu nại</h3>
<?php if (!empty($complaints)): ?>
    <table>
        <tr><th>ID</th><th>Student</th><th>File</th><th>Message</th><th>Status</th><th>Action</th></tr>
        <?php foreach ($complaints as $c): ?>
            <tr>
                <td><?php echo $c['id']; ?></td>
                <td><?php echo htmlspecialchars($c['student_code'] . ' - ' . $c['fullname']); ?></td>
                <td><?php if($c['file_path']): ?><a href="/<?php echo htmlspecialchars($c['file_path']); ?>">View</a><?php endif; ?></td>
                <td><?php echo htmlspecialchars($c['message']); ?></td>
                <td><?php echo htmlspecialchars($c['status']); ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="complaint_id" value="<?php echo $c['id']; ?>">
                        <select name="status">
                            <option value="new">new</option>
                            <option value="review">review</option>
                            <option value="resolved">resolved</option>
                        </select>
                        <button name="complaint_action">Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Không có đơn khiếu nại.</p>
<?php endif; ?>

<h3>Học phí (Payments)</h3>
<?php if (!empty($payments)): ?>
    <table>
        <tr><th>ID</th><th>Student</th><th>Amount</th><th>Status</th><th>Action</th></tr>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?php echo $p['id']; ?></td>
                <td><?php echo htmlspecialchars($p['student_code'] . ' - ' . $p['fullname']); ?></td>
                <td><?php echo $p['amount']; ?></td>
                <td><?php echo htmlspecialchars($p['status']); ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                        <select name="status"><option value="unpaid">unpaid</option><option value="paid">paid</option></select>
                        <button name="payment_action">Set</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Không có giao dịch.</p>
<?php endif; ?>

<h3>All Grades</h3>
<?php if (!empty($grades)): ?>
    <table>
        <tr><th>ID</th><th>Student</th><th>Course</th><th>Grade</th></tr>
        <?php foreach ($grades as $g): ?>
            <tr>
                <td><?php echo $g['id']; ?></td>
                <td><?php echo htmlspecialchars($g['student_code'] . ' - ' . $g['fullname']); ?></td>
                <td><?php echo htmlspecialchars($g['course']); ?></td>
                <td><?php echo htmlspecialchars($g['grade']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Không có grade.</p>
<?php endif; ?>

<h3>Quản lý lương giáo viên</h3>
<p>Chỉ admin có thể chỉnh sửa thông tin lương.</p>
<form method="post">
    <table>
        <tr><th>Teacher</th><th>Amount (VND)</th><th>Bank Account</th></tr>
        <?php
        $teachers = getDB()->query("SELECT u.username, u.fullname, ts.amount, ts.bank_account FROM users u LEFT JOIN teacher_salary ts ON ts.teacher_id = u.id WHERE u.role = 'teacher' ORDER BY u.username ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($teachers as $t): ?>
            <tr>
                <td>
                    <?php echo htmlspecialchars($t['username'] . ' - ' . $t['fullname']); ?>
                    <input type="hidden" name="teacher_user[]" value="<?php echo htmlspecialchars($t['username']); ?>">
                </td>
                <td>
                    <input type="number" name="amount[]" value="<?php echo intval($t['amount'] ?? 0) ?: ''; ?>" placeholder="0">
                </td>
                <td>
                    <input type="text" name="bank_account[]" value="<?php echo htmlspecialchars($t['bank_account'] ?? ''); ?>" placeholder="0123456789 - BankName">
                </td>
            </tr>
        <?php endforeach; ?>
        <!-- empty row for quick new teacher entry (must match an existing teacher username) -->
        <tr>
            <td>
                <input type="text" name="teacher_user[]" placeholder="new_teacher_username">
            </td>
            <td>
                <input type="number" name="amount[]" placeholder="0">
            </td>
            <td>
                <input type="text" name="bank_account[]" placeholder="0123456789 - BankName">
            </td>
        </tr>
    </table>
    <button name="bulk_salary_update">Lưu thay đổi lương</button>
</form>

<h3>Thống kê nhanh</h3>
<ul>
    <li>Tổng users: <?php echo intval($total_users); ?></li>
    <li>Tổng sinh viên: <?php echo intval($total_students); ?></li>
    <li>Tổng đơn khiếu nại: <?php echo intval($total_complaints); ?></li>
    <li>Tổng bản ghi điểm: <?php echo count($grades); ?></li>
</ul>

<h3>Gán giáo viên cho môn (ví dụ)</h3>
<form method="post">
    <input name="role" type="hidden" value="teacher">
    <label>Teacher username</label>
    <input name="teacher_user" placeholder="TungNM" />
    <label>Course</label>
    <input name="course" placeholder="Toán" />
    <label>Day</label>
    <input name="day" placeholder="Mon" />
    <label>Time</label>
    <input name="time" placeholder="08:00" />
    <button name="assign_teacher">Assign</button>
</form>


</body>
</html>