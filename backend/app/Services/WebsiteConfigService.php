<?php

namespace App\Services;

use App\Models\WebsiteConfig;
use Illuminate\Support\Facades\Cache;

class WebsiteConfigService
{
    /**
     * Default configuration groups
     */
    const GROUP_GENERAL = 'general';
    const GROUP_SEO = 'seo';
    const GROUP_SOCIAL = 'social';
    const GROUP_UI = 'ui';
    const GROUP_METADATA = 'metadata';

    /**
     * Cache key for website config
     */
    const CACHE_KEY = 'website_config';

    /**
     * Get all configurations
     *
     * @return array
     */
    public function getAll(): array
    {
        return Cache::store('file')->remember(self::CACHE_KEY, 86400, function () {
            $configs = WebsiteConfig::all();
            $result = [];
            
            foreach ($configs as $config) {
                $result[$config->group][$config->key] = $config->value;
            }
            
            return $result;
        });
    }

    /**
     * Get configuration by group
     *
     * @param string $group
     * @return array
     */
    public function getByGroup(string $group): array
    {
        $configs = $this->getAll();
        return $configs[$group] ?? [];
    }

    /**
     * Update configurations for a specific group
     *
     * @param string $group
     * @param array $data
     * @return bool
     */
    public function updateGroup(string $group, array $data): bool
    {
        foreach ($data as $key => $value) {
            WebsiteConfig::updateOrCreate(
                ['key' => $key, 'group' => $group],
                ['value' => $value]
            );
        }
        
        $this->clearCache();
        return true;
    }

    /**
     * Clear configuration cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::store('file')->forget(self::CACHE_KEY);
    }

    /**
     * Get a specific configuration
     *
     * @param string $key
     * @param string $group
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, string $group, $default = null)
    {
        $configs = $this->getAll();
        return $configs[$group][$key] ?? $default;
    }

    /**
     * Set a specific configuration
     *
     * @param string $key
     * @param string $group
     * @param mixed $value
     * @return bool
     */
    public function set(string $key, string $group, $value): bool
    {
        WebsiteConfig::updateOrCreate(
            ['key' => $key, 'group' => $group],
            ['value' => $value]
        );
        
        $this->clearCache();
        return true;
    }
} 