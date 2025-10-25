<?php
declare(strict_types=1);

// --- configuración de sesión / cookies (antes de session_start)
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax'); // 'Strict' si es posible

// cookie_secure solo con HTTPS
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if ($secure) {
    ini_set('session.cookie_secure', '1');
}

// ajustar lifetime de la cookie
session_set_cookie_params([
    'lifetime' => 1800,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();



// seguridad de cookies de sesión (puede repetirse en php.ini)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax'); // o 'Strict'
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', '1');
}

// ------------------- Sanitizar / escape para XSS -------------------
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ------------------- CSRF -------------------
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_check(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ------------------- TOTP (RFC 6238 compatible) -------------------
// Base32 encode/decode helpers
// ===============================================================
//  FUNCIONES BASE32 — 100% COMPATIBLES CON GOOGLE AUTHENTICATOR
// ===============================================================
function base32_encode_raw(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($data) as $c) {
        $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    }
    $encoded = '';
    foreach (str_split($bits, 5) as $chunk) {
        $encoded .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
    }
    while (strlen($encoded) % 8 !== 0) {
        $encoded .= '=';
    }
    return $encoded;
}

function base32_decode_raw(string $b32): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper($b32);
    $b32 = preg_replace('/[^A-Z2-7]/', '', $b32);  // limpia caracteres inválidos
    $bits = '';
    for ($i = 0; $i < strlen($b32); $i++) {
        $val = strpos($alphabet, $b32[$i]);
        if ($val === false) continue;
        $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
    }
    $decoded = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $decoded .= chr(bindec($byte));
        }
    }
    return $decoded;
}


// Generar secreto base32
function generate_totp_secret(int $length = 16): string {
    $random = random_bytes($length);
    // convert raw bytes to base32 without padding for storage
    $b32 = rtrim(base32_encode_raw($random), '=');
    return $b32;
}

// genera HOTP
function hotp(string $secret, int $counter, int $digits = 6): string {
    $key = base32_decode_raw($secret);
    $counterBytes = pack('N2', ($counter >> 32) & 0xFFFFFFFF, $counter & 0xFFFFFFFF);
    $hash = hash_hmac('sha1', $counterBytes, $key, true);
    $offset = ord($hash[19]) & 0xf;
    $binary = (ord($hash[$offset]) & 0x7f) << 24 |
              (ord($hash[$offset+1]) & 0xff) << 16 |
              (ord($hash[$offset+2]) & 0xff) << 8 |
              (ord($hash[$offset+3]) & 0xff);
    $otp = $binary % pow(10, $digits);
    return str_pad((string)$otp, $digits, '0', STR_PAD_LEFT);
}

// verifica TOTP (permite drift)
function verify_totp(string $secret, string $code, int $window = 3, int $timeStep = 30, int $digits = 6): bool {
    // Se calcula el bloque de tiempo actual (entero)
    $time = (int) floor(time() / $timeStep);
    $code = trim($code);

    // Se revisan los códigos del bloque actual, los 3 anteriores y los 3 siguientes
    for ($i = -$window; $i <= $window; $i++) {
        $counter = (int)($time + $i);
        $calc = hotp($secret, $counter, $digits);
        if (hash_equals($calc, $code)) {
            return true; // Código válido dentro del rango permitido
        }
    }

    return false; // Ningún código coincidió
}


// Genera otpauth URL para QR (compatible con Google Authenticator)
function get_otpauth_url(string $secret, string $account, string $issuer = 'MiApp'): string {
    $secret = rtrim($secret, '=');
    $accountEnc = rawurlencode($account);
    $issuerEnc = rawurlencode($issuer);
    return "otpauth://totp/{$issuerEnc}:{$accountEnc}?secret={$secret}&issuer={$issuerEnc}&algorithm=SHA1&digits=6&period=30";
}
