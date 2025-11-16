<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Mucan54\IkasImport\Model\Logger\Logger;

/**
 * Queue publisher for product and image imports
 *
 * Publishes batch product data and image data to respective queues
 */
class Publisher
{
    private const TOPIC_PRODUCT_BATCH = 'ikas.product.import.batch';
    private const TOPIC_PRODUCT_IMAGES = 'ikas.product.images';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param PublisherInterface $publisher
     * @param SerializerInterface $serializer
     * @param Logger $logger
     */
    public function __construct(
        PublisherInterface $publisher,
        SerializerInterface $serializer,
        Logger $logger
    ) {
        $this->publisher = $publisher;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * Publish product batch to queue
     *
     * @param array $productBatch Array of product data (max 50 items)
     * @param int $batchNumber Batch sequence number
     * @return void
     */
    public function publishProductBatch(array $productBatch, int $batchNumber): void
    {
        try {
            $message = $this->serializer->serialize([
                'batch_number' => $batchNumber,
                'products' => $productBatch,
                'count' => count($productBatch),
                'timestamp' => time()
            ]);

            $this->publisher->publish(self::TOPIC_PRODUCT_BATCH, $message);

            $this->logger->logImport('Product batch published to queue', [
                'batch_number' => $batchNumber,
                'product_count' => count($productBatch),
                'topic' => self::TOPIC_PRODUCT_BATCH
            ]);

        } catch (\Exception $e) {
            $this->logger->logError('Failed to publish product batch to queue', [
                'batch_number' => $batchNumber,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Publish product images to queue
     *
     * @param int $productId Product ID
     * @param string $sku Product SKU
     * @param array $imageUrls Array of image URLs
     * @return void
     */
    public function publishProductImages(int $productId, string $sku, array $imageUrls): void
    {
        if (empty($imageUrls)) {
            return;
        }

        try {
            $message = $this->serializer->serialize([
                'product_id' => $productId,
                'sku' => $sku,
                'image_urls' => $imageUrls,
                'timestamp' => time()
            ]);

            $this->publisher->publish(self::TOPIC_PRODUCT_IMAGES, $message);

            $this->logger->logImport('Product images published to queue', [
                'product_id' => $productId,
                'sku' => $sku,
                'image_count' => count($imageUrls),
                'topic' => self::TOPIC_PRODUCT_IMAGES
            ]);

        } catch (\Exception $e) {
            $this->logger->logError('Failed to publish product images to queue', [
                'product_id' => $productId,
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
            // Don't throw - images are not critical
        }
    }
}
