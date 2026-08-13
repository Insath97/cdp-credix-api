<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected static function cacheKey(string $key): string
    {
        return "setting:{$key}";
    }

    /**
     * Get a setting's value, cast according to its stored type.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever(static::cacheKey($key), function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return match ($setting->type) {
                'integer' => (int) $setting->value,
                'boolean' => (bool) $setting->value,
                'json' => json_decode($setting->value, true) ?? [],
                default => $setting->value,
            };
        });
    }

    /**
     * Update a setting's value and bust its cache entry.
     */
    public static function set(string $key, $value): void
    {
        $setting = static::where('key', $key)->firstOrFail();
        $setting->update(['value' => static::serialize($value)]);

        Cache::forget(static::cacheKey($key));
    }

    /**
     * Serialize a raw value for storage in the `value` text column.
     */
    public static function serialize($value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_bool($value)) {
            return (string) (int) $value;
        }

        return (string) $value;
    }
}
