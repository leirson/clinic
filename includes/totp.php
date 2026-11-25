<?php
/**
 * TOTP (Time-based One-Time Password) - RFC 6238
 * Compatível com Google Authenticator, Microsoft Authenticator, Authy, etc.
 */

class TOTP {

    public static function generateSecret($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    public static function base32Decode($b32) {
        $b32 = strtoupper($b32);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $bin = '';
        for ($i = 0; $i < strlen($b32); $i++) {
            $pos = strpos($alphabet, $b32[$i]);
            if ($pos === false) continue;
            $bin .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        for ($i = 0; $i+8 <= strlen($bin); $i += 8) {
            $output .= chr(bindec(substr($bin, $i, 8)));
        }
        return $output;
    }

    public static function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) $timeSlice = floor(time() / 30);
        $secretBin = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretBin, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) |
                 ((ord($hash[$offset+1]) & 0xFF) << 16) |
                 ((ord($hash[$offset+2]) & 0xFF) << 8) |
                 (ord($hash[$offset+3]) & 0xFF);
        return str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
    }

    public static function verify($secret, $code, $discrepancy = 1) {
        if (!$secret || !$code) return false;
        $currentTimeSlice = floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $expected = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($expected, $code)) return true;
        }
        return false;
    }

    public static function getQrCodeUrl($secret, $label, $issuer = 'ClinicApp') {
        $otpauth = 'otpauth://totp/' . rawurlencode($issuer . ':' . $label)
                 . '?secret=' . $secret
                 . '&issuer=' . rawurlencode($issuer);
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpauth);
    }

    public static function generateRecoveryCodes($count = 8) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }
        return $codes;
    }
}
