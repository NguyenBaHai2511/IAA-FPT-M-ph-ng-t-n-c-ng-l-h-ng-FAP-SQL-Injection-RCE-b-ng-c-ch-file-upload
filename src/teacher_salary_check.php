<?php
require_once __DIR__ . '/db.php';
$pdo = getDB();
$stmt = $pdo->query("SELECT u.username, u.fullname, ts.amount, ts.bank_account FROM users u LEFT JOIN teacher_salary ts ON ts.teacher_id = u.id WHERE u.role = 'teacher'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['username'] . ' | ' . ($r['amount'] ? $r['amount'] : '-') . ' | ' . ($r['bank_account'] ? $r['bank_account'] : '-') . PHP_EOL;
}
?>