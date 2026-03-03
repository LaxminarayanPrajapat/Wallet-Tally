# Contributing to Wallet Tally

Thank you for considering contributing to Wallet Tally! This document provides guidelines for contributing to the project.

## Code of Conduct

- Be respectful and inclusive
- Focus on constructive feedback
- Help others learn and grow
- Maintain professional communication

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/YOUR_USERNAME/wallet-tally.git`
3. Add upstream remote: `git remote add upstream https://github.com/ORIGINAL_OWNER/wallet-tally.git`
4. Create a branch: `git checkout -b feature/your-feature-name`

## Development Workflow

### 1. Before You Start

- Check existing issues and pull requests to avoid duplicates
- Discuss major changes by opening an issue first
- Review the spec documents in `.kiro/specs/firebase-migration/`

### 2. Making Changes

- Follow PSR-12 coding standards
- Write clear, descriptive commit messages
- Add tests for new functionality
- Update documentation as needed
- Keep commits focused and atomic

### 3. Commit Message Format

Use conventional commits format:

```
type(scope): subject

body (optional)

footer (optional)
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(auth): add OTP verification for login
fix(transactions): correct decimal precision for amounts
docs(readme): update installation instructions
test(migration): add property test for data completeness
```

### 4. Code Style

Run the linter before committing:
```bash
composer lint
composer lint:fix  # Auto-fix issues
```

**PHP Standards:**
- Follow PSR-12
- Use type hints
- Document public methods with PHPDoc
- Keep functions small and focused
- Use meaningful variable names

### 5. Testing

All new code should include tests:

```bash
# Run all tests
composer test

# Run specific test suite
composer test:unit
composer test:property
composer test:integration

# Check coverage
composer test:coverage
```

**Test Requirements:**
- Unit tests for new functions/methods
- Property tests for universal correctness properties
- Integration tests for API endpoints
- Minimum 80% code coverage for new code

### 6. Pull Request Process

1. **Update your branch:**
   ```bash
   git fetch upstream
   git rebase upstream/develop
   ```

2. **Push to your fork:**
   ```bash
   git push origin feature/your-feature-name
   ```

3. **Create Pull Request:**
   - Go to GitHub and create a PR from your branch to `develop`
   - Fill out the PR template completely
   - Link related issues
   - Add screenshots for UI changes
   - Request review from maintainers

4. **PR Requirements:**
   - ✅ All tests pass
   - ✅ Code follows style guidelines
   - ✅ Documentation updated
   - ✅ No merge conflicts
   - ✅ Reviewed and approved

5. **After Review:**
   - Address feedback promptly
   - Push additional commits to the same branch
   - Request re-review when ready

## Project Structure

```
├── admin/              # Admin panel
├── api/                # API endpoints
├── assets/             # Frontend assets
├── config/             # Configuration files
├── includes/           # Shared PHP components
├── src/                # New Firebase abstraction layer
├── tests/              # Test suites
│   ├── Unit/          # Unit tests
│   ├── Property/      # Property-based tests
│   ├── Integration/   # Integration tests
│   └── Performance/   # Performance tests
├── uploads/            # User uploads
└── .kiro/             # Kiro AI specs
```

## Firebase Migration Tasks

The project is currently migrating from MySQL to Firebase. See `.kiro/specs/firebase-migration/tasks.md` for the implementation plan.

**Priority Areas:**
1. Database abstraction layer
2. Query translator
3. Migration service
4. Testing infrastructure

## Reporting Bugs

When reporting bugs, include:

1. **Description**: Clear description of the issue
2. **Steps to Reproduce**: Detailed steps to reproduce the bug
3. **Expected Behavior**: What should happen
4. **Actual Behavior**: What actually happens
5. **Environment**: PHP version, OS, browser (if applicable)
6. **Screenshots**: If applicable
7. **Error Messages**: Full error messages and stack traces

**Bug Report Template:**
```markdown
## Bug Description
[Clear description]

## Steps to Reproduce
1. Go to '...'
2. Click on '...'
3. See error

## Expected Behavior
[What should happen]

## Actual Behavior
[What actually happens]

## Environment
- PHP Version: 8.1
- OS: Ubuntu 22.04
- Browser: Chrome 120

## Screenshots
[If applicable]

## Error Messages
[Full error messages]
```

## Suggesting Features

When suggesting features:

1. **Check existing issues** to avoid duplicates
2. **Describe the problem** the feature would solve
3. **Propose a solution** with implementation details
4. **Consider alternatives** and trade-offs
5. **Discuss impact** on existing functionality

## Security Issues

**DO NOT** open public issues for security vulnerabilities.

Instead:
1. Email security concerns to [security@example.com]
2. Include detailed description and reproduction steps
3. Allow time for fix before public disclosure

## Documentation

Help improve documentation:

- Fix typos and clarify confusing sections
- Add examples and use cases
- Update outdated information
- Translate documentation (if applicable)

## Questions?

- Open a discussion on GitHub Discussions
- Check existing documentation
- Review closed issues for similar questions

## License

By contributing, you agree that your contributions will be licensed under the same license as the project.

## Recognition

Contributors will be recognized in:
- CONTRIBUTORS.md file
- Release notes
- Project documentation

Thank you for contributing! 🎉
