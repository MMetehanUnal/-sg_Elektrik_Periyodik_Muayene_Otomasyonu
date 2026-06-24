<?php
// ============================================================
// POST /api/auth/login.php — Kullanıcı Girişi
// ============================================================
// Kullanıcı adı ve şifre ile giriş yaparak JWT token alır.
// Auth: Gerekmiyor
// ============================================================

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';

// Sadece POST kabul et
requireMethod(['POST']);

// JSON body oku
$data = getJsonBody();

// Zorunlu alanlar
requireFields($data, ['username', 'password']);

$username = sanitize($data['username']);
$password = $data['password']; // Şifre sanitize edilmez (hash karşılaştırması için)

try {
    // Kullanıcıyı bul
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonError('AUTH_FAILED', 'Geçersiz kullanıcı adı veya şifre.', 401);
    }

    // JWT token oluştur
    $token = jwtEncode([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role']
    ]);

    // Başarılı yanıt
    jsonSuccess([
        'token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => JWT_EXPIRY,
        'user' => [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]
    ], 'Giriş başarılı.');

} catch (PDOException $e) {
    jsonError('SERVER_ERROR', 'Sunucu hatası oluştu.', 500);
}
