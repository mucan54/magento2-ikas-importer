<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Processor;

use Mucan54\IkasImport\Api\ProcessorInterface;
use Mucan54\IkasImport\Model\Config\ImportConfig;
use Mucan54\IkasImport\Model\Cache\CategoryCache;
use Mucan54\IkasImport\Model\Logger\Logger;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Category processor with hierarchy support and caching
 *
 * Creates category hierarchy automatically from path strings
 */
class CategoryProcessor implements ProcessorInterface
{
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var CategoryInterfaceFactory
     */
    private $categoryFactory;

    /**
     * @var CategoryCache
     */
    private $categoryCache;

    /**
     * @var ImportConfig
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var bool
     */
    private $secureAreaRegistered = false;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryInterfaceFactory $categoryFactory
     * @param CategoryCache $categoryCache
     * @param ImportConfig $config
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param State $appState
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryInterfaceFactory $categoryFactory,
        CategoryCache $categoryCache,
        ImportConfig $config,
        Logger $logger,
        StoreManagerInterface $storeManager,
        State $appState
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->categoryCache = $categoryCache;
        $this->config = $config;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->appState = $appState;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data, array $context = []): bool
    {
        $categoryPath = $data['category_path'] ?? null;

        if (!$categoryPath) {
            return false;
        }

        try {
            // Register secure area if not already registered
            if (!$this->secureAreaRegistered) {
                $this->appState->emulateAreaCode(
                    \Magento\Framework\App\Area::AREA_ADMINHTML,
                    [$this, 'processCategory'],
                    [$categoryPath]
                );
                return true;
            } else {
                $this->processCategory($categoryPath);
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->logError('Failed to process category', [
                'path' => $categoryPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Process category creation/retrieval
     *
     * @param string $categoryPath
     * @return int Category ID
     * @throws \Exception
     */
    public function processCategory(string $categoryPath): int
    {
        $this->secureAreaRegistered = true;

        // Split path by slash
        $parts = array_filter(array_map('trim', explode('/', $categoryPath)));

        if (empty($parts)) {
            throw new \InvalidArgumentException('Invalid category path');
        }

        // Get root category ID
        $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
        $parentId = $rootCategoryId;

        // Create/get each level of the hierarchy
        foreach ($parts as $categoryName) {
            $categoryId = $this->getOrCreateCategory($parentId, $categoryName);
            $parentId = $categoryId;
        }

        return $parentId;
    }

    /**
     * Get or create category
     *
     * @param int $parentId
     * @param string $name
     * @return int Category ID
     * @throws \Exception
     */
    private function getOrCreateCategory(int $parentId, string $name): int
    {
        // Check cache first
        if ($this->categoryCache->has($parentId, $name)) {
            return $this->categoryCache->get($parentId, $name);
        }

        // Try to find existing category
        $categoryId = $this->findCategoryByName($parentId, $name);

        if ($categoryId) {
            $this->categoryCache->set($parentId, $name, $categoryId);
            return $categoryId;
        }

        // Create new category
        if ($this->config->shouldAutoCreateCategories()) {
            $categoryId = $this->createCategory($parentId, $name);
            $this->categoryCache->set($parentId, $name, $categoryId);
            return $categoryId;
        }

        throw new \RuntimeException("Category not found and auto-creation is disabled: {$name}");
    }

    /**
     * Find category by name under parent
     *
     * @param int $parentId
     * @param string $name
     * @return int|null
     */
    private function findCategoryByName(int $parentId, string $name): ?int
    {
        try {
            $parent = $this->categoryRepository->get($parentId);
            $children = $parent->getChildrenCategories();

            foreach ($children as $child) {
                if (mb_strtolower($child->getName(), 'UTF-8') === mb_strtolower($name, 'UTF-8')) {
                    return (int)$child->getId();
                }
            }
        } catch (\Exception $e) {
            $this->logger->logError('Error finding category', [
                'parent_id' => $parentId,
                'name' => $name,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Create new category
     *
     * @param int $parentId
     * @param string $name
     * @return int Category ID
     * @throws \Exception
     */
    private function createCategory(int $parentId, string $name): int
    {
        $category = $this->categoryFactory->create();
        $category->setName($name);
        $category->setIsActive($this->config->isCategoryActive());
        $category->setIncludeInMenu($this->config->shouldIncludeInMenu());
        $category->setParentId($parentId);
        $category->setStoreId($this->storeManager->getStore()->getId());

        // Generate URL key from name
        $urlKey = $this->generateUrlKey($name);
        $category->setUrlKey($urlKey);

        $savedCategory = $this->categoryRepository->save($category);

        $this->logger->logImport('Category created', [
            'name' => $name,
            'id' => $savedCategory->getId(),
            'parent_id' => $parentId
        ]);

        return (int)$savedCategory->getId();
    }

    /**
     * Generate URL key from category name
     *
     * @param string $name
     * @return string
     */
    private function generateUrlKey(string $name): string
    {
        // Turkish character transliteration
        $transliteration = [
            'ı' => 'i', 'İ' => 'i',
            'ş' => 's', 'Ş' => 's',
            'ğ' => 'g', 'Ğ' => 'g',
            'ç' => 'c', 'Ç' => 'c',
            'ö' => 'o', 'Ö' => 'o',
            'ü' => 'u', 'Ü' => 'u',
        ];

        $urlKey = strtr($name, $transliteration);
        $urlKey = mb_strtolower($urlKey, 'UTF-8');
        $urlKey = preg_replace('/[^a-z0-9]+/', '-', $urlKey);
        $urlKey = trim($urlKey, '-');

        return $urlKey;
    }

    /**
     * @inheritdoc
     */
    public function supports(string $dataType): bool
    {
        return $dataType === 'category';
    }

    /**
     * @inheritdoc
     */
    public function validate(array $data): bool
    {
        return isset($data['category_path']) && !empty($data['category_path']);
    }
}
