<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration helper
 *
 * Provides easy access to module configuration values
 */
class ImportConfig
{
    private const XML_PATH_ENABLED = 'ikas_import/general/enabled';
    private const XML_PATH_BATCH_SIZE = 'ikas_import/general/batch_size';
    private const XML_PATH_CATEGORY_ROOT = 'ikas_import/general/category_root';
    private const XML_PATH_MAX_EXECUTION_TIME = 'ikas_import/general/max_execution_time';

    private const XML_PATH_ENABLE_DYNAMIC = 'ikas_import/attributes/enable_dynamic';
    private const XML_PATH_ATTR_PREFIX = 'ikas_import/attributes/prefix';
    private const XML_PATH_ATTR_GROUP = 'ikas_import/attributes/group';
    private const XML_PATH_ATTR_BLACKLIST = 'ikas_import/attributes/blacklist';
    private const XML_PATH_MIN_KEY_LENGTH = 'ikas_import/attributes/min_key_length';
    private const XML_PATH_MAX_VALUE_LENGTH = 'ikas_import/attributes/max_value_length';

    private const XML_PATH_MANAGE_STOCK = 'ikas_import/stock/manage_stock';
    private const XML_PATH_BACKORDERS = 'ikas_import/stock/backorders';
    private const XML_PATH_USE_CONFIG_MANAGE_STOCK = 'ikas_import/stock/use_config_manage_stock';
    private const XML_PATH_DEFAULT_SOURCE_CODE = 'ikas_import/stock/default_source_code';
    private const XML_PATH_OUT_OF_STOCK_THRESHOLD = 'ikas_import/stock/out_of_stock_threshold';

    private const XML_PATH_ENABLE_ASYNC = 'ikas_import/images/enable_async';
    private const XML_PATH_QUEUE_CONNECTION = 'ikas_import/images/queue_connection';
    private const XML_PATH_IMAGE_QUALITY = 'ikas_import/images/image_quality';
    private const XML_PATH_RESIZE_IMAGES = 'ikas_import/images/resize_images';
    private const XML_PATH_MAX_IMAGES = 'ikas_import/images/max_images_per_product';
    private const XML_PATH_ALLOWED_EXTENSIONS = 'ikas_import/images/allowed_extensions';
    private const XML_PATH_DOWNLOAD_TIMEOUT = 'ikas_import/images/download_timeout';
    private const XML_PATH_CLEAR_EXISTING = 'ikas_import/images/clear_existing';

    private const XML_PATH_AUTO_CREATE_CATEGORIES = 'ikas_import/categories/auto_create';
    private const XML_PATH_CATEGORY_IS_ACTIVE = 'ikas_import/categories/is_active';
    private const XML_PATH_CATEGORY_IN_MENU = 'ikas_import/categories/include_in_menu';

    private const XML_PATH_VALIDATE_SKU = 'ikas_import/validation/validate_sku_format';
    private const XML_PATH_VALIDATE_PRICES = 'ikas_import/validation/validate_prices';
    private const XML_PATH_VALIDATE_URLS = 'ikas_import/validation/validate_urls';
    private const XML_PATH_REQUIRED_FIELDS = 'ikas_import/validation/required_fields';
    private const XML_PATH_MIN_PRICE = 'ikas_import/validation/min_price';
    private const XML_PATH_MAX_PRICE = 'ikas_import/validation/max_price';

