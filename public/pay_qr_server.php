<?php
// public/pay_qr_server.php
// Server-side proxy: tạo ảnh QR bằng Google Chart API và trả về image/png
// Chỉ cho phép role 'student' truy cập (demo local)
session_start();
require_once __DIR__ . '/../src/db.php';

$user = $_SESSION['user'] ?? null;
if (!$user || (($user['role'] ?? '') !== 'student')) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden: only students can access this QR (demo)';
    exit;
}

$tx = $_GET['tx_ref'] ?? 'TX1001';
$amount = intval($_GET['amount'] ?? 1000);
$bank = 'SCHOOL_ACC: 123456789 - VietBank';

$payload = json_encode(['tx_ref' => $tx, 'amount' => $amount, 'bank_account' => $bank]);

$qrUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . rawurlencode($payload);

// Fetch the image from Google Chart API (simple proxy)
$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$img = @file_get_contents($qrUrl, false, $ctx);
if ($img === false) {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo 'Unable to generate QR (network error)';
    exit;
}

header('Content-Type: image/png');
echo $img;

?>
