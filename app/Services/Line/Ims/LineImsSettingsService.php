<?php

namespace App\Services\Line\Ims;

use App\Models\AppSetting;

class LineImsSettingsService
{
    public const KEY_RECEPTION_ENABLED = 'line_ims_reception_enabled';

    public function receptionEnabled(): bool
    {
        $stored = $this->get(self::KEY_RECEPTION_ENABLED);

        if ($stored !== null) {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('services.line.ims.reception_enabled', true);
    }

    /**
     * @param  array{reception_enabled?: bool}  $data
     */
    public function save(array $data): void
    {
        if (array_key_exists('reception_enabled', $data)) {
            $this->put(self::KEY_RECEPTION_ENABLED, $data['reception_enabled'] ? '1' : '0');
        }
    }

    public function get(string $key): ?string
    {
        $row = AppSetting::query()->find($key);

        return $row?->value;
    }

    public function put(string $key, ?string $value): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }
}
