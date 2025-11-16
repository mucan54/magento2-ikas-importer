<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Parser;

use Mucan54\IkasImport\Model\Config\ImportConfig;
use Mucan54\IkasImport\Model\Logger\Logger;

/**
 * Parse dynamic attributes from HTML descriptions
 *
 * Extracts key-value pairs from HTML content in format "Key : Value"
 */
class AttributeParser
{
    /**
     * @var ImportConfig
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ImportConfig $config
     * @param Logger $logger
     */
    public function __construct(
        ImportConfig $config,
        Logger $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Parse attributes from HTML description
     *
     * @param string $html
     * @return array Associative array of attribute code => value
     */
    public function parse(string $html): array
    {
        if (empty($html)) {
            return [];
        }

        $attributes = [];

        // Suppress DOMDocument warnings for malformed HTML
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        // Extract from <li> tags
        $listItems = $dom->getElementsByTagName('li');
        foreach ($listItems as $item) {
            $text = trim($item->textContent);
            $parsed = $this->parseKeyValue($text);
            if ($parsed) {
                $attributes = array_merge($attributes, $parsed);
            }
        }

        // Extract from <p> tags if no <li> found
        if (empty($attributes)) {
            $paragraphs = $dom->getElementsByTagName('p');
            foreach ($paragraphs as $paragraph) {
                $text = trim($paragraph->textContent);
                $parsed = $this->parseKeyValue($text);
                if ($parsed) {
                    $attributes = array_merge($attributes, $parsed);
                }
            }
        }

        return $attributes;
    }

    /**
     * Parse key-value pair from text
     *
     * Expected format: "Key : Value" or "Key: Value"
     *
     * @param string $text
     * @return array|null
     */
    private function parseKeyValue(string $text): ?array
    {
        // Split on colon
        $parts = explode(':', $text, 2);

        if (count($parts) !== 2) {
            return null;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        // Validate key and value
        if (empty($key) || empty($value)) {
            return null;
        }

        // Check minimum key length
        if (mb_strlen($key) < $this->config->getMinKeyLength()) {
            return null;
        }

        // Check if key is blacklisted
        if ($this->isBlacklisted($key)) {
            $this->logger->debug('Attribute key is blacklisted', ['key' => $key]);
            return null;
        }

        // Generate attribute code from key
        $attributeCode = $this->generateAttributeCode($key);

        return [$attributeCode => $value];
    }

    /**
     * Check if attribute key is blacklisted
     *
     * @param string $key
     * @return bool
     */
    private function isBlacklisted(string $key): bool
    {
        $blacklist = $this->config->getAttributeBlacklist();
        $keyLower = mb_strtolower($key, 'UTF-8');

        foreach ($blacklist as $blacklisted) {
            if (mb_strpos($keyLower, $blacklisted) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate attribute code from Turkish label
     *
     * Converts Turkish characters to ASCII equivalents and creates valid attribute code
     *
     * @param string $label
     * @return string
     */
    public function generateAttributeCode(string $label): string
    {
        // Convert Turkish characters to ASCII
        $transliteration = [
            'ı' => 'i', 'İ' => 'i',
            'ş' => 's', 'Ş' => 's',
            'ğ' => 'g', 'Ğ' => 'g',
            'ç' => 'c', 'Ç' => 'c',
            'ö' => 'o', 'Ö' => 'o',
            'ü' => 'u', 'Ü' => 'u',
        ];

        $code = strtr($label, $transliteration);

        // Convert to lowercase
        $code = mb_strtolower($code, 'UTF-8');

        // Replace non-alphanumeric with underscore
        $code = preg_replace('/[^a-z0-9]+/', '_', $code);

        // Trim underscores
        $code = trim($code, '_');

        // Add prefix
        $prefix = $this->config->getAttributePrefix();
        $code = $prefix . $code;

        // Limit length to 30 characters (Magento limit)
        if (strlen($code) > 30) {
            $code = substr($code, 0, 30);
            $code = rtrim($code, '_');
        }

        return $code;
    }
}
