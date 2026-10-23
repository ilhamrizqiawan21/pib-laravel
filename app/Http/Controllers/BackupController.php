<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(
        private readonly BackupService $backupService,
    ) {}

    public function status(): JsonResponse
    {
        return response()->json($this->backupService->status());
    }

    public function download(): StreamedResponse
    {
        $payload = $this->backupService->exportJson();
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $filename = 'backup-pib-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function createManual(): JsonResponse
    {
        $file = $this->backupService->createScheduledBackup(force: true);

        return response()->json($this->backupService->status(), 201);
    }
}
