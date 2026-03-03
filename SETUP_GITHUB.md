# GitHub Repository Setup Guide

This guide will help you set up your GitHub repository for the Wallet Tally project.

## Step 1: Initialize Git Repository

```bash
# Initialize git in your project directory
git init

# Add all files to staging
git add .

# Create initial commit
git commit -m "Initial commit: Wallet Tally application"
```

## Step 2: Create GitHub Repository

1. Go to https://github.com/new
2. Enter repository name: `wallet-tally` (or your preferred name)
3. Choose visibility: Private or Public
4. **DO NOT** initialize with README, .gitignore, or license (we already have these)
5. Click "Create repository"

## Step 3: Connect Local Repository to GitHub

```bash
# Add GitHub remote (replace with your repository URL)
git remote add origin https://github.com/YOUR_USERNAME/wallet-tally.git

# Verify remote was added
git remote -v

# Push to GitHub
git branch -M main
git push -u origin main
```

## Step 4: Set Up Branch Protection (Recommended)

1. Go to your repository on GitHub
2. Click "Settings" → "Branches"
3. Click "Add rule" under "Branch protection rules"
4. Branch name pattern: `main`
5. Enable:
   - ✅ Require a pull request before merging
   - ✅ Require status checks to pass before merging
   - ✅ Require branches to be up to date before merging
6. Click "Create"

## Step 5: Configure Secrets for CI/CD

If you plan to use automated testing with Firebase:

1. Go to "Settings" → "Secrets and variables" → "Actions"
2. Click "New repository secret"
3. Add the following secrets:
   - `FIREBASE_PROJECT_ID`: Your Firebase project ID
   - `FIREBASE_CREDENTIALS`: Content of your Firebase service account JSON

## Step 6: Create Development Branch

```bash
# Create and switch to develop branch
git checkout -b develop

# Push develop branch to GitHub
git push -u origin develop
```

## Step 7: Set Up .gitignore for Sensitive Files

The `.gitignore` file is already configured to exclude:
- Database credentials (`config/db.php`)
- Email configuration (`config/email_config.php`)
- Firebase credentials (`config/firebase_config.php`, `firebase-credentials.json`)
- Log files
- Uploaded files
- Vendor directory

**IMPORTANT**: Before committing, ensure you:
1. Copy `config/firebase_config.example.php` to `config/firebase_config.php`
2. Copy `config/email_config.example.php` to `config/email_config.php`
3. Add your actual credentials to these files
4. Verify they are NOT being tracked by git: `git status`

## Step 8: Install Dependencies

```bash
# Install PHP dependencies
composer install

# This will install:
# - PHPMailer
# - Firebase PHP SDK
# - PHPUnit (dev)
# - Eris for property-based testing (dev)
# - PHP CodeSniffer (dev)
```

## Step 9: Verify Setup

```bash
# Check git status
git status

# Verify .gitignore is working
git check-ignore config/firebase_config.php
# Should output: config/firebase_config.php

# Run tests (will fail until implemented)
composer test

# Run linter
composer lint
```

## Recommended Workflow

### For New Features:
```bash
# Create feature branch from develop
git checkout develop
git pull origin develop
git checkout -b feature/your-feature-name

# Make changes and commit
git add .
git commit -m "Add: your feature description"

# Push to GitHub
git push -u origin feature/your-feature-name

# Create Pull Request on GitHub to merge into develop
```

### For Bug Fixes:
```bash
# Create bugfix branch from develop
git checkout develop
git pull origin develop
git checkout -b bugfix/issue-description

# Make changes and commit
git add .
git commit -m "Fix: issue description"

# Push to GitHub
git push -u origin bugfix/issue-description

# Create Pull Request on GitHub to merge into develop
```

### For Releases:
```bash
# Create release branch from develop
git checkout develop
git pull origin develop
git checkout -b release/v1.0.0

# Final testing and version bumps
# Create Pull Request to merge into main
# After merge, tag the release
git checkout main
git pull origin main
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

## GitHub Actions CI/CD

The repository includes a GitHub Actions workflow (`.github/workflows/ci.yml`) that will:
- Run on every push and pull request
- Test against PHP 7.4, 8.0, and 8.1
- Run linter (PHP CodeSniffer)
- Run unit tests
- Run property-based tests
- Generate code coverage reports
- Upload coverage to Codecov (optional)

## Troubleshooting

### Issue: "Permission denied (publickey)"
**Solution**: Set up SSH keys or use HTTPS with personal access token
```bash
# Use HTTPS instead
git remote set-url origin https://github.com/YOUR_USERNAME/wallet-tally.git
```

### Issue: "Updates were rejected because the remote contains work"
**Solution**: Pull changes first
```bash
git pull origin main --rebase
git push origin main
```

### Issue: Sensitive files accidentally committed
**Solution**: Remove from history
```bash
# Remove file from git but keep locally
git rm --cached config/firebase_config.php

# Commit the removal
git commit -m "Remove sensitive config file"

# Push changes
git push origin main
```

## Next Steps

After setting up GitHub:
1. Review the Firebase migration spec in `.kiro/specs/firebase-migration/`
2. Start implementing tasks from `tasks.md`
3. Create feature branches for each major task
4. Use pull requests for code review
5. Keep `develop` branch stable
6. Merge to `main` only for releases

## Resources

- [GitHub Docs](https://docs.github.com)
- [Git Branching Strategy](https://nvie.com/posts/a-successful-git-branching-model/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [GitHub Actions](https://docs.github.com/en/actions)
