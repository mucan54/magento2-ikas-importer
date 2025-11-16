<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Logger;

use Monolog\Logger as MonologLogger;

/**
 * Custom logger for Ikas import operations
 */
class Logger extends MonologLogger
{
    /**
     * Add context to all log messages
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logImport(string $message, array $context = []): void
    {
        $context['import_session'] = $context['import_session'] ?? session_id();
        $context['timestamp'] = date('Y-m-d H:i:s');

        $this->info($message, $context);
    }

    /**
     * Log error with context
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logError(string $message, array $context = []): void
    {
        $context['import_session'] = $context['import_session'] ?? session_id();
        $context['timestamp'] = date('Y-m-d H:i:s');

        $this->error($message, $context);
    }
}
