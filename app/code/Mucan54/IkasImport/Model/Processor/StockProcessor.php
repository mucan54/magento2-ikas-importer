<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Processor;

use Mucan54\IkasImport\Api\ProcessorInterface;
use Mucan54\IkasImport\Model\Config\ImportConfig;
use Mucan54\IkasImport\Model\Logger\Logger;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Stock processor - USES StockRegistryInterface (NOT setStockData)
 *
 * Properly manages stock using the recommended API
 */
class StockProcessor implements ProcessorInterface
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var ImportConfig
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param ImportConfig $config
     * @param Logger $logger
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        ImportConfig $config,
        Logger $logger
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data, array $context = []): bool
    {
        $sku = $data['sku'] ?? null;
        $qty = $data['stock_qty'] ?? 0;

        if (!$sku) {
            $this->logger->logError('Cannot process stock: SKU is missing');
            return false;
        }

        try {
            // Get stock item by SKU using StockRegistryInterface
            $stockItem = $this->stockRegistry->getStockItemBySku($sku);

            // Update stock quantity
            $stockItem->setQty($qty);

            // Set in stock status
            $stockItem->setIsInStock($qty > $this->config->getOutOfStockThreshold());

            // Configure stock management
            $stockItem->setManageStock($this->config->isManageStock());
            $stockItem->setUseConfigManageStock($this->config->isUseConfigManageStock());
            $stockItem->setBackorders($this->config->getBackorders());

            // Save using StockRegistryInterface
            $this->stockRegistry->updateStockItemBySku($sku, $stockItem);

            $this->logger->logImport('Stock updated successfully', [
                'sku' => $sku,
                'qty' => $qty,
                'is_in_stock' => $stockItem->getIsInStock()
            ]);

            return true;

        } catch (NoSuchEntityException $e) {
            $this->logger->logError('Product not found for stock update', [
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->logError('Failed to update stock', [
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function supports(string $dataType): bool
    {
        return $dataType === 'stock';
    }

    /**
     * @inheritdoc
     */
    public function validate(array $data): bool
    {
        if (!isset($data['sku']) || empty($data['sku'])) {
            return false;
        }

        if (isset($data['stock_qty']) && !is_numeric($data['stock_qty'])) {
            return false;
        }

        return true;
    }
}
