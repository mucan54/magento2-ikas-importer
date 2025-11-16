<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Api\Data;

/**
 * Import configuration interface
 *
 * Holds configuration options for import operation
 *
 * @api
 */
interface ImportConfigInterface
{
    /**
     * Get batch size
     *
     * @return int
     */
    public function getBatchSize(): int;

    /**
     * Set batch size
     *
     * @param int $size
     * @return $this
     */
    public function setBatchSize(int $size): self;

    /**
     * Check if async processing is enabled
     *
     * @return bool
     */
    public function isAsyncEnabled(): bool;

    /**
     * Set async processing
     *
     * @param bool $enabled
     * @return $this
     */
    public function setAsyncEnabled(bool $enabled): self;

    /**
     * Check if validation only mode
     *
     * @return bool
     */
    public function isValidationOnly(): bool;

    /**
     * Set validation only mode
     *
     * @param bool $validationOnly
     * @return $this
     */
    public function setValidationOnly(bool $validationOnly): self;

    /**
     * Check if images should be skipped
     *
     * @return bool
     */
    public function shouldSkipImages(): bool;

    /**
     * Set skip images
     *
     * @param bool $skip
     * @return $this
     */
    public function setSkipImages(bool $skip): self;

    /**
     * Check if reindex should be performed after import
     *
     * @return bool
     */
    public function shouldReindex(): bool;

    /**
     * Set reindex option
     *
     * @param bool $reindex
     * @return $this
     */
    public function setShouldReindex(bool $reindex): self;

    /**
     * Get configuration as array
     *
     * @return array
     */
    public function toArray(): array;
}
