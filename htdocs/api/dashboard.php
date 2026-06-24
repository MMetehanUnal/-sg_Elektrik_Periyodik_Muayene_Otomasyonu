<?php
/**
 * Dashboard API Endpoint
 * 
 * GET /api/dashboard.php - Özet istatistikleri getir
 * 
 * Kapsam: user_id (oturum açan kullanıcı)
 * Opsiyonel: X-Institution-Id header ile kurum bazlı rapor sayıları
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/helpers/auth_middleware.php';
require_once __DIR__ . '/helpers/validator.php';

authenticateRequest();
requireMethod(['GET']);

$user = getApiUser();

try {
    $result = [];

    // Toplam kurum sayısı
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM institutions WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    $result['total_institutions'] = (int) $stmt->fetch()['total'];

    // X-Institution-Id header varsa rapor sayılarını getir
    $kurumIdHeader = $_SERVER['HTTP_X_INSTITUTION_ID'] ?? null;

    if ($kurumIdHeader) {
        $kurumId = (int) $kurumIdHeader;

        // Kurumun bu kullanıcıya ait olduğunu doğrula
        $checkStmt = $pdo->prepare("SELECT id FROM institutions WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$kurumId, $user['user_id']]);

        if ($checkStmt->fetch()) {
            // Topraklama raporları
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM grounding_reports WHERE kurum_id = ?");
            $stmt->execute([$kurumId]);
            $result['grounding_reports'] = (int) $stmt->fetch()['total'];

            // İç tesisat raporları
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM internal_installation_reports WHERE kurum_id = ?");
            $stmt->execute([$kurumId]);
            $result['internal_installation_reports'] = (int) $stmt->fetch()['total'];

            // Yıldırımdan korunma raporları
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM lightning_protection_reports WHERE kurum_id = ?");
            $stmt->execute([$kurumId]);
            $result['lightning_protection_reports'] = (int) $stmt->fetch()['total'];

            // Yangın algılama raporları
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM fire_detection_reports WHERE kurum_id = ?");
            $stmt->execute([$kurumId]);
            $result['fire_detection_reports'] = (int) $stmt->fetch()['total'];

            // Toplam rapor sayısı
            $result['total_reports'] = $result['grounding_reports']
                + $result['internal_installation_reports']
                + $result['lightning_protection_reports']
                + $result['fire_detection_reports'];

            $result['kurum_id'] = $kurumId;
        } else {
            jsonError('NOT_FOUND', 'Kurum bulunamadı veya erişim yetkiniz yok', 404);
        }
    }

    jsonSuccess($result, 'Dashboard verileri');

} catch (PDOException $e) {
    jsonError('DB_ERROR', 'Veritabanı hatası: ' . $e->getMessage(), 500);
}
