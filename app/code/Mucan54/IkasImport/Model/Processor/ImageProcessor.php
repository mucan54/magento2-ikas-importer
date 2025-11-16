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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Catalog\Model\Product\Gallery\Processor as GalleryProcessor;

/**
 * Image processor
 *
 * Downloads and assigns images to products
 */
class ImageProcessor implements ProcessorInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var GalleryProcessor
     */
    private $galleryProcessor;

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
     * @param Filesystem $filesystem
     * @param Curl $curl
     * @param GalleryProcessor $galleryProcessor
     * @param ImportConfig $config
     * @param Logger $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Filesystem $filesystem,
        Curl $curl,
        GalleryProcessor $galleryProcessor,
        ImportConfig $config,
        Logger $logger
    ) {
        $this->productRepository = $productRepository;
        $this->filesystem = $filesystem;
        $this->curl = $curl;
        $this->galleryProcessor = $galleryProcessor;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data, array $context = []): bool
    {
        $productId = $data['product_id'] ?? null;
        $sku = $data['sku'] ?? null;
        $imageUrls = $data['images'] ?? [];

        if (!$productId || !$sku || empty($imageUrls)) {
            return false;
        }

        try {
            // Load product
            $product = $this->productRepository->getById($productId);

            // Clear existing images if configured
            if ($this->config->shouldClearExistingImages()) {
                $this->galleryProcessor->clearMediaAttribute($product, ['image', 'small_image', 'thumbnail']);
            }

            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $tmpDir = $mediaDirectory->getAbsolutePath('import/');

            // Create tmp directory if not exists
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }

            $successCount = 0;
            $maxImages = $this->config->getMaxImagesPerProduct();
            $allowedExtensions = $this->config->getAllowedExtensions();

            foreach ($imageUrls as $index => $imageUrl) {
                // Respect max images limit
                if ($successCount >= $maxImages) {
                    break;
                }

                try {
                    // Validate URL
                    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $this->logger->logError('Invalid image URL', [
                            'sku' => $sku,
                            'url' => $imageUrl
                        ]);
                        continue;
                    }

                    // Download image
                    $imagePath = $this->downloadImage($imageUrl, $tmpDir, $allowedExtensions);

                    if (!$imagePath) {
                        continue;
                    }

                    // Add to product
                    $isMainImage = ($index === 0);
                    $imageRoles = $isMainImage ? ['image', 'small_image', 'thumbnail'] : [];

                    $product->addImageToMediaGallery(
                        $imagePath,
                        $imageRoles,
                        false, // not excluded
                        false  // not disabled
                    );

                    $successCount++;

                    $this->logger->logImport('Image added to product', [
                        'sku' => $sku,
                        'url' => $imageUrl,
                        'is_main' => $isMainImage
                    ]);

                    // Clean up temporary file
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }

                } catch (\Exception $e) {
                    $this->logger->logError('Failed to process image', [
                        'sku' => $sku,
                        'url' => $imageUrl,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Save product with images
            if ($successCount > 0) {
                $this->productRepository->save($product);

                $this->logger->logImport('Product images processed successfully', [
                    'sku' => $sku,
                    'total_urls' => count($imageUrls),
                    'successful' => $successCount
                ]);
            }

            return $successCount > 0;

        } catch (\Exception $e) {
            $this->logger->logError('Failed to process product images', [
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Download image from URL
     *
     * @param string $url
     * @param string $tmpDir
     * @param array $allowedExtensions
     * @return string|null Path to downloaded file or null on failure
     */
    private function downloadImage(string $url, string $tmpDir, array $allowedExtensions): ?string
    {
        try {
            // Set timeout
            $this->curl->setTimeout($this->config->getDownloadTimeout());
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->curl->setOption(CURLOPT_MAXREDIRS, 5);

            // Download
            $this->curl->get($url);

            $imageContent = $this->curl->getBody();
            $httpStatus = $this->curl->getStatus();

            if ($httpStatus !== 200 || empty($imageContent)) {
                $this->logger->logError('Failed to download image', [
                    'url' => $url,
                    'http_status' => $httpStatus
                ]);
                return null;
            }

            // Generate filename
            $extension = $this->getImageExtensionFromUrl($url);
            if (!in_array($extension, $allowedExtensions)) {
                $this->logger->logError('Image extension not allowed', [
                    'url' => $url,
                    'extension' => $extension
                ]);
                return null;
            }

            $filename = uniqid('ikas_') . '_' . time() . '.' . $extension;
            $filepath = $tmpDir . $filename;

            // Save file
            file_put_contents($filepath, $imageContent);

            // Validate MIME type and convert WebP to JPG if needed
            $mimeType = mime_content_type($filepath);
            if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $this->logger->logError('Invalid image MIME type', [
                    'url' => $url,
                    'mime_type' => $mimeType
                ]);
                unlink($filepath);
                return null;
            }

            // Convert WebP to JPG (Magento doesn't fully support WebP in product gallery)
            if ($mimeType === 'image/webp' || $extension === 'webp') {
                $convertedPath = $this->convertWebpToJpg($filepath);
                if ($convertedPath) {
                    // Delete original WebP file
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }
                    return $convertedPath;
                } else {
                    $this->logger->logError('Failed to convert WebP to JPG', [
                        'url' => $url,
                        'file' => $filepath
                    ]);
                    unlink($filepath);
                    return null;
                }
            }

            return $filepath;

        } catch (\Exception $e) {
            $this->logger->logError('Exception while downloading image', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get image extension from URL
     *
     * @param string $url
     * @return string
     */
    private function getImageExtensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Normalize
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        return $extension ?: 'jpg';
    }

    /**
     * Convert WebP image to JPG format
     *
     * @param string $webpPath Path to WebP file
     * @return string|null Path to converted JPG file or null on failure
     */
    private function convertWebpToJpg(string $webpPath): ?string
    {
        try {
            // Check if GD library supports WebP
            if (!function_exists('imagecreatefromwebp')) {
                $this->logger->logError('GD library does not support WebP. Please install php-gd with WebP support.');
                return null;
            }

            // Load WebP image
            $image = @imagecreatefromwebp($webpPath);
            if (!$image) {
                $this->logger->logError('Failed to load WebP image', ['file' => $webpPath]);
                return null;
            }

            // Create JPG filename
            $jpgPath = preg_replace('/\.webp$/i', '.jpg', $webpPath);
            if ($jpgPath === $webpPath) {
                $jpgPath = $webpPath . '.jpg';
            }

            // Convert to JPG with quality from config
            $quality = $this->config->getImageQuality() ?: 85;
            $success = imagejpeg($image, $jpgPath, $quality);
            
            // Free memory
            imagedestroy($image);

            if (!$success) {
                $this->logger->logError('Failed to save converted JPG', ['file' => $jpgPath]);
                return null;
            }

            $this->logger->logImport('WebP image converted to JPG', [
                'original' => basename($webpPath),
                'converted' => basename($jpgPath)
            ]);

            return $jpgPath;

        } catch (\Exception $e) {
            $this->logger->logError('Exception during WebP conversion', [
                'file' => $webpPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function supports(string $dataType): bool
    {
        return $dataType === 'image';
    }

    /**
     * @inheritdoc
     */
    public function validate(array $data): bool
    {
        return isset($data['product_id']) && isset($data['sku']) && isset($data['images']);
    }
}
