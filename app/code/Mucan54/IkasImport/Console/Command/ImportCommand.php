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
use Mucan54\IkasImport\Model\Logger\Logger;
use Mucan54\IkasImport\Api\ParserInterface;
use Mucan54\IkasImport\Model\Processor\StockProcessor;

/**
 * CLI command for importing products from CSV
 *
 * Usage: php bin/magento ikas:import:run <file_path>
 */
class ImportCommand extends Command
{
    private const ARGUMENT_FILE = 'file';
    private const OPTION_VALIDATE_ONLY = 'validate-only';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var StockProcessor
     */
    private $stockProcessor;

    /**
     * @param Logger $logger
     * @param ParserInterface $parser
     * @param StockProcessor $stockProcessor
     * @param string|null $name
     */
    public function __construct(
        Logger $logger,
        ParserInterface $parser,
        StockProcessor $stockProcessor,
        string $name = null
    ) {
        $this->logger = $logger;
        $this->parser = $parser;
        $this->stockProcessor = $stockProcessor;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('ikas:import:run')
            ->setDescription('Import products from Ikas CSV file')
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

        $output->writeln('<info>Ikas Product Import</info>');
        $output->writeln('File: ' . $filePath);

        try {
            // Validate file
            if (!$this->parser->validate($filePath)) {
                $output->writeln('<error>Invalid CSV file</error>');
                return Command::FAILURE;
            }

            $output->writeln('<info>File validation passed</info>');

            if ($validateOnly) {
                $output->writeln('<info>Validation only mode - skipping import</info>');
                return Command::SUCCESS;
            }

            // Get row count
            $totalRows = $this->parser->getRowCount($filePath);
            $output->writeln("Total rows to process: {$totalRows}");

            // Parse and process
            $processedCount = 0;
            $errorCount = 0;

            foreach ($this->parser->parse($filePath) as $productData) {
                try {
                    // For now, just process stock as a demonstration
                    // Full implementation would process entire product
                    if ($this->stockProcessor->validate($productData)) {
                        $this->stockProcessor->process($productData);
                        $processedCount++;
                    }

                    if ($processedCount % 10 === 0) {
                        $output->writeln("Processed: {$processedCount}/{$totalRows}");
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->logger->logError('Failed to process row', [
                        'row' => $productData['row_number'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $output->writeln('<info>Import completed</info>');
            $output->writeln("Successfully processed: {$processedCount}");
            $output->writeln("Errors: {$errorCount}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Import failed: ' . $e->getMessage() . '</error>');
            $this->logger->logError('Import failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }
}
