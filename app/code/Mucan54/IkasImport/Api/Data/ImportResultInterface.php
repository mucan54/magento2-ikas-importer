<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Api\Data;

/**
 * Import result interface
 *
 * Holds the results of an import operation including statistics and errors
 *
 * @api
 */
interface ImportResultInterface
{
    /**
     * Get success status
     *
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * Set success status
     *
     * @param bool $success
     * @return $this
     */
    public function setSuccess(bool $success): self;

    /**
     * Get total processed rows
     *
     * @return int
     */
    public function getProcessedRows(): int;

    /**
     * Set total processed rows
     *
     * @param int $count
     * @return $this
     */
    public function setProcessedRows(int $count): self;

    /**
     * Get created products count
     *
     * @return int
     */
    public function getCreatedProducts(): int;

    /**
     * Set created products count
     *
     * @param int $count
     * @return $this
     */
    public function setCreatedProducts(int $count): self;

    /**
     * Get updated products count
     *
     * @return int
     */
    public function getUpdatedProducts(): int;

    /**
     * Set updated products count
     *
     * @param int $count
     * @return $this
     */
    public function setUpdatedProducts(int $count): self;

    /**
     * Get skipped rows count
     *
     * @return int
     */
    public function getSkippedRows(): int;

    /**
     * Set skipped rows count
     *
     * @param int $count
     * @return $this
     */
    public function setSkippedRows(int $count): self;

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Set errors
     *
     * @param array $errors
     * @return $this
     */
    public function setErrors(array $errors): self;

    /**
     * Add error
     *
     * @param string $message
     * @param array $context
     * @return $this
     */
    public function addError(string $message, array $context = []): self;

    /**
     * Get warnings
     *
     * @return array
     */
    public function getWarnings(): array;

    /**
     * Set warnings
     *
     * @param array $warnings
     * @return $this
     */
    public function setWarnings(array $warnings): self;

    /**
     * Add warning
     *
     * @param string $message
     * @param array $context
     * @return $this
     */
    public function addWarning(string $message, array $context = []): self;

    /**
     * Get execution time in seconds
     *
     * @return float
     */
    public function getExecutionTime(): float;

    /**
     * Set execution time in seconds
     *
     * @param float $time
     * @return $this
     */
    public function setExecutionTime(float $time): self;

    /**
     * Get memory usage in bytes
     *
     * @return int
     */
    public function getMemoryUsage(): int;

    /**
     * Set memory usage in bytes
     *
     * @param int $bytes
     * @return $this
     */
    public function setMemoryUsage(int $bytes): self;
}
