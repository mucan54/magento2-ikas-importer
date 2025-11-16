<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model;

use Mucan54\IkasImport\Api\Data\ValidationResultInterface;

/**
 * Validation result implementation
 */
class ValidationResult implements ValidationResultInterface
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $warnings = [];

    /**
     * @inheritdoc
     */
    public function isValid(): bool
    {
        return empty($this->errors);
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
    public function addError(string $message, array $context = []): ValidationResultInterface
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
    public function addWarning(string $message, array $context = []): ValidationResultInterface
    {
        $this->warnings[] = array_merge(['message' => $message], $context);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * @inheritdoc
     */
    public function getWarningCount(): int
    {
        return count($this->warnings);
    }
}
