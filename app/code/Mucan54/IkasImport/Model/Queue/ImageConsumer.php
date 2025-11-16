<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Queue;

use Magento\Framework\Serialize\SerializerInterface;
use Mucan54\IkasImport\Model\Logger\Logger;
use Mucan54\IkasImport\Model\Processor\ImageProcessor;

/**
 * Image consumer
 *
 * Processes image downloads for individual products from queue
 */
class ImageConsumer
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
     * @var ImageProcessor
     */
    private $imageProcessor;

    /**
     * @param SerializerInterface $serializer
     * @param Logger $logger
     * @param ImageProcessor $imageProcessor
     */
    public function __construct(
        SerializerInterface $serializer,
        Logger $logger,
        ImageProcessor $imageProcessor
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->imageProcessor = $imageProcessor;
    }

    /**
     * Process product images from queue
     *
     * @param string $message JSON encoded image data
     * @return void
     */
    public function process(string $message): void
    {
        try {
            $data = $this->serializer->unserialize($message);

            $productId = $data['product_id'] ?? null;
            $sku = $data['sku'] ?? null;
            $imageUrls = $data['image_urls'] ?? [];

            if (!$productId || !$sku || empty($imageUrls)) {
                $this->logger->logError('Invalid image data in queue message', [
                    'product_id' => $productId,
                    'sku' => $sku
                ]);
                return;
            }

            $this->logger->logImport('Processing product images from queue', [
                'product_id' => $productId,
                'sku' => $sku,
                'image_count' => count($imageUrls)
            ]);

            // Process images
            $result = $this->imageProcessor->process([
                'product_id' => $productId,
                'sku' => $sku,
                'images' => $imageUrls
            ]);

            if ($result) {
                $this->logger->logImport('Product images processed successfully', [
                    'product_id' => $productId,
                    'sku' => $sku
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->logError('Failed to process product images from queue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - let queue retry
        }
    }
}
