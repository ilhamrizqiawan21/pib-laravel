<?php

$target = __DIR__.'/legacy-sample.sqlite';
if (file_exists($target)) {
    unlink($target);
}

$pdo = new PDO('sqlite:'.$target);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Skema lama persis lib/migrations.ts
$pdo->exec("
    CREATE TABLE schema_migrations(version INTEGER PRIMARY KEY, applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE users(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,email TEXT NOT NULL UNIQUE,password_hash TEXT NOT NULL,role TEXT NOT NULL DEFAULT 'TEACHER',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE academic_years(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,semester TEXT NOT NULL,is_active INTEGER NOT NULL DEFAULT 0,created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE(name,semester));
    CREATE TABLE classes(id INTEGER PRIMARY KEY AUTOINCREMENT,academic_year_id INTEGER NOT NULL REFERENCES academic_years(id),name TEXT NOT NULL,grade_level TEXT NOT NULL DEFAULT '',homeroom_teacher TEXT NOT NULL DEFAULT '',is_active INTEGER NOT NULL DEFAULT 1,UNIQUE(academic_year_id,name));
    CREATE TABLE students(id INTEGER PRIMARY KEY AUTOINCREMENT,class_id INTEGER NOT NULL REFERENCES classes(id),nis TEXT UNIQUE,nisn TEXT NOT NULL DEFAULT '',name TEXT NOT NULL,gender TEXT NOT NULL DEFAULT '',is_active INTEGER NOT NULL DEFAULT 1);
    CREATE TABLE curriculum_templates(id INTEGER PRIMARY KEY AUTOINCREMENT,academic_year_id INTEGER NOT NULL REFERENCES academic_years(id),name TEXT NOT NULL,is_active INTEGER NOT NULL DEFAULT 1,UNIQUE(academic_year_id,name));
    CREATE TABLE chapters(id INTEGER PRIMARY KEY AUTOINCREMENT,template_id INTEGER NOT NULL REFERENCES curriculum_templates(id) ON DELETE CASCADE,title TEXT NOT NULL,display_order INTEGER NOT NULL DEFAULT 0,UNIQUE(template_id,title));
    CREATE TABLE subchapters(id INTEGER PRIMARY KEY AUTOINCREMENT,chapter_id INTEGER NOT NULL REFERENCES chapters(id) ON DELETE CASCADE,title TEXT NOT NULL,display_order INTEGER NOT NULL DEFAULT 0,UNIQUE(chapter_id,title));
    CREATE TABLE assessments(id INTEGER PRIMARY KEY AUTOINCREMENT,subchapter_id INTEGER NOT NULL REFERENCES subchapters(id) ON DELETE CASCADE,title TEXT NOT NULL,description TEXT NOT NULL DEFAULT '',weight REAL NOT NULL DEFAULT 1,display_order INTEGER NOT NULL DEFAULT 0,is_active INTEGER NOT NULL DEFAULT 1);
    CREATE TABLE scores(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,assessment_id INTEGER NOT NULL REFERENCES assessments(id) ON DELETE CASCADE,mistakes INTEGER,score INTEGER,assessed_at TEXT,assessor_id INTEGER REFERENCES users(id),initials TEXT NOT NULL DEFAULT '',note TEXT NOT NULL DEFAULT '',updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE(student_id,assessment_id));
    CREATE TABLE settings(key TEXT PRIMARY KEY,value TEXT NOT NULL);
    CREATE TABLE audit_logs(id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INTEGER,entity TEXT NOT NULL,entity_id TEXT NOT NULL,action TEXT NOT NULL,summary TEXT NOT NULL,created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
");

$pdo->exec('INSERT INTO schema_migrations(version) VALUES (1), (2), (3);');

// Users
$userStmt = $pdo->prepare('INSERT INTO users(name, email, password_hash, role) VALUES (?, ?, ?, ?)');
$userStmt->execute(['Guru PAI', 'guru@pib.local', password_hash('password123', PASSWORD_BCRYPT), 'TEACHER']);
$guruId = $pdo->lastInsertId();
$userStmt->execute(['Admin Sekolah', 'admin@pib.local', password_hash('admin123', PASSWORD_BCRYPT), 'ADMIN']);
$userStmt->execute(['Akun Lama', 'lama@pib.local', 'LOCAL_ONLY', 'TEACHER']);

// Settings
$settingStmt = $pdo->prepare('INSERT INTO settings(key, value) VALUES (?, ?)');
$settingStmt->execute(['schoolName', 'SMP Islam Terpadu Contoh']);
$settingStmt->execute(['schoolAddress', 'Jl. Pendidikan No. 45, Bandung']);
$settingStmt->execute(['teacherName', 'Ustadz Abdullah, S.Pd.I']);

// Academic Years
$ayStmt = $pdo->prepare('INSERT INTO academic_years(name, semester, is_active) VALUES (?, ?, ?)');
$ayStmt->execute(['2026/2027', 'Ganjil', 1]);
$yearId = $pdo->lastInsertId();
$ayStmt->execute(['2026/2027', 'Genap', 0]);

// Curriculum Template
$tplStmt = $pdo->prepare('INSERT INTO curriculum_templates(academic_year_id, name, is_active) VALUES (?, ?, 1)');
$tplStmt->execute([$yearId, 'Kurikulum PAI 2026']);
$templateId = $pdo->lastInsertId();

// Chapters, Subchapters, Assessments
$curriculum = [
    'Thaharah' => [
        'Wudhu' => [
            ['title' => 'Niat dan membasuh wajah', 'weight' => 1.0],
            ['title' => 'Membasuh tangan dan mengusap kepala', 'weight' => 1.5],
            ['title' => 'Membasuh kaki dan tertib', 'weight' => 1.0],
            ['title' => 'Doa setelah wudhu', 'weight' => 2.0],
        ],
        'Tayamum' => [
            ['title' => 'Niat dan tata cara tayamum', 'weight' => 1.0],
        ],
    ],
    'Shalat' => [
        'Gerakan dan Bacaan' => [
            ['title' => 'Takbiratul ihram & iftitah', 'weight' => 2.0],
            ['title' => 'Ruku dan i\'tidal', 'weight' => 1.0],
            ['title' => 'Sujud dan duduk di antara dua sujud', 'weight' => 1.5],
            ['title' => 'Tasyahud akhir dan salam', 'weight' => 2.5],
        ],
    ],
];

$chOrder = 1;
$assessmentIds = [];
$assessmentWeights = [];

$chStmt = $pdo->prepare('INSERT INTO chapters(template_id, title, display_order) VALUES (?, ?, ?)');
$subStmt = $pdo->prepare('INSERT INTO subchapters(chapter_id, title, display_order) VALUES (?, ?, ?)');
$assStmt = $pdo->prepare('INSERT INTO assessments(subchapter_id, title, description, weight, display_order, is_active) VALUES (?, ?, ?, ?, ?, 1)');

foreach ($curriculum as $chTitle => $subs) {
    $chStmt->execute([$templateId, $chTitle, $chOrder++]);
    $chId = $pdo->lastInsertId();
    $subOrder = 1;
    foreach ($subs as $subTitle => $materials) {
        $subStmt->execute([$chId, $subTitle, $subOrder++]);
        $subId = $pdo->lastInsertId();
        $assOrder = 1;
        foreach ($materials as $mat) {
            $assStmt->execute([$subId, $mat['title'], '', $mat['weight'], $assOrder++]);
            $assId = (int) $pdo->lastInsertId();
            $assessmentIds[] = $assId;
            $assessmentWeights[$assId] = $mat['weight'];
        }
    }
}

// 3 Classes
$classStmt = $pdo->prepare('INSERT INTO classes(academic_year_id, name, grade_level, homeroom_teacher, is_active) VALUES (?, ?, ?, ?, 1)');
$classes = [
    ['name' => 'VII A', 'grade' => '7', 'teacher' => 'Bu Nurul'],
    ['name' => 'VII B', 'grade' => '7', 'teacher' => 'Pak Hendra'],
    ['name' => 'VIII A', 'grade' => '8', 'teacher' => 'Bu Fatimah'],
];

$classIds = [];
foreach ($classes as $c) {
    $classStmt->execute([$yearId, $c['name'], $c['grade'], $c['teacher']]);
    $classIds[] = (int) $pdo->lastInsertId();
}

// 30 Students (10 per class)
$names = [
    // VII A
    'Ahmad Fauzi', 'Siti Aisyah', 'Muhammad Rizky', 'Nur Hidayah', 'Aditya Pratama',
    'Putri Maharani', 'Fajar Ramadhan', 'Dewi Lestari', 'Bagus Santoso', 'Rahma Wulandari',
    // VII B
    'Ilham Maulana', 'Zahra Amelia', 'Dimas Prasetyo', 'Nabila Khairunnisa', 'Yusuf Hakim',
    'Aulia Rahmawati', 'Raka Putra', 'Salsabila Putri', 'Hafiz Alfarizi', 'Intan Permata',
    // VIII A
    'Naufal Akbar', 'Khansa Azzahra', 'Farhan Maulidi', 'Syifa Nurhaliza', 'Rendi Kurniawan',
    'Alya Safitri', 'Zidan Ramadhan', 'Mutiara Ayu', 'Arif Budiman', 'Nadia Fitriani',
];

$studentStmt = $pdo->prepare('INSERT INTO students(class_id, nis, nisn, name, gender, is_active) VALUES (?, ?, ?, ?, ?, 1)');
$scoreStmt = $pdo->prepare('INSERT INTO scores(student_id, assessment_id, mistakes, score, assessed_at, assessor_id, initials, note, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

$nis = 1001;
$idx = 0;

foreach ($classIds as $clsId) {
    for ($i = 0; $i < 10; $i++, $idx++) {
        $gender = ($idx % 2 === 0) ? 'L' : 'P';
        $studentStmt->execute([$clsId, (string) ($nis++), '00'.(61000000 + $idx), $names[$idx], $gender]);
        $studentId = (int) $pdo->lastInsertId();

        // 3 siswa per kelas lengkap dinilai, 4 siswa sebagian, 3 siswa belum dinilai (0 score)
        if ($i < 3) {
            $assessCount = count($assessmentIds); // Lengkap
        } elseif ($i < 7) {
            $assessCount = 4; // Sebagian (4 materi awal)
        } else {
            $assessCount = 0; // Kosong
        }

        for ($k = 0; $k < $assessCount; $k++) {
            $assId = $assessmentIds[$k];
            // Variasi mistakes: 0, 5, 10, 15, 20
            $mistakes = (($idx + $k) % 5) * 5;
            $score = 90 - $mistakes;
            $date = date('Y-m-d H:i:s', strtotime('-'.(10 - $k).' days'));
            $note = $mistakes > 10 ? 'Perlu perbaikan makhraj' : 'Lancar';
            $scoreStmt->execute([
                $studentId,
                $assId,
                $mistakes,
                $score,
                $date,
                $guruId,
                'GP',
                $note,
                $date,
            ]);
        }
    }
}

// Audit Logs
$auditStmt = $pdo->prepare('INSERT INTO audit_logs(user_id, entity, entity_id, action, summary, created_at) VALUES (?, ?, ?, ?, ?, ?)');
$auditStmt->execute([$guruId, 'score', '1', 'UPSERT', 'Nilai awal dimasukkan', date('Y-m-d H:i:s')]);

echo "Fixture legacy-sample.sqlite berhasil dibuat!\n";
echo 'Jumlah materi: '.count($assessmentIds)."\n";
echo 'Jumlah kelas: '.count($classIds)."\n";
echo 'Jumlah siswa: '.$idx."\n";
