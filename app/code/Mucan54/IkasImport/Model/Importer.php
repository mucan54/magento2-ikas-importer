<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model;

use Mucan54\IkasImport\Api\ImporterInterface;
use Mucan54\IkasImport\Api\Data\ImportResultInterface;
use Mucan54\IkasImport\Api\Data\ValidationResultInterface;
use Mucan54\IkasImport\Api\ParserInterface;
use Mucan54\IkasImport\Api\ValidatorInterface;
use Mucan54\IkasImport\Model\ImportResult;
use Mucan54\IkasImport\Model\Config\ImportConfig;
use Mucan54\IkasImport\Model\Queue\Publisher;
use Mucan54\IkasImport\Model\Logger\Logger;
use Mucan54\IkasImport\Model\Processor\CategoryProcessor;
use Mucan54\IkasImport\Model\Processor\AttributeProcessor;

/**
 * Main importer orchestrator
 *
 * Parses CSV and publishes product batches to queue
 */
class Importer implements ImporterInterface
{
    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var ImportConfig
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CategoryProcessor
     */
    private $categoryProcessor;

    /**
     * @var AttributeProcessor
     */
    private $attributeProcessor;

    /**
     * @param ParserInterface $parser
     * @param ValidatorInterface $validator
     * @param Publisher $publisher
     * @param ImportConfig $config
     * @param Logger $logger
     * @param CategoryProcessor $categoryProcessor
     * @param AttributeProcessor $attributeProcessor
     */
    public function __construct(
        ParserInterface $parser,
        ValidatorInterface $validator,
        Publisher $publisher,
        ImportConfig $config,
        Logger $logger,
        CategoryProcessor $categoryProcessor,
        AttributeProcessor $attributeProcessor
    ) {
        $this->parser = $parser;
        $this->validator = $validator;
        $this->publisher = $publisher;
        $this->config = $config;
        $this->logger = $logger;
        $this->categoryProcessor = $categoryProcessor;
        $this->attributeProcessor = $attributeProcessor;
    }

    /**
     * @inheritdoc
     */
    public function import(string $filePath, array $config = []): ImportResultInterface
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $result = new ImportResult();
        $result->setSuccess(false);

