<?php
// src/api.php - API demo chứa lỗ hổng logic (insecure-by-design để demo)
// Mô tả vấn đề:
// - endpoint action=confirm_payment chấp nhận payload JSON và trực tiếp mark payment là 'paid'
//   dựa trên tx_ref do client gửi mà không có cơ chế verify/chữ ký/timestamp.
// - Đây là ví dụ về 'insecure business logic' / 'trusting client data'.
// Mitigation: xác thực nguồn (webhook signature), kiểm tra với provider, và tránh tin tưởng dữ liệu client.
require_once __DIR__ . '/db.php';
$action = $_GET['action'] ?? '';
if ($action === 'confirm_payment') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) { echo 'Invalid payload'; exit; }
    $tx = $data['tx_ref'] ?? '';
    $amount = intval($data['amount'] ?? 0);
    // VULN: Không verify chữ ký/ngân hàng, trực tiếp mark paid
    // NOTE: giữ hành vi gốc cho demo. Trong thực tế, cần verify với hệ thống thanh toán,
    // kiểm tra trạng thái trong DB, và dùng chữ ký/secret để xác thực request.
    $pdo = getDB();
    $stmt = $pdo->prepare('UPDATE payments SET status = ? WHERE tx_ref = ?');
    $stmt->execute(['paid', $tx]);
    echo "Payment marked paid for tx_ref=" . htmlspecialchars($tx) . " amount=" . $amount;
    exit;
}

// Return salary info for a teacher as JSON.
if ($action === 'get_salary') {
    session_start();
    $username = $_GET['username'] ?? null;
    if (!$username && isset($_SESSION['user'])) {
        $username = $_SESSION['user']['username'];
    }
    header('Content-Type: application/json');
    if (!$username) {
        echo json_encode(['success' => false, 'message' => 'No username provided']);
        exit;
    }
    $pdo = getDB();
    $u = $pdo->prepare('SELECT id,username,fullname FROM users WHERE lower(username)=lower(?) LIMIT 1');
    $u->execute([$username]);
    $row = $u->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    $s = $pdo->prepare('SELECT amount,bank_account FROM teacher_salary WHERE teacher_id = ? LIMIT 1');
    $s->execute([$row['id']]);
    $salary = $s->fetch(PDO::FETCH_ASSOC);
    if (!$salary) {
        echo json_encode(['success' => true, 'username' => $row['username'], 'fullname' => $row['fullname'], 'salary' => null]);
        exit;
    }
    echo json_encode(['success' => true, 'username' => $row['username'], 'fullname' => $row['fullname'], 'salary' => ['amount' => intval($salary['amount']), 'bank_account' => $salary['bank_account']]]);
    exit;
}

echo 'No action';
?>