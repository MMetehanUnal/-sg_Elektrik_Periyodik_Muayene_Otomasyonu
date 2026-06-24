<?php
// ============================================================
// Topraklama Ölçümleri Endpoint'i (5.1 ve 5.2)
// ============================================================

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';

authenticateRequest();
$kurumId = requireInstitution($pdo);
$method = $_SERVER['REQUEST_METHOD'];

$data = getJsonBody();
$reportId = getIdParam('report_id') ?? ($data['report_id'] ?? null);
$section = $_GET['section'] ?? ($data['section'] ?? null);

if (!$reportId) jsonError('VALIDATION_ERROR', 'report_id parametresi gereklidir.', 400);
if (!in_array($section, ['5_1', '5_2'])) jsonError('VALIDATION_ERROR', 'Geçerli bir section belirtilmelidir (5_1 veya 5_2).', 400);

// Rapor sahipliğini doğrula
$checkStmt = $pdo->prepare("SELECT id FROM grounding_reports WHERE id = ? AND kurum_id = ?");
$checkStmt->execute([$reportId, $kurumId]);
if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı veya yetkiniz yok.', 404);

try {
    if ($method === 'GET') {
        if ($section === '5_1') {
            $stmt = $pdo->prepare("SELECT * FROM measurements_5_1 WHERE report_id = ? ORDER BY point_no ASC");
            $stmt->execute([$reportId]);
            jsonSuccess($stmt->fetchAll());
        } elseif ($section === '5_2') {
            $stmt = $pdo->prepare("SELECT * FROM measurements_5_2 WHERE report_id = ? ORDER BY row_no ASC");
            $stmt->execute([$reportId]);
            jsonSuccess($stmt->fetchAll());
        }
    } elseif ($method === 'POST') {
        if (!isset($data['rows']) || !is_array($data['rows'])) {
            jsonError('VALIDATION_ERROR', 'rows dizisi gereklidir.', 400);
        }

        $pdo->beginTransaction();

        if ($section === '5_1') {
            $pdo->prepare("DELETE FROM measurements_5_1 WHERE report_id = ?")->execute([$reportId]);
            
            $stmt = $pdo->prepare("INSERT INTO measurements_5_1 (report_id, point_no, point_name, prot_in, prot_type, prot_ia, prot_ik1, measured_zx_rx, limit_zs_ra, rcd_type_limits, rcd_test_ia, rcd_test_ta, result) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($data['rows'] as $row) {
                $stmt->execute([
                    $reportId,
                    sanitize($row['point_no'] ?? null),
                    sanitize($row['point_name'] ?? null),
                    sanitize($row['prot_in'] ?? null),
                    sanitize($row['prot_type'] ?? null),
                    sanitize($row['prot_ia'] ?? null),
                    sanitize($row['prot_ik1'] ?? null),
                    sanitize($row['measured_zx_rx'] ?? null),
                    sanitize($row['limit_zs_ra'] ?? null),
                    sanitize($row['rcd_type_limits'] ?? null),
                    sanitize($row['rcd_test_ia'] ?? null),
                    sanitize($row['rcd_test_ta'] ?? null),
                    sanitize($row['result'] ?? null)
                ]);
            }
        } elseif ($section === '5_2') {
            $pdo->prepare("DELETE FROM measurements_5_2 WHERE report_id = ?")->execute([$reportId]);
            
            $stmt = $pdo->prepare("INSERT INTO measurements_5_2 (report_id, row_no, upstream_panel, upstream_rcd_type, upstream_rcd_in, upstream_rcd_idn, upstream_rcd_dt, downstream_panel, downstream_rcd_type, downstream_rcd_idn, downstream_rcd_t, result) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($data['rows'] as $row) {
                $stmt->execute([
                    $reportId,
                    sanitize($row['row_no'] ?? null),
                    sanitize($row['upstream_panel'] ?? null),
                    sanitize($row['upstream_rcd_type'] ?? null),
                    sanitize($row['upstream_rcd_in'] ?? null),
                    sanitize($row['upstream_rcd_idn'] ?? null),
                    sanitize($row['upstream_rcd_dt'] ?? null),
                    sanitize($row['downstream_panel'] ?? null),
                    sanitize($row['downstream_rcd_type'] ?? null),
                    sanitize($row['downstream_rcd_idn'] ?? null),
                    sanitize($row['downstream_rcd_t'] ?? null),
                    sanitize($row['result'] ?? null)
                ]);
            }
        }

        $pdo->commit();
        jsonSuccess(null, 'Ölçümler başarıyla kaydedildi.');
    } else {
        requireMethod(['GET', 'POST']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
