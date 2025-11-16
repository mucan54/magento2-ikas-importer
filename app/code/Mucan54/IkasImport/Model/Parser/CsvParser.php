<?php
/**
 * Copyright © Mucan54. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mucan54\IkasImport\Model\Parser;

use Mucan54\IkasImport\Api\ParserInterface;
use Mucan54\IkasImport\Model\Logger\Logger;
use Mucan54\IkasImport\Model\Parser\AttributeParser;

/**
 * CSV parser with generator support for memory efficiency
 *
 * Handles UTF-8, Turkish characters, BOM detection
 */
class CsvParser implements ParserInterface
{
    /**
     * Expected CSV headers from Ikas
     */
    private const EXPECTED_HEADERS = [
        'Ürün Grup ID',
        'Varyant ID',
        'İsim',
        'Açıklama',
        'Satış Fiyatı',
        'İndirimli Fiyatı',
        'Alış Fiyatı',
        'Barkod Listesi',
        'SKU',
        'Silindi mi?',
        'Marka',
        'Kategoriler',
        'Etiketler',
        'Resim URL',
        'Metadata Açıklama',
        'Slug',
        'Stok:Ana Depo',
        'Desi',
        'HS Kod',
        'Google Ürün Kategorisi',
        'Stoğu Tükenince Satmaya Devam Et',
        'Satış Kanalı:safranntuhafiye',
        'Satış Kanalı:GELİVER',
        'Sepet Başına Maksimum Alma Adeti:safranntuhafiye',
        'Varyant Aktiflik'
    ];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AttributeParser
     */
    private $attributeParser;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @param Logger $logger
     * @param AttributeParser $attributeParser
     */
    public function __construct(
        Logger $logger,
        AttributeParser $attributeParser
    ) {
        $this->logger = $logger;
        $this->attributeParser = $attributeParser;
    }