    private const XML_PATH_DEBUG_MODE = 'ikas_import/advanced/debug_mode';
    private const XML_PATH_MEMORY_LIMIT = 'ikas_import/advanced/memory_limit';
    private const XML_PATH_CLEAR_CACHE = 'ikas_import/advanced/clear_cache_after_import';
    private const XML_PATH_REINDEX = 'ikas_import/advanced/reindex_after_import';
    private const XML_PATH_LOG_LEVEL = 'ikas_import/advanced/log_level';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get batch size
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_BATCH_SIZE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get default category root
     *
     * @return string
     */
    public function getCategoryRoot(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_CATEGORY_ROOT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get maximum execution time
     *
     * @return int
     */
    public function getMaxExecutionTime(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_MAX_EXECUTION_TIME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if dynamic attribute creation is enabled
     *
     * @return bool
     */
    public function isDynamicAttributeEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE_DYNAMIC, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get attribute prefix
     *
     * @return string
     */
    public function getAttributePrefix(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_ATTR_PREFIX, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get attribute group name
     *
     * @return string
     */
    public function getAttributeGroup(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_ATTR_GROUP, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get blacklisted attribute keys
     *
     * @return array
     */
    public function getAttributeBlacklist(): array
    {
        $blacklist = $this->scopeConfig->getValue(self::XML_PATH_ATTR_BLACKLIST, ScopeInterface::SCOPE_STORE);
        return $blacklist ? array_map('trim', explode(',', strtolower($blacklist))) : [];
    }

    /**
     * Get minimum key length
     *
     * @return int
     */
    public function getMinKeyLength(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_MIN_KEY_LENGTH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get maximum value length for varchar type
     *
     * @return int
     */
    public function getMaxValueLength(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_MAX_VALUE_LENGTH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if stock management is enabled
     *
     * @return bool
     */
    public function isManageStock(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_MANAGE_STOCK, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get backorders setting
     *
     * @return int
     */
    public function getBackorders(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_BACKORDERS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if use config manage stock is enabled
     *
     * @return bool
     */
    public function isUseConfigManageStock(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_USE_CONFIG_MANAGE_STOCK, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get default source code for MSI
     *
     * @return string
     */
    public function getDefaultSourceCode(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_DEFAULT_SOURCE_CODE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get out of stock threshold
     *
     * @return float
     */
    public function getOutOfStockThreshold(): float
    {
        return (float)$this->scopeConfig->getValue(self::XML_PATH_OUT_OF_STOCK_THRESHOLD, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if async image processing is enabled
     *
     * @return bool
     */
    public function isAsyncImageProcessing(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE_ASYNC, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get queue connection type
     *
     * @return string
     */
    public function getQueueConnection(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_QUEUE_CONNECTION, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get image quality
     *
     * @return int
     */
    public function getImageQuality(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_IMAGE_QUALITY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if images should be resized
     *
     * @return bool
     */
    public function shouldResizeImages(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_RESIZE_IMAGES, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get maximum images per product
     *
     * @return int
     */
    public function getMaxImagesPerProduct(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_MAX_IMAGES, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get allowed image extensions
     *
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        $extensions = $this->scopeConfig->getValue(self::XML_PATH_ALLOWED_EXTENSIONS, ScopeInterface::SCOPE_STORE);
        return $extensions ? array_map('trim', explode(',', strtolower($extensions))) : [];
    }

    /**
     * Get download timeout
     *
     * @return int
     */
    public function getDownloadTimeout(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_DOWNLOAD_TIMEOUT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if existing images should be cleared
     *
     * @return bool
     */
    public function shouldClearExistingImages(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CLEAR_EXISTING, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if categories should be auto-created
     *
     * @return bool
     */
    public function shouldAutoCreateCategories(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_CREATE_CATEGORIES, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get default category active status
     *
     * @return bool
     */
    public function isCategoryActive(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CATEGORY_IS_ACTIVE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if categories should be included in menu
     *
     * @return bool
     */
    public function shouldIncludeInMenu(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CATEGORY_IN_MENU, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if SKU validation is enabled
     *
     * @return bool
     */
    public function shouldValidateSku(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_VALIDATE_SKU, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if price validation is enabled
     *
     * @return bool
     */
    public function shouldValidatePrices(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_VALIDATE_PRICES, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if URL validation is enabled
     *
     * @return bool
     */
    public function shouldValidateUrls(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_VALIDATE_URLS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get required fields
     *
     * @return array
     */
    public function getRequiredFields(): array
    {
        $fields = $this->scopeConfig->getValue(self::XML_PATH_REQUIRED_FIELDS, ScopeInterface::SCOPE_STORE);
        return $fields ? array_map('trim', explode(',', strtolower($fields))) : [];
    }

    /**
     * Get minimum price
     *
     * @return float
     */
    public function getMinPrice(): float
    {
        return (float)$this->scopeConfig->getValue(self::XML_PATH_MIN_PRICE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get maximum price
     *
     * @return float
     */
    public function getMaxPrice(): float
    {
        return (float)$this->scopeConfig->getValue(self::XML_PATH_MAX_PRICE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DEBUG_MODE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get memory limit
     *
     * @return string
     */
    public function getMemoryLimit(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_MEMORY_LIMIT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if cache should be cleared after import
     *
     * @return bool
     */
    public function shouldClearCache(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CLEAR_CACHE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if reindex should be performed after import
     *
     * @return bool
     */
    public function shouldReindex(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_REINDEX, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get log level
     *
     * @return string
     */
    public function getLogLevel(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE);
    }
}
