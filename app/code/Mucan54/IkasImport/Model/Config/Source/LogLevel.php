<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Log level source model
 */
class LogLevel implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'DEBUG', 'label' => __('Debug')],
            ['value' => 'INFO', 'label' => __('Info')],
            ['value' => 'WARNING', 'label' => __('Warning')],
            ['value' => 'ERROR', 'label' => __('Error')],
            ['value' => 'CRITICAL', 'label' => __('Critical')],
        ];
    }
}
