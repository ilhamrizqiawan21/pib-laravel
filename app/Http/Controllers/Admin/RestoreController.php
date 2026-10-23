<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RestoreException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRestoreRequest;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;

class RestoreController extends Controller
{
    public function __construct(
        private readonly BackupService $backupService,
    ) {}

    public function restore(AdminRestoreRequest $request): JsonResponse
    {
        $uploaded = $request->file('file');
        if (! $uploaded) {
            return response()->json(['error' => 'Berkas cadangan tidak ditemukan.'], 422);
        }

        try {
            $count = $this->backupService->restoreFromFile($uploaded->getPathname(), $request->user());

            return response()->json([
                'restored' => $count,
                'message' => "{$count} data berhasil dipulihkan.",
            ]);
        } catch (RestoreException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
