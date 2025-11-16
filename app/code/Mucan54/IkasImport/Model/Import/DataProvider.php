<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Import;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;

/**
 * Data provider for import form
 */
class DataProvider implements DataProviderInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $primaryFieldName;

    /**
     * @var string
     */
    private $requestFieldName;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var array
     */
    private $loadedData;

    /**
     * @var array
     */
    private $meta = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->name = $name;
        $this->primaryFieldName = $primaryFieldName;
        $this->requestFieldName = $requestFieldName;
        $this->dataPersistor = $dataPersistor;
        $this->meta = $meta;
        $this->data = $data;
    }

    /**
     * Get data provider name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get primary field name
     *
     * @return string
     */
    public function getPrimaryFieldName()
    {
        return $this->primaryFieldName;
    }

    /**
     * Get field name in request
     *
     * @return string
     */
    public function getRequestFieldName()
    {
        return $this->requestFieldName;
    }

    /**
     * Get meta information
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Get field set meta info
     *
     * @param string $fieldSetName
     * @return array
     */
    public function getFieldSetMetaInfo($fieldSetName)
    {
        return $this->meta[$fieldSetName] ?? [];
    }

    /**
     * Get fields meta info
     *
     * @param string $fieldSetName
     * @return array
     */
    public function getFieldsMetaInfo($fieldSetName)
    {
        return $this->meta[$fieldSetName]['children'] ?? [];
    }

    /**
     * Get field meta info
     *
     * @param string $fieldSetName
     * @param string $fieldName
     * @return array
     */
    public function getFieldMetaInfo($fieldSetName, $fieldName)
    {
        return $this->meta[$fieldSetName]['children'][$fieldName] ?? [];
    }

    /**
     * Add filter
     *
     * @param \Magento\Framework\Api\Filter $filter
     * @return void
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        // Not needed for this simple form
    }

    /**
     * Add field to select
     *
     * @param string|array $field
     * @param string|null $alias
     * @return void
     */
    public function addField($field, $alias = null)
    {
        // Not needed for this simple form
    }

    /**
     * Add order
     *
     * @param string $field
     * @param string $direction
     * @return void
     */
    public function addOrder($field, $direction)
    {
        // Not needed for this simple form
    }

    /**
     * Set limit
     *
     * @param int $offset
     * @param int $size
     * @return void
     */
    public function setLimit($offset, $size)
    {
        // Not needed for this simple form
    }

    /**
     * Remove field
     *
     * @param string $field
     * @param bool $isAlias
     * @return void
     */
    public function removeField($field, $isAlias = false)
    {
        // Not needed for this simple form
    }

    /**
     * Remove all fields
     *
     * @return void
     */
    public function removeAllFields()
    {
        // Not needed for this simple form
    }

    /**
     * Get config data
     *
     * @return array
     */
    public function getConfigData()
    {
        return $this->data;
    }

    /**
     * Set config data
     *
     * @param mixed $config
     * @return void
     */
    public function setConfigData($config)
    {
        $this->data = array_replace_recursive($this->data, $config);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $data = $this->dataPersistor->get('ikas_import');
        if (!empty($data)) {
            $this->loadedData[''] = $data;
            $this->dataPersistor->clear('ikas_import');
        }

        return $this->loadedData ?? [];
    }

    /**
     * Get search criteria
     *
     * @return \Magento\Framework\Api\Search\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Get search result
     *
     * @return \Magento\Framework\Api\Search\SearchResultInterface|null
     */
    public function getSearchResult()
    {
        return null;
    }
}
