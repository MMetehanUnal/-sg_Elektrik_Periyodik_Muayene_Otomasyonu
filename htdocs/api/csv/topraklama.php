<?php
// ============================================================
// Topraklama CSV Şablon ve Yükleme Endpoint'i
// ============================================================

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';

authenticateRequest();
$kurumId = requireInstitution($pdo);
$method = $_SERVER['REQUEST_METHOD'];

$action = $_GET['action'] ?? null;
$type = $_GET['type'] ?? null;

if (!in_array($type, ['5_1', '5_2'])) {
    jsonError('VALIDATION_ERROR', 'Geçerli bir type belirtilmelidir (5_1 veya 5_2).', 400);
}

try {
    if ($method === 'GET' && $action === 'download') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="topraklama_' . $type . '_sablon.csv"');
        
        $output = fopen('php://output', 'w');
        fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); // UTF-8 BOM

        if ($type === '5_1') {
            fputcsv($output, ['point_no', 'point_name', 'prot_in', 'prot_type', 'prot_ia', 'prot_ik1', 'measured_zx_rx', 'limit_zs_ra', 'rcd_type_limits', 'rcd_test_ia', 'rcd_test_ta', 'result']);
            fputcsv($output, ['1', 'Örnek Nokta', '16', 'C', '160', '250', '0.5', '1.43', '30mA', '30', '25', 'Uygun']);
        } else {
            fputcsv($output, ['row_no', 'upstream_panel', 'upstream_rcd_type', 'upstream_rcd_in', 'upstream_rcd_idn', 'upstream_rcd_dt', 'downstream_panel', 'downstream_rcd_type', 'downstream_rcd_idn', 'downstream_rcd_t', 'result']);
            fputcsv($output, ['1', 'Ana Pano', 'A Tipi', '40', '30', '300', 'Tali Pano', 'AC Tipi', '30', '30', 'Uygun']);
        }

        fclose($output);
        exit;

    } elseif ($method === 'POST') {
        $reportId = $_GET['report_id'] ?? null;
        if (!$reportId) jsonError('VALIDATION_ERROR', 'report_id gereklidir.', 400);

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            jsonError('VALIDATION_ERROR', 'Lütfen geçerli bir CSV dosyası seçin.', 400);
        }

        // Rapor sahipliğini doğrula
        $checkStmt = $pdo->prepare("SELECT id FROM grounding_reports WHERE id = ? AND kurum_id = ?");
        $checkStmt->execute([$reportId, $kurumId]);
        if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı veya yetkiniz yok.', 404);

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        if ($handle === false) jsonError('SERVER_ERROR', 'CSV dosyası okunamadı.', 500);

        // İlk satırı atla (başlık)
        fgetcsv($handle, 1000, ",");

        $pdo->beginTransaction();
        $importedCount = 0;

        if ($type === '5_1') {
            $pdo->prepare("DELETE FROM measurements_5_1 WHERE report_id = ?")->execute([$reportId]);
            $stmt = $pdo->prepare("INSERT INTO measurements_5_1 (report_id, point_no, point_name, prot_in, prot_type, prot_ia, prot_ik1, measured_zx_rx, limit_zs_ra, rcd_type_limits, rcd_test_ia, rcd_test_ta, result) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (count($data) >= 12) {
                    $stmt->execute([
                        $reportId, sanitize($data[0]), sanitize($data[1]), sanitize($data[2]), sanitize($data[3]), sanitize($data[4]), sanitize($data[5]), sanitize($data[6]), sanitize($data[7]), sanitize($data[8]), sanitize($data[9]), sanitize($data[10]), sanitize($data[11])
                    ]);
                    $importedCount++;
                }
            }
        } else {
            $pdo->prepare("DELETE FROM measurements_5_2 WHERE report_id = ?")->execute([$reportId]);
            $stmt = $pdo->prepare("INSERT INTO measurements_5_2 (report_id, row_no, upstream_panel, upstream_rcd_type, upstream_rcd_in, upstream_rcd_idn, upstream_rcd_dt, downstream_panel, downstream_rcd_type, downstream_rcd_idn, downstream_rcd_t, result) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (count($data) >= 11) {
                    $stmt->execute([
                        $reportId, sanitize($data[0]), sanitize($data[1]), sanitize($data[2]), sanitize($data[3]), sanitize($data[4]), sanitize($data[5]), sanitize($data[6]), sanitize($data[7]), sanitize($data[8]), sanitize($data[9]), sanitize($data[10])
                    ]);
                    $importedCount++;
                }
            }
        }

        fclose($handle);
        $pdo->commit();

        jsonSuccess(['imported_rows' => $importedCount], $importedCount . ' satır başarıyla içe aktarıldı.');

    } else {
        requireMethod(['GET', 'POST']);
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
