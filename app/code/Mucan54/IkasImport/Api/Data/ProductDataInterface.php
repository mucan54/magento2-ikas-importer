<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Api\Data;

/**
 * Product data interface
 *
 * Represents product data parsed from CSV
 *
 * @api
 */
interface ProductDataInterface
{
    /**
     * Get SKU
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * Get product name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set product name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): self;

    /**
     * Get short description
     *
     * @return string|null
     */
    public function getShortDescription(): ?string;

    /**
     * Set short description
     *
     * @param string|null $shortDescription
     * @return $this
     */
    public function setShortDescription(?string $shortDescription): self;

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Set price
     *
     * @param float $price
     * @return $this
     */
    public function setPrice(float $price): self;

    /**
     * Get special price
     *
     * @return float|null
     */
    public function getSpecialPrice(): ?float;

    /**
     * Set special price
     *
     * @param float|null $specialPrice
     * @return $this
     */
    public function setSpecialPrice(?float $specialPrice): self;

    /**
     * Get stock quantity
     *
     * @return float
     */
    public function getStockQty(): float;

    /**
     * Set stock quantity
     *
     * @param float $qty
     * @return $this
     */
    public function setStockQty(float $qty): self;

    /**
     * Get categories
     *
     * @return array
     */
    public function getCategories(): array;

    /**
     * Set categories
     *
     * @param array $categories
     * @return $this
     */
    public function setCategories(array $categories): self;

    /**
     * Get dynamic attributes
     *
     * @return array
     */
    public function getAttributes(): array;

    /**
     * Set dynamic attributes
     *
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes): self;

    /**
     * Get image URLs
     *
     * @return array
     */
    public function getImages(): array;

    /**
     * Set image URLs
     *
     * @param array $images
     * @return $this
     */
    public function setImages(array $images): self;

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int;

    /**
     * Set status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status): self;

    /**
     * Get weight
     *
     * @return float|null
     */
    public function getWeight(): ?float;

    /**
     * Set weight
     *
     * @param float|null $weight
     * @return $this
     */
    public function setWeight(?float $weight): self;

    /**
     * Get URL key
     *
     * @return string|null
     */
    public function getUrlKey(): ?string;

    /**
     * Set URL key
     *
     * @param string|null $urlKey
     * @return $this
     */
    public function setUrlKey(?string $urlKey): self;

    /**
     * Get visibility
     *
     * @return int
     */
    public function getVisibility(): int;

    /**
     * Set visibility
     *
     * @param int $visibility
     * @return $this
     */
    public function setVisibility(int $visibility): self;

    /**
     * Get all data as array
     *
     * @return array
     */
    public function toArray(): array;
}
