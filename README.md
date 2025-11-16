# Magento 2 - Ikas Product Import Module

Professional, extensible Magento 2 module for importing products from Ikas e-commerce platform.

**Module Name:** `Mucan54_IkasImport`
**Namespace:** `Mucan54\IkasImport`
**Version:** 1.0.0

---

## Overview

This module provides a robust, production-ready solution for importing products from Ikas e-commerce platform into Magento 2. It follows SOLID principles, Magento 2 best practices, and is designed for easy extension and maintenance.

### Key Features

- **CSV Import with Memory Efficiency**: Uses PHP generators to handle large files (10,000+ products) without timeout
- **Turkish Character Support**: Full UTF-8 support with automatic encoding detection
- **Dynamic Attribute Creation**: Automatically extracts and creates product attributes from HTML descriptions
- **Proper Stock Management**: Uses `StockRegistryInterface` (not deprecated `setStockData`)
- **Category Hierarchy**: Automatically creates nested category structures
- **Async Image Processing**: Queue-based image downloading for better performance
- **Comprehensive Validation**: Multiple validation layers for data integrity
- **Extensible Architecture**: Well-defined interfaces, events, and plugins support
- **Detailed Logging**: Separate log file with context-aware logging
- **CLI Support**: Command-line tools for automation
- **Admin Interface**: User-friendly admin panel for manual imports

---

## Installation

### Via Composer (Recommended)

```bash
composer require mucan54/magento2-ikas-importer
php bin/magento module:enable Mucan54_IkasImport
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

### Manual Installation

1. Create directory: `app/code/Mucan54/IkasImport`
2. Copy all module files to this directory
3. Run:
```bash
php bin/magento module:enable Mucan54_IkasImport
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

---

## Configuration

Navigate to: **Stores > Configuration > Ikas Import > Configuration**

### General Settings
- **Enable Module**: Enable/disable import functionality
- **Batch Size**: Number of products per batch (default: 100)
- **Default Category Root**: Root category for products (e.g., "İplikler")
- **Max Execution Time**: Maximum execution time in seconds (default: 3600)

### Attribute Settings
- **Enable Dynamic Attributes**: Auto-create attributes from descriptions
- **Attribute Prefix**: Prefix for generated attributes (default: `ikas_`)
- **Attribute Group**: Group name for attributes (default: "Ikas Attributes")
- **Blacklisted Keys**: Comma-separated keys to ignore
- **Min Key Length**: Minimum attribute key length (default: 2)

### Stock Settings
- **Manage Stock**: Enable stock management
- **Backorders**: Allow backorders setting
- **Default Source Code**: MSI source code (default: "default")
- **Out of Stock Threshold**: Threshold value (default: 0)

### Image Settings
- **Enable Async Processing**: Use message queue for images
- **Queue Connection**: `db` or `amqp` (RabbitMQ)
- **Max Images Per Product**: Maximum images (default: 10)
- **Allowed Extensions**: jpg,jpeg,png,gif,webp
- **Download Timeout**: Timeout in seconds (default: 30)

### Category Settings
- **Auto-create Categories**: Automatically create missing categories
- **Category Active Status**: Default active status for new categories
- **Include in Menu**: Include categories in navigation menu

### Validation Settings
- **Validate SKU Format**: Enforce alphanumeric SKU format
- **Validate Prices**: Enable price range validation
- **Validate URLs**: Validate image URL formats
- **Required Fields**: Comma-separated required fields
- **Min/Max Price**: Price range limits

### Advanced Settings
- **Debug Mode**: Enable detailed debug logging
- **Memory Limit**: PHP memory limit (e.g., 512M, 1G)
- **Clear Cache After Import**: Auto-clear cache
- **Reindex After Import**: Auto-reindex
- **Log Level**: DEBUG, INFO, WARNING, ERROR, CRITICAL

---

## CSV Format

The module expects Ikas-formatted CSV files with the following structure:

### Expected Headers (25 columns)

