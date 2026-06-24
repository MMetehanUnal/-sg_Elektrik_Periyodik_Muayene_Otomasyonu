<?php
/**
 * Tesis Seçimi (Facility Selection) API Endpoint
 * 
 * POST   /api/tesis-secimi.php  - Aktif kurum seç (Android uygulama için)
 * DELETE /api/tesis-secimi.php  - Yerel seçimi temizle (no-op)
 * 
 * Tablo: institutions (salt okunur doğrulama)
 * Kapsam: user_id (oturum açan kullanıcı)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/helpers/auth_middleware.php';
require_once __DIR__ . '/helpers/validator.php';

authenticateRequest();

$user = getApiUser();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        case 'POST':
            $data = getJsonBody();
            requireFields($data, ['kurum_id']);

            $kurumId = (int) $data['kurum_id'];

            // Sahiplik kontrolü
            $stmt = $pdo->prepare(
                "SELECT id, firma_adi, adresi, il_kodu, kurum_kodu FROM institutions WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$kurumId, $user['user_id']]);
            $kurum = $stmt->fetch();

            if (!$kurum) {
                jsonError('NOT_FOUND', 'Kurum bulunamadı veya erişim yetkiniz yok', 404);
            }

            jsonSuccess($kurum, 'Tesis başarıyla seçildi');
            break;

        case 'DELETE':
            // No-op: Android uygulama yerel seçimi temizler
            jsonSuccess(null, 'Tesis seçimi temizlendi');
            break;

        default:
            requireMethod(['POST', 'DELETE']);
            break;
    }
} catch (PDOException $e) {
    jsonError('DB_ERROR', 'Veritabanı hatası: ' . $e->getMessage(), 500);
}
