<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/** Satu-satunya pintu penulisan audit log. Panggil di dalam transaksi yang sama dengan perubahan datanya. */
final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $meta  jangan isi data sensitif (password, token)
     */
    public function record(string $entity, string|int $entityId, string $action, string $summary, ?array $meta = null, ?int $userId = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'entity' => $entity,
            'entity_id' => (string) $entityId,
            'action' => $action,
            'summary' => mb_substr($summary, 0, 255),
            'meta' => $meta,
        ]);
    }
}
