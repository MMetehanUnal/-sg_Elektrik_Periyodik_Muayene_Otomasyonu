<?php
// ============================================================
// GET /api/auth/me.php — Mevcut Kullanıcı Bilgisi
// ============================================================
// Token sahibinin kullanıcı bilgilerini döndürür.
// Auth: Bearer Token gerekli
// ============================================================

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/validator.php';

// Sadece GET kabul et
requireMethod(['GET']);

// Token doğrula
authenticateRequest();

$user = getApiUser();

try {
    // Veritabanından güncel bilgileri al
    $stmt = $pdo->prepare("SELECT id, username, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user['user_id']]);
    $dbUser = $stmt->fetch();

    if (!$dbUser) {
        jsonError('NOT_FOUND', 'Kullanıcı bulunamadı.', 404);
    }

    jsonSuccess([
        'id' => (int)$dbUser['id'],
        'username' => $dbUser['username'],
        'role' => $dbUser['role'],
        'created_at' => $dbUser['created_at']
    ]);

} catch (PDOException $e) {
    jsonError('SERVER_ERROR', 'Sunucu hatası oluştu.', 500);
}
