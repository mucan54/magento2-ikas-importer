<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Block\Adminhtml\Import;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\UrlInterface;

/**
 * Import form block
 */
class Form extends Template
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        parent::__construct($context, $data);
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormAction(): string
    {
        return $this->urlBuilder->getUrl('*/*/import');
    }

    /**
     * Get upload URL
     *
     * @return string
     */
    public function getUploadUrl(): string
    {
        return $this->urlBuilder->getUrl('*/*/upload');
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }
}
