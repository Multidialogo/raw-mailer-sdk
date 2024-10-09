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
use multidialogo\RawMailerSdk\Facade;
use multidialogo\RawMailerSdk\Model\SmtpMessage;

$facade = new Facade(
    Facade::DRIVERS['SES'], // or 'STD' for standard SMTP, 'FAKE' for testing
    [
        'version' => 'latest',
        'region' => 'your-region',
        'accessKey' => 'your-access-key',
        'secretKey' => 'your-secret-key',
    ],
    '/path/to/results', // Directory for result files
    'catchall.example.com', // Optional catchall domain
    5 // Max parallel jobs
);

// Creating a message
$message = new SmtpMessage('sender@example.com', 'recipient@example.com', 'Subject', 'Plain text body', '<p>HTML body</p>');
// Add attachments if needed
$message->addAttachment('/path/to/attachment.pdf');


// Sending multiple emails in parallel
$messages = [$message, /* other SmtpMessage instances */];
$results = $facade->parallelSend($messages, 3); // 3 attempts for each message
```

## Configuration

### Drivers

The SDK supports three drivers:

- **FAKE**: A fake mail client for testing purposes.
- **SES**: AWS Simple Email Service.
- **STD**: Standard SMTP.

### Attachment Size Limits

- AWS SES: Maximum attachment size is **6.5 MB**.
- Standard SMTP: Maximum attachment size is **25 MB**.

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

## Development Environment
This repository includes a devcontainer configuration to streamline the development environment setup. You can use either PhpStorm or Visual Studio Code (VSCode) to take advantage of the development container.

### Easy jump start with docker compose

#### Install php dependencies
(From the root directory)

```bash
docker compose run --rm app composer install --optimize-autoloader
```

#### Launch test suite
(From the root directory)

```bash
docker compose run --rm app ./vendor/bin/phpunit
```

This will run the unit test suite.

### Check mailcatcher web ui to debug messages sent with STD driver
```text
http://localhost:1080/
```

### Using the DevContainer with VSCode
Install Docker: Ensure that Docker is installed on your machine. Download Docker

Install Visual Studio Code: If you don't have VSCode installed, you can download it from here.

Install the Dev Containers extension: In VSCode, go to the Extensions view (⇧⌘X or Ctrl+Shift+X), search for Dev Containers, and install the extension by Microsoft.

Clone the Repository:

```bash
git clone https://github.com/Multidialogo/raw-mailer-sdk.git
```

Open the Repository in VSCode: Open the project folder in VSCode.

Reopen in Container: Once the repository is opened in VSCode, a pop-up will appear asking to "Reopen in Container". If not, you can manually reopen by clicking on the green icon in the bottom-left corner and selecting "Reopen in Container".

Start Development: The container will automatically install the necessary PHP dependencies and set up the environment.

### Using the DevContainer with PhpStorm
Install Docker: Ensure that Docker is installed on your machine. Download Docker

Install PhpStorm: You can download PhpStorm from here.

Clone the Repository:

```bash
git clone https://github.com/Multidialogo/raw-mailer-sdk.git
```

Open the Project in PhpStorm: Open the repository folder in PhpStorm.

Configure Dev Container:

Open Settings in PhpStorm.
Go to Build, Execution, Deployment > Docker and ensure Docker is properly configured.
Go to File > Settings > PHP > CLI Interpreter, click Add..., and choose From Docker, Vagrant, VM, WSL, or Remote. Then select the appropriate container configuration.
Start Development: PhpStorm will utilize the devcontainer and set up the environment, allowing you to work on the project seamlessly within the Dockerized PHP environment.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.