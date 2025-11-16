<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Api;

/**
 * CSV parser interface
 *
 * Parses CSV files and yields rows as generators for memory efficiency
 *
 * @api
 */
interface ParserInterface
{
    /**
     * Parse CSV file and yield rows
     *
     * @param string $filePath
     * @return \Generator
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function parse(string $filePath): \Generator;

    /**
     * Validate CSV file structure
     *
     * @param string $filePath
     * @return bool
     */
    public function validate(string $filePath): bool;

    /**
     * Get CSV headers
     *
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Get total row count
     *
     * @param string $filePath
     * @return int
     */
    public function getRowCount(string $filePath): int;
}