```
Ürün Grup ID, Varyant ID, İsim, Açıklama, Satış Fiyatı, İndirimli Fiyatı,
Alış Fiyatı, Barkod Listesi, SKU, Silindi mi?, Marka, Kategoriler, Etiketler,
Resim URL, Metadata Açıklama, Slug, Stok:Ana Depo, Desi, HS Kod,
Google Ürün Kategorisi, Stoğu Tükenince Satmaya Devam Et,
Satış Kanalı:safranntuhafiye, Satış Kanalı:GELİVER,
Sepet Başına Maksimum Alma Adeti:safranntuhafiye, Varyant Aktiflik
```

### Key Fields Mapping

- **SKU**: Product SKU (required, unique identifier)
- **İsim**: Product name (required)
- **Açıklama**: HTML description (supports dynamic attributes)
- **Satış Fiyatı**: Regular price (required)
- **İndirimli Fiyatı**: Special price (optional)
- **Stok:Ana Depo**: Stock quantity
- **Kategoriler**: Categories (semicolon-separated, e.g., "İplikler/Cotton Gold")
- **Resim URL**: Image URLs (semicolon-separated)
- **Slug**: URL key
- **Varyant Aktiflik**: Product status ("Aktif" = enabled)
- **Desi**: Weight

### Sample CSV Row

```csv
SKU,İsim,Açıklama,Satış Fiyatı,İndirimli Fiyatı,Stok:Ana Depo,Kategoriler,Resim URL,Varyant Aktiflik
PROD001,Cotton Gold,"<ul><li>Kullanım Alanı : Hırka, yelek</li><li>Karışım : % 45 Akrilik - % 55 Pamuk</li></ul>",25.50,22.00,100,İplikler/Cotton Gold,https://example.com/image1.jpg;https://example.com/image2.jpg,Aktif
```

---

## Usage

### CLI Import

```bash
# Basic import
php bin/magento ikas:import:run /path/to/file.csv

# Validate only (no import)
php bin/magento ikas:import:run /path/to/file.csv --validate-only
```

### Queue Consumers

For async image processing, start the queue consumer:

```bash
# Database queue
php bin/magento queue:consumers:start ikas.product.images.consumer

# Continuous mode
php bin/magento queue:consumers:start ikas.product.images.consumer --max-messages=0

# With supervisor (recommended for production)
```

Supervisor configuration example:
```ini
[program:ikas_image_consumer]
command=php /var/www/magento/bin/magento queue:consumers:start ikas.product.images.consumer --max-messages=100
autostart=true
autorestart=true
user=www-data
numprocs=1
```

### Admin Interface

1. Navigate to: **Ikas Import > Import Products**
2. Upload CSV file
3. Configure import options
4. Click "Import"
5. Monitor progress and review results

---

## Architecture

### Design Patterns

- **Strategy Pattern**: Different parsing/processing strategies
- **Factory Pattern**: Object creation via factories
- **Repository Pattern**: Data access abstraction
- **Chain of Responsibility**: Validation chain
- **Observer Pattern**: Event dispatching for extensibility
- **Dependency Injection**: All dependencies via constructor

### Key Components

#### API Interfaces
- `ImporterInterface`: Main import orchestrator
- `ParserInterface`: CSV parsing with generators
- `ProcessorInterface`: Data processing
- `ValidatorInterface`: Data validation
- Data interfaces: `ImportResultInterface`, `ProductDataInterface`, etc.

#### Models
- **Parser/CsvParser**: Memory-efficient CSV parsing with UTF-8 support
- **Parser/AttributeParser**: Extract attributes from HTML descriptions
- **Processor/StockProcessor**: Stock management via `StockRegistryInterface`
- **Processor/CategoryProcessor**: Category hierarchy creation with caching
- **Validator/ProductDataValidator**: Comprehensive data validation
- **Cache/CategoryCache**: In-memory category caching
- **Cache/AttributeCache**: In-memory attribute caching

#### Configuration
- **Model/Config/ImportConfig**: Central configuration accessor
- System configuration in `etc/adminhtml/system.xml`
- Default values in `etc/config.xml`

---

## Dynamic Attribute Parsing

### How It Works

The module automatically extracts product attributes from HTML descriptions in key-value format.

### Supported Format

```html
<ul>
  <li>Kullanım Alanı : Hırka, yelek, süveter</li>
  <li>Karışım : % 45 Akrilik - % 55 Pamuk</li>
  <li>Metraj : 100 gr - 330 m</li>
</ul>
```

