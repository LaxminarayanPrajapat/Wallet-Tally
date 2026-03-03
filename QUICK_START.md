# Quick Start Guide

## Initial Setup

```bash
# 1. Initialize Git
git init
git add .
git commit -m "Initial commit: Wallet Tally application"

# 2. Create GitHub repository (via web interface)
# Then connect it:
git remote add origin https://github.com/YOUR_USERNAME/wallet-tally.git
git branch -M main
git push -u origin main

# 3. Install dependencies
composer install

# 4. Set up configuration files
cp config/firebase_config.example.php config/firebase_config.php
cp config/email_config.example.php config/email_config.php
# Edit these files with your actual credentials
```

## Daily Development

```bash
# Start working on a new feature
git checkout develop
git pull origin develop
git checkout -b feature/your-feature-name

# Make changes, then:
git add .
git commit -m "feat(scope): description"
git push origin feature/your-feature-name

# Create Pull Request on GitHub
```

## Testing

```bash
# Run all tests
composer test

# Run specific test suites
composer test:unit          # Unit tests
composer test:property      # Property-based tests
composer test:integration   # Integration tests

# Check code coverage
composer test:coverage

# Run linter
composer lint
composer lint:fix           # Auto-fix issues
```

## Firebase Migration Tasks

```bash
# View the implementation plan
cat .kiro/specs/firebase-migration/tasks.md

# View requirements
cat .kiro/specs/firebase-migration/requirements.md

# View design
cat .kiro/specs/firebase-migration/design.md
```

## Common Git Commands

```bash
# Check status
git status

# View changes
git diff

# View commit history
git log --oneline

# Update your branch with latest changes
git fetch origin
git rebase origin/develop

# Undo last commit (keep changes)
git reset --soft HEAD~1

# Discard local changes
git checkout -- filename.php
```

## Useful Composer Commands

```bash
# Update dependencies
composer update

# Add new dependency
composer require package/name

# Add dev dependency
composer require --dev package/name

# Show installed packages
composer show

# Validate composer.json
composer validate
```

## Firebase Emulator (for testing)

```bash
# Install Firebase CLI
npm install -g firebase-tools

# Login to Firebase
firebase login

# Initialize Firebase in project
firebase init

# Start emulators
firebase emulators:start

# Run tests against emulator
APP_ENV=testing composer test
```

## Troubleshooting

```bash
# Clear Composer cache
composer clear-cache

# Reinstall dependencies
rm -rf vendor
composer install

# Check PHP version
php -v

# Check installed extensions
php -m

# Verify .gitignore is working
git check-ignore config/firebase_config.php
```

## Project Structure

```
wallet-tally/
├── .github/              # GitHub workflows and templates
├── .kiro/                # Kiro AI specs
│   └── specs/
│       └── firebase-migration/
├── admin/                # Admin panel
├── api/                  # API endpoints
├── assets/               # CSS, JS, images
├── config/               # Configuration files
├── includes/             # Shared PHP components
├── src/                  # New Firebase abstraction layer
├── tests/                # Test suites
├── uploads/              # User uploads
├── vendor/               # Composer dependencies
├── .gitignore
├── composer.json
├── phpunit.xml
└── README.md
```

## Next Steps

1. ✅ Set up GitHub repository (see SETUP_GITHUB.md)
2. ✅ Install dependencies
3. ✅ Configure Firebase and email settings
4. 📋 Review Firebase migration spec
5. 🚀 Start implementing tasks from tasks.md
6. ✅ Write tests for new code
7. 🔄 Create pull requests for review
8. 🎉 Deploy to production

## Resources

- [Full Setup Guide](SETUP_GITHUB.md)
- [Contributing Guidelines](CONTRIBUTING.md)
- [Firebase Migration Spec](.kiro/specs/firebase-migration/)
- [GitHub Docs](https://docs.github.com)
- [Composer Docs](https://getcomposer.org/doc/)
- [PHPUnit Docs](https://phpunit.de/documentation.html)
