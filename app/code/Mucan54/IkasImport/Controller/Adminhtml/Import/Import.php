<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Mucan54\IkasImport\Api\ImporterInterface;
use Mucan54\IkasImport\Model\ImportStatus;
use Psr\Log\LoggerInterface;

/**
 * Import action controller
 */
class Import extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Mucan54_IkasImport::import';

    /**
     * @var ImporterInterface
     */
    private $importer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ImportStatus
     */
    private $importStatus;

    /**
     * @param Context $context
     * @param ImporterInterface $importer
     * @param LoggerInterface $logger
     * @param ImportStatus $importStatus
     */
    public function __construct(
        Context $context,
        ImporterInterface $importer,
        LoggerInterface $logger,
        ImportStatus $importStatus
    ) {
        parent::__construct($context);
        $this->importer = $importer;
        $this->logger = $logger;
        $this->importStatus = $importStatus;
    }

    /**
     * Execute import action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');

        // Check if import is already running
        if ($this->importStatus->isRunning()) {
            $this->messageManager->addWarningMessage(
                __('An import is already running. Please wait for it to complete.')
            );
            $this->messageManager->addNoticeMessage($this->importStatus->getStatusMessage());
            return $resultRedirect;
        }

        try {
            // Get uploaded file
            $files = $this->getRequest()->getFiles();
            if (!isset($files['csv_file']) || !$files['csv_file']['tmp_name']) {
                throw new LocalizedException(__('Please upload a CSV file.'));
            }

            $csvFile = $files['csv_file']['tmp_name'];
            $originalFileName = $files['csv_file']['name'];
            
            // Get form parameters
            $batchSize = (int) $this->getRequest()->getParam('batch_size', 100);
            $useQueue = (bool) $this->getRequest()->getParam('use_queue', true);

            // Validate file
            if (!file_exists($csvFile)) {
                throw new LocalizedException(__('Uploaded file not found.'));
            }

            if (!is_readable($csvFile)) {
                throw new LocalizedException(__('Cannot read uploaded file.'));
            }

            // Validate file extension
            $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
            if ($fileExtension !== 'csv') {
                throw new LocalizedException(__('Invalid file type. Only CSV files are allowed.'));
            }

            // Start import
            $this->logger->info('Starting import from admin panel', [
                'file' => $originalFileName,
                'batch_size' => $batchSize,
                'use_queue' => $useQueue
            ]);

            // Prepare config
            $config = [
                'batch_size' => $batchSize,
                'use_queue' => $useQueue,
                'skip_validation' => true  // Skip file extension validation since we already validated
            ];

            // Start import status tracking
            $this->importStatus->start($originalFileName, $config);

            $result = $this->importer->import($csvFile, $config);

            // Mark import as complete
            $this->importStatus->complete([
                'processed' => $result->getProcessedRows(),
                'created' => $result->getCreatedProducts(),
                'updated' => $result->getUpdatedProducts(),
                'skipped' => $result->getSkippedRows(),
                'errors' => count($result->getErrors())
            ]);

            if ($result->isSuccess()) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'Import completed successfully. Processed: %1, Created: %2, Updated: %3, Skipped: %4',
                        $result->getProcessedRows(),
                        $result->getCreatedProducts(),
                        $result->getUpdatedProducts(),
                        $result->getSkippedRows()
                    )
                );

                if (!empty($result->getErrors())) {
                    foreach (array_slice($result->getErrors(), 0, 5) as $error) {
                        $errorMessage = is_array($error) ? json_encode($error) : (string)$error;
                        $this->messageManager->addWarningMessage($errorMessage);
                    }
                    if (count($result->getErrors()) > 5) {
                        $this->messageManager->addNoticeMessage(
                            __('... and %1 more errors. Check logs for details.', count($result->getErrors()) - 5)
                        );
                    }
                }
            } else {
                $this->messageManager->addErrorMessage(
                    __('Import completed with errors. Check logs for details.')
                );
                
                foreach (array_slice($result->getErrors(), 0, 10) as $error) {
                    $errorMessage = is_array($error) ? json_encode($error) : (string)$error;
                    $this->messageManager->addErrorMessage($errorMessage);
                }
            }

        } catch (LocalizedException $e) {
            $this->importStatus->fail($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->error('Import error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        } catch (\Exception $e) {
            $this->importStatus->fail($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred during import: %1', $e->getMessage())
            );
            $this->logger->error('Import exception: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $resultRedirect;
    }
}
