<?php

namespace App\Services;

use App\Domain\Scoring\ScoreCalculator;
use App\Enums\SyncStatus;
use App\Exceptions\ScoreConflictException;
use App\Models\Assessment;
use App\Models\Score;
use App\Models\Student;
use App\Models\SyncOperation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScoreService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Rekam nilai siswa untuk satu materi secara idempoten dan dengan deteksi konflik optimistik.
     *
     * @param  array{initials?: string, note?: string, assessed_at?: string|Carbon}  $extras
     *
     * @throws ScoreConflictException
     * @throws InvalidArgumentException
     */
    public function record(
        User $user,
        int $studentId,
        int $assessmentId,
        ?int $mistakes,
        ?int $baseVersion,
        string $opId,
        string $deviceId,
        array $extras = [],
        bool $checkConflict = true
    ): ?Score {
        if ($mistakes !== null && ! ScoreCalculator::isValidMistakes($mistakes)) {
            throw new InvalidArgumentException(
                'Jumlah kesalahan harus bilangan bulat 0–'.ScoreCalculator::maxScore().'.'
            );
        }

        return DB::transaction(function () use (
            $user,
            $studentId,
            $assessmentId,
            $mistakes,
            $baseVersion,
            $opId,
            $deviceId,
            $extras,
            $checkConflict
        ) {
            // 1. Idempotensi via sync_operations
            $existingOp = SyncOperation::query()->where('id', $opId)->first();
            if ($existingOp && $existingOp->status === SyncStatus::Synced) {
                return Score::query()
                    ->where('student_id', $studentId)
                    ->where('assessment_id', $assessmentId)
                    ->first();
            }

            // 2. Pastikan Student dan Assessment ada (fail fast 404 jika tidak ditemukan)
            Student::findOrFail($studentId);
            Assessment::findOrFail($assessmentId);

            // 3. Ambil data nilai terkini
            $currentScore = Score::query()
                ->where('student_id', $studentId)
                ->where('assessment_id', $assessmentId)
                ->first();

            $currentVersion = $currentScore?->version;

            // 4. Deteksi konflik optimistik (bila diaktifkan)
            if ($checkConflict && $baseVersion !== $currentVersion) {
                throw new ScoreConflictException(
                    $currentScore,
                    'Konflik versi data nilai: versi di server telah berubah.'
                );
            }

            $scoreValue = ScoreCalculator::score($mistakes);
            $action = 'UPSERT';
            $resultScore = null;

            // 5. Tulis atau perbarui
            if ($mistakes === null) {
                // Mengosongkan nilai
                if ($currentScore === null) {
                    // No-op bila baris memang belum pernah ada
                    $resultScore = null;
                } else {
                    $currentScore->update([
                        'mistakes' => null,
                        'score' => null,
                        'assessed_at' => null,
                        'assessor_id' => $user->id,
                        'version' => $currentScore->version + 1,
                    ]);
                    $resultScore = $currentScore->refresh();
                    $action = 'CLEAR';
                }
            } else {
                $nextVersion = ($currentScore !== null ? $currentScore->version : 0) + 1;
                $assessedAt = isset($extras['assessed_at'])
                    ? Carbon::parse($extras['assessed_at'])
                    : now();

                $initials = $extras['initials'] ?? ($currentScore !== null ? $currentScore->initials : '');
                $note = $extras['note'] ?? ($currentScore !== null ? $currentScore->note : '');

                /** @var Score $resultScore */
                $resultScore = Score::query()->updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'assessment_id' => $assessmentId,
                    ],
                    [
                        'mistakes' => $mistakes,
                        'score' => $scoreValue,
                        'assessed_at' => $assessedAt,
                        'assessor_id' => $user->id,
                        'initials' => $initials,
                        'note' => $note,
                        'version' => $nextVersion,
                    ]
                );
            }

            // 6. Catat sync_operations
            SyncOperation::query()->updateOrCreate(
                ['id' => $opId],
                [
                    'device_id' => $deviceId,
                    'operation' => 'UPSERT_SCORE',
                    'payload' => [
                        'student_id' => $studentId,
                        'assessment_id' => $assessmentId,
                        'mistakes' => $mistakes,
                        'base_version' => $baseVersion,
                        'extras' => $extras,
                    ],
                    'status' => SyncStatus::Synced,
                    'synced_at' => now(),
                ]
            );

            // 7. Catat audit log bila terjadi perubahan data
            if ($resultScore !== null) {
                $summary = $action === 'CLEAR'
                    ? "Mengosongkan nilai materi #{$assessmentId} untuk siswa #{$studentId}"
                    : "Merekam nilai materi #{$assessmentId} untuk siswa #{$studentId} (kesalahan: {$mistakes}, nilai: {$resultScore->score})";

                $this->auditLogger->record(
                    entity: 'score',
                    entityId: (string) $resultScore->id,
                    action: $action,
                    summary: $summary,
                    meta: [
                        'op_id' => $opId,
                        'device_id' => $deviceId,
                        'student_id' => $studentId,
                        'assessment_id' => $assessmentId,
                        'mistakes' => $mistakes,
                        'score' => $resultScore->score,
                        'version' => $resultScore->version,
                    ],
                    userId: $user->id
                );
            }

            return $resultScore;
        });
    }
}
