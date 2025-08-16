<?php

/**
 * Google Authenticator 2FA Handler for PHP 8+
 *
 * @author  Saif
 * @license BSD License
 * @link    https://www.saifistiak.me
 */
class PHPSaif_GoogleAuthenticator
{
    protected int $codeLength = 6;

    /**
     * Create a new secret key.
     *
     * @param int $secretLength
     * @return string
     */
    public function createSecret(int $secretLength = 16): string
    {
        if ($secretLength < 16 || $secretLength > 128) {
            throw new InvalidArgumentException('Secret length must be between 16 and 128.');
        }

        $validChars = $this->getBase32LookupTable();
        $randomBytes = random_bytes($secretLength);

        $secret = '';
        for ($i = 0; $i < $secretLength; $i++) {
            $secret .= $validChars[ord($randomBytes[$i]) & 31];
        }

        return $secret;
    }

    /**
     * Generate a TOTP code.
     *
     * @param string   $secret
     * @param int|null $timeSlice
     * @return string
     */
    public function getCode(string $secret, ?int $timeSlice = null): string
    {
        $timeSlice = $timeSlice ?? (int) floor(time() / 30);
        $secretKey = $this->base32Decode($secret);

        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('SHA1', $time, $secretKey, true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $part = substr($hash, $offset, 4);

        $value = unpack('N', $part)[1] & 0x7FFFFFFF;
        $modulo = 10 ** $this->codeLength;

        return str_pad((string) ($value % $modulo), $this->codeLength, '0', STR_PAD_LEFT);
    }

    /**
     * Generate QR Code URL for authenticator apps.
     *
     * @param string      $name
     * @param string      $secret
     * @param string|null $issuer
     * @param array       $params
     * @return string
     */
    public function getQRCodeUrl(string $name, string $secret, ?string $issuer = null, array $params = []): string
    {
        $width  = $params['width']  ?? 200;
        $height = $params['height'] ?? 200;
        $level  = in_array($params['level'] ?? 'M', ['L', 'M', 'Q', 'H'], true) ? $params['level'] : 'M';

        $otpauth = "otpauth://totp/{$name}?secret={$secret}";
        if ($issuer) {
            $otpauth .= "&issuer=" . rawurlencode($issuer);
        }

        $data = urlencode($otpauth);

        return "https://api.qrserver.com/v1/create-qr-code/?data={$data}&size={$width}x{$height}&ecc={$level}";
    }

    /**
     * Verify user entered code.
     *
     * @param string   $secret
     * @param string   $code
     * @param int      $discrepancy
     * @param int|null $currentTimeSlice
     * @return bool
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1, ?int $currentTimeSlice = null): bool
    {
        $currentTimeSlice = $currentTimeSlice ?? (int) floor(time() / 30);

        if (strlen($code) !== $this->codeLength) {
            return false;
        }

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculated = $this->getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the code length (>=6).
     *
     * @param int $length
     * @return $this
     */
    public function setCodeLength(int $length): self
    {
        if ($length < 6) {
            throw new InvalidArgumentException("Code length must be >= 6.");
        }

        $this->codeLength = $length;
        return $this;
    }

    /**
     * Decode Base32 encoded secret.
     *
     * @param string $secret
     * @return string
     */
    protected function base32Decode(string $secret): string
    {
        if ($secret === '') {
            return '';
        }

        $chars = $this->getBase32LookupTable();
        $flipped = array_flip($chars);

        $secret = strtoupper($secret);
        $secret = str_replace('=', '', $secret);

        $binaryString = '';
        for ($i = 0; $i < strlen($secret); $i += 8) {
            $chunk = '';
            for ($j = 0; $j < 8; $j++) {
                $char = $secret[$i + $j] ?? null;
                if ($char === null || !isset($flipped[$char])) {
                    continue;
                }
                $chunk .= str_pad(decbin($flipped[$char]), 5, '0', STR_PAD_LEFT);
            }

            foreach (str_split($chunk, 8) as $byte) {
                if (strlen($byte) === 8) {
                    $binaryString .= chr(bindec($byte));
                }
            }
        }

        return $binaryString;
    }

    /**
     * Get Base32 lookup table.
     *
     * @return array
     */
    protected function getBase32LookupTable(): array
    {
        return [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
            'Y', 'Z', '2', '3', '4', '5', '6', '7',
            '='
        ];
    }
}
