<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Struktur akademik: tahun ajaran -> kelas -> siswa.
 * Lihat docs/SCHEMA.md. Hanya fitur Schema Builder yang portabel (SQLite + MySQL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20); // "2026/2027"
            $table->string('semester', 10); // App\Enums\Semester
            $table->boolean('is_active')->default(false); // maksimal satu aktif; dijaga service
            $table->timestamps();

            $table->unique(['name', 'semester']);
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name', 50);
            $table->string('grade_level', 20)->default('');
            $table->string('homeroom_teacher', 100)->default('');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academic_year_id', 'name']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->restrictOnDelete();
            $table->string('nis', 30)->nullable()->unique(); // NULL diizinkan berulang (siswa tanpa NIS)
            $table->string('nisn', 30)->default('');
            $table->string('name', 120);
            $table->string('gender', 1)->nullable(); // App\Enums\Gender: L | P
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_class_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('academic_years');
    }
};
