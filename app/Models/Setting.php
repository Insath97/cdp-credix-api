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

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
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
        $cacheKey = static::cacheKey($key);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $setting = static::where('key', $key)->first();

        // A key with no row is NOT cached.
        //
        // rememberForever() stored whatever the closure returned, the fallback
        // included, and set() uses firstOrFail() so a missing key cannot be
        // created through it either. Between them, a key read once before it
        // was seeded went on answering with the fallback for the life of the
        // cache, and seeding it afterwards changed nothing.
        if (!$setting) {
            return $default;
        }

        return Cache::rememberForever($cacheKey, function () use ($setting) {
            return match ($setting->type) {
                'integer' => (int) $setting->value,
                'boolean' => (bool) $setting->value,
                'decimal' => (float) $setting->value,
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

        // The words, as well as the type.
        //
        // get() casts a boolean setting with (bool), and in PHP only "" and "0"
        // are falsy strings -- so "false" stored verbatim read back as TRUE and
        // a switch that had been turned off stayed on. Callers that hand over
        // the word rather than the value now land on "0" like everything else.
        if (is_string($value) && in_array(strtolower(trim($value)), ['true', 'false'], true)) {
            return strtolower(trim($value)) === 'true' ? '1' : '0';
        }

        return (string) $value;
    }
}
