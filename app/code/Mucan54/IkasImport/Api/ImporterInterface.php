<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Api;

use Mucan54\IkasImport\Api\Data\ImportResultInterface;
use Mucan54\IkasImport\Api\Data\ValidationResultInterface;

/**
 * Main importer interface
 *
 * Orchestrates the entire import process
 *
 * @api
 */
interface ImporterInterface
{
    /**
     * Import products from CSV file
     *
     * @param string $filePath
     * @param array $config
     * @return ImportResultInterface
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function import(string $filePath, array $config = []): ImportResultInterface;

    /**
     * Validate CSV file without importing
     *
     * @param string $filePath
     * @return ValidationResultInterface
     * @throws \InvalidArgumentException
     */
    public function validate(string $filePath): ValidationResultInterface;
}
