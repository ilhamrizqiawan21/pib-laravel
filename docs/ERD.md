# ERD — PIB Penilaian

Diagram dirender oleh GitHub/GitLab/VS Code (Mermaid). Detail kolom: [SCHEMA.md](SCHEMA.md).

```mermaid
erDiagram
    users ||--o{ scores : "assessor_id (SET NULL)"
    users ||--o{ audit_logs : "user_id (SET NULL)"

    academic_years ||--o{ school_classes : "RESTRICT"
    academic_years ||--o{ curriculum_templates : "RESTRICT"
    school_classes ||--o{ students : "RESTRICT"

    curriculum_templates ||--o{ chapters : "CASCADE"
    chapters ||--o{ subchapters : "CASCADE"
    subchapters ||--o{ assessments : "CASCADE"

    students ||--o{ scores : "CASCADE"
    assessments ||--o{ scores : "RESTRICT"

    users {
        bigint id PK
        string name
        string email UK
        string password
        string role "admin|teacher"
        bool is_active
    }
    academic_years {
        bigint id PK
        string name "2026/2027"
        string semester "Ganjil|Genap"
        bool is_active "maks satu"
    }
    school_classes {
        bigint id PK
        bigint academic_year_id FK
        string name "UK(year,name)"
        string grade_level
        string homeroom_teacher
        bool is_active
    }
    students {
        bigint id PK
        bigint school_class_id FK
        string nis UK "nullable"
        string nisn
        string name
        string gender "L|P|null"
        bool is_active
    }
    curriculum_templates {
        bigint id PK
        bigint academic_year_id FK
        string name "UK(year,name)"
        bool is_active
    }
    chapters {
        bigint id PK
        bigint curriculum_template_id FK
        string title "UK(template,title)"
        int display_order
    }
    subchapters {
        bigint id PK
        bigint chapter_id FK
        string title "UK(chapter,title)"
        int display_order
    }
    assessments {
        bigint id PK
        bigint subchapter_id FK
        string title
        text description
        decimal weight "0.1-100"
        int display_order
        bool is_active
    }
    scores {
        bigint id PK
        bigint student_id FK
        bigint assessment_id FK "UK(student,assessment)"
        tinyint mistakes "0-90 null"
        tinyint score "0-90 null"
        timestamp assessed_at
        bigint assessor_id FK
        string initials
        string note
        int version "optimistic lock"
    }
    settings {
        string key PK
        text value
    }
    audit_logs {
        bigint id PK
        bigint user_id FK
        string entity
        string entity_id
        string action
        string summary
        json meta
        timestamp created_at
    }
    sync_operations {
        string id PK "dari klien"
        string device_id
        string operation
        json payload
        string status "PENDING|SYNCED|FAILED"
        string error
        timestamp synced_at
    }
```

## Aturan relasi yang penting

| Relasi | Perilaku hapus | Mengapa |
|---|---|---|
| `scores.assessment_id → assessments` | **RESTRICT** | Struktur kurikulum yang sudah bernilai tidak boleh hilang lewat cascade |
| `scores.student_id → students` | CASCADE | Hapus permanen siswa (dikonfirmasi) membawa nilainya |
| `students → school_classes`, `school_classes → academic_years`, `curriculum_templates → academic_years` | RESTRICT | Data induk yang dipakai harus dinonaktifkan, bukan dihapus |
| `chapters/subchapters/assessments` ke atasnya | CASCADE | Hapus template kosong membersihkan struktur; diblokir otomatis bila ada nilai (rantai RESTRICT di atas) |
| `scores.assessor_id`, `audit_logs.user_id` | SET NULL | Menghapus akun tidak menghapus riwayat |

`settings`, `sync_operations` berdiri sendiri (tanpa FK). `individual_test_sessions*` dari aplikasi lama **tidak dibawa**.
