<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Cache;

/**
 * In-memory cache for attributes during import session
 *
 * Avoids duplicate database queries when checking attribute existence
 */
class AttributeCache
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * Get attribute from cache
     *
     * @param string $attributeCode
     * @return mixed|null
     */
    public function get(string $attributeCode)
    {
        return $this->cache[$attributeCode] ?? null;
    }

    /**
     * Set attribute in cache
     *
     * @param string $attributeCode
     * @param mixed $attribute
     * @return void
     */
    public function set(string $attributeCode, $attribute): void
    {
        $this->cache[$attributeCode] = $attribute;
    }

    /**
     * Check if attribute exists in cache
     *
     * @param string $attributeCode
     * @return bool
     */
    public function has(string $attributeCode): bool
    {
        return isset($this->cache[$attributeCode]);
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
}
