<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Setting Model
 *
 * Manages system-wide configuration settings stored as key-value pairs.
 * Provides static methods for easy retrieval and storage of settings.
 *
 * @author Dev Agent
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        // Try to decode JSON values
        $decoded = json_decode($setting->value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @return static
     */
    public static function set(string $key, $value, ?string $description = null): self
    {
        // Encode arrays and objects as JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description
            ]
        );
    }

    /**
     * Check if a setting exists.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Delete a setting by key.
     *
     * @param string $key
     * @return bool
     */
    public static function forget(string $key): bool
    {
        return static::where('key', $key)->delete() > 0;
    }

    /**
     * Get multiple settings by keys.
     *
     * @param array $keys
     * @return array
     */
    public static function getMultiple(array $keys): array
    {
        $settings = static::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        $result = [];
        foreach ($keys as $key) {
            $value = $settings[$key] ?? null;
            if ($value !== null) {
                $decoded = json_decode($value, true);
                $result[$key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            } else {
                $result[$key] = null;
            }
        }

        return $result;
    }
}
