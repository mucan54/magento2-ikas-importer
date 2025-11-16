# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-11-16

### Added
- Initial release of Magento 2 Ikas Product Import Module
- CSV import with memory-efficient generator support for large files (10,000+ products)
- Full UTF-8 encoding support with automatic encoding detection
- Turkish character support with proper transliteration
- Dynamic attribute creation from HTML descriptions
- Stock management using StockRegistryInterface (not deprecated setStockData)
- Automatic category hierarchy creation with caching
- Asynchronous image processing via message queue (Database/RabbitMQ)
- Comprehensive validation system (CSV structure, data format, business rules)
- Extensive configuration system with 40+ options in 7 sections
- CLI command for imports: `php bin/magento ikas:import:run`
- Admin interface with menu and ACL
- Custom logger with dedicated log file (`var/log/ikas_import.log`)
- In-memory caching for categories and attributes
- Multi-layer validation with detailed error reporting
- Event dispatching for extensibility
- Internationalization support (English and Turkish)
- Comprehensive documentation (README in English and Turkish)
- Sample CSV file with realistic Turkish product data
- 8 API interfaces following SOLID principles
- 15 model implementations
- 2 processors (Stock, Category)
- 2 validators and 2 parsers
- Queue configuration for async operations
- Admin controller and routes
- Translation files (en_US, tr_TR)

### Features
- **Memory Optimization**: Uses PHP generators to handle large CSV files without timeout
- **Turkish Language**: Full support for Turkish characters in product names, categories, attributes
- **Attribute Parser**: Extracts key-value pairs from HTML descriptions
- **Category Management**: Creates nested category structures automatically
- **Image Processing**: Queue-based async image downloading and validation
- **Security**: Input validation, ACL, secure file handling, URL validation
- **Performance**: Batch processing, in-memory caching, indexer management
- **Extensibility**: Well-defined interfaces, events, plugins support
- **Logging**: Context-aware logging with configurable log levels

### Technical
- Follows Magento 2 coding standards
- Implements SOLID principles
- Uses design patterns: Strategy, Factory, Repository, Chain of Responsibility, Observer
- Full dependency injection via di.xml
- Compatible with PHP 7.4, 8.0, 8.1, 8.2
- Compatible with Magento 2.4.x
- PSR-4 autoloading
- Comprehensive PHPDoc documentation

### Documentation
- Detailed README with installation guide
- Configuration guide with all options explained
- CSV format specification
- Usage examples (CLI and Admin)
- Troubleshooting guide
- Development guide for extending the module
- Turkish translation of all documentation

[1.0.0]: https://github.com/mucan54/magento2-ikas-importer/releases/tag/v1.0.0
