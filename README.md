# TYPO3 Extension: Mailjet

[![TYPO3](https://img.shields.io/badge/TYPO3-11%20|%2012%20|%2013%20|%2014-orange.svg)](https://typo3.org/)
[![PHP](https://img.shields.io/badge/PHP-8.3%20|%208.4-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-blue.svg)](LICENSE)

This TYPO3 extension configures the system to send emails through [Mailjet](https://www.mailjet.com/) SMTP service and provides comprehensive email tracking capabilities.

## Features

- **Mailjet Integration**: Configure TYPO3 to use Mailjet's SMTP servers for email delivery
- **Email Logging**: Automatically track all sent emails in a database table
- **Privacy-Focused**: Logs only metadata (timestamp, subject, Mailjet status) - no recipient addresses or email content
- **Backend Module**: View email statistics directly in the TYPO3 backend
- **Event-Based**: Uses TYPO3's `AfterMailerSentMessageEvent` for reliable email tracking
- **Flexible Architecture**: Works with any TYPO3 extension that uses the standard mail API

## Requirements

- TYPO3 11.0 - 14.9
- PHP 8.3 or 8.4
- Active Mailjet account with API credentials

## Installation

### Via Composer (recommended)

```bash
composer require worlddirect/mailjet
```

### Manual Installation

1. Download the extension from the TYPO3 Extension Repository (TER)
2. Upload to `typo3conf/ext/mailjet/`
3. Activate the extension in the Extension Manager

## Configuration

### 1. Mailjet API Credentials

First, obtain your Mailjet API credentials:
1. Log in to your [Mailjet account](https://app.mailjet.com/)
2. Navigate to Account Settings → REST API → API Key Management
3. Copy your **API Key** (username) and **Secret Key** (password)

### 2. Extension Configuration

Configure the extension in the TYPO3 backend:

**Admin Tools → Settings → Extension Configuration → mailjet**

Set the following values:

| Setting           | Description                           | Example                 |
| ----------------- | ------------------------------------- | ----------------------- |
| **SMTP Server**   | Mailjet SMTP server address with port | `in-v3.mailjet.com:465` |
| **SMTP Username** | Your Mailjet API Key                  | `a1b2c3d4e5f6g7h8i9j0`  |
| **SMTP Password** | Your Mailjet Secret Key               | `x1y2z3a4b5c6d7e8f9g0`  |

**Note**: Use port `465` for SSL/TLS or port `587` for STARTTLS.

### 3. Database Schema Update

After installation, update the database schema:

**Maintenance → Analyze Database Structure → Compare**

This will create the `tx_mailjet_domain_model_sentemail` table for email logging.

## Email Logging

### What is Logged?

The extension automatically logs every email sent through TYPO3 with the following information:

- **Timestamp**: When the email was sent
- **Subject**: The email subject line
- **Mailjet Status**: Whether Mailjet was configured and active at the time of sending

### What is NOT Logged?

To ensure GDPR compliance and privacy:
- ❌ Recipient email addresses
- ❌ Sender email addresses
- ❌ Email body content
- ❌ Attachments
- ❌ Any personally identifiable information (PII)

### Viewing Email Logs

Access the email logs in the TYPO3 backend:

**List → Root Level → Sent Email Log**

You can:
- View all sent emails chronologically
- Search by subject
- Filter by Mailjet status
- Export data for analysis

## Database Schema

The extension creates the following table:

```sql
CREATE TABLE tx_mailjet_domain_model_sentemail (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT 0 NOT NULL,
    tstamp int(11) unsigned DEFAULT 0 NOT NULL,
    crdate int(11) unsigned DEFAULT 0 NOT NULL,
    deleted tinyint(4) unsigned DEFAULT 0 NOT NULL,
    sent_at int(11) DEFAULT 0 NOT NULL,
    mailjet_enabled tinyint(1) unsigned DEFAULT 0 NOT NULL,
    subject varchar(998) DEFAULT '' NOT NULL,
    
    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY subject (subject)
);
```

## Usage Examples

### Tracking Form Submissions

When users submit forms via the TYPO3 Form extension, the extension automatically logs:
- Form notification emails
- Confirmation emails to users
- Each logged with the email subject for easy identification

### Monitoring System Emails

Track system-generated emails:
- Password reset emails
- User registration confirmations
- Backend email notifications
- Scheduler task notifications

### Email Statistics

Use the subject field to categorize and analyze email activity:
```sql
-- Count emails by subject
SELECT subject, COUNT(*) as count 
FROM tx_mailjet_domain_model_sentemail 
GROUP BY subject 
ORDER BY count DESC;

-- Daily email volume
SELECT FROM_UNIXTIME(sent_at, '%Y-%m-%d') as date, COUNT(*) as count
FROM tx_mailjet_domain_model_sentemail
GROUP BY date
ORDER BY date DESC;
```

## Troubleshooting

### Emails are not being sent

1. Verify your Mailjet credentials in Extension Configuration
2. Check that your Mailjet account is active and not suspended
3. Review TYPO3 logs: **Admin Tools → Log**
4. Test email sending from Install Tool: **Settings → Mail Test**

### Email logs are empty

1. Ensure database schema is up to date: **Maintenance → Analyze Database Structure**
2. Clear all caches: **Admin Tools → Flush Cache**
3. Verify the extension is activated in Extension Manager

### Subject is missing in logs

The extension attempts to extract the subject from sent emails. If empty:
- The email may not have a subject line
- Try sending a test email with a subject
- Check TYPO3 version compatibility

## Support

- **Issues**: [GitHub Issues](https://github.com/world-direct/mailjet/issues)
- **Source Code**: [GitHub Repository](https://github.com/world-direct/mailjet)
- **Author**: Klaus Hörmann-Engl <kho@world-direct.at>
- **Company**: [World-Direct eBusiness solutions GmbH](https://www.world-direct.at)

## License

This extension is licensed under GPL-2.0-only. See [LICENSE](LICENSE) file for details.

## Credits

Developed and maintained by [World-Direct eBusiness solutions GmbH](https://www.world-direct.at).

---

**Note**: This extension is not officially associated with or endorsed by Mailjet. Mailjet is a trademark of Mailjet SAS.
