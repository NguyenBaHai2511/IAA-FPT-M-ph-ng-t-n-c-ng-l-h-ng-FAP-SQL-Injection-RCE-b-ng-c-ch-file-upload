<?php
require_once __DIR__ . '/db.php';
$username = $argv[1] ?? null;
$amount = isset($argv[2]) ? intval($argv[2]) : null;
if (!$username || !$amount) {
    echo "Usage: php set_salary.php <username> <amount>\n";
    exit(1);
}
$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM users WHERE lower(username)=lower(?) AND role = "teacher" LIMIT 1');
$stmt->execute([$username]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) {
    echo "Teacher not found: $username\n";
    exit(1);
}
$tid = $r['id'];
$exists = $pdo->prepare('SELECT id FROM teacher_salary WHERE teacher_id = ?');
$exists->execute([$tid]);
if ($exists->fetch()) {
    $upd = $pdo->prepare('UPDATE teacher_salary SET amount = ? WHERE teacher_id = ?');
    $upd->execute([$amount, $tid]);
    echo "Updated $username => $amount\n";
} else {
    $ins = $pdo->prepare('INSERT INTO teacher_salary(teacher_id,amount,bank_account) VALUES(?,?,?)');
    $ins->execute([$tid, $amount, '']);
    echo "Inserted $username => $amount\n";
}

?>
