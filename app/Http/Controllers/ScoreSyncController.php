<?php

namespace App\Http\Controllers;

use App\Exceptions\ScoreConflictException;
use App\Http\Requests\ScoreSyncRequest;
use App\Models\User;
use App\Services\ScoreService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class ScoreSyncController extends Controller
{
    public function sync(ScoreSyncRequest $request, ScoreService $scoreService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $extras = [];
        if (isset($validated['initials'])) {
            $extras['initials'] = (string) $validated['initials'];
        }
        if (isset($validated['note'])) {
            $extras['note'] = (string) $validated['note'];
        }
        if (isset($validated['assessed_at'])) {
            $extras['assessed_at'] = (string) $validated['assessed_at'];
        }

        try {
            $score = $scoreService->record(
                user: $user,
                studentId: (int) $validated['student_id'],
                assessmentId: (int) $validated['assessment_id'],
                mistakes: array_key_exists('mistakes', $validated) && $validated['mistakes'] !== null
                    ? (int) $validated['mistakes']
                    : null,
                baseVersion: array_key_exists('base_version', $validated) && $validated['base_version'] !== null
                    ? (int) $validated['base_version']
                    : null,
                opId: (string) $validated['id'],
                deviceId: (string) $validated['device_id'],
                extras: $extras,
                checkConflict: $request->has('base_version')
            );

            return response()->json([
                'status' => 'SYNCED',
                'score' => $score?->score,
                'mistakes' => $score?->mistakes,
                'version' => $score !== null ? $score->version : 0,
                'assessed_at' => $score?->assessed_at?->toISOString(),
            ]);
        } catch (ScoreConflictException $e) {
            $current = $e->currentScore;

            return response()->json([
                'error' => 'Konflik versi data nilai: data di server telah diperbarui oleh pengguna lain.',
                'current' => $current ? [
                    'score' => $current->score,
                    'mistakes' => $current->mistakes,
                    'version' => $current->version,
                    'assessed_at' => $current->assessed_at?->toISOString(),
                ] : null,
            ], 409);
        } catch (ModelNotFoundException) {
            return response()->json([
                'error' => 'Siswa atau materi tidak ditemukan.',
            ], 404);
        }
    }
}
