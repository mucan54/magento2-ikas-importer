<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Api\Data;

/**
 * Validation result interface
 *
 * Holds validation results with errors and warnings
 *
 * @api
 */
interface ValidationResultInterface
{
    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Add validation error
     *
     * @param string $message
     * @param array $context
     * @return $this
     */
    public function addError(string $message, array $context = []): self;

    /**
     * Get validation warnings
     *
     * @return array
     */
    public function getWarnings(): array;

    /**
     * Add validation warning
     *
     * @param string $message
     * @param array $context
     * @return $this
     */
    public function addWarning(string $message, array $context = []): self;

    /**
     * Get error count
     *
     * @return int
     */
    public function getErrorCount(): int;

    /**
     * Get warning count
     *
     * @return int
     */
    public function getWarningCount(): int;
}
