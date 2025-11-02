<?php
// src/db.php
// Kết nối SQLite (data/fap.db)
// NOTE: File này được giữ ở dạng đơn giản để phục vụ mục đích demo nghiên cứu.
// Có nhiều chỗ minh họa các anti-pattern (ví dụ: lưu password plaintext, dựng SQL bằng concat) --
// những cách này là không an toàn và KHÔNG được dùng trong môi trường sản xuất.
function getDB() {
    $dbFile = __DIR__ . '/../data/fap.db';
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Ensure essential tables exist (helps when init_db.php was not run)
    ensureSchema($pdo);
    return $pdo;
}

function ensureSchema(PDO $pdo) {
    try {
        // Always attempt to create all required tables if they do not exist.
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            password TEXT,
            role TEXT,
            fullname TEXT
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_code TEXT UNIQUE,
            fullname TEXT,
            class TEXT,
            info TEXT
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER,
            amount INTEGER,
            status TEXT,
            tx_ref TEXT
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS grades (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER,
            course TEXT,
            grade TEXT,
            teacher_id INTEGER,
            created_at TEXT
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS schedule (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role TEXT,
            user_ref TEXT,
            course TEXT,
            day TEXT,
            time TEXT
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS complaints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER,
            file_path TEXT,
            message TEXT,
            status TEXT DEFAULT 'new',
            created_at TEXT
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER,
            course TEXT,
            date TEXT,
            status TEXT,
            teacher_id INTEGER
        );");

        // Seed minimal data if users table empty
        $count = $pdo->query('SELECT COUNT(*) as c FROM users')->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
        if (intval($count) === 0) {
            $seed = [
                ['admin_dt','admin123','admin','Admin ĐT'],
                ['admin_ng','adm!n456','admin','Admin NG'],
                ['TungNM','gv123','teacher','Tung Nguyen'],
                ['LeTH','gv234','teacher','Le Thi H'],
                ['PhamDT','gv345','teacher','Pham DT'],
                ['SE180001','sv123','student','Nguyễn Văn A'],
                ['SE180002','sv123','student','Trần Thị B'],
                ['SE180003','sv321','student','Lê Văn C'],
            ];
            $stmt = $pdo->prepare('INSERT OR IGNORE INTO users(username,password,role,fullname) VALUES(?,?,?,?)');
            foreach ($seed as $u) $stmt->execute($u);

            $stmt = $pdo->prepare('INSERT OR IGNORE INTO students(student_code,fullname,class,info) VALUES(?,?,?,?)');
            $students = [
                ['SE180001','Nguyễn Văn A','SE18','Thông tin A'],
                ['SE180002','Trần Thị B','SE18','Thông tin B'],
                ['SE180003','Lê Văn C','SE18','Thông tin C'],
            ];
            foreach ($students as $s) $stmt->execute($s);

            $pdo->exec("INSERT INTO payments(student_id,amount,status,tx_ref) VALUES(1,1000,'unpaid','TX1001')");
        }
    } catch (Exception $e) {
        // ignore
    }
}

// CHÚ Ý: Một số hàm dùng concat chuỗi để minh họa SQL Injection (cấm dùng trong sản phẩm thật)
function findUserByUsername($username) {
    $pdo = getDB();
    // VULN: Trực tiếp nối chuỗi => SQL Injection (demo)
    // Mô tả: hàm này ghép trực tiếp giá trị $username vào câu lệnh SQL.
    // Nếu $username chứa ký tự như quote (') hoặc payload có cấu trúc SQL,
    // kẻ tấn công có thể thao túng câu lệnh SQL.
    // Tại môi trường sản xuất: luôn dùng prepared statements (bind params),
    // validate/sanitize input, và áp dụng các cơ chế kiểm soát truy cập.
    $sql = "SELECT * FROM users WHERE username = '" . $username . "' LIMIT 1";
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getStudent($id) {
    $pdo = getDB();
    // VULN: trực tiếp nối chuỗi từ input => SQL Injection nếu $id đến từ request không kiểm tra
    // Mô tả: dùng concat để tạo query cho trường numeric. Nếu $id là dữ liệu không tin cậy,
    // hoặc chứa các ký tự không mong muốn, sẽ dẫn tới lỗi hoặc injection.
    // Mitigation: ép kiểu rõ ràng (int)$id, hoặc dùng prepared statement: SELECT * FROM students WHERE id = ?
    $sql = "SELECT * FROM students WHERE id = " . $id;
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>