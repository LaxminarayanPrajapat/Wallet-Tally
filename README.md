# Wallet Tally

A complete web application for personal finance management built with PHP, HTML, CSS, JavaScript, and Firebase.

## Features

- User authentication with OTP verification
- Transaction management (income/expense tracking)
- Custom categories
- Dashboard with financial summaries
- Admin panel for user management
- Email notifications
- Profile management with picture uploads
- Feedback and testimonials system

## Tech Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: Firebase Firestore (migrated from MySQL)
- **Storage**: Firebase Storage
- **Authentication**: Custom PHP session management

## Project Structure

```
├── admin/              # Admin panel pages and functionality
├── api/                # API endpoints for AJAX requests
├── assets/             # CSS, JavaScript, and images
├── config/             # Configuration files
├── includes/           # Reusable PHP components
├── uploads/            # User-uploaded files
├── cron/               # Scheduled tasks
└── .kiro/              # Kiro AI specs and documentation
```

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- Composer
- Firebase project with Firestore enabled
- Web server (Apache/Nginx)

### Installation

1. Clone the repository:
```bash
git clone <your-repo-url>
cd wallet-tally
```

2. Install dependencies:
```bash
composer install
```

3. Configure Firebase:
   - Create a Firebase project at https://console.firebase.google.com
   - Download service account credentials JSON
   - Place credentials in `config/firebase-credentials.json`
   - Copy `config/firebase_config.example.php` to `config/firebase_config.php`
   - Update with your Firebase project details

4. Configure email settings:
   - Copy `config/email_config.example.php` to `config/email_config.php`
   - Update with your SMTP credentials

5. Set up file permissions:
```bash
chmod 755 uploads/profile_pictures
```

6. Configure your web server to point to the project root

### Firebase Migration

If migrating from MySQL to Firebase, see the migration documentation:
- Requirements: `.kiro/specs/firebase-migration/requirements.md`
- Design: `.kiro/specs/firebase-migration/design.md`
- Tasks: `.kiro/specs/firebase-migration/tasks.md`

## Development

### Running Tests

```bash
# Run all tests
composer test

# Run unit tests only
composer test:unit

# Run property tests only
composer test:property

# Run with coverage
composer test:coverage
```

### Code Style

Follow PSR-12 coding standards. Run the linter:
```bash
composer lint
```

## Deployment

1. Set up production Firebase project
2. Update configuration files with production credentials
3. Deploy to your hosting provider
4. Set up cron jobs for cleanup operations:
```bash
# Add to crontab
0 2 * * * php /path/to/project/cron/cleanup_expired_records.php
```

## Security

- Never commit configuration files with credentials
- Keep Firebase credentials secure
- Use environment variables for sensitive data
- Regularly update dependencies

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests
5. Submit a pull request

## License

[Your License Here]

## Support

For issues and questions, please open a GitHub issue.
