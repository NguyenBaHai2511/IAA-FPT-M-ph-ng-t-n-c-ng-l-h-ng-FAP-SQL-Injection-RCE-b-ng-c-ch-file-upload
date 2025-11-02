<?php
// src/init_db.php - Chạy 1 lần để tạo DB và seed data
require_once __DIR__ . '/db.php';
$pdo = getDB();

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

$pdo->exec("CREATE TABLE IF NOT EXISTS teacher_salary (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER,
    amount INTEGER,
    bank_account TEXT
);");

// Seed users (mật khẩu lưu plaintext để demo, KHÔNG LÀM THẬT)
$users = [
    ['admin_dt','admin123','admin','Admin ĐT'],
    ['admin_ng','adm!n456','admin','Admin NG'],
    ['TungNM','gv123','teacher','Tung Nguyen'],
    ['LeTH','gv234','teacher','Le Thi H'],
    ['PhamDT','gv345','teacher','Pham DT'],
    ['SE180001','sv123','student','Nguyễn Văn A'],
    ['SE180002','sv123','student','Trần Thị B'],
    ['SE180003','sv321','student','Lê Văn C'],
    ['SE180004','sv234','student','Phạm Thị D'],
    ['SE180005','sv234','student','Hoàng Văn E'],
    ['SE180006','sv345','student','Đỗ Thị F'],
];

foreach ($users as $u) {
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO users(username,password,role,fullname) VALUES(?,?,?,?)');
    $stmt->execute($u);
}

// Seed students
$students = [
    ['SE180001','Nguyễn Văn A','SE18','Thông tin A'],
    ['SE180002','Trần Thị B','SE18','Thông tin B'],
    ['SE180003','Lê Văn C','SE18','Thông tin C'],
    ['SE180004','Phạm Thị D','SE18','Thông tin D'],
    ['SE180005','Hoàng Văn E','SE18','Thông tin E'],
    ['SE180006','Đỗ Thị F','SE18','Thông tin F'],
];

foreach ($students as $s) {
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO students(student_code,fullname,class,info) VALUES(?,?,?,?)');
    $stmt->execute($s);
}

// Seed a sample payment (unpaid)
$pdo->exec("INSERT INTO payments(student_id,amount,status,tx_ref) VALUES(1,1000,'unpaid','TX1001')");

// Seed sample grades

// Seed sample grades (multiple entries)
$pdo->exec("INSERT INTO grades(student_id,course,grade,teacher_id,created_at) VALUES(1,'Toán','8','3',datetime('now'))");
$pdo->exec("INSERT INTO grades(student_id,course,grade,teacher_id,created_at) VALUES(1,'Lập trình','8.5','3',datetime('now'))");
$pdo->exec("INSERT INTO grades(student_id,course,grade,teacher_id,created_at) VALUES(2,'Toán','7','4',datetime('now'))");
$pdo->exec("INSERT INTO grades(student_id,course,grade,teacher_id,created_at) VALUES(3,'Lập trình','6.5','3',datetime('now'))");

// Seed schedule (students and teachers) - multiple entries
$pdo->exec("INSERT INTO schedule(role,user_ref,course,day,time) VALUES('student','SE180001','Toán','Mon','08:00')");
$pdo->exec("INSERT INTO schedule(role,user_ref,course,day,time) VALUES('student','SE180001','Lập trình','Wed','10:00')");
$pdo->exec("INSERT INTO schedule(role,user_ref,course,day,time) VALUES('student','SE180002','Toán','Mon','08:00')");
$pdo->exec("INSERT INTO schedule(role,user_ref,course,day,time) VALUES('teacher','TungNM','Toán','Mon','08:00')");
$pdo->exec("INSERT INTO schedule(role,user_ref,course,day,time) VALUES('teacher','TungNM','Lập trình','Wed','10:00')");

// Seed sample attendance
$pdo->exec("INSERT INTO attendance(student_id,course,date,status,teacher_id) VALUES(1,'Toán',date('now'),'present',3)");
$pdo->exec("INSERT INTO attendance(student_id,course,date,status,teacher_id) VALUES(2,'Toán',date('now'),'absent',3)");
$pdo->exec("INSERT INTO attendance(student_id,course,date,status,teacher_id) VALUES(1,'Lập trình',date('now','-1 day'),'present',3)");

// Seed additional payments
$pdo->exec("INSERT INTO payments(student_id,amount,status,tx_ref) VALUES(2,1200,'paid','TX1002')");
$pdo->exec("INSERT INTO payments(student_id,amount,status,tx_ref) VALUES(3,1500,'unpaid','TX1003')");

// Seed complaints (sample)
$pdo->exec("INSERT INTO complaints(student_id,file_path,message,status,created_at) VALUES(1,'uploads/complaints/sample-complaint-1.txt','Khiếu nại về điểm thi giữa kỳ','new',datetime('now'))");
$pdo->exec("INSERT INTO complaints(student_id,file_path,message,status,created_at) VALUES(2,'uploads/complaints/sample-complaint-2.txt','Đề nghị gia hạn nộp học phí','review',datetime('now'))");
$pdo->exec("INSERT INTO complaints(student_id,file_path,message,status,created_at) VALUES(3,'uploads/complaints/sample-complaint-3.txt','Khiếu nại về thời gian học','resolved',datetime('now'))");

// Seed teacher salary examples (use subquery to find teacher id)
$pdo->exec("INSERT OR IGNORE INTO teacher_salary(teacher_id,amount,bank_account) VALUES((SELECT id FROM users WHERE username='TungNM'),15000000,'0123456789 - VietBank')");
$pdo->exec("INSERT OR IGNORE INTO teacher_salary(teacher_id,amount,bank_account) VALUES((SELECT id FROM users WHERE username='LeTH'),12000000,'0987654321 - ABCBank')");
$pdo->exec("INSERT OR IGNORE INTO teacher_salary(teacher_id,amount,bank_account) VALUES((SELECT id FROM users WHERE username='PhamDT'),10000000,'0112233445 - XYZBank')");

// Ensure uploads folder exists
@mkdir(__DIR__ . '/../public/uploads/complaints', 0755, true);

echo "DB created/seeded at: " . __DIR__ . "/../data/fap.db\n";
?>