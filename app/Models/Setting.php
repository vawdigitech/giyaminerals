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

    /**
     * Cache key prefix for settings
     */
    protected const CACHE_PREFIX = 'settings.';

    /**
     * Cache TTL in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember(self::CACHE_PREFIX . $key, self::CACHE_TTL, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @param string $group
     * @param string|null $description
     * @return Setting
     */
    public static function set(string $key, $value, ?string $type = null, string $group = 'general', ?string $description = null): Setting
    {
        // Convert value to string for storage
        $stringValue = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type ?? self::detectType($value),
                'group' => $group,
                'description' => $description,
            ]
        );

        // Clear cache for this key
        Cache::forget(self::CACHE_PREFIX . $key);

        return $setting;
    }

    /**
     * Get all settings for a group
     *
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getGroup(string $group)
    {
        return static::where('group', $group)->get();
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        }
    }

    /**
     * Cast value to appropriate type
     *
     * @param string $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue(string $value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'decimal', 'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }

    /**
     * Detect type from value
     *
     * @param mixed $value
     * @return string
     */
    protected static function detectType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'decimal';
        }

        return 'string';
    }
}
