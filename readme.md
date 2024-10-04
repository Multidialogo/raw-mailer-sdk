# Multidialogo Raw Mailer SDK

Welcome to the **Multidialogo Raw Mailer SDK**! This library provides a flexible and robust way to send emails using various drivers, including AWS SES, standard SMTP, and a fake mail client for testing purposes.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Features](#features)
- [Contributing](#contributing)
- [License](#license)

## Installation

To install the library, clone the repository and run:

```bash
composer install multidialogo/raw-mailer-sdk
```

Ensure you have the required dependencies installed, including the AWS SDK for PHP if you plan to use the SES driver.

## Usage

### Basic Setup

To get started, include the necessary namespaces and create an instance of the `Facade` class:

```php
use multidialogo\RawMailer\Facade;

$facade = new Facade(
    'SES', // or 'STD' for standard SMTP, 'FAKE' for testing
    [
        'version' => 'latest',
        'region' => 'your-region',
        'accessKey' => 'your-access-key',
        'secretKey' => 'your-secret-key',
    ],
    null, // For simple SMTP config, provide an associative array
    'sender@example.com',
    'replyto@example.com',
    'development', // or 'production'
);
```

### Sending Emails

You can send multiple emails in parallel:

```php
$messages = [/* Array of Message objects */];
$results = $facade->parallelSend($messages, 3); // 3 attempts for each message
```

### Sending a Single Email

To send a single email, use the `send` method directly:

```php
$result = $facade->send(
    'sender@example.com',
    'recipient@example.com',
    'Subject',
    [],
    'Plain text body',
    '<p>HTML body</p>',
    ['/path/to/attachment.pdf']
);
```

## Configuration

### Drivers

The SDK supports three drivers:

- **FAKE**: A fake mail client for testing purposes.
- **SES**: AWS Simple Email Service.
- **STD**: Standard SMTP.

### Attachment Size Limits

- AWS SES: Maximum attachment size is **6.5 MB**.
- Standard SMTP: Maximum attachment size is **15 MB**.

### Parallel Job Configuration

You can set the number of parallel jobs (default is **10**):

```php
$facade = new Facade(
    'SES',
    null,
    null,
    'sender@example.com',
    null,
    'production',
    'boundary_',
    5 // Set max parallel jobs
);
```

## Features

- Send emails in parallel for better performance.
- Handle attachments with MIME types.
- Customizable email headers and boundaries.
- Catch-all domain modification for non-production environments.
- Error handling for invalid email configurations.

## Contributing

Contributions are welcome! Please open an issue or submit a pull request for any enhancements or bug fixes.

1. Fork the repository.
2. Create your feature branch (`git checkout -b feature/MyFeature`).
3. Commit your changes (`git commit -m 'Add some feature'`).
4. Push to the branch (`git push origin feature/MyFeature`).
5. Open a pull request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.