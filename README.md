# TYPO3 Extension: Mailjet

[![TYPO3](https://img.shields.io/badge/TYPO3-11%20|%2012%20|%2013%20|%2014-orange.svg)](https://typo3.org/)
[![PHP](https://img.shields.io/badge/PHP-8.3%20|%208.4-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-blue.svg)](LICENSE)

This TYPO3 extension configures the system to send emails through [Mailjet](https://www.mailjet.com/) SMTP service and provides comprehensive email tracking capabilities.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [How It Works](#how-it-works)
- [Email Logging](#email-logging)
- [Database Schema](#database-schema)
- [Usage Examples](#usage-examples)
- [Troubleshooting](#troubleshooting)
- [Support](#support)
- [License](#license)
- [Credits](#credits)

## Features

- **Mailjet Integration**: Configure TYPO3 to use Mailjet's SMTP servers for email delivery
- **Master Enable Switch**: Quickly enable/disable Mailjet without removing credentials
- **Email Logging**: Automatically track all email attempts with delivery status (sent/failed)
- **Error Tracking**: Captures exception messages when email delivery fails
- **Privacy-Focused**: Logs only metadata (timestamp, subject, sender, status) - no recipient addresses or email content
- **Event-Based Architecture**: Uses TYPO3's mail events for reliable email tracking
- **Flexible**: Works with any TYPO3 extension that uses the standard mail API

## Requirements

- TYPO3 11.0 - 14.9
- PHP 8.3 or 8.4
- Active Mailjet account with API credentials

## Installation

### Via Composer (recommended)

```bash
composer require worlddirect/mailjet
```

## Configuration

### 1. Mailjet API Credentials

First, obtain your Mailjet API credentials:
1. Log in to your [Mailjet account](https://app.mailjet.com/)
2. Navigate to Account Settings → REST API → API Key Management
3. Copy your **API Key** (username) and **Secret Key** (password)

### 2. Extension Configuration

Configure the extension by using **"Environment Variables"** in the file `settings.php`. So no secret values are being inserted into the Git repository.

Set the following values:

| Setting            | Description                                                | Example                 |
| ------------------ | ---------------------------------------------------------- | ----------------------- |
| **Enable Mailjet** | Master switch to enable/disable Mailjet (default: enabled) | 1                       |
| **SMTP Server**    | Mailjet SMTP server address with port                      | `in-v3.mailjet.com:465` |
| **SMTP Username**  | Your Mailjet API Key                                       | `a1b2c3d4e5f6g7h8i9j0`  |
| **SMTP Password**  | Your Mailjet Secret Key                                    | `x1y2z3a4b5c6d7e8f9g0`  |

**Note**: Use port `465` for SSL/TLS or port `587` for STARTTLS.

### 3. Database Schema Update

After installation, update the database schema:

**Maintenance → Analyze Database Structure → Compare**

This will create the `tx_mailjet_domain_model_emaillog` table for email logging.

## How It Works

### Email Sending Workflow

```
1. TYPO3 prepares email
         ↓
2. BeforeMailerSentMessageEvent fires
   → Extension extracts: subject, sender, recipients
   → Stores attempt in memory
         ↓
3. Email transmission attempt
         ↓
    ┌────┴──────────────────────────────┐
    ↓                                   ↓
4a. SUCCESS                         4b. FAILURE
    ↓                                   ↓
5a. AfterMailerSentMessageEvent     5b. Exception caught
    → Matches attempt by:             → Exception stored
      - Subject                       ↓
      - Sender                      6b. Shutdown handler
      - Recipients                    → Logs failed attempt
      - Timestamp (5s window)         → Status: "failed"
    → Logs to database                → Includes exception message
    → Status: "sent"
```

### Error Handling

**When email sending fails:**
- Exception is captured by the transport decorator
- Failed attempt remains in pending list
- Shutdown handler processes all pending attempts
- Database entry created with:
  - `delivery_status = 'failed'`
  - `exception_message` contains error details

**Common failure scenarios logged:**
- SMTP connection errors
- Authentication failures
- Invalid recipient addresses
- Server rejections
- Network timeouts

## Email Logging

### What is Logged?

The extension automatically logs every email attempt with:

- **Timestamp**: When the email was sent/attempted
- **Subject**: The email subject line (max 998 chars)
- **Sender Address**: The from address (max 255 chars)
- **Delivery Status**: `sent` or `failed`
- **Exception Message**: Error details (only for failed emails)
- **Mailjet Status**: Whether Mailjet was enabled for this email

### What is NOT Logged?

To ensure GDPR compliance and privacy:
- ❌ Recipient email addresses
- ❌ Email body content
- ❌ Attachments
- ❌ CC/BCC addresses

### Viewing Email Logs

Access the email logs in the TYPO3 backend:

**List → Root Level → Email Log**

You can:
- View all email attempts chronologically
- See delivery status (sent/failed)
- Review error messages for failed deliveries
- Search by subject or sender
- Filter by Mailjet status
- Identify delivery issues quickly

## Usage Examples

### Monitoring Email Delivery

Track both successful and failed email attempts:
- Form notification emails
- User registration confirmations
- Password reset emails
- Scheduler task notifications
- Any TYPO3-generated email

### Email Statistics

Analyze email activity and identify issues:

```sql
-- Count emails by delivery status
SELECT delivery_status, COUNT(*) as count 
FROM tx_mailjet_domain_model_emaillog 
GROUP BY delivery_status;

-- Recent failed emails
SELECT sent_at, subject, sender_address, exception_message
FROM tx_mailjet_domain_model_emaillog
WHERE delivery_status = 'failed'
ORDER BY sent_at DESC
LIMIT 10;

-- Daily email volume
SELECT FROM_UNIXTIME(sent_at, '%Y-%m-%d') as date, 
       delivery_status,
       COUNT(*) as count
FROM tx_mailjet_domain_model_emaillog
GROUP BY date, delivery_status
ORDER BY date DESC;

-- Most common errors
SELECT exception_message, COUNT(*) as count
FROM tx_mailjet_domain_model_emaillog
WHERE delivery_status = 'failed'
GROUP BY exception_message
ORDER BY count DESC;
```

## Troubleshooting

### Emails are not being sent

1. Check if Mailjet is enabled in Extension Configuration
2. Verify your Mailjet credentials are correct
3. Check that your Mailjet account is active and not suspended
4. Review email logs for failed attempts with error messages
5. Test email sending: **Settings → Mail Test** (Install Tool)

### Email logs show "failed" status

1. Check the **Exception Message** field in the log entry
2. Common issues:
   - Authentication failures → verify credentials
   - SMTP connection errors → check server/port settings
   - Invalid sender address → verify default mail address in TYPO3
3. Review TYPO3 system logs: **Admin Tools → Log**

### Want to temporarily disable Mailjet?

Uncheck **Enable Mailjet** in Extension Configuration. This:
- Disables Mailjet without removing credentials
- Falls back to TYPO3 default mail configuration
- Email logs will show `mailjet_enabled = 0`

### Email logs are empty

1. Ensure database schema is up to date: **Maintenance → Analyze Database Structure**
2. Clear all caches: **Admin Tools → Flush Cache**
3. Verify the extension is activated in Extension Manager
4. Send a test email and check if logging works

## Support

- **Issues**: [GitHub Issues](https://github.com/world-direct-cms/wd-ext-mailjet/issues)
- **Source Code**: [GitHub Repository](https://github.com/world-direct-cms/wd-ext-mailjet)
- **Author**: Klaus Hörmann-Engl <kho@world-direct.at>
- **Company**: [World-Direct eBusiness solutions GmbH](https://www.world-direct.at)

## License

This extension is licensed under GPL-2.0-only. See [LICENSE](LICENSE) file for details.

## Credits

Developed and maintained by **Klaus Hörmann-Engl** at [World-Direct eBusiness solutions GmbH](https://www.world-direct.at).

---

**Note**: This extension is not officially associated with or endorsed by Mailjet. Mailjet is a trademark of Mailjet SAS.