### Attribute Code Generation

Turkish characters are converted to ASCII equivalents:
- `ı → i`, `İ → i`
- `ş → s`, `Ş → s`
- `ğ → g`, `Ğ → g`
- `ç → c`, `Ç → c`
- `ö → o`, `Ö → o`
- `ü → u`, `Ü → u`

Example transformations:
- "Kullanım Alanı" → `ikas_kullanim_alani`
- "Karışım" → `ikas_karisim`
- "Metraj" → `ikas_metraj`

### Blacklisting

Configure unwanted keys in:
**Stores > Configuration > Ikas Import > Attribute Settings > Blacklisted Attribute Keys**

Example: `uyarı,not,artikel no,açıklama`

---

## Stock Management

### Important: Uses StockRegistryInterface

This module **properly** uses `StockRegistryInterface` instead of the deprecated `setStockData()` method.

```php
$stockItem = $stockRegistry->getStockItemBySku($sku);
$stockItem->setQty($qty);
$stockItem->setIsInStock($qty > 0);
$stockItem->setManageStock(true);
$stockRegistry->updateStockItemBySku($sku, $stockItem);
```

### MSI Support

The module is designed to support Multi Source Inventory (MSI) in future versions via configurable source codes.

---

## Category Management

### Hierarchy Creation

Categories are created automatically from paths:

Example: `İplikler/Cotton Gold/Renk Kartelası`

Creates:
1. İplikler (root)
2. Cotton Gold (child of İplikler)
3. Renk Kartelası (child of Cotton Gold)

### Caching

Category lookups are cached in-memory during import to avoid redundant database queries.

### Turkish URL Keys

URL keys are generated with proper Turkish character transliteration:
- "İplikler" → `iplikler`
- "Çocuk Ürünleri" → `cocuk-urunleri`

---

## Image Processing

### Async Queue-Based Processing

Images are processed asynchronously to avoid blocking the main import:

1. Product is created/updated
2. Image URLs are published to message queue
3. Consumer downloads and assigns images
4. First image becomes main image (image, small_image, thumbnail)
5. Other images added to gallery

### Supported Formats

- JPEG (.jpg, .jpeg)
- PNG (.png)
- GIF (.gif)
- WebP (.webp)

### Error Handling

Failed image downloads are logged but don't fail the entire import.

---

## Validation

### Multi-Layer Validation

1. **CSV Structure**: Headers, encoding, column count
2. **Data Format**: SKU, prices, URLs, stock quantities
3. **Business Rules**: Price ranges, required fields, unique constraints

### Validation Result

Validation results include:
- Error count and details
- Warning count and details
- Context information (row number, SKU, field name)

---

## Logging

### Log File

All import operations are logged to: `var/log/ikas_import.log`

### Log Levels

- **DEBUG**: Detailed processing info (when debug mode enabled)
- **INFO**: Normal operations (products created, stock updated)
- **WARNING**: Non-critical issues (missing optional fields)
- **ERROR**: Import failures (validation errors, exceptions)
- **CRITICAL**: System errors

### Log Format

```
[2025-11-16 10:30:45] IkasImport.INFO: Product created {"sku":"PROD001","name":"Cotton Gold","row":123}
```

---

## Extensibility

### Events

The module dispatches events for customization:

```php
// Before product save
ikas_import_product_save_before

// After product save
ikas_import_product_save_after

// On import complete
ikas_import_complete

// On error
ikas_import_error
```

### Plugins

Use plugins to modify behavior:

```xml
<type name="Mucan54\IkasImport\Model\Parser\CsvParser">
    <plugin name="custom_csv_parser" type="Vendor\Module\Plugin\CsvParserPlugin"/>
</type>
```

### Preferences

Override implementations:

```xml
<preference for="Mucan54\IkasImport\Api\ParserInterface"
            type="Vendor\Module\Model\CustomParser"/>
```

---

## Performance

### Memory Optimization

- **Generators**: CSV parsing uses generators to process one row at a time
- **Batch Processing**: Configurable batch sizes
- **Cache Clearing**: Periodic entity cache clearing
- **Object Release**: Proper memory management

