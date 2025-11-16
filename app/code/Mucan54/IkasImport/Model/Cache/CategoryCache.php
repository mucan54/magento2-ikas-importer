<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Cache;

/**
 * In-memory cache for categories during import session
 *
 * Avoids duplicate database queries when checking category existence
 */
class CategoryCache
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * Get category ID from cache
     *
     * @param int $parentId
     * @param string $name
     * @return int|null
     */
    public function get(int $parentId, string $name): ?int
    {
        $key = $this->getCacheKey($parentId, $name);
        return $this->cache[$key] ?? null;
    }

    /**
     * Set category ID in cache
     *
     * @param int $parentId
     * @param string $name
     * @param int $categoryId
     * @return void
     */
    public function set(int $parentId, string $name, int $categoryId): void
    {
        $key = $this->getCacheKey($parentId, $name);
        $this->cache[$key] = $categoryId;
    }

    /**
     * Check if category exists in cache
     *
     * @param int $parentId
     * @param string $name
     * @return bool
     */
    public function has(int $parentId, string $name): bool
    {
        $key = $this->getCacheKey($parentId, $name);
        return isset($this->cache[$key]);
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public function clear(): void
    {
        $this->cache = [];
    }

    /**
     * Get cache key
     *
     * @param int $parentId
     * @param string $name
     * @return string
     */
    private function getCacheKey(int $parentId, string $name): string
    {
        return $parentId . '/' . mb_strtolower($name, 'UTF-8');
    }
}