        try {
            // Validate file (skip if requested)
            if (empty($config['skip_validation']) && !$this->parser->validate($filePath)) {
                $result->addError('Invalid CSV file', ['file' => $filePath]);
                return $result;
            }

            $this->logger->logImport('Import started', [
                'file' => $filePath,
                'config' => $config
            ]);

            // PRE-CREATE all attributes and categories from CSV
            $this->prepareAttributesAndCategories($filePath);

            // Get batch size from config
            $batchSize = $config['batch_size'] ?? $this->config->getBatchSize();

            $batch = [];
            $batchNumber = 0;
            $totalRows = 0;
            $publishedBatches = 0;
            $validationErrors = 0;
            $seenSkus = []; // Track SKUs to detect duplicates
            $duplicateSkus = 0;

            // Parse CSV using generator
            foreach ($this->parser->parse($filePath) as $productData) {
                $totalRows++;

                // Check for duplicate SKUs
                $sku = $productData['sku'] ?? '';
                if (isset($seenSkus[$sku])) {
                    $duplicateSkus++;
                    $this->logger->logImport('Duplicate SKU detected - skipping', [
                        'sku' => $sku,
                        'first_seen_row' => $seenSkus[$sku],
                        'duplicate_row' => $productData['row_number'] ?? $totalRows,
                        'stock_qty' => $productData['stock_qty'] ?? 'N/A'
                    ]);
                    continue; // Skip duplicate SKUs
                }
                $seenSkus[$sku] = $productData['row_number'] ?? $totalRows;

                // Validate product data
                $validationResult = $this->validator->validate($productData);

                if (!$validationResult->isValid()) {
                    $validationErrors++;
                    foreach ($validationResult->getErrors() as $error) {
                        $result->addError($error['message'], $error);
                    }
                    foreach ($validationResult->getWarnings() as $warning) {
                        $result->addWarning($warning['message'], $warning);
                    }
                    continue;
                }

                // Add to batch
                $batch[] = $productData;

                // Publish when batch is full
                if (count($batch) >= $batchSize) {
                    $batchNumber++;
                    $this->publisher->publishProductBatch($batch, $batchNumber);
                    $publishedBatches++;
                    $batch = [];

                    $this->logger->logImport('Batch published', [
                        'batch_number' => $batchNumber,
                        'size' => $batchSize
                    ]);
                }
            }

            // Publish remaining items
            if (!empty($batch)) {
                $batchNumber++;
                $this->publisher->publishProductBatch($batch, $batchNumber);
                $publishedBatches++;

                $this->logger->logImport('Final batch published', [
                    'batch_number' => $batchNumber,
                    'size' => count($batch)
                ]);
            }

            $result->setSuccess(true);
            $result->setProcessedRows($totalRows);
            $result->setSkippedRows($validationErrors);

            $this->logger->logImport('Import completed - batches queued', [
                'total_rows' => $totalRows,
                'published_batches' => $publishedBatches,
                'validation_errors' => $validationErrors,
                'duplicate_skus' => $duplicateSkus,
                'batch_size' => $batchSize
            ]);

        } catch (\Exception $e) {
            $result->setSuccess(false);
            $result->addError('Import failed: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            $this->logger->logError('Import failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
        }

        $executionTime = microtime(true) - $startTime;
        $memoryUsed = memory_get_usage() - $startMemory;

        $result->setExecutionTime($executionTime);
        $result->setMemoryUsage($memoryUsed);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function validate(string $filePath): ValidationResultInterface
    {
        try {
            // Validate file structure
            if (!$this->parser->validate($filePath)) {
                $validationResult = new \Mucan54\IkasImport\Model\ValidationResult();
                $validationResult->addError('Invalid CSV file structure', ['file' => $filePath]);
                return $validationResult;
            }

            $this->logger->logImport('Validation started', ['file' => $filePath]);

            $combinedResult = new \Mucan54\IkasImport\Model\ValidationResult();
            $rowCount = 0;

            // Validate each row
            foreach ($this->parser->parse($filePath) as $productData) {
                $rowCount++;
                $validationResult = $this->validator->validate($productData);

                if (!$validationResult->isValid()) {
                    foreach ($validationResult->getErrors() as $error) {
                        $combinedResult->addError($error['message'], $error);
                    }
                }

                foreach ($validationResult->getWarnings() as $warning) {
                    $combinedResult->addWarning($warning['message'], $warning);
                }
            }

            $this->logger->logImport('Validation completed', [
                'file' => $filePath,
                'rows' => $rowCount,
                'errors' => $combinedResult->getErrorCount(),
                'warnings' => $combinedResult->getWarningCount()
            ]);

            return $combinedResult;

        } catch (\Exception $e) {
            $validationResult = new \Mucan54\IkasImport\Model\ValidationResult();
            $validationResult->addError('Validation failed: ' . $e->getMessage());
            return $validationResult;
        }
    }

    /**
     * Pre-create all attributes and categories from CSV before import
     * 
     * Scans CSV file and creates all dynamic attributes and categories upfront
     * to prevent "attribute doesn't exist" errors during product save
     *
     * @param string $filePath Path to CSV file
     * @return void
     */
    private function prepareAttributesAndCategories(string $filePath): void
    {
        if (!$this->config->isDynamicAttributeEnabled()) {
            $this->logger->logImport('Dynamic attribute creation disabled - skipping pre-creation');
            return;
        }

        $this->logger->logImport('Starting pre-creation scan of CSV for attributes and categories');

        $allCategories = [];
        $allAttributes = [];

        // PASS 1: Scan CSV and collect all unique categories and attributes
        foreach ($this->parser->parse($filePath) as $productData) {
            // Collect categories (already parsed as array by CSV parser)
            if (!empty($productData['categories']) && is_array($productData['categories'])) {
                foreach ($productData['categories'] as $category) {
                    $category = trim($category);
                    if ($category !== '') {
                        $allCategories[$category] = true;
                    }
                }
            }

            // Collect all ikas_* attributes
            foreach ($productData as $key => $value) {
                if (strpos($key, 'ikas_') === 0 && $value !== '' && $value !== null) {
                    // Store attribute code with a sample value
                    if (!isset($allAttributes[$key])) {
                        $allAttributes[$key] = $value;
                    }
                }
            }
        }

        $categoryCount = count($allCategories);
        $attributeCount = count($allAttributes);

        $this->logger->logImport('CSV pre-scan completed', [
            'unique_categories' => $categoryCount,
            'unique_attributes' => $attributeCount,
            'attribute_codes' => array_keys($allAttributes)
        ]);

        // PASS 2: Create all categories
        if ($categoryCount > 0) {
            $this->logger->logImport('Creating categories before product import', [
                'count' => $categoryCount
            ]);

            foreach (array_keys($allCategories) as $categoryName) {
                try {
                    $this->categoryProcessor->process([
                        'name' => $categoryName
                    ]);
                } catch (\Exception $e) {
                    $this->logger->logError('Failed to pre-create category', [
                        'category' => $categoryName,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logger->logImport('Category pre-creation completed', [
                'created' => $categoryCount
            ]);
        }

        // PASS 3: Create all attributes
        if ($attributeCount > 0) {
            $this->logger->logImport('Creating attributes before product import', [
                'count' => $attributeCount,
                'attribute_codes' => array_keys($allAttributes)
            ]);

            $createdCount = 0;
            foreach ($allAttributes as $attributeCode => $sampleValue) {
                try {
                    // Call AttributeProcessor's ensureAttributeExists directly via reflection
                    // or we can create a dummy product to trigger attribute creation
                    $dummyProduct = new \Magento\Framework\DataObject();
                    
                    $this->attributeProcessor->process([
                        'product' => $dummyProduct,
                        'attributes' => [$attributeCode => $sampleValue]
                    ]);
                    
                    $createdCount++;
                    
                    $this->logger->logImport('Pre-created attribute', [
                        'attribute_code' => $attributeCode,
                        'sample_value' => mb_substr((string)$sampleValue, 0, 50)
                    ]);
                } catch (\Exception $e) {
                    $this->logger->logError('Failed to pre-create attribute', [
                        'attribute_code' => $attributeCode,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logger->logImport('Attribute pre-creation completed', [
                'attempted' => $attributeCount,
                'created' => $createdCount
            ]);
        }

        $this->logger->logImport('Pre-creation phase complete - ready for product import');
    }
}
