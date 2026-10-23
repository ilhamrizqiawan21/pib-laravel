<?php

namespace App\Services;

use App\Domain\Scoring\ScoreCalculator;
use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;

class LegacyImporter
{
    public function __construct(
        private readonly BackupService $backupService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{
     *     tables: array<string, int>,
     *     anomalies: array<int, string>,
     *     dry_run: bool
     * }
     */
    public function import(string $sqlitePath, bool $dryRun = false, bool $force = false): array
    {
        if (! File::exists($sqlitePath)) {
            throw new RuntimeException("Berkas SQLite tidak ditemukan: {$sqlitePath}");
        }

        // 1. Cek apakah database tujuan kosong
        $targetCount = Student::count() + SchoolClass::count() + AcademicYear::count() + User::count();
        if ($targetCount > 0 && ! $force) {
            throw new RuntimeException('Database tujuan tidak kosong. Gunakan opsi --force untuk melanjutkan pemulihan.');
        }

        // 2. Snapshot darurat jika bukan dry run dan dipaksa
        if ($force && ! $dryRun && $targetCount > 0) {
            $this->backupService->createPreRestoreSnapshot();
        }

        $pdo = new PDO('sqlite:'.$sqlitePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tablesReport = [];
        $anomalies = [];

        DB::beginTransaction();

        try {
            if ($force) {
                // Bersihkan tabel tujuan secara terbalik
                $clearTables = [
                    'scores',
                    'assessments',
                    'subchapters',
                    'chapters',
                    'curriculum_templates',
                    'students',
                    'school_classes',
                    'academic_years',
                    'settings',
                    'audit_logs',
                ];

                foreach ($clearTables as $t) {
                    try {
                        DB::table($t)->delete();
                    } catch (\Throwable) {
                        // Abaikan
                    }
                }
            }

            // 1. Users
            $usersCount = 0;
            if ($this->hasTable($pdo, 'users')) {
                $stmt = $pdo->query('SELECT * FROM users');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $hash = (string) ($row['password_hash'] ?? '');
                    $isActive = true;
                    if ($hash === 'LOCAL_ONLY') {
                        $password = Hash::make(Str::random(32));
                        $isActive = false;
                    } else {
                        $password = $hash;
                    }

                    $roleStr = strtolower((string) ($row['role'] ?? 'teacher'));
                    $role = $roleStr === 'admin' ? Role::Admin->value : Role::Teacher->value;

                    DB::table('users')->updateOrInsert(
                        ['id' => (int) $row['id']],
                        [
                            'name' => (string) $row['name'],
                            'email' => (string) $row['email'],
                            'password' => $password,
                            'role' => $role,
                            'is_active' => $isActive,
                            'created_at' => $row['created_at'] ?? now(),
                            'updated_at' => $row['updated_at'] ?? now(),
                        ]
                    );
                    $usersCount++;
                }
            }
            $tablesReport['users'] = $usersCount;

            // 2. Academic Years
            $ayCount = 0;
            if ($this->hasTable($pdo, 'academic_years')) {
                $stmt = $pdo->query('SELECT * FROM academic_years');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    DB::table('academic_years')->insert([
                        'id' => (int) $row['id'],
                        'name' => (string) $row['name'],
                        'semester' => (string) $row['semester'],
                        'is_active' => (bool) $row['is_active'],
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => now(),
                    ]);
                    $ayCount++;
                }
            }
            $tablesReport['academic_years'] = $ayCount;

            // 3. Classes -> school_classes
            $classesCount = 0;
            if ($this->hasTable($pdo, 'classes')) {
                $stmt = $pdo->query('SELECT * FROM classes');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    DB::table('school_classes')->insert([
                        'id' => (int) $row['id'],
                        'academic_year_id' => (int) $row['academic_year_id'],
                        'name' => (string) $row['name'],
                        'grade_level' => (string) ($row['grade_level'] ?? ''),
                        'homeroom_teacher' => (string) ($row['homeroom_teacher'] ?? ''),
                        'is_active' => (bool) ($row['is_active'] ?? 1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $classesCount++;
                }
            }
            $tablesReport['school_classes'] = $classesCount;

            // 4. Students
            $studentsCount = 0;
            if ($this->hasTable($pdo, 'students')) {
                $stmt = $pdo->query('SELECT * FROM students');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $nis = ! empty($row['nis']) ? (string) $row['nis'] : null;
                    $gender = ! empty($row['gender']) ? (string) $row['gender'] : null;

                    DB::table('students')->insert([
                        'id' => (int) $row['id'],
                        'school_class_id' => (int) $row['class_id'],
                        'nis' => $nis,
                        'nisn' => (string) ($row['nisn'] ?? ''),
                        'name' => (string) $row['name'],
                        'gender' => $gender,
                        'is_active' => (bool) ($row['is_active'] ?? 1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $studentsCount++;
                }
            }
            $tablesReport['students'] = $studentsCount;

            // 5. Curriculum Templates
            $templatesCount = 0;
            if ($this->hasTable($pdo, 'curriculum_templates')) {
                $stmt = $pdo->query('SELECT * FROM curriculum_templates');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    DB::table('curriculum_templates')->insert([
                        'id' => (int) $row['id'],
                        'academic_year_id' => (int) $row['academic_year_id'],
                        'name' => (string) $row['name'],
                        'is_active' => (bool) ($row['is_active'] ?? 1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $templatesCount++;
                }
            }
            $tablesReport['curriculum_templates'] = $templatesCount;

            // 6. Chapters
            $chaptersCount = 0;
            if ($this->hasTable($pdo, 'chapters')) {
                $stmt = $pdo->query('SELECT * FROM chapters');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    DB::table('chapters')->insert([
                        'id' => (int) $row['id'],
                        'curriculum_template_id' => (int) $row['template_id'],
                        'title' => (string) $row['title'],
                        'display_order' => (int) ($row['display_order'] ?? 0),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $chaptersCount++;
                }
            }
            $tablesReport['chapters'] = $chaptersCount;

            // 7. Subchapters
            $subchaptersCount = 0;
            if ($this->hasTable($pdo, 'subchapters')) {
                $stmt = $pdo->query('SELECT * FROM subchapters');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    DB::table('subchapters')->insert([
                        'id' => (int) $row['id'],
                        'chapter_id' => (int) $row['chapter_id'],
                        'title' => (string) $row['title'],
                        'display_order' => (int) ($row['display_order'] ?? 0),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $subchaptersCount++;
                }
            }
            $tablesReport['subchapters'] = $subchaptersCount;

            // 8. Assessments
            $assessmentsCount = 0;
            if ($this->hasTable($pdo, 'assessments')) {
                $stmt = $pdo->query('SELECT * FROM assessments');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $desc = ! empty($row['description']) ? (string) $row['description'] : null;

                    DB::table('assessments')->insert([
                        'id' => (int) $row['id'],
                        'subchapter_id' => (int) $row['subchapter_id'],
                        'title' => (string) $row['title'],
                        'description' => $desc,
                        'weight' => (float) ($row['weight'] ?? 1.0),
                        'display_order' => (int) ($row['display_order'] ?? 0),
                        'is_active' => (bool) ($row['is_active'] ?? 1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $assessmentsCount++;
                }
            }
            $tablesReport['assessments'] = $assessmentsCount;

            // 9. Scores
            $scoresCount = 0;
            if ($this->hasTable($pdo, 'scores')) {
                $stmt = $pdo->query('SELECT * FROM scores');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $mistakes = $row['mistakes'] !== null ? (int) $row['mistakes'] : null;
                    $score = $row['score'] !== null ? (int) $row['score'] : null;

                    if ($mistakes !== null) {
                        $expectedScore = ScoreCalculator::score($mistakes);
                        if ($score !== $expectedScore) {
                            $anomalies[] = "Siswa #{$row['student_id']} materi #{$row['assessment_id']}: nilai lama {$score} ≠ harapan {$expectedScore} (kesalahan {$mistakes}). Disesuaikan ke {$expectedScore}.";
                            $score = $expectedScore;
                        }
                    }

                    $note = ! empty($row['note']) ? (string) $row['note'] : null;
                    $initials = ! empty($row['initials']) ? (string) $row['initials'] : null;

                    DB::table('scores')->insert([
                        'id' => (int) $row['id'],
                        'student_id' => (int) $row['student_id'],
                        'assessment_id' => (int) $row['assessment_id'],
                        'mistakes' => $mistakes,
                        'score' => $score,
                        'assessed_at' => $row['assessed_at'] ?? null,
                        'assessor_id' => $row['assessor_id'] ?? null,
                        'initials' => $initials,
                        'note' => $note,
                        'version' => 1,
                        'created_at' => $row['updated_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? now(),
                    ]);
                    $scoresCount++;
                }
            }
            $tablesReport['scores'] = $scoresCount;

            // 10. Settings
            $settingsCount = 0;
            if ($this->hasTable($pdo, 'settings')) {
                $stmt = $pdo->query('SELECT * FROM settings');
                $keyMap = [
                    'schoolName' => 'school_name',
                    'schoolAddress' => 'school_address',
                    'teacherName' => 'teacher_name',
                ];

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $key = $keyMap[$row['key']] ?? (string) $row['key'];
                    DB::table('settings')->updateOrInsert(
                        ['key' => $key],
                        ['value' => (string) $row['value']]
                    );
                    $settingsCount++;
                }
            }
            $tablesReport['settings'] = $settingsCount;

            // 11. Audit Logs
            $auditCount = 0;
            if ($this->hasTable($pdo, 'audit_logs')) {
                $stmt = $pdo->query('SELECT * FROM audit_logs');
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    DB::table('audit_logs')->insert([
                        'id' => (int) $row['id'],
                        'user_id' => $row['user_id'] ?? null,
                        'entity' => (string) $row['entity'],
                        'entity_id' => (string) $row['entity_id'],
                        'action' => (string) $row['action'],
                        'summary' => (string) $row['summary'],
                        'meta' => null,
                        'created_at' => $row['created_at'] ?? now(),
                    ]);
                    $auditCount++;
                }
            }
            $tablesReport['audit_logs'] = $auditCount;

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();

                // Catat audit import
                $this->auditLogger->record(
                    'import',
                    'legacy',
                    'IMPORT',
                    'Impor data lama berhasil dari berkas: '.basename($sqlitePath)
                );
            }

            return [
                'tables' => $tablesReport,
                'anomalies' => $anomalies,
                'dry_run' => $dryRun,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function hasTable(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
        $stmt->execute([$table]);

        return (bool) $stmt->fetch();
    }
}
