<?php
// ============================================================
// İç Tesisat Panoları ve Ölçümleri Endpoint'i
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

if (!$reportId && $method === 'GET') {
    jsonError('VALIDATION_ERROR', 'report_id parametresi gereklidir.', 400);
}

// Ortak fonksiyon: Pano silinirken fotoğrafları da sil (diskten)
function deletePanelPhotos($pdo, $panelId) {
    $stmt = $pdo->prepare("SELECT file_path FROM ic_tesisat_photos WHERE panel_id = ?");
    $stmt->execute([$panelId]);
    while ($photo = $stmt->fetch()) {
        $fullPath = __DIR__ . '/../../' . $photo['file_path'];
        if (file_exists($fullPath)) unlink($fullPath);
    }
}

try {
    if ($method === 'GET') {
        // Rapor sahipliğini doğrula
        $checkStmt = $pdo->prepare("SELECT id FROM internal_installation_reports WHERE id = ? AND kurum_id = ?");
        $checkStmt->execute([$reportId, $kurumId]);
        if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı veya yetkiniz yok.', 404);

        $result = [
            'panels' => [],
            'section6_header' => null,
            'section6_2_rows' => [],
            'section6_3_rows' => []
        ];

        // Report-level veriler
        $stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_header WHERE report_id = ?");
        $stmt->execute([$reportId]);
        $result['section6_header'] = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_2_rows WHERE report_id = ? ORDER BY no_col ASC");
        $stmt->execute([$reportId]);
        $result['section6_2_rows'] = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_3_rows WHERE report_id = ? ORDER BY no_col ASC");
        $stmt->execute([$reportId]);
        $result['section6_3_rows'] = $stmt->fetchAll();

        // Panolar
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

    } elseif ($method === 'POST') {
        $action = $data['action'] ?? null;
        if (!$action) jsonError('VALIDATION_ERROR', 'action belirtilmelidir.', 400);

        // Rapor sahipliğini doğrula (add_panel vb. için)
        if ($reportId) {
            $checkStmt = $pdo->prepare("SELECT id FROM internal_installation_reports WHERE id = ? AND kurum_id = ?");
            $checkStmt->execute([$reportId, $kurumId]);
            if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı veya yetkiniz yok.', 404);
        }

        $pdo->beginTransaction();

        if ($action === 'add_panel') {
            requireFields($data, ['report_id', 'panel_name']);
            $stmt = $pdo->prepare("INSERT INTO ic_tesisat_panels (report_id, panel_name, panel_order) VALUES (?, ?, ?)");
            $stmt->execute([$reportId, sanitize($data['panel_name']), (int)($data['panel_order'] ?? 0)]);
            $panelId = $pdo->lastInsertId();
            $pdo->commit();
            jsonSuccess(['panel_id' => $panelId], 'Pano eklendi.');

        } elseif ($action === 'delete_panel') {
            requireFields($data, ['panel_id']);
            $panelId = $data['panel_id'];
            
            // Pano sahiplik doğrula
            $stmt = $pdo->prepare("SELECT r.kurum_id FROM ic_tesisat_panels p JOIN internal_installation_reports r ON p.report_id = r.id WHERE p.id = ?");
            $stmt->execute([$panelId]);
            if ($stmt->fetchColumn() != $kurumId) {
                $pdo->rollBack();
                jsonError('FORBIDDEN', 'Yetkiniz yok.', 403);
            }
            
            deletePanelPhotos($pdo, $panelId);
            $pdo->prepare("DELETE FROM ic_tesisat_panels WHERE id = ?")->execute([$panelId]);
            $pdo->commit();
            jsonSuccess(null, 'Pano silindi.');

        } elseif ($action === 'save_section5') {
            requireFields($data, ['panel_id', 'answers']);
            $panelId = $data['panel_id'];
            
            $pdo->prepare("DELETE FROM ic_tesisat_section5 WHERE panel_id = ?")->execute([$panelId]);
            $stmt = $pdo->prepare("INSERT INTO ic_tesisat_section5 (panel_id, question_key, answer) VALUES (?, ?, ?)");
            foreach ($data['answers'] as $item) {
                if (isset($item['question_key'], $item['answer'])) {
                    $stmt->execute([$panelId, sanitize($item['question_key']), sanitize($item['answer'])]);
                }
            }
            $pdo->commit();
            jsonSuccess(null, 'Section 5 kaydedildi.');

        } elseif ($action === 'save_section6_header') {
            requireFields($data, ['report_id']);
            $stmt = $pdo->prepare("SELECT id FROM ic_tesisat_section6_header WHERE report_id = ?");
            $stmt->execute([$reportId]);
            if ($stmt->fetch()) {
                $updStmt = $pdo->prepare("UPDATE ic_tesisat_section6_header SET measurement_method=? WHERE report_id=?");
                $updStmt->execute([sanitize($data['measurement_method'] ?? null), $reportId]);
            } else {
                $insStmt = $pdo->prepare("INSERT INTO ic_tesisat_section6_header (report_id, measurement_method) VALUES (?, ?)");
                $insStmt->execute([$reportId, sanitize($data['measurement_method'] ?? null)]);
            }
            $pdo->commit();
            jsonSuccess(null, 'Section 6 header kaydedildi.');

        } elseif ($action === 'save_section6_1') {
            requireFields($data, ['panel_id', 'header']);
            $panelId = $data['panel_id'];
            $h = $data['header'];
            
            $pdo->prepare("DELETE FROM ic_tesisat_section6_1 WHERE panel_id = ?")->execute([$panelId]);
            $insStmt = $pdo->prepare("INSERT INTO ic_tesisat_section6_1 (panel_id, zx, zln, voltage_ff, voltage_ln, voltage_npe, short_circuit_3ph, dkd_type, dkd_current) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insStmt->execute([$panelId, sanitize($h['zx']??null), sanitize($h['zln']??null), sanitize($h['voltage_ff']??null), sanitize($h['voltage_ln']??null), sanitize($h['voltage_npe']??null), sanitize($h['short_circuit_3ph']??null), sanitize($h['dkd_type']??null), sanitize($h['dkd_current']??null)]);
            
            $pdo->prepare("DELETE FROM ic_tesisat_section6_1_rows WHERE panel_id = ?")->execute([$panelId]);
            if (!empty($data['rows'])) {
                $rStmt = $pdo->prepare("INSERT INTO ic_tesisat_section6_1_rows (panel_id, no_col, linye_adi, acma_egrisi, kutup_sayisi, in_a, icu, faz_kesiti, npen_kesiti, pe_kesiti, ib_tasarim, iz_kapasite, rcd_ia, rcd_ta, sonuc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['rows'] as $r) {
                    $rStmt->execute([$panelId, sanitize($r['no_col']??null), sanitize($r['linye_adi']??null), sanitize($r['acma_egrisi']??null), sanitize($r['kutup_sayisi']??null), sanitize($r['in_a']??null), sanitize($r['icu']??null), sanitize($r['faz_kesiti']??null), sanitize($r['npen_kesiti']??null), sanitize($r['pe_kesiti']??null), sanitize($r['ib_tasarim']??null), sanitize($r['iz_kapasite']??null), sanitize($r['rcd_ia']??null), sanitize($r['rcd_ta']??null), sanitize($r['sonuc']??null)]);
                }
            }
            $pdo->commit();
            jsonSuccess(null, 'Section 6.1 kaydedildi.');

        } elseif ($action === 'save_section6_2') {
            requireFields($data, ['report_id']);
            $pdo->prepare("DELETE FROM ic_tesisat_section6_2_rows WHERE report_id = ?")->execute([$reportId]);
            if (!empty($data['rows'])) {
                $stmt = $pdo->prepare("INSERT INTO ic_tesisat_section6_2_rows (report_id, no_col, bolum, pd_kesiti, pd_sureklilik, tpd_kesiti, tpd_sureklilik, sonuc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['rows'] as $r) {
                    $stmt->execute([$reportId, sanitize($r['no_col']??null), sanitize($r['bolum']??null), sanitize($r['pd_kesiti']??null), sanitize($r['pd_sureklilik']??null), sanitize($r['tpd_kesiti']??null), sanitize($r['tpd_sureklilik']??null), sanitize($r['sonuc']??null)]);
                }
            }
            $pdo->commit();
            jsonSuccess(null, 'Section 6.2 kaydedildi.');

        } elseif ($action === 'save_section6_3') {
            requireFields($data, ['report_id']);
            $pdo->prepare("DELETE FROM ic_tesisat_section6_3_rows WHERE report_id = ?")->execute([$reportId]);
            if (!empty($data['rows'])) {
                $stmt = $pdo->prepare("INSERT INTO ic_tesisat_section6_3_rows (report_id, no_col, hali_yeri, eni, boyu, direnc, sonuc) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['rows'] as $r) {
                    $stmt->execute([$reportId, sanitize($r['no_col']??null), sanitize($r['hali_yeri']??null), sanitize($r['eni']??null), sanitize($r['boyu']??null), sanitize($r['direnc']??null), sanitize($r['sonuc']??null)]);
                }
            }
            $pdo->commit();
            jsonSuccess(null, 'Section 6.3 kaydedildi.');

        } else {
            $pdo->rollBack();
            jsonError('VALIDATION_ERROR', 'Geçersiz action.', 400);
        }

    } else {
        requireMethod(['GET', 'POST']);
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
