<?php
// ============================================================
// Yangın Algılama Raporları Endpoint'i
// ============================================================

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';

authenticateRequest();
$kurumId = requireInstitution($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$id = getIdParam();

try {
    if ($method === 'GET') {
        if ($id) {
            $stmt = $pdo->prepare("
                SELECT yr.*, 
                       ap.adi_soyadi as authorized_person_name,
                       d1.device_name as device1_name, d1.serial_no as device1_serial,
                       d2.device_name as device2_name, d2.serial_no as device2_serial
                FROM fire_detection_reports yr 
                LEFT JOIN authorized_persons ap ON yr.authorized_person_id = ap.id 
                LEFT JOIN measurement_devices d1 ON yr.device1_id = d1.id
                LEFT JOIN measurement_devices d2 ON yr.device2_id = d2.id
                WHERE yr.id = ? AND yr.kurum_id = ?
            ");
            $stmt->execute([$id, $kurumId]);
            $report = $stmt->fetch();
            
            if (!$report) jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);

            if ($report['inspection_results']) {
                $report['inspection_results'] = json_decode($report['inspection_results'], true);
            }

            $loopStmt = $pdo->prepare("SELECT * FROM fire_detection_section5_2 WHERE report_id = ? ORDER BY loop_no ASC");
            $loopStmt->execute([$id]);
            $report['loops'] = $loopStmt->fetchAll();

            jsonSuccess($report);
        } else {
            $stmt = $pdo->prepare("
                SELECT yr.id, yr.report_no, yr.report_date, yr.control_reason, yr.result, 
                       ap.adi_soyadi as authorized_person_name 
                FROM fire_detection_reports yr 
                LEFT JOIN authorized_persons ap ON yr.authorized_person_id = ap.id 
                WHERE yr.kurum_id = ? 
                ORDER BY yr.id DESC
            ");
            $stmt->execute([$kurumId]);
            $reports = $stmt->fetchAll();
            jsonSuccess($reports);
        }
    } elseif ($method === 'POST') {
        $data = getJsonBody();
        $action = $_GET['action'] ?? ($data['action'] ?? null);

        if ($action === 'save_inspection') {
            requireFields($data, ['report_id', 'inspection_results', 'loops']);
            $reportId = $data['report_id'];

            // Ownership check
            $checkStmt = $pdo->prepare("SELECT id FROM fire_detection_reports WHERE id = ? AND kurum_id = ?");
            $checkStmt->execute([$reportId, $kurumId]);
            if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);

            $pdo->beginTransaction();

            $updStmt = $pdo->prepare("UPDATE fire_detection_reports SET inspection_results = ? WHERE id = ?");
            $updStmt->execute([json_encode($data['inspection_results']), $reportId]);

            $pdo->prepare("DELETE FROM fire_detection_section5_2 WHERE report_id = ?")->execute([$reportId]);
            if (!empty($data['loops'])) {
                $loopStmt = $pdo->prepare("INSERT INTO fire_detection_section5_2 (report_id, loop_no, bolum_adi, ekipman_adi, projede_mi, erisim_durumu, montaj_durumu, test, sesli_uyari, isikli_uyari, adresleme) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['loops'] as $l) {
                    $loopStmt->execute([
                        $reportId, sanitize($l['loop_no']??null), sanitize($l['bolum_adi']??null), sanitize($l['ekipman_adi']??null),
                        sanitize($l['projede_mi']??null), sanitize($l['erisim_durumu']??null), sanitize($l['montaj_durumu']??null),
                        sanitize($l['test']??null), sanitize($l['sesli_uyari']??null), sanitize($l['isikli_uyari']??null), sanitize($l['adresleme']??null)
                    ]);
                }
            }
            $pdo->commit();
            jsonSuccess(null, 'Denetim sonuçları kaydedildi.');
            exit;
        }

        // Rapor oluşturma
        $inst = getApiInstitution();
        $instStmt = $pdo->prepare("SELECT il_kodu, kurum_kodu FROM institutions WHERE id = ?");
        $instStmt->execute([$kurumId]);
        $instData = $instStmt->fetch();
        $reportNo = $instData['il_kodu'] . '-' . $instData['kurum_kodu'] . '-ya-' . time();

        $stmt = $pdo->prepare("
            INSERT INTO fire_detection_reports (
                kurum_id, report_no, report_date, start_date, end_date, next_control_date, isg_katip_id, 
                algilama_sistemi, uyari_sistemi, sistem_calisma_tipi, proje_onay_kurumu, control_reason, 
                proje_onay_bilgileri, panel_marka_model, ilk_kontrol_tarihi, prev_control_date, weather_condition, 
                ground_moisture, panel_seri_no, panel_calisma_gerilimi, algilama_ekipmanlari, panel_yeri, 
                uyari_ekipmanlari, sondurme_ekipmanlari, installation_change, prev_label_exists, bina_kullanma_sinifi, 
                bina_tehlike_sinifi, tehlike_kategorisi, toplam_alan, kat_sayisi, bina_yuksekligi, yapi_kullanma_izin_tarihi, 
                bolum_sayisi, diger_tespitler, device1_id, device2_id, authorized_person_id, defects, notes, result
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $kurumId, $reportNo,
            isValidDate($data['report_date'] ?? null) ? ($data['report_date'] ?? null) : null,
            isValidDateTime($data['start_date'] ?? null) ? ($data['start_date'] ?? null) : null,
            isValidDateTime($data['end_date'] ?? null) ? ($data['end_date'] ?? null) : null,
            isValidDate($data['next_control_date'] ?? null) ? ($data['next_control_date'] ?? null) : null,
            sanitize($data['isg_katip_id'] ?? null),
            sanitize($data['algilama_sistemi'] ?? null), sanitize($data['uyari_sistemi'] ?? null),
            sanitize($data['sistem_calisma_tipi'] ?? null), sanitize($data['proje_onay_kurumu'] ?? null),
            sanitize($data['control_reason'] ?? null), sanitize($data['proje_onay_bilgileri'] ?? null),
            sanitize($data['panel_marka_model'] ?? null),
            isValidDate($data['ilk_kontrol_tarihi'] ?? null) ? ($data['ilk_kontrol_tarihi'] ?? null) : null,
            isValidDate($data['prev_control_date'] ?? null) ? ($data['prev_control_date'] ?? null) : null,
            sanitize($data['weather_condition'] ?? null), sanitize($data['ground_moisture'] ?? null),
            sanitize($data['panel_seri_no'] ?? null), sanitize($data['panel_calisma_gerilimi'] ?? null),
            sanitize($data['algilama_ekipmanlari'] ?? null), sanitize($data['panel_yeri'] ?? null),
            sanitize($data['uyari_ekipmanlari'] ?? null), sanitize($data['sondurme_ekipmanlari'] ?? null),
            toBool($data['installation_change'] ?? 0), toBool($data['prev_label_exists'] ?? 0),
            sanitize($data['bina_kullanma_sinifi'] ?? null), sanitize($data['bina_tehlike_sinifi'] ?? null),
            sanitize($data['tehlike_kategorisi'] ?? null), sanitize($data['toplam_alan'] ?? null),
            sanitize($data['kat_sayisi'] ?? null), sanitize($data['bina_yuksekligi'] ?? null),
            isValidDate($data['yapi_kullanma_izin_tarihi'] ?? null) ? ($data['yapi_kullanma_izin_tarihi'] ?? null) : null,
            sanitize($data['bolum_sayisi'] ?? null), sanitize($data['diger_tespitler'] ?? null),
            isValidInteger($data['device1_id'] ?? null) ? $data['device1_id'] : null,
            isValidInteger($data['device2_id'] ?? null) ? $data['device2_id'] : null,
            isValidInteger($data['authorized_person_id'] ?? null) ? $data['authorized_person_id'] : null,
            sanitize($data['defects'] ?? null), sanitize($data['notes'] ?? null), sanitize($data['result'] ?? null)
        ]);
        
        jsonSuccess(['id' => $pdo->lastInsertId(), 'report_no' => $reportNo], 'Rapor başarıyla oluşturuldu.', 201);

    } elseif ($method === 'PUT') {
        if (!$id) jsonError('VALIDATION_ERROR', 'Rapor ID gereklidir.', 400);

        // Ownership check
        $checkStmt = $pdo->prepare("SELECT id FROM fire_detection_reports WHERE id = ? AND kurum_id = ?");
        $checkStmt->execute([$id, $kurumId]);
        if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);

        $data = getJsonBody();

        $stmt = $pdo->prepare("
            UPDATE fire_detection_reports SET 
                report_date=?, start_date=?, end_date=?, next_control_date=?, isg_katip_id=?, 
                algilama_sistemi=?, uyari_sistemi=?, sistem_calisma_tipi=?, proje_onay_kurumu=?, control_reason=?, 
                proje_onay_bilgileri=?, panel_marka_model=?, ilk_kontrol_tarihi=?, prev_control_date=?, weather_condition=?, 
                ground_moisture=?, panel_seri_no=?, panel_calisma_gerilimi=?, algilama_ekipmanlari=?, panel_yeri=?, 
                uyari_ekipmanlari=?, sondurme_ekipmanlari=?, installation_change=?, prev_label_exists=?, bina_kullanma_sinifi=?, 
                bina_tehlike_sinifi=?, tehlike_kategorisi=?, toplam_alan=?, kat_sayisi=?, bina_yuksekligi=?, yapi_kullanma_izin_tarihi=?, 
                bolum_sayisi=?, diger_tespitler=?, device1_id=?, device2_id=?, authorized_person_id=?, defects=?, notes=?, result=?
            WHERE id=? AND kurum_id=?
        ");

        $stmt->execute([
            isValidDate($data['report_date'] ?? null) ? ($data['report_date'] ?? null) : null,
            isValidDateTime($data['start_date'] ?? null) ? ($data['start_date'] ?? null) : null,
            isValidDateTime($data['end_date'] ?? null) ? ($data['end_date'] ?? null) : null,
            isValidDate($data['next_control_date'] ?? null) ? ($data['next_control_date'] ?? null) : null,
            sanitize($data['isg_katip_id'] ?? null),
            sanitize($data['algilama_sistemi'] ?? null), sanitize($data['uyari_sistemi'] ?? null),
            sanitize($data['sistem_calisma_tipi'] ?? null), sanitize($data['proje_onay_kurumu'] ?? null),
            sanitize($data['control_reason'] ?? null), sanitize($data['proje_onay_bilgileri'] ?? null),
            sanitize($data['panel_marka_model'] ?? null),
            isValidDate($data['ilk_kontrol_tarihi'] ?? null) ? ($data['ilk_kontrol_tarihi'] ?? null) : null,
            isValidDate($data['prev_control_date'] ?? null) ? ($data['prev_control_date'] ?? null) : null,
            sanitize($data['weather_condition'] ?? null), sanitize($data['ground_moisture'] ?? null),
            sanitize($data['panel_seri_no'] ?? null), sanitize($data['panel_calisma_gerilimi'] ?? null),
            sanitize($data['algilama_ekipmanlari'] ?? null), sanitize($data['panel_yeri'] ?? null),
            sanitize($data['uyari_ekipmanlari'] ?? null), sanitize($data['sondurme_ekipmanlari'] ?? null),
            toBool($data['installation_change'] ?? 0), toBool($data['prev_label_exists'] ?? 0),
            sanitize($data['bina_kullanma_sinifi'] ?? null), sanitize($data['bina_tehlike_sinifi'] ?? null),
            sanitize($data['tehlike_kategorisi'] ?? null), sanitize($data['toplam_alan'] ?? null),
            sanitize($data['kat_sayisi'] ?? null), sanitize($data['bina_yuksekligi'] ?? null),
            isValidDate($data['yapi_kullanma_izin_tarihi'] ?? null) ? ($data['yapi_kullanma_izin_tarihi'] ?? null) : null,
            sanitize($data['bolum_sayisi'] ?? null), sanitize($data['diger_tespitler'] ?? null),
            isValidInteger($data['device1_id'] ?? null) ? $data['device1_id'] : null,
            isValidInteger($data['device2_id'] ?? null) ? $data['device2_id'] : null,
            isValidInteger($data['authorized_person_id'] ?? null) ? $data['authorized_person_id'] : null,
            sanitize($data['defects'] ?? null), sanitize($data['notes'] ?? null), sanitize($data['result'] ?? null),
            $id, $kurumId
        ]);
        
        jsonSuccess(null, 'Rapor başarıyla güncellendi.');

    } elseif ($method === 'DELETE') {
        if (!$id) jsonError('VALIDATION_ERROR', 'Rapor ID gereklidir.', 400);

        // Ownership check
        $stmt = $pdo->prepare("DELETE FROM fire_detection_reports WHERE id = ? AND kurum_id = ?");
        $stmt->execute([$id, $kurumId]);
        
        if ($stmt->rowCount() > 0) {
            jsonSuccess(null, 'Rapor başarıyla silindi.');
        } else {
            jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);
        }
    } else {
        requireMethod(['GET', 'POST', 'PUT', 'DELETE']);
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
