<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kurikulum materi: template -> bab -> subbab -> materi (assessment).
 * Penghapusan merambat ke bawah (cascade), tetapi tabel scores memakai RESTRICT sehingga
 * struktur yang sudah bernilai tidak bisa hilang diam-diam (lihat create_scores_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academic_year_id', 'name']);
        });

        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_template_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['curriculum_template_id', 'title']);
        });

        Schema::create('subchapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['chapter_id', 'title']);
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subchapter_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->decimal('weight', 6, 2)->default(1); // validasi aplikasi: 0,1 – 100
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['subchapter_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('subchapters');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('curriculum_templates');
    }
};
