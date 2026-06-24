<?php
// ============================================================
// Yangın Algılama Sonuçları Endpoint'i
// ============================================================

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';

authenticateRequest();
$kurumId = requireInstitution($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$reportId = getIdParam('report_id');

requireMethod(['GET']);

try {
    if ($reportId) {
        $checkStmt = $pdo->prepare("SELECT id FROM fire_detection_reports WHERE id = ? AND kurum_id = ?");
        $checkStmt->execute([$reportId, $kurumId]);
        if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);

        $result = [];
        
        $stmt = $pdo->prepare("SELECT * FROM fire_detection_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch();

        if ($report['inspection_results']) {
            $report['inspection_results'] = json_decode($report['inspection_results'], true);
        }
        $result['report'] = $report;

        $loopStmt = $pdo->prepare("SELECT * FROM fire_detection_section5_2 WHERE report_id = ? ORDER BY loop_no ASC");
        $loopStmt->execute([$reportId]);
        $result['loops'] = $loopStmt->fetchAll();

        jsonSuccess($result);
    } else {
        $stmt = $pdo->prepare("SELECT id, report_no, report_date, result FROM fire_detection_reports WHERE kurum_id = ? ORDER BY id DESC");
        $stmt->execute([$kurumId]);
        jsonSuccess($stmt->fetchAll());
    }
} catch (PDOException $e) {
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