    /**
     * @inheritdoc
     */
    public function parse(string $filePath): \Generator
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException("File is not readable: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Failed to open file: {$filePath}");
        }

        try {
            // Detect and skip BOM if present
            $this->skipBom($handle);

            // Read headers
            $this->headers = $this->readHeaders($handle);
            if (empty($this->headers)) {
                throw new \RuntimeException("CSV file has no headers");
            }

            $this->logger->logImport('CSV parsing started', [
                'file' => $filePath,
                'headers_count' => count($this->headers),
                'headers' => $this->headers
            ]);

            $rowNumber = 1;
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $rowNumber++;

                // Skip empty rows
                if ($this->isEmptyRow($data)) {
                    continue;
                }

                // Validate column count
                if (count($data) !== count($this->headers)) {
                    $this->logger->warning("Row {$rowNumber}: Column count mismatch", [
                        'expected' => count($this->headers),
                        'actual' => count($data),
                        'row' => $rowNumber,
                        'headers' => $this->headers,
                        'data' => $data
                    ]);
                    continue;
                }

                // Combine headers with data
                $row = array_combine($this->headers, $data);
                
                // Debug log for first few rows
                if ($rowNumber <= 3) {
                    $this->logger->logImport("Parsed row {$rowNumber}", [
                        'row' => $row,
                        'sku_value' => $row['SKU'] ?? 'NOT_FOUND'
                    ]);
                }

                // Convert to UTF-8 if needed
                $row = $this->ensureUtf8($row);

                // Parse product data
                $productData = $this->parseProductData($row, $rowNumber);

                yield $productData;
            }

            $this->logger->logImport('CSV parsing completed', [
                'file' => $filePath,
                'total_rows' => $rowNumber - 1
            ]);

        } finally {
            fclose($handle);
        }
    }

    /**
     * @inheritdoc
     */
    public function validate(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException("File is not readable: {$filePath}");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            throw new \InvalidArgumentException("Invalid file type. Expected CSV, got: {$extension}");
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function getRowCount(string $filePath): int
    {
        if (!file_exists($filePath)) {
            return 0;
        }

        $count = 0;
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return 0;
        }

        // Skip header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            if (!$this->isEmptyRow($data)) {
                $count++;
            }
        }

        fclose($handle);
        return $count;
    }

    /**
     * Skip BOM (Byte Order Mark) if present
     *
     * @param resource $handle
     * @return void
     */
    private function skipBom($handle): void
    {
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            // Not a BOM, rewind
            rewind($handle);
        }
    }

    /**
     * Read CSV headers
     *
     * @param resource $handle
     * @return array
     */
    private function readHeaders($handle): array
    {
        $headers = fgetcsv($handle, 0, ',');
        if ($headers === false) {
            return [];
        }

        // Ensure UTF-8 and trim
        $headers = array_map(function ($header) {
            $header = $this->convertToUtf8($header);
            return trim($header);
        }, $headers);

        return $headers;
    }

    /**
     * Check if row is empty
     *
     * @param array $data
     * @return bool
     */
    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if (!empty(trim($value))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Ensure all values are UTF-8
     *
     * @param array $data
     * @return array
     */
    private function ensureUtf8(array $data): array
    {
        return array_map([$this, 'convertToUtf8'], $data);
    }

    /**
     * Convert string to UTF-8
     *
     * @param string $string
     * @return string
     */
    private function convertToUtf8(string $string): string
    {
        $encoding = mb_detect_encoding($string, ['UTF-8', 'ISO-8859-9', 'Windows-1254'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            return mb_convert_encoding($string, 'UTF-8', $encoding);
        }

        return $string;
    }

    /**
     * Parse product data from CSV row
     *
     * @param array $row
     * @param int $rowNumber
     * @return array
     */
    private function parseProductData(array $row, int $rowNumber): array
    {
        $sku = $this->getValue($row, 'SKU');
        $name = $this->getValue($row, 'İsim');
        
        // If SKU is invalid or numeric, generate one from name/row
        if (empty($sku) || !preg_match('/^[a-zA-Z0-9_-]+$/', $sku)) {
            $originalSku = $sku;
            // Try to generate SKU from name
            if (!empty($name)) {
                $sku = $this->generateSkuFromName($name, $rowNumber);
            } else {
                $sku = 'PRODUCT-' . str_pad($rowNumber, 6, '0', STR_PAD_LEFT);
            }
            
            $this->logger->logImport("Generated SKU for row {$rowNumber}", [
                'original_sku' => $originalSku,
                'generated_sku' => $sku,
                'name' => $name
            ]);
        }
        
        $description = $this->getValue($row, 'Açıklama');
        $price = $this->parseFloat($this->getValue($row, 'Satış Fiyatı'));
        $specialPrice = $this->parseFloat($this->getValue($row, 'İndirimli Fiyatı'));
        $stock = $this->parseFloat($this->getValue($row, 'Stok:Ana Depo'));
        $categories = $this->parseCategories($this->getValue($row, 'Kategoriler'));
        $images = $this->parseImages($this->getValue($row, 'Resim URL'));
        $weight = $this->parseFloat($this->getValue($row, 'Desi'));
        $slug = $this->getValue($row, 'Slug');
        $isActive = $this->getValue($row, 'Varyant Aktiflik');

        // Parse dynamic attributes from description
        $attributes = [];
        if (!empty($description)) {
            $attributes = $this->attributeParser->parse($description);
        }

        // Determine status based on Varyant Aktiflik - default to ENABLED if not explicitly disabled
        $status = 1; // Default to Enabled
        if (!empty($isActive)) {
            // Only disable if explicitly set to "Pasif" or "0"
            if ($isActive === 'Pasif' || $isActive === '0' || strtolower($isActive) === 'disabled') {
                $status = 2; // Disabled
            }
        }

        // Log stock for debugging
        if ($rowNumber <= 5) {
            $this->logger->logImport("Product data prepared", [
                'row' => $rowNumber,
                'sku' => $sku,
                'stock_qty' => $stock,
                'stock_type' => gettype($stock),
                'status' => $status,
                'is_active' => $isActive
            ]);
        }

        return [
            'row_number' => $rowNumber,
            'sku' => $sku,
            'name' => $name,
            'description' => $description,
            'short_description' => mb_substr(strip_tags($description ?? ''), 0, 255),
            'price' => $price,
            'special_price' => $specialPrice,
            'stock_qty' => $stock,
            'categories' => $categories,
            'images' => $images,
            'attributes' => $attributes,
            'status' => $status,
            'weight' => $weight,
            'url_key' => $slug,
            'visibility' => 4, // Catalog, Search
        ];
    }

    /**
     * Generate SKU from product name
     *
     * @param string $name
     * @param int $rowNumber
     * @return string
     */
    private function generateSkuFromName(string $name, int $rowNumber): string
    {
        // Remove Turkish characters and special chars
        $sku = $this->transliterate($name);
        
        // Keep only alphanumeric and replace spaces with hyphens
        $sku = preg_replace('/[^a-zA-Z0-9]+/', '-', $sku);
        
        // Remove leading/trailing hyphens
        $sku = trim($sku, '-');
        
        // Convert to uppercase
        $sku = strtoupper($sku);
        
        // If SKU is empty after cleaning, use generic prefix
        if (empty($sku)) {
            $sku = 'PRODUCT';
        }
        
        // Create a hash from the original name for additional uniqueness
        // This ensures even identical names get different SKUs
        $hash = substr(md5($name . $rowNumber . microtime(true)), 0, 6);
        
        // Limit base SKU to 40 chars to leave room for row number and hash
        if (strlen($sku) > 40) {
            $sku = substr($sku, 0, 40);
        }
        
        // Combine: BASE-ROWNUMBER-HASH for guaranteed uniqueness
        $sku = $sku . '-' . str_pad((string)$rowNumber, 4, '0', STR_PAD_LEFT) . '-' . strtoupper($hash);
        
        return $sku;
    }

    /**
     * Transliterate Turkish characters to ASCII
     *
     * @param string $text
     * @return string
     */
    private function transliterate(string $text): string
    {
        $turkishChars = ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'];
        $asciiChars = ['i', 'g', 'u', 's', 'o', 'c', 'I', 'G', 'U', 'S', 'O', 'C'];
        
        return str_replace($turkishChars, $asciiChars, $text);
    }

    /**
     * Get value from row array safely
     *
     * @param array $row
     * @param string $key
     * @return string
     */
    private function getValue(array $row, string $key): string
    {
        return isset($row[$key]) ? trim($row[$key]) : '';
    }

    /**
     * Parse float value
     *
     * @param string $value
     * @return float|null
     */
    private function parseFloat(string $value): ?float
    {
        if (empty($value)) {
            return null;
        }

        // Handle Turkish decimal separator (comma)
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);

        return $value !== '' ? (float)$value : null;
    }

    /**
     * Parse categories from semicolon-separated string
     *
     * @param string $categories
     * @return array
     */
    private function parseCategories(string $categories): array
    {
        if (empty($categories)) {
            return [];
        }

        $parts = explode(';', $categories);
        return array_filter(array_map('trim', $parts));
    }

    /**
     * Parse images from semicolon-separated URLs
     *
     * @param string $images
     * @return array
     */
    private function parseImages(string $images): array
    {
        if (empty($images)) {
            return [];
        }

        $parts = explode(';', $images);
        return array_filter(array_map('trim', $parts));
    }
}
