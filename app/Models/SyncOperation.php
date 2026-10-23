<?php

namespace App\Models;

use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $device_id
 * @property string $operation
 * @property array<string, mixed> $payload
 * @property SyncStatus $status
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $synced_at
 */
#[Fillable(['id', 'device_id', 'operation', 'payload', 'status', 'error', 'synced_at'])]
class SyncOperation extends Model
{
    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => SyncStatus::class,
            'synced_at' => 'datetime',
        ];
    }
}
