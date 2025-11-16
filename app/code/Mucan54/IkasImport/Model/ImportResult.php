<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model;

use Mucan54\IkasImport\Api\Data\ImportResultInterface;

/**
 * Import result implementation
 */
class ImportResult implements ImportResultInterface
{
    /**
     * @var bool
     */
    private $success = false;

    /**
     * @var int
     */
    private $processedRows = 0;

    /**
     * @var int
     */
    private $createdProducts = 0;

    /**
     * @var int
     */
    private $updatedProducts = 0;

    /**
     * @var int
     */
    private $skippedRows = 0;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $warnings = [];

    /**
     * @var float
     */
    private $executionTime = 0.0;

    /**
     * @var int
     */
    private $memoryUsage = 0;

    /**
     * @inheritdoc
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @inheritdoc
     */
    public function setSuccess(bool $success): ImportResultInterface
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProcessedRows(): int
    {
        return $this->processedRows;
    }

    /**
     * @inheritdoc
     */
    public function setProcessedRows(int $count): ImportResultInterface
    {
        $this->processedRows = $count;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedProducts(): int
    {
        return $this->createdProducts;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedProducts(int $count): ImportResultInterface
    {
        $this->createdProducts = $count;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedProducts(): int
    {
        return $this->updatedProducts;
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedProducts(int $count): ImportResultInterface
    {
        $this->updatedProducts = $count;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSkippedRows(): int
    {
        return $this->skippedRows;
    }

    /**
     * @inheritdoc
     */
    public function setSkippedRows(int $count): ImportResultInterface
    {
        $this->skippedRows = $count;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @inheritdoc
     */
    public function setErrors(array $errors): ImportResultInterface
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addError(string $message, array $context = []): ImportResultInterface
    {
        $this->errors[] = array_merge(['message' => $message], $context);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * @inheritdoc
     */
    public function setWarnings(array $warnings): ImportResultInterface
    {
        $this->warnings = $warnings;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addWarning(string $message, array $context = []): ImportResultInterface
    {
        $this->warnings[] = array_merge(['message' => $message], $context);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * @inheritdoc
     */
    public function setExecutionTime(float $time): ImportResultInterface
    {
        $this->executionTime = $time;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMemoryUsage(): int
    {
        return $this->memoryUsage;
    }

    /**
     * @inheritdoc
     */
    public function setMemoryUsage(int $bytes): ImportResultInterface
    {
        $this->memoryUsage = $bytes;
        return $this;
    }
}
