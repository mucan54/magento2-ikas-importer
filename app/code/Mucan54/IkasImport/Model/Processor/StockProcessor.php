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
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;

/**
 * Stock processor - USES BOTH Legacy Stock AND MSI (Multi-Source Inventory)
 *
 * Properly manages stock using both the legacy API and MSI for salable quantities
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
     * @var SourceItemsSaveInterface|null
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemInterfaceFactory|null
     */
    private $sourceItemFactory;

    /**
     * @var GetSourceItemsBySkuInterface|null
     */
    private $getSourceItemsBySku;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param ImportConfig $config
     * @param Logger $logger
     * @param SourceItemsSaveInterface|null $sourceItemsSave
     * @param SourceItemInterfaceFactory|null $sourceItemFactory
     * @param GetSourceItemsBySkuInterface|null $getSourceItemsBySku
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        ImportConfig $config,
        Logger $logger,
        SourceItemsSaveInterface $sourceItemsSave = null,
        SourceItemInterfaceFactory $sourceItemFactory = null,
        GetSourceItemsBySkuInterface $getSourceItemsBySku = null
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->config = $config;
        $this->logger = $logger;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
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

        // Log stock processing attempt
        $this->logger->logImport('Processing stock update', [
            'sku' => $sku,
            'qty' => $qty,
            'qty_type' => gettype($qty),
            'data_has_stock_qty' => array_key_exists('stock_qty', $data),
            'stock_qty_value' => $data['stock_qty'] ?? 'NOT_SET'
        ]);

        try {
            // 1. Update legacy CatalogInventory stock
            $stockItem = $this->stockRegistry->getStockItemBySku($sku);
            $stockItem->setQty($qty);
            $stockItem->setIsInStock($qty > $this->config->getOutOfStockThreshold());
            $stockItem->setManageStock($this->config->isManageStock());
            $stockItem->setUseConfigManageStock($this->config->isUseConfigManageStock());
            $stockItem->setBackorders($this->config->getBackorders());
            $this->stockRegistry->updateStockItemBySku($sku, $stockItem);

            // 2. Update MSI (Multi-Source Inventory) if available
            if ($this->sourceItemsSave && $this->sourceItemFactory) {
                $this->updateMsiStock($sku, $qty);
            }

            $this->logger->logImport('Stock updated successfully', [
                'sku' => $sku,
                'qty' => $qty,
                'is_in_stock' => $stockItem->getIsInStock(),
                'msi_enabled' => $this->sourceItemsSave !== null
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

    /**
     * Update MSI (Multi-Source Inventory) stock
     *
     * @param string $sku
     * @param float $qty
     * @return void
     */
    private function updateMsiStock(string $sku, float $qty): void
    {
        try {
            // Get existing source items or create new one
            $sourceItems = [];
            
            if ($this->getSourceItemsBySku) {
                try {
                    $existingSourceItems = $this->getSourceItemsBySku->execute($sku);
                    $sourceItems = $existingSourceItems;
                } catch (\Exception $e) {
                    // No existing source items, will create new one
                    $this->logger->logImport('No existing MSI source items', [
                        'sku' => $sku
                    ]);
                }
            }

            // Update existing or create new source item for default source
            $sourceItem = null;
            foreach ($sourceItems as $item) {
                if ($item->getSourceCode() === 'default') {
                    $sourceItem = $item;
                    break;
                }
            }

            if (!$sourceItem && $this->sourceItemFactory) {
                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setSourceCode('default');
                $sourceItem->setSku($sku);
            }

            if ($sourceItem) {
                $sourceItem->setQuantity($qty);
                $sourceItem->setStatus($qty > 0 ? 1 : 0); // In stock if qty > 0
                
                $this->sourceItemsSave->execute([$sourceItem]);
                
                $this->logger->logImport('MSI stock updated', [
                    'sku' => $sku,
                    'qty' => $qty,
                    'source' => 'default'
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->logError('Failed to update MSI stock', [
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
        }
    }
}
