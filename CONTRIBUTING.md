# Contributing to PHP URL Shortener

Thank you for your interest in contributing! This document provides guidelines for contributing to this project.

## How to Contribute

### Reporting Bugs
- Check if the bug has already been reported in Issues
- If not, create a new issue with:
  - Clear title and description
  - Steps to reproduce
  - Expected vs actual behavior
  - PHP version and database type
  - Screenshots if applicable

### Suggesting Features
- Check if the feature has been suggested
- Create an issue describing:
  - The problem it solves
  - Proposed solution
  - Alternative solutions considered

### Pull Requests
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Make your changes
4. Test thoroughly with all database types (SQLite, MySQL, PostgreSQL)
5. Commit with clear messages (`git commit -m 'Add amazing feature'`)
6. Push to your branch (`git push origin feature/AmazingFeature`)
7. Open a Pull Request

## Development Guidelines

### Code Style
- Follow PSR-12 coding standards where applicable
- Use meaningful variable names
- Add comments for complex logic
- Keep functions focused and concise

### Testing
Before submitting a PR, test:
- ✅ SQLite database
- ✅ MySQL database (if possible)
- ✅ PostgreSQL database (if possible)
- ✅ Both English and Indonesian languages
- ✅ All user management features
- ✅ Statistics and analytics
- ✅ QR code generation

### Database Changes
If modifying database schema:
- Ensure compatibility with all three database types
- Add migration logic for existing installations
- Update DOCUMENTATION.md with schema changes

### Translation
When adding new strings:
- Add to both 'en' and 'id' arrays in `getTranslations()`
- Use descriptive keys (e.g., `'btn_save'` not `'bs'`)
- Keep translations concise

## Project Structure

```
/
├── index.php              # Main application file
├── .htaccess             # Apache rewrite rules
├── nginx.conf.example    # Nginx configuration example
├── README.md             # English README
├── README.id.md          # Indonesian README
├── DOCUMENTATION.md      # English technical docs
├── DOCUMENTATION.id.md   # Indonesian technical docs
├── LICENSE               # MIT License
├── .gitignore           # Git ignore rules
├── logo.png             # Application logo
└── favicon.ico          # Favicon
```

## Questions?

Feel free to open an issue for any questions or clarifications.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
