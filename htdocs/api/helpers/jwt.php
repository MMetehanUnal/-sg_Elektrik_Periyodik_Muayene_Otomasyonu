<?php
// ============================================================
// JWT (JSON Web Token) — Pure PHP Implementation
// ============================================================
// HMAC-SHA256 imzalama ile JWT oluşturma ve doğrulama.
// Harici kütüphane gerektirmez.
// ============================================================

/**
 * Base64URL encode (JWT standardı için)
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64URL decode
 */
function base64UrlDecode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * JWT token oluştur
 *
 * @param array $payload Token'a gömülecek veri (user_id, username, role vb.)
 * @return string JWT token string
 */
function jwtEncode($payload) {
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    // Standart JWT claim'leri ekle
    $payload['iss'] = JWT_ISSUER;
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;

    // Header ve payload encode
    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));

    // İmza oluştur
    $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
    $signatureEncoded = base64UrlEncode($signature);

    return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

/**
 * JWT token doğrula ve payload'ı döndür
 *
 * @param string $token JWT token string
 * @return array|false Başarılıysa payload array, değilse false
 */
function jwtDecode($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

    // İmza doğrula
    $expectedSignature = base64UrlEncode(
        hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true)
    );

    if (!hash_equals($expectedSignature, $signatureEncoded)) {
        return false;
    }

    // Payload decode
    $payload = json_decode(base64UrlDecode($payloadEncoded), true);
    if (!$payload) {
        return false;
    }

    // Süre kontrolü
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }

    return $payload;
}

/**
 * JWT token'dan detaylı doğrulama sonucu döndür
 * Hata nedenini de içerir.
 *
 * @param string $token JWT token string
 * @return array ['valid' => bool, 'payload' => array|null, 'error' => string|null]
 */
function jwtValidate($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return ['valid' => false, 'payload' => null, 'error' => 'AUTH_INVALID'];
    }

    list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

    // İmza doğrula
    $expectedSignature = base64UrlEncode(
        hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true)
    );

    if (!hash_equals($expectedSignature, $signatureEncoded)) {
        return ['valid' => false, 'payload' => null, 'error' => 'AUTH_INVALID'];
    }

    // Payload decode
    $payload = json_decode(base64UrlDecode($payloadEncoded), true);
    if (!$payload) {
        return ['valid' => false, 'payload' => null, 'error' => 'AUTH_INVALID'];
    }

    // Süre kontrolü
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return ['valid' => false, 'payload' => null, 'error' => 'AUTH_EXPIRED'];
    }

    return ['valid' => true, 'payload' => $payload, 'error' => null];
}
