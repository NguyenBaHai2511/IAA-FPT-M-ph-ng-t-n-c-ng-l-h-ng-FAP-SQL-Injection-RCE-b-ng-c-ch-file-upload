# IAA-FPT-Mô phỏng lỗ hổng trang web FAP, SQL Injection, RCE bằng File Upload
Kịch bản tấn công trang web quản lí mô phỏng FAP
## FAP - Vulnerable Demo (mục đích nghiên cứu)

Mục đích: repository này là một bản demo có chủ ý chứa nhiều lỗ hổng bảo mật (SQL Injection, Unrestricted File Upload / RCE, Insecure API, Broken Access Control, v.v.) để phục vụ nghiên cứu, giảng dạy và phân tích. **Không** được triển khai trên mạng công cộng hoặc dùng với dữ liệu thật.

Nội dung README tóm tắt:
- Hướng dẫn chạy local an toàn
- Danh sách demo/lỗ hổng và nơi tìm trong mã nguồn
- Cách vô hiệu hoá / làm an toàn các demo

---

GHI CHÚ PHÁP LÝ & ĐẠO ĐỨC
- Dùng repo này chỉ cho mục đích học tập, thử nghiệm trong môi trường được kiểm soát (máy ảo, mạng nội bộ). Không dùng để tấn công hệ thống của người khác.

Yêu cầu cơ bản
- PHP 7.4+ (hoặc môi trường hỗ trợ SQLite)
- (Tuỳ chọn) Docker và docker-compose nếu muốn chạy bằng container

1) Chạy nhanh bằng PHP built-in server (local, dev)

Mở PowerShell trong thư mục project và chạy:

```powershell
# Chạy server phục vụ thư mục public
php -S localhost:8000 -t public
```

Mở trình duyệt tới: http://localhost:8000

2) Hoặc chạy bằng Docker (nếu có `docker-compose.yml` cấu hình sẵn)

```powershell
docker-compose up --build
```

3) Tạo/Reset database (SQLite)

Trong môi trường local, bạn có thể tái khởi tạo DB mẫu bằng script:

```powershell
php src/init_db.php
```

File DB tạo ra tại `data/fap.db`.

---

Danh sách demo lỗ hổng (vị trí và mô tả ngắn)

- Unrestricted File Upload / RCE
  - File: `public/upload.php`
  - Mô tả: trang upload không kiểm tra extension/MIME và lưu file trực tiếp vào `public/uploads/`.
  - Hậu quả: nếu upload file .php, file đó có thể được truy cập và thực thi trên webserver.

- Web shell mẫu (ví dụ hậu quả của upload)
  - File: `public/uploads/Untitled-1.php`
  - Mô tả: một webshell demo (thực thi lệnh hệ thống qua tham số `?cmd=`). File được giữ trong repo để phân tích hậu quả.
  - LƯU Ý: file đã được chú thích rõ ràng; xóa hoặc cách ly file này nếu không cần.

- SQL Injection (ví dụ minh họa)
  - File: `src/db.php` (hàm `findUserByUsername`, `getStudent`)
  - Mô tả: một vài hàm ghép chuỗi trực tiếp vào SQL thay vì dùng prepared statements.

- Insecure API / Trusting client data
  - File: `src/api.php` (action=confirm_payment)
  - Mô tả: API chấp nhận payload và mark payment là 'paid' mà không xác thực nguồn/chữ ký.

- Broken Access Control / Authorization flaws
  - File: `public/admin.php`
  - Mô tả: một số endpoint và thao tác dựa trên session nhưng có thể thiếu kiểm tra role nhất quán; các hành động quản trị được giữ để demo.

- Plaintext password seeds
  - File: `src/init_db.php` (seed users)
  - Mô tả: mật khẩu được lưu plaintext (chỉ cho mục đích demo). Trong thực tế phải băm mật khẩu.

---

Hướng dẫn an toàn khi thử nghiệm
- Chỉ chạy trên máy ảo hoặc máy test, không dùng dữ liệu thực.
- Ngắt kết nối mạng (hoặc cấu hình firewall) nếu bạn muốn đảm bảo không có truy cập từ ngoài.
- Sau khi phân tích, xóa file trong `public/uploads/` (ví dụ `Untitled-1.php`) hoặc đổi tên sang `.txt`.

Một số phương án khắc phục (tổng quát)
- SQL Injection: dùng prepared statements / parameterized queries; validate input.
- File upload: kiểm tra extension, kiểm tra MIME, đổi tên file lưu (random name), lưu file ở thư mục không public,
  và nếu phải public thì đảm bảo không cho thực thi PHP trong thư mục uploads (ví dụ cấu hình webserver).
- API trust: áp dụng chữ ký webhook / HMAC, verify với provider.
- Passwords: băm bằng bcrypt/argon2, không lưu plaintext.
- Access control: đảm bảo kiểm tra role/permission ở backend cho mọi hành động nhạy cảm.

---

Lưu ý khi sửa mã nguồn để phục vụ báo cáo khoa học
- Tôi đã thêm comment tiếng Việt trong các file nguồn chính để giải thích điểm lỗ hổng và gợi ý mitigation.
- Những comment không thay đổi logic demo (để giữ khả năng phân tích). Nếu bạn muốn "vô hiệu hoá" demo mà vẫn giữ code,
  hãy xóa/rename files trong `public/uploads/` và/hoặc chỉnh `public/upload.php` để từ chối các loại file nguy hiểm.

Liên hệ / Ghi nguồn
- Repo này phục vụ mục đích học thuật. Khi công bố công trình sử dụng repo này, vui lòng nêu rõ nguồn và mục đích sử dụng.

Chúc bạn nghiên cứu an toàn và có trách nhiệm.

