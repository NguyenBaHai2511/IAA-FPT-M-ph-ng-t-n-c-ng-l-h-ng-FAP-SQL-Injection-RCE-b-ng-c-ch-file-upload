<?php
require_once __DIR__ . '/db.php';
$username = $argv[1] ?? 'TungNM';
$pdo = getDB();
$stmt = $pdo->prepare('SELECT u.id,u.username,u.fullname, ts.amount, ts.bank_account FROM users u LEFT JOIN teacher_salary ts ON ts.teacher_id = u.id WHERE lower(u.username)=lower(?) LIMIT 1');
$stmt->execute([$username]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) {
    echo "No user found for: $username\n";
    exit(0);
}
echo "username: " . $r['username'] . "\n";
echo "fullname: " . ($r['fullname'] ?? '-') . "\n";
echo "amount: " . ($r['amount'] ?? 'NULL') . "\n";
echo "bank_account: " . ($r['bank_account'] ?? 'NULL') . "\n";

// Also print raw teacher_salary table rows for visibility
$all = $pdo->query('SELECT * FROM teacher_salary')->fetchAll(PDO::FETCH_ASSOC);
echo "\nAll teacher_salary rows:\n";
foreach ($all as $row) {
    echo json_encode($row) . "\n";
}

?>