### Database Optimization

- **Category Caching**: Avoids duplicate queries
- **Attribute Caching**: Reuses attribute instances
- **Indexer Management**: Indexers set to schedule mode during import

### Recommended Settings

For large imports (10,000+ products):
- Batch Size: 50-100
- Memory Limit: 1G
- Max Execution Time: 7200
- Enable Async Image Processing
- Use queue consumers in supervisor

---

## Troubleshooting

### Common Issues

**1. Memory Exhausted**
- Increase memory_limit in configuration
- Reduce batch size
- Enable async processing

**2. Timeout**
- Increase max_execution_time
- Use CLI instead of admin
- Process in smaller batches

**3. Stock Not Updating**
- Check SKU matches existing product
- Verify stock management is enabled
- Check `var/log/ikas_import.log`

**4. Images Not Processing**
- Ensure queue consumer is running
- Check image URL accessibility
- Verify allowed extensions
- Check `var/log/ikas_import.log`

**5. Turkish Characters Garbled**
- Ensure CSV is UTF-8 encoded
- Check for BOM (module handles this)
- Verify database charset is UTF-8

**6. Categories Not Created**
- Enable "Auto-create Categories" in config
- Check category path format (use `/` separator)
- Check ACL permissions

**7. Attributes Not Created**
- Enable "Dynamic Attribute Creation"
- Check attribute blacklist
- Verify HTML format in description

### Debug Mode

Enable debug mode for detailed logging:
**Stores > Configuration > Ikas Import > Advanced > Debug Mode**

### Check Logs

```bash
tail -f var/log/ikas_import.log
tail -f var/log/system.log
tail -f var/log/exception.log
```

---

## Development

### Running Tests

```bash
# Unit tests
php bin/magento dev:tests:run unit Mucan54_IkasImport

# Integration tests
php bin/magento dev:tests:run integration Mucan54_IkasImport
```

### Code Standards

```bash
# Check coding standards
vendor/bin/phpcs --standard=Magento2 app/code/Mucan54/IkasImport

# Fix coding standards
vendor/bin/phpcbf --standard=Magento2 app/code/Mucan54/IkasImport
```

### Adding Custom Processors

```php
namespace Vendor\Module\Model\Processor;

use Mucan54\IkasImport\Api\ProcessorInterface;

class CustomProcessor implements ProcessorInterface
{
    public function process(array $data, array $context = []): bool
    {
        // Your logic here
        return true;
    }

    public function supports(string $dataType): bool
    {
        return $dataType === 'custom';
    }

    public function validate(array $data): bool
    {
        return true;
    }
}
```

Register in `di.xml`:
```xml
<type name="Mucan54\IkasImport\Model\ProcessorPool">
    <arguments>
        <argument name="processors" xsi:type="array">
            <item name="custom" xsi:type="object">Vendor\Module\Model\Processor\CustomProcessor</item>
        </argument>
    </arguments>
</type>
```

---

## Security

### File Upload Security

- File extension validation (.csv only)
- MIME type checking
- File size limits
- Secure storage location
- Cleanup after processing

### Image Download Security

- URL scheme validation (http/https only)
- MIME type validation after download
- File size limits
- Timeout limits
- Malicious content scanning (recommended)

### Access Control

- ACL resources for admin access
- Role-based permissions
- Activity logging
- Rate limiting (configurable)

---

## Requirements

- Magento 2.4.x
- PHP 7.4 / 8.0 / 8.1
- MySQL 5.7+ / MariaDB 10.2+
- Composer
- Optional: RabbitMQ (for AMQP queue)

---

## License

Proprietary - Copyright © Mucan54. All rights reserved.

---

## Support

For issues and feature requests, please refer to the project documentation or contact support.

---

## Changelog

### Version 1.0.0 (2025-11-16)
- Initial release
- CSV import with generator support
- Dynamic attribute creation from HTML
- Stock management via StockRegistryInterface
- Category hierarchy creation
- Async image processing
- Comprehensive validation
- CLI commands
- Admin interface
- Detailed logging
- Turkish character support
- Extensive configuration options

---

## Credits

Developed by Mucan54 with ❤️ for the Magento community.
