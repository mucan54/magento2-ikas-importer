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
     * @param SerializerInterface $serializer
     * @param Logger $logger
     * @param ProductProcessor $productProcessor
     * @param Publisher $publisher
     */
    public function __construct(
        SerializerInterface $serializer,
        Logger $logger,
        ProductProcessor $productProcessor,
        Publisher $publisher
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->productProcessor = $productProcessor;
        $this->publisher = $publisher;
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

                    if ($result && isset($productData['product_id'])) {
                        $successCount++;

                        // If product has images, publish to image queue
                        if (!empty($productData['images'])) {
                            $this->publisher->publishProductImages(
                                $productData['product_id'],
                                $productData['sku'],
                                $productData['images']
                            );
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
