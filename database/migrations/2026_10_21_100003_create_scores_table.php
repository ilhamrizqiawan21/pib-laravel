<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nilai. Aturan inti (docs/PRD.md §Aturan Nilai):
 *  - score = 90 - mistakes, keduanya 0..90.
 *  - NULL berarti "Belum dinilai"; 0 hanya sah bila mistakes = 90.
 *  - Baris yang dikosongkan TIDAK dihapus: `version` tetap dipakai untuk deteksi konflik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            // RESTRICT: materi yang sudah bernilai tidak boleh terhapus lewat cascade kurikulum.
            $table->foreignId('assessment_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('mistakes')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('initials', 30)->default('');
            $table->string('note', 500)->default('');
            $table->unsignedInteger('version')->default(1); // optimistic concurrency
            $table->timestamps();

            $table->unique(['student_id', 'assessment_id']);
            $table->index(['assessment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
