<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Queue;

use Magento\Framework\Serialize\SerializerInterface;
use Mucan54\IkasImport\Model\Logger\Logger;
use Mucan54\IkasImport\Model\Processor\ProductProcessor;
use Mucan54\IkasImport\Model\Queue\Publisher;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Product batch consumer
 *
 * Processes batches of 50 products from queue
 * For each product, publishes image import job to separate queue
 */
class ProductBatchConsumer
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ProductProcessor
     */
    private $productProcessor;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param SerializerInterface $serializer
     * @param Logger $logger
     * @param ProductProcessor $productProcessor
     * @param Publisher $publisher
     * @param ProductRepositoryInterface $productRepository
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        SerializerInterface $serializer,
        Logger $logger,
        ProductProcessor $productProcessor,
        Publisher $publisher,
        ProductRepositoryInterface $productRepository,
        IndexerRegistry $indexerRegistry
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->productProcessor = $productProcessor;
        $this->publisher = $publisher;
        $this->productRepository = $productRepository;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Process product batch from queue
     *
     * @param string $message JSON encoded batch data
     * @return void
     */
    public function process(string $message): void
    {
        try {
            $data = $this->serializer->unserialize($message);

            $batchNumber = $data['batch_number'] ?? 0;
            $products = $data['products'] ?? [];
            $productCount = count($products);

            $this->logger->logImport('Processing product batch from queue', [
                'batch_number' => $batchNumber,
                'product_count' => $productCount
            ]);

            $successCount = 0;
            $errorCount = 0;

            foreach ($products as $productData) {
                try {
                    // Process product (create/update)
                    $result = $this->productProcessor->process($productData);

                    if ($result) {
                        $successCount++;

                        // Invalidate inventory index for this product
                        try {
                            $indexer = $this->indexerRegistry->get('cataloginventory_stock');
                            if (!$indexer->isScheduled()) {
                                $indexer->invalidate();
                            }
                        } catch (\Exception $e) {
                            // Log but don't fail if indexer invalidation fails
                            $this->logger->logError('Failed to invalidate inventory index', [
                                'sku' => $productData['sku'] ?? 'unknown',
                                'error' => $e->getMessage()
                            ]);
                        }

                        // Resolve product id (productProcessor saved the product)
                        $productId = $this->productProcessor->getProductIdBySku($productData['sku'] ?? '');

                        // If product has images and we have a product id, verify product exists and publish to image queue
                        if ($productId && !empty($productData['images'])) {
                            // Verify product is actually saved and accessible before publishing image job
                            try {
                                $product = $this->productRepository->getById($productId);
                                
                                // Product exists, safe to publish image job
                                $this->publisher->publishProductImages(
                                    $productId,
                                    $productData['sku'],
                                    $productData['images']
                                );
                            } catch (\Exception $e) {
                                // Product not found yet, log but don't fail the batch
                                $this->logger->logError('Product not accessible for image import', [
                                    'product_id' => $productId,
                                    'sku' => $productData['sku'] ?? 'unknown',
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    } else {
                        $errorCount++;
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->logger->logError('Failed to process product in batch', [
                        'batch_number' => $batchNumber,
                        'sku' => $productData['sku'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logger->logImport('Product batch processing completed', [
                'batch_number' => $batchNumber,
                'total' => $productCount,
                'success' => $successCount,
                'errors' => $errorCount
            ]);

        } catch (\Exception $e) {
            $this->logger->logError('Failed to process product batch from queue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
