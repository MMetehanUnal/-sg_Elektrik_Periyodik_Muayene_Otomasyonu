<?php
// ============================================================
// İç Tesisat Sonuçları Endpoint'i
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
        $checkStmt = $pdo->prepare("SELECT id FROM internal_installation_reports WHERE id = ? AND kurum_id = ?");
        $checkStmt->execute([$reportId, $kurumId]);
        if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);

        $result = [
            'report' => null,
            'panels' => [],
            'section6_header' => null,
            'section6_2_rows' => [],
            'section6_3_rows' => []
        ];

        $stmt = $pdo->prepare("SELECT * FROM internal_installation_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $result['report'] = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_header WHERE report_id = ?");
        $stmt->execute([$reportId]);
        $result['section6_header'] = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_2_rows WHERE report_id = ? ORDER BY no_col ASC");
        $stmt->execute([$reportId]);
        $result['section6_2_rows'] = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_3_rows WHERE report_id = ? ORDER BY no_col ASC");
        $stmt->execute([$reportId]);
        $result['section6_3_rows'] = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM ic_tesisat_panels WHERE report_id = ? ORDER BY panel_order ASC, id ASC");
        $stmt->execute([$reportId]);
        $panels = $stmt->fetchAll();

        foreach ($panels as $panel) {
            $panelId = $panel['id'];
            
            $stmtSec5 = $pdo->prepare("SELECT question_key, answer FROM ic_tesisat_section5 WHERE panel_id = ?");
            $stmtSec5->execute([$panelId]);
            $panel['section5'] = $stmtSec5->fetchAll(PDO::FETCH_KEY_PAIR);

            $stmtSec61 = $pdo->prepare("SELECT * FROM ic_tesisat_section6_1 WHERE panel_id = ?");
            $stmtSec61->execute([$panelId]);
            $panel['section6_1'] = $stmtSec61->fetch();

            $stmtSec61Rows = $pdo->prepare("SELECT * FROM ic_tesisat_section6_1_rows WHERE panel_id = ? ORDER BY no_col ASC");
            $stmtSec61Rows->execute([$panelId]);
            $panel['section6_1_rows'] = $stmtSec61Rows->fetchAll();

            $stmtPhotos = $pdo->prepare("SELECT * FROM ic_tesisat_photos WHERE panel_id = ?");
            $stmtPhotos->execute([$panelId]);
            $panel['photos'] = $stmtPhotos->fetchAll();

            $result['panels'][] = $panel;
        }

        jsonSuccess($result);
    } else {
        $stmt = $pdo->prepare("SELECT id, report_no, report_date, result FROM internal_installation_reports WHERE kurum_id = ? ORDER BY id DESC");
        $stmt->execute([$kurumId]);
        jsonSuccess($stmt->fetchAll());
    }
} catch (PDOException $e) {
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
