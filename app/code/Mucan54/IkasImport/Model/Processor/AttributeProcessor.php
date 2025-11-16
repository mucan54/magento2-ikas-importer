<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Processor;

use Mucan54\IkasImport\Api\ProcessorInterface;
use Mucan54\IkasImport\Model\Config\ImportConfig;
use Mucan54\IkasImport\Model\Cache\AttributeCache;
use Mucan54\IkasImport\Model\Logger\Logger;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterfaceFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Attribute processor
 *
 * Creates dynamic product attributes and assigns values
 */
class AttributeProcessor implements ProcessorInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var AttributeInterfaceFactory
     */
    private $attributeFactory;

    /**
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * @var AttributeCache
     */
    private $attributeCache;

    /**
     * @var ImportConfig
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param AttributeRepositoryInterface $attributeRepository
     * @param AttributeInterfaceFactory $attributeFactory
     * @param EavSetup $eavSetup
     * @param AttributeCache $attributeCache
     * @param ImportConfig $config
     * @param Logger $logger
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        AttributeInterfaceFactory $attributeFactory,
        EavSetup $eavSetup,
        AttributeCache $attributeCache,
        ImportConfig $config,
        Logger $logger
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->attributeFactory = $attributeFactory;
        $this->eavSetup = $eavSetup;
        $this->attributeCache = $attributeCache;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data, array $context = []): bool
    {
        if (!$this->config->isDynamicAttributeEnabled()) {
            return true;
        }

        $product = $data['product'] ?? null;
        $attributes = $data['attributes'] ?? [];

        if (!$product || empty($attributes)) {
            return true;
        }

        foreach ($attributes as $attributeCode => $value) {
            try {
                // Ensure attribute exists
                $this->ensureAttributeExists($attributeCode, $value);

                // Set attribute value on product
                $product->setData($attributeCode, $value);

                $this->logger->debug('Attribute set on product', [
                    'attribute_code' => $attributeCode,
                    'value' => mb_substr($value, 0, 50)
                ]);

            } catch (\Exception $e) {
                $this->logger->logError('Failed to process attribute', [
                    'attribute_code' => $attributeCode,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return true;
    }

    /**
     * Ensure attribute exists, create if not
     *
     * @param string $attributeCode
     * @param string $value
     * @return void
     * @throws \Exception
     */
    private function ensureAttributeExists(string $attributeCode, string $value): void
    {
        // Check cache first
        if ($this->attributeCache->has($attributeCode)) {
            return;
        }

        // Try to load from repository
        try {
            $this->attributeRepository->get(Product::ENTITY, $attributeCode);
            $this->attributeCache->set($attributeCode, true);
            return;
        } catch (NoSuchEntityException $e) {
            // Attribute doesn't exist, create it
        }

        // Create new attribute
        $this->createAttribute($attributeCode, $value);
        $this->attributeCache->set($attributeCode, true);
    }

    /**
     * Create new product attribute
     *
     * @param string $attributeCode
     * @param string $sampleValue
     * @return void
     * @throws \Exception
     */
    private function createAttribute(string $attributeCode, string $sampleValue): void
    {
        // Determine type based on value length
        $maxLength = $this->config->getMaxValueLength();
        $type = (mb_strlen($sampleValue) > $maxLength) ? 'text' : 'varchar';
        $input = ($type === 'text') ? 'textarea' : 'text';

        // Generate label from attribute code
        $label = $this->generateLabel($attributeCode);

        $this->eavSetup->addAttribute(
            Product::ENTITY,
            $attributeCode,
            [
                'type' => $type,
                'backend' => '',
                'frontend' => '',
                'label' => $label,
                'input' => $input,
                'class' => '',
                'source' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => '',
                'searchable' => true,
                'filterable' => true,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => '',
                'group' => $this->config->getAttributeGroup()
            ]
        );

        $this->logger->logImport('Dynamic attribute created', [
            'attribute_code' => $attributeCode,
            'label' => $label,
            'type' => $type
        ]);
    }

    /**
     * Generate human-readable label from attribute code
     *
     * @param string $attributeCode
     * @return string
     */
    private function generateLabel(string $attributeCode): string
    {
        // Remove prefix
        $prefix = $this->config->getAttributePrefix();
        $label = str_replace($prefix, '', $attributeCode);

        // Replace underscores with spaces
        $label = str_replace('_', ' ', $label);

        // Capitalize words
        $label = ucwords($label);

        return $label;
    }

    /**
     * @inheritdoc
     */
    public function supports(string $dataType): bool
    {
        return $dataType === 'attribute';
    }

    /**
     * @inheritdoc
     */
    public function validate(array $data): bool
    {
        return isset($data['product']) && isset($data['attributes']);
    }
}
