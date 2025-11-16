<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model;

use Mucan54\IkasImport\Api\Data\ProductDataInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Product data implementation
 */
class ProductData implements ProductDataInterface
{
    /**
     * @var string
     */
    private $sku;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string|null
     */
    private $shortDescription;

    /**
     * @var float
     */
    private $price = 0.0;

    /**
     * @var float|null
     */
    private $specialPrice;

    /**
     * @var float
     */
    private $stockQty = 0.0;

    /**
     * @var array
     */
    private $categories = [];

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $images = [];

    /**
     * @var int
     */
    private $status = Status::STATUS_ENABLED;

    /**
     * @var float|null
     */
    private $weight;

    /**
     * @var string|null
     */
    private $urlKey;

    /**
     * @var int
     */
    private $visibility = Visibility::VISIBILITY_BOTH;

    /**
     * @inheritdoc
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @inheritdoc
     */
    public function setSku(string $sku): ProductDataInterface
    {
        $this->sku = $sku;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): ProductDataInterface
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @inheritdoc
     */
    public function setDescription(?string $description): ProductDataInterface
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * @inheritdoc
     */
    public function setShortDescription(?string $shortDescription): ProductDataInterface
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @inheritdoc
     */
    public function setPrice(float $price): ProductDataInterface
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSpecialPrice(): ?float
    {
        return $this->specialPrice;
    }

    /**
     * @inheritdoc
     */
    public function setSpecialPrice(?float $specialPrice): ProductDataInterface
    {
        $this->specialPrice = $specialPrice;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStockQty(): float
    {
        return $this->stockQty;
    }

    /**
     * @inheritdoc
     */
    public function setStockQty(float $qty): ProductDataInterface
    {
        $this->stockQty = $qty;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @inheritdoc
     */
    public function setCategories(array $categories): ProductDataInterface
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritdoc
     */
    public function setAttributes(array $attributes): ProductDataInterface
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @inheritdoc
     */
    public function setImages(array $images): ProductDataInterface
    {
        $this->images = $images;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @inheritdoc
     */
    public function setStatus(int $status): ProductDataInterface
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * @inheritdoc
     */
    public function setWeight(?float $weight): ProductDataInterface
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUrlKey(): ?string
    {
        return $this->urlKey;
    }

    /**
     * @inheritdoc
     */
    public function setUrlKey(?string $urlKey): ProductDataInterface
    {
        $this->urlKey = $urlKey;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getVisibility(): int
    {
        return $this->visibility;
    }

    /**
     * @inheritdoc
     */
    public function setVisibility(int $visibility): ProductDataInterface
    {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->shortDescription,
            'price' => $this->price,
            'special_price' => $this->specialPrice,
            'stock_qty' => $this->stockQty,
            'categories' => $this->categories,
            'attributes' => $this->attributes,
            'images' => $this->images,
            'status' => $this->status,
            'weight' => $this->weight,
            'url_key' => $this->urlKey,
            'visibility' => $this->visibility,
        ];
    }
}
