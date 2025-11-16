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
     * @param ParserInterface $parser
     * @param ValidatorInterface $validator
     * @param Publisher $publisher
     * @param ImportConfig $config
     * @param Logger $logger
     */
    public function __construct(
        ParserInterface $parser,
        ValidatorInterface $validator,
        Publisher $publisher,
        ImportConfig $config,
        Logger $logger
    ) {
        $this->parser = $parser;
        $this->validator = $validator;
        $this->publisher = $publisher;
        $this->config = $config;
        $this->logger = $logger;
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
}
