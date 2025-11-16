<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Import status manager
 */
class ImportStatus
{
    private const CACHE_KEY = 'ikas_import_status';
    private const CACHE_LIFETIME = 3600; // 1 hour

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param CacheInterface $cache
     * @param DateTime $dateTime
     */
    public function __construct(
        CacheInterface $cache,
        DateTime $dateTime
    ) {
        $this->cache = $cache;
        $this->dateTime = $dateTime;
    }

    /**
     * Check if import is running
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        $status = $this->getStatus();
        return !empty($status) && $status['status'] === 'running';
    }

    /**
     * Get current import status
     *
     * @return array|null
     */
    public function getStatus(): ?array
    {
        $data = $this->cache->load(self::CACHE_KEY);
        if ($data) {
            return json_decode($data, true);
        }
        return null;
    }

    /**
     * Start import
     *
     * @param string $filename
     * @param array $config
     * @return void
     */
    public function start(string $filename, array $config = []): void
    {
        $status = [
            'status' => 'running',
            'filename' => $filename,
            'started_at' => $this->dateTime->gmtDate(),
            'config' => $config,
            'progress' => [
                'processed' => 0,
                'total' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0
            ]
        ];

        $this->cache->save(
            json_encode($status),
            self::CACHE_KEY,
            [],
            self::CACHE_LIFETIME
        );
    }

    /**
     * Update import progress
     *
     * @param array $progress
     * @return void
     */
    public function updateProgress(array $progress): void
    {
        $status = $this->getStatus();
        if ($status) {
            $status['progress'] = array_merge($status['progress'], $progress);
            $status['updated_at'] = $this->dateTime->gmtDate();

            $this->cache->save(
                json_encode($status),
                self::CACHE_KEY,
                [],
                self::CACHE_LIFETIME
            );
        }
    }

    /**
     * Complete import
     *
     * @param array $finalStats
     * @return void
     */
    public function complete(array $finalStats = []): void
    {
        $status = $this->getStatus();
        if ($status) {
            $status['status'] = 'completed';
            $status['completed_at'] = $this->dateTime->gmtDate();
            $status['progress'] = array_merge($status['progress'], $finalStats);

            $this->cache->save(
                json_encode($status),
                self::CACHE_KEY,
                [],
                300 // Keep for 5 minutes after completion
            );
        }
    }

    /**
     * Mark import as failed
     *
     * @param string $error
     * @return void
     */
    public function fail(string $error): void
    {
        $status = $this->getStatus();
        if ($status) {
            $status['status'] = 'failed';
            $status['failed_at'] = $this->dateTime->gmtDate();
            $status['error'] = $error;

            $this->cache->save(
                json_encode($status),
                self::CACHE_KEY,
                [],
                300 // Keep for 5 minutes after failure
            );
        }
    }

    /**
     * Clear status
     *
     * @return void
     */
    public function clear(): void
    {
        $this->cache->remove(self::CACHE_KEY);
    }

    /**
     * Get formatted status message
     *
     * @return string
     */
    public function getStatusMessage(): string
    {
        $status = $this->getStatus();
        if (!$status) {
            return 'No import running';
        }

        $progress = $status['progress'];
        $message = sprintf(
            'Import Status: %s | File: %s | Started: %s',
            ucfirst($status['status']),
            $status['filename'] ?? 'Unknown',
            $status['started_at'] ?? 'Unknown'
        );

        if ($status['status'] === 'running') {
            $message .= sprintf(
                ' | Progress: %d/%d (Created: %d, Updated: %d, Skipped: %d, Errors: %d)',
                $progress['processed'],
                $progress['total'],
                $progress['created'],
                $progress['updated'],
                $progress['skipped'],
                $progress['errors']
            );
        } elseif ($status['status'] === 'completed') {
            $message .= sprintf(
                ' | Completed: %s | Total: %d (Created: %d, Updated: %d, Skipped: %d, Errors: %d)',
                $status['completed_at'] ?? 'Unknown',
                $progress['processed'],
                $progress['created'],
                $progress['updated'],
                $progress['skipped'],
                $progress['errors']
            );
        } elseif ($status['status'] === 'failed') {
            $message .= sprintf(
                ' | Failed: %s | Error: %s',
                $status['failed_at'] ?? 'Unknown',
                $status['error'] ?? 'Unknown error'
            );
        }

        return $message;
    }
}
