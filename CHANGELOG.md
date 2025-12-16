# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2025-12-16

### Added
- **Docker Support**: Included `Dockerfile` and `docker-compose.yml` for containerized deployment
- **Short Code Flexibility**: Added support for hyphens (`-`) and underscores (`_`) in short codes
- **Community Health Files**: Added `LICENSE`, `CONTRIBUTING.md`, and `SECURITY.md`
- **Default Configuration**: Reset `index.php` to unconfigured state for cleaner fresh installs

### Changed
- **Documentation**: Comprehensive updates to `README.md` and `DOCUMENTATION.md` including Docker guides
- **Project Structure**: Cleaned up temporary files and reorganized project root

## [2.0.0] - 2025-12-14

### Added
- **PostgreSQL Support**: Full support for PostgreSQL database alongside MySQL and SQLite
- **Database Optimization**: Automatic index creation for optimal query performance
  - Indexes on `visits` table (url_id, created_at, ip_address, composite url_date)
  - Indexes on `urls` table (user_id, short_code)
- **Daily Statistics Summary**: Optional `daily_stats` table for fast statistics queries
- **Data Retention Management**: Configurable automatic cleanup of old visit data
- **Internationalization (i18n)**: Full bilingual support (English/Indonesian)
  - Translation system with `__()` helper function
  - All UI strings translatable
  - Language switching via configuration
- **Bilingual Documentation**: Complete documentation in both English and Indonesian
  - README.md / README.id.md
  - DOCUMENTATION.md / DOCUMENTATION.id.md
- **Configuration Options**: New optimization settings
  - `$enableIndexes` - Enable/disable automatic indexing
  - `$dataRetentionDays` - Data retention period
  - `$enableDailySummary` - Enable daily stats summary
  - `$autoArchiveOldData` - Automatic data cleanup
  - `$dbPort` - Database port configuration

### Changed
- **Setup Wizard**: Updated to support PostgreSQL and port configuration
- **Database Schema**: Enhanced with performance indexes
- **Documentation**: Complete rewrite with optimization guides

### Performance
- Query speed improved 10-100x with automatic indexing
- Statistics page 100-1000x faster with daily summary table
- Optimized for high-volume sites (>1M visits)

## [1.0.0] - 2024-12-XX

### Added
- Initial release
- Single-file PHP application
- MySQL and SQLite support
- User management system
- Admin promotion/demotion
- Comprehensive analytics with Chart.js
- QR code generation
- Geographic tracking (Country/City)
- Device, OS, and browser detection
- CSRF protection
- Secure session handling
- Bcrypt password hashing
- Responsive Bulma CSS interface
- Dynamic BASE_PATH handling
- Self-configuring setup wizard

[2.0.0]: https://github.com/yourusername/php-url-shortener/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/yourusername/php-url-shortener/releases/tag/v1.0.0
