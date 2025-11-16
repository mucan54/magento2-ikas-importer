<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Processor;

use Mucan54\IkasImport\Api\ProcessorInterface;
use Mucan54\IkasImport\Model\Config\ImportConfig;
use Mucan54\IkasImport\Model\Logger\Logger;
use Mucan54\IkasImport\Model\Processor\StockProcessor;
use Mucan54\IkasImport\Model\Processor\CategoryProcessor;
use Mucan54\IkasImport\Model\Processor\AttributeProcessor;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Complete product processor
 *
 * Creates/updates products with all attributes, categories, stock
 */
class ProductProcessor implements ProcessorInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @var StockProcessor
     */
    private $stockProcessor;

    /**
     * @var CategoryProcessor
     */
    private $categoryProcessor;

    /**
     * @var AttributeProcessor
     */
    private $attributeProcessor;

    /**
     * @var ImportConfig
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductInterfaceFactory $productFactory
     * @param StockProcessor $stockProcessor
     * @param CategoryProcessor $categoryProcessor
     * @param AttributeProcessor $attributeProcessor
     * @param ImportConfig $config
     * @param Logger $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        StockProcessor $stockProcessor,
        CategoryProcessor $categoryProcessor,
        AttributeProcessor $attributeProcessor,
        ImportConfig $config,
        Logger $logger
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->stockProcessor = $stockProcessor;
        $this->categoryProcessor = $categoryProcessor;
        $this->attributeProcessor = $attributeProcessor;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data, array $context = []): bool
    {
        $sku = $data['sku'] ?? null;

        if (!$sku) {
            $this->logger->logError('Cannot process product: SKU is missing');
            return false;
        }

        try {
            // Try to load existing product
            $product = $this->getProductBySku($sku);
            $isNew = false;

            if (!$product) {
                // Create new product
                $product = $this->productFactory->create();
                $product->setSku($sku);
                $product->setTypeId(Type::TYPE_SIMPLE);
                $product->setAttributeSetId(4); // Default attribute set
                $isNew = true;

                $this->logger->logImport('Creating new product', ['sku' => $sku]);
            } else {
                $this->logger->logImport('Updating existing product', ['sku' => $sku]);
            }

            // Set basic attributes
            $product->setName($data['name'] ?? '');
            $product->setPrice($data['price'] ?? 0);
            $product->setStatus($data['status'] ?? Status::STATUS_ENABLED);
            $product->setVisibility($data['visibility'] ?? Visibility::VISIBILITY_BOTH);
            $product->setWeight($data['weight'] ?? 0);

            // Set descriptions
            if (isset($data['description'])) {
                $product->setDescription($data['description']);
            }

            if (isset($data['short_description'])) {
                $product->setShortDescription($data['short_description']);
            }

            // Set URL key
            if (isset($data['url_key']) && !empty($data['url_key'])) {
                $product->setUrlKey($data['url_key']);
            }

            // Set special price
            if (isset($data['special_price']) && $data['special_price'] > 0) {
                $product->setSpecialPrice($data['special_price']);
            }

            // Process categories
            if (!empty($data['categories'])) {
                $categoryIds = [];
                foreach ($data['categories'] as $categoryPath) {
                    try {
                        $categoryId = $this->categoryProcessor->processCategory($categoryPath);
                        $categoryIds[] = $categoryId;
                    } catch (\Exception $e) {
                        $this->logger->logError('Failed to process category', [
                            'sku' => $sku,
                            'category' => $categoryPath,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                if (!empty($categoryIds)) {
                    $product->setCategoryIds($categoryIds);
                }
            }

            // Process dynamic attributes
            if (!empty($data['attributes'])) {
                $this->attributeProcessor->process([
                    'product' => $product,
                    'attributes' => $data['attributes']
                ]);
            }

            // Save product
            $savedProduct = $this->productRepository->save($product);
            $productId = $savedProduct->getId();

            $this->logger->logImport('Product saved successfully', [
                'sku' => $sku,
                'product_id' => $productId,
                'is_new' => $isNew
            ]);

            // Update stock
            if (isset($data['stock_qty'])) {
                $this->stockProcessor->process([
                    'sku' => $sku,
                    'stock_qty' => $data['stock_qty']
                ]);
            }

            // Store product ID for image processing
            $data['product_id'] = $productId;

            return true;

        } catch (\Exception $e) {
            $this->logger->logError('Failed to process product', [
                'sku' => $sku,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get product by SKU
     *
     * @param string $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    private function getProductBySku(string $sku): ?\Magento\Catalog\Api\Data\ProductInterface
    {
        try {
            return $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function supports(string $dataType): bool
    {
        return $dataType === 'product';
    }

    /**
     * @inheritdoc
     */
    public function validate(array $data): bool
    {
        if (!isset($data['sku']) || empty($data['sku'])) {
            return false;
        }

        if (!isset($data['name']) || empty($data['name'])) {
            return false;
        }

        return true;
    }
}
