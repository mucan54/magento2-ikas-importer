<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Validator;

use Mucan54\IkasImport\Api\ValidatorInterface;
use Mucan54\IkasImport\Api\Data\ValidationResultInterface;
use Mucan54\IkasImport\Model\ValidationResult;
use Mucan54\IkasImport\Model\Config\ImportConfig;

/**
 * Product data validator
 *
 * Validates product data according to business rules
 */
class ProductDataValidator implements ValidatorInterface
{
    /**
     * @var ImportConfig
     */
    private $config;

    /**
     * @var ValidationResult
     */
    private $validationResultFactory;

    /**
     * @param ImportConfig $config
     */
    public function __construct(ImportConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function validate($data): ValidationResultInterface
    {
        $result = new ValidationResult();

        if (!is_array($data)) {
            $result->addError('Invalid data type. Expected array.');
            return $result;
        }

        // Validate required fields
        $this->validateRequiredFields($data, $result);

        // Validate SKU format
        if ($this->config->shouldValidateSku() && isset($data['sku'])) {
            $this->validateSku($data['sku'], $result);
        }

        // Validate prices
        if ($this->config->shouldValidatePrices()) {
            $this->validatePrices($data, $result);
        }

        // Validate URLs
        if ($this->config->shouldValidateUrls() && isset($data['images'])) {
            $this->validateImageUrls($data['images'], $result);
        }

        // Validate stock quantity
        if (isset($data['stock_qty'])) {
            $this->validateStockQty($data['stock_qty'], $result);
        }

        // Validate weight
        if (isset($data['weight']) && $data['weight'] !== null) {
            $this->validateWeight($data['weight'], $result);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getRules(): array
    {
        return [
            'required_fields' => $this->config->getRequiredFields(),
            'validate_sku' => $this->config->shouldValidateSku(),
            'validate_prices' => $this->config->shouldValidatePrices(),
            'validate_urls' => $this->config->shouldValidateUrls(),
            'min_price' => $this->config->getMinPrice(),
            'max_price' => $this->config->getMaxPrice(),
        ];
    }

    /**
     * Validate required fields
     *
     * @param array $data
     * @param ValidationResultInterface $result
     * @return void
     */
    private function validateRequiredFields(array $data, ValidationResultInterface $result): void
    {
        $requiredFields = $this->config->getRequiredFields();

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $result->addError("Required field '{$field}' is missing or empty", [
                    'field' => $field,
                    'row' => $data['row_number'] ?? null
                ]);
            }
        }
    }

    /**
     * Validate SKU format
     *
     * @param string $sku
     * @param ValidationResultInterface $result
     * @return void
     */
    private function validateSku(string $sku, ValidationResultInterface $result): void
    {
        // SKU should be alphanumeric with hyphens and underscores allowed
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $sku)) {
            $result->addError("Invalid SKU format. Only alphanumeric characters, hyphens, and underscores are allowed", [
                'sku' => $sku
            ]);
        }

        // Check length
        if (strlen($sku) > 64) {
            $result->addError("SKU is too long. Maximum 64 characters allowed", [
                'sku' => $sku,
                'length' => strlen($sku)
            ]);
        }
    }

    /**
     * Validate prices
     *
     * @param array $data
     * @param ValidationResultInterface $result
     * @return void
     */
    private function validatePrices(array $data, ValidationResultInterface $result): void
    {
        $price = $data['price'] ?? null;
        $specialPrice = $data['special_price'] ?? null;
        $minPrice = $this->config->getMinPrice();
        $maxPrice = $this->config->getMaxPrice();

        // Validate regular price
        if ($price !== null) {
            if (!is_numeric($price)) {
                $result->addError("Price must be a numeric value", [
                    'price' => $price,
                    'sku' => $data['sku'] ?? null
                ]);
            } elseif ($price < 0) {
                $result->addError("Price cannot be negative", [
                    'price' => $price,
                    'sku' => $data['sku'] ?? null
                ]);
            } elseif ($price < $minPrice) {
                $result->addError("Price is below minimum allowed value", [
                    'price' => $price,
                    'min_price' => $minPrice,
                    'sku' => $data['sku'] ?? null
                ]);
            } elseif ($price > $maxPrice) {
                $result->addError("Price exceeds maximum allowed value", [
                    'price' => $price,
                    'max_price' => $maxPrice,
                    'sku' => $data['sku'] ?? null
                ]);
            }
        }

        // Validate special price
        if ($specialPrice !== null && $price !== null) {
            if (!is_numeric($specialPrice)) {
                $result->addError("Special price must be a numeric value", [
                    'special_price' => $specialPrice,
                    'sku' => $data['sku'] ?? null
                ]);
            } elseif ($specialPrice < 0) {
                $result->addError("Special price cannot be negative", [
                    'special_price' => $specialPrice,
                    'sku' => $data['sku'] ?? null
                ]);
            } elseif ($specialPrice >= $price) {
                $result->addWarning("Special price should be less than regular price", [
                    'price' => $price,
                    'special_price' => $specialPrice,
                    'sku' => $data['sku'] ?? null
                ]);
            }
        }
    }

    /**
     * Validate image URLs
     *
     * @param array $images
     * @param ValidationResultInterface $result
     * @return void
     */
    private function validateImageUrls(array $images, ValidationResultInterface $result): void
    {
        foreach ($images as $index => $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $result->addError("Invalid image URL format", [
                    'url' => $url,
                    'index' => $index
                ]);
                continue;
            }

            // Check if URL scheme is http or https
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (!in_array($scheme, ['http', 'https'])) {
                $result->addError("Image URL must use http or https protocol", [
                    'url' => $url,
                    'scheme' => $scheme
                ]);
            }
        }
    }

    /**
     * Validate stock quantity
     *
     * @param mixed $qty
     * @param ValidationResultInterface $result
     * @return void
     */
    private function validateStockQty($qty, ValidationResultInterface $result): void
    {
        if ($qty === null) {
            return;
        }

        if (!is_numeric($qty)) {
            $result->addError("Stock quantity must be a numeric value", [
                'stock_qty' => $qty
            ]);
        } elseif ($qty < 0) {
            $result->addError("Stock quantity cannot be negative", [
                'stock_qty' => $qty
            ]);
        }
    }

    /**
     * Validate weight
     *
     * @param mixed $weight
     * @param ValidationResultInterface $result
     * @return void
     */
    private function validateWeight($weight, ValidationResultInterface $result): void
    {
        if (!is_numeric($weight)) {
            $result->addError("Weight must be a numeric value", [
                'weight' => $weight
            ]);
        } elseif ($weight < 0) {
            $result->addError("Weight cannot be negative", [
                'weight' => $weight
            ]);
        }
    }
}
