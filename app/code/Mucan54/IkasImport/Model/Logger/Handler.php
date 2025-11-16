<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Custom log handler for Ikas import
 */
class Handler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/ikas_import.log';
}
