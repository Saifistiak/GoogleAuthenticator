# PHP Google Authenticator 2FA Library

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)
![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-brightgreen)

A lightweight PHP library for implementing **Google Authenticator 2-Factor Authentication (TOTP)**.  
Compatible with PHP 7.4+ and easy to integrate in any project.

---

## ✨ Features

- 🔑 Generate secure **Base32 secret keys**
- 🔢 Create **TOTP verification codes**
- ✅ Verify user-entered codes
- 📷 Generate **QR codes** for easy setup
- ⚙️ Customizable code length (**6+ digits**)
- ⏱️ Time discrepancy handling
- 🧩 **PSR-4 compatible** with Composer

---

## 📦 Installation

```bash
composer require saifistiak/google-authenticator
```

---

## 🚀 Basic Usage

```php
<?php
use PHPSaif\GoogleAuthenticator;

$ga = new GoogleAuthenticator();

// Generate secret
$secret = $ga->createSecret(); // Returns 16-character base32 secret

// Generate QR Code URL
$qrCodeUrl = $ga->getQRCodeUrl(
    'MyApp:user@example.com',
    $secret,
    'MyApp'
);

// Verify code
if ($ga->verifyCode($secret, $_POST['code'])) {
    // Authentication successful
}
```

---

## ⚡ Advanced Usage

```php
// Custom secret length (16-128 chars)
$secret = $ga->createSecret(32);

// 8-digit codes
$ga->setCodeLength(8);

// Verify with 2-step discrepancy (±60 seconds)
$isValid = $ga->verifyCode($secret, $code, 2);

// Custom QR code options
$qrCodeUrl = $ga->getQRCodeUrl('AppName', $secret, null, [
    'width' => 300,
    'height' => 300,
    'level' => 'H' // Error correction level (L, M, Q, H)
]);
```

---

## 📖 API Reference

| Method | Description |
|--------|-------------|
| `createSecret(int $length = 16): string` | Generates base32 secret key |
| `getCode(string $secret): string` | Gets current TOTP code |
| `verifyCode(string $secret, string $code, int $discrepancy = 1): bool` | Verifies a user-entered code |
| `getQRCodeUrl(string $name, string $secret, ?string $issuer, array $params = []): string` | Generates QR code URL |
| `setCodeLength(int $length): self` | Sets code length (≥6) |

---

## 🔐 Security Best Practices

- Store **2FA secrets encrypted** in the database  
- Default **30-second window** is sufficient for most apps  
- Implement **rate limiting** on verification attempts  
- For **banking/finance apps**, consider shorter windows (15s)

---

## ⚙️ Requirements

- PHP **≥ 7.4**
- OpenSSL extension

---

## 📝 Examples

### 1️⃣ Registration Flow

```php
// Generate and store secret for user
$secret = $ga->createSecret();
$user->set2FASecret($secret);

// Show QR code to user
$qrCode = $ga->getQRCodeUrl(
    $user->email,
    $secret,
    'YourAppName'
);
```

### 2️⃣ Verification Flow

```php
if ($ga->verifyCode($user->get2FASecret(), $_POST['code'])) {
    // Grant access
} else {
    // Show error
}
```

---

## 📄 License

MIT License. See [LICENSE](LICENSE) file for details.

---

## 💬 Support

- 🐛 Report Issues  
- ✉️ Email: **saifistiak.bd@outlook.com**  

---
