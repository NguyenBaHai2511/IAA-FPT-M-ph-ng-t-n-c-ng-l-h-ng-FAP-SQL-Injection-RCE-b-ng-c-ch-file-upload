<?php
// public/pay_qr_demo.php
// Trang demo hiển thị mã QR tạo bởi server-side proxy (chỉ dành cho lab)
session_start();
require_once __DIR__ . '/../src/db.php';

$user = $_SESSION['user'] ?? null;
$isStudent = ($user && (($user['role'] ?? '') === 'student'));
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Demo Thanh toán - QR (Server-generated)</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 18px; }
    #qr { width: 300px; height: 300px; border: 1px solid #ddd; display:block; }
    .note { color: #666; font-size: 90%; }
    button { margin-right: 8px; }
  </style>
</head>
<body>
  <h2>Thanh toán học phí (QR do server tạo)</h2>
  <?php if (!$isStudent): ?>
    <p class="note">Bạn chưa đăng nhập với vai trò <strong>student</strong>. Hãy đăng nhập bằng tài khoản học sinh để xem QR (demo).</p>
  <?php endif; ?>

  <p>Nội dung QR sẽ là JSON cố định (ví dụ tài khoản ngân hàng của nhà trường). Bạn có thể thay tx_ref/amount bằng query string.</p>

  <div>
    <label>tx_ref: <input id="tx" value="TX1001" /></label>
    <label>amount: <input id="amount" value="1000" /></label>
    <button id="gen">Tạo QR</button>
    <button id="download">Mở QR trong tab mới</button>
  </div>

  <p>
    <img id="qr" src="" alt="QR" />
  </p>

  <p>
    <button id="swap">Mô phỏng thay QR (QR hacker)</button>
    <button id="restore">Khôi phục QR trường</button>
  </p>

  <script>
    function gen() {
      var tx = document.getElementById('tx').value || 'TX1001';
      var amount = parseInt(document.getElementById('amount').value) || 1000;
      var src = '/pay_qr_server.php?tx_ref=' + encodeURIComponent(tx) + '&amount=' + encodeURIComponent(amount);
      document.getElementById('qr').src = src;
    }
    document.getElementById('gen').addEventListener('click', gen);
    document.getElementById('download').addEventListener('click', function(){
      var src = document.getElementById('qr').src;
      if (!src) { alert('Chưa có QR'); return; }
      window.open(src, '_blank');
    });

    // Demo: thay QR bằng payload hacker (chỉ để minh họa client-side tampering)
    document.getElementById('swap').addEventListener('click', function(){
      var hacker = JSON.stringify({tx_ref: document.getElementById('tx').value || 'TX1001', amount: parseInt(document.getElementById('amount').value) || 1000, bank_account: 'HACKER_ACC: 999999999 - BadBank'});
      var src = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' + encodeURIComponent(hacker);
      document.getElementById('qr').src = src;
    });
    document.getElementById('restore').addEventListener('click', gen);

    // gen initial
    gen();
  </script>
</body>
</html>
