<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengaturan identitas sekolah/guru. Kunci: school_name, school_address, teacher_name.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value');
            $table->timestamps();
        });

        // Jejak perubahan penting. Append-only: tidak ada update/delete dari aplikasi.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity', 50);
            $table->string('entity_id', 100);
            $table->string('action', 30);
            $table->string('summary', 255);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['entity', 'entity_id']);
        });

        // Idempotensi antrean draft/offline dari klien: satu operasi (id dari klien) diproses sekali.
        Schema::create('sync_operations', function (Blueprint $table) {
            $table->string('id', 100)->primary(); // dibuat klien
            $table->string('device_id', 100);
            $table->string('operation', 30); // UPSERT_SCORE
            $table->json('payload');
            $table->string('status', 10)->default('PENDING'); // App\Enums\SyncStatus
            $table->string('error')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('synced_at')->nullable();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_operations');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
    }
};
