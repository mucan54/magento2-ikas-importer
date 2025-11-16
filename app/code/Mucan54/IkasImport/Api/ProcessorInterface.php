<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Api;

/**
 * Processor interface
 *
 * Processes specific types of data (products, categories, attributes, etc.)
 *
 * @api
 */
interface ProcessorInterface
{
    /**
     * Process data
     *
     * @param array $data
     * @param array $context
     * @return bool
     * @throws \Exception
     */
    public function process(array $data, array $context = []): bool;

    /**
     * Check if processor supports given data type
     *
     * @param string $dataType
     * @return bool
     */
    public function supports(string $dataType): bool;

    /**
     * Validate data before processing
     *
     * @param array $data
     * @return bool
     */
    public function validate(array $data): bool;
}
