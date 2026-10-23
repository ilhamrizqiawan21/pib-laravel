<?php

namespace App\Repositories;

use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SettingsRepository
{
    private const CACHE_KEY = 'pib.settings';

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        /** @var array<string, string> */
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Setting::query()->pluck('value', 'key')->all();
        });
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $all = $this->all();

        return $all[$key] ?? $default;
    }

    public function schoolName(): string
    {
        return $this->get('school_name', '') ?? '';
    }

    public function schoolAddress(): string
    {
        return $this->get('school_address', '') ?? '';
    }

    public function teacherName(): string
    {
        return $this->get('teacher_name', '') ?? '';
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public function setMany(array $data, ?int $userId = null): void
    {
        DB::transaction(function () use ($data, $userId): void {
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value ?? '']
                );
            }

            $this->auditLogger->record(
                entity: 'settings',
                entityId: 'identity',
                action: 'UPDATE',
                summary: 'Perbarui identitas sekolah',
                meta: [
                    'keys' => array_keys($data),
                ],
                userId: $userId
            );
        });

        Cache::forget(self::CACHE_KEY);
    }
}
