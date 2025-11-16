<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mucan54\IkasImport\Api\ImporterInterface;
use Mucan54\IkasImport\Model\Logger\Logger;

/**
 * CLI command for importing products from CSV (Queue-based)
 *
 * Usage: php bin/magento ikas:import:run <file_path>
 *
 * This command parses CSV and publishes product batches to queue.
 * Start queue consumers to process: php bin/magento queue:consumers:start ikas.product.import.batch.consumer
 */
class ImportCommand extends Command
{
    private const ARGUMENT_FILE = 'file';
    private const OPTION_VALIDATE_ONLY = 'validate-only';
    private const OPTION_BATCH_SIZE = 'batch-size';

    /**
     * @var ImporterInterface
     */
    private $importer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ImporterInterface $importer
     * @param Logger $logger
     * @param string|null $name
     */
    public function __construct(
        ImporterInterface $importer,
        Logger $logger,
        ?string $name = null
    ) {
        $this->importer = $importer;
        $this->logger = $logger;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('ikas:import:run')
            ->setDescription('Import products from Ikas CSV file (queue-based)')
            ->addArgument(
                self::ARGUMENT_FILE,
                InputArgument::REQUIRED,
                'Path to CSV file'
            )
            ->addOption(
                self::OPTION_VALIDATE_ONLY,
                null,
                InputOption::VALUE_NONE,
                'Only validate the CSV file without importing'
            )
            ->addOption(
                self::OPTION_BATCH_SIZE,
                'b',
                InputOption::VALUE_REQUIRED,
                'Batch size for queue publishing (default: 50)',
                50
            );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getArgument(self::ARGUMENT_FILE);
        $validateOnly = $input->getOption(self::OPTION_VALIDATE_ONLY);
        $batchSize = (int)$input->getOption(self::OPTION_BATCH_SIZE);

        $output->writeln('<info>====================================</info>');
        $output->writeln('<info>   Ikas Product Import (Queue)    </info>');
        $output->writeln('<info>====================================</info>');
        $output->writeln('');
        $output->writeln('File: ' . $filePath);
        $output->writeln('Batch Size: ' . $batchSize);
        $output->writeln('');

        try {
            if ($validateOnly) {
                $output->writeln('<comment>Validation Mode</comment>');
                $output->writeln('');

                $validationResult = $this->importer->validate($filePath);

                if ($validationResult->isValid()) {
                    $output->writeln('<info>✓ Validation passed</info>');
                } else {
                    $output->writeln('<error>✗ Validation failed</error>');
                    $output->writeln('');
                    $output->writeln('Errors: ' . $validationResult->getErrorCount());
                    $output->writeln('Warnings: ' . $validationResult->getWarningCount());

                    if ($output->isVerbose()) {
                        $output->writeln('');
                        $output->writeln('<error>Error Details:</error>');
                        foreach ($validationResult->getErrors() as $error) {
                            $output->writeln('  - ' . $error['message']);
                        }
                    }
                }

                return $validationResult->isValid() ? Command::SUCCESS : Command::FAILURE;
            }

            // Import mode - publish to queue
            $output->writeln('<comment>Import Mode - Publishing to Queue</comment>');
            $output->writeln('');

            $result = $this->importer->import($filePath, ['batch_size' => $batchSize]);

            $output->writeln('');
            $output->writeln('<info>====================================</info>');
            $output->writeln('<info>         Import Summary            </info>');
            $output->writeln('<info>====================================</info>');
            $output->writeln('');
            $output->writeln('Status: ' . ($result->isSuccess() ? '<info>✓ SUCCESS</info>' : '<error>✗ FAILED</error>'));
            $output->writeln('Processed Rows: ' . $result->getProcessedRows());
            $output->writeln('Skipped Rows: ' . $result->getSkippedRows());
            $output->writeln('Execution Time: ' . round($result->getExecutionTime(), 2) . 's');
            $output->writeln('Memory Used: ' . round($result->getMemoryUsage() / 1024 / 1024, 2) . ' MB');
            $output->writeln('');

            if ($result->isSuccess()) {
                $output->writeln('<info>✓ Product batches have been published to queue</info>');
                $output->writeln('');
                $output->writeln('<comment>Next Steps:</comment>');
                $output->writeln('1. Start product batch consumer:');
                $output->writeln('   php bin/magento queue:consumers:start ikas.product.import.batch.consumer');
                $output->writeln('');
                $output->writeln('2. Start image consumer:');
                $output->writeln('   php bin/magento queue:consumers:start ikas.product.images.consumer');
                $output->writeln('');
                $output->writeln('<comment>Tip:</comment> Run consumers in background with supervisor for production');
            } else {
                $output->writeln('<error>Import failed. Check logs: var/log/ikas_import.log</error>');

                if (!empty($result->getErrors())) {
                    $output->writeln('');
                    $output->writeln('<error>Errors:</error>');
                    foreach (array_slice($result->getErrors(), 0, 5) as $error) {
                        $output->writeln('  - ' . $error['message']);
                    }

                    if (count($result->getErrors()) > 5) {
                        $output->writeln('  ... and ' . (count($result->getErrors()) - 5) . ' more errors');
                    }
                }
            }

            $output->writeln('');

            return $result->isSuccess() ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $output->writeln('');
            $output->writeln('<error>Fatal Error: ' . $e->getMessage() . '</error>');
            $output->writeln('');
            $this->logger->logError('CLI Import failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }
}
