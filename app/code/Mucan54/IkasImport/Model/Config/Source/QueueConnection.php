<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Queue connection type source model
 */
class QueueConnection implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'db', 'label' => __('Database')],
            ['value' => 'amqp', 'label' => __('RabbitMQ (AMQP)')],
        ];
    }
}
