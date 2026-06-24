<?php
// ============================================================
// Yıldırımdan Korunma Raporları Endpoint'i
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
                SELECT lr.*, 
                       ap.adi_soyadi as authorized_person_name,
                       d1.device_name as device1_name, d1.serial_no as device1_serial,
                       d2.device_name as device2_name, d2.serial_no as device2_serial,
                       tc.device_name as thermal_camera_name, tc.serial_no as thermal_camera_serial
                FROM lightning_protection_reports lr 
                LEFT JOIN authorized_persons ap ON lr.authorized_person_id = ap.id 
                LEFT JOIN measurement_devices d1 ON lr.device1_id = d1.id
                LEFT JOIN measurement_devices d2 ON lr.device2_id = d2.id
                LEFT JOIN measurement_devices tc ON lr.thermal_camera_id = tc.id
                WHERE lr.id = ? AND lr.kurum_id = ?
            ");
            $stmt->execute([$id, $kurumId]);
            $report = $stmt->fetch();
            
            if (!$report) jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);

            // Fetch section 4 checklist
            $sec4Stmt = $pdo->prepare("SELECT question_key, answer FROM lightning_protection_section4 WHERE report_id = ?");
            $sec4Stmt->execute([$id]);
            $report['section4'] = $sec4Stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            jsonSuccess($report);
        } else {
            $stmt = $pdo->prepare("
                SELECT lr.id, lr.report_no, lr.report_date, lr.control_reason, lr.result, 
                       ap.adi_soyadi as authorized_person_name 
                FROM lightning_protection_reports lr 
                LEFT JOIN authorized_persons ap ON lr.authorized_person_id = ap.id 
                WHERE lr.kurum_id = ? 
                ORDER BY lr.id DESC
            ");
            $stmt->execute([$kurumId]);
            $reports = $stmt->fetchAll();
            jsonSuccess($reports);
        }
    } elseif ($method === 'POST') {
        $data = getJsonBody();
        
        $inst = getApiInstitution();
        $instStmt = $pdo->prepare("SELECT il_kodu, kurum_kodu FROM institutions WHERE id = ?");
        $instStmt->execute([$kurumId]);
        $instData = $instStmt->fetch();
        $reportNo = $instData['il_kodu'] . '-' . $instData['kurum_kodu'] . '-yk-' . time();

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO lightning_protection_reports (
                kurum_id, report_no, report_date, start_date, end_date, next_control_date, isg_katip_id, 
                energy_provider, sebeke_tipi, sebeke_voltage, has_project, project_details, has_risk_analysis, 
                control_reason, grounding_type, building_type, usage_purpose_yks_type, prev_control_date, 
                weather_condition, ground_moisture, installation_change, prev_label_exists, equipment_identification, 
                protection_system_type, protection_level_eps, building_usage_details, thermal_camera_id, 
                device1_id, device2_id, authorized_person_id, defects, notes, result, result_notes_selection
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $kurumId,
            $reportNo,
            isValidDate($data['report_date'] ?? null) ? ($data['report_date'] ?? null) : null,
            isValidDateTime($data['start_date'] ?? null) ? ($data['start_date'] ?? null) : null,
            isValidDateTime($data['end_date'] ?? null) ? ($data['end_date'] ?? null) : null,
            isValidDate($data['next_control_date'] ?? null) ? ($data['next_control_date'] ?? null) : null,
            sanitize($data['isg_katip_id'] ?? null),
            sanitize($data['energy_provider'] ?? null),
            sanitize($data['sebeke_tipi'] ?? null),
            sanitize($data['sebeke_voltage'] ?? null),
            toBool($data['has_project'] ?? 0),
            sanitize($data['project_details'] ?? null),
            toBool($data['has_risk_analysis'] ?? 0),
            sanitize($data['control_reason'] ?? null),
            sanitize($data['grounding_type'] ?? null),
            sanitize($data['building_type'] ?? null),
            sanitize($data['usage_purpose_yks_type'] ?? null),
            isValidDate($data['prev_control_date'] ?? null) ? ($data['prev_control_date'] ?? null) : null,
            sanitize($data['weather_condition'] ?? null),
            sanitize($data['ground_moisture'] ?? null),
            toBool($data['installation_change'] ?? 0),
            toBool($data['prev_label_exists'] ?? 0),
            sanitize($data['equipment_identification'] ?? null),
            sanitize($data['protection_system_type'] ?? null),
            sanitize($data['protection_level_eps'] ?? null),
            sanitize($data['building_usage_details'] ?? null),
            isValidInteger($data['thermal_camera_id'] ?? null) ? $data['thermal_camera_id'] : null,
            isValidInteger($data['device1_id'] ?? null) ? $data['device1_id'] : null,
            isValidInteger($data['device2_id'] ?? null) ? $data['device2_id'] : null,
            isValidInteger($data['authorized_person_id'] ?? null) ? $data['authorized_person_id'] : null,
            sanitize($data['defects'] ?? null),
            sanitize($data['notes'] ?? null),
            sanitize($data['result'] ?? null),
            sanitize($data['result_notes_selection'] ?? null)
        ]);
        
        $newId = $pdo->lastInsertId();

        // Save section4 checklist
        if (isset($data['section4']) && is_array($data['section4'])) {
            $sec4Stmt = $pdo->prepare("INSERT INTO lightning_protection_section4 (report_id, question_key, answer) VALUES (?, ?, ?)");
            foreach ($data['section4'] as $item) {
                if (isset($item['question_key'], $item['answer'])) {
                    $sec4Stmt->execute([$newId, sanitize($item['question_key']), sanitize($item['answer'])]);
                }
            }
        }

        $pdo->commit();
        jsonSuccess(['id' => $newId, 'report_no' => $reportNo], 'Rapor başarıyla oluşturuldu.', 201);

    } elseif ($method === 'PUT') {
        if (!$id) jsonError('VALIDATION_ERROR', 'Rapor ID gereklidir.', 400);

        // Ownership check
        $checkStmt = $pdo->prepare("SELECT id FROM lightning_protection_reports WHERE id = ? AND kurum_id = ?");
        $checkStmt->execute([$id, $kurumId]);
        if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);

        $data = getJsonBody();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE lightning_protection_reports SET 
                report_date=?, start_date=?, end_date=?, next_control_date=?, isg_katip_id=?, 
                energy_provider=?, sebeke_tipi=?, sebeke_voltage=?, has_project=?, project_details=?, has_risk_analysis=?, 
                control_reason=?, grounding_type=?, building_type=?, usage_purpose_yks_type=?, prev_control_date=?, 
                weather_condition=?, ground_moisture=?, installation_change=?, prev_label_exists=?, equipment_identification=?, 
                protection_system_type=?, protection_level_eps=?, building_usage_details=?, thermal_camera_id=?, 
                device1_id=?, device2_id=?, authorized_person_id=?, defects=?, notes=?, result=?, result_notes_selection=?
            WHERE id=? AND kurum_id=?
        ");

        $stmt->execute([
            isValidDate($data['report_date'] ?? null) ? ($data['report_date'] ?? null) : null,
            isValidDateTime($data['start_date'] ?? null) ? ($data['start_date'] ?? null) : null,
            isValidDateTime($data['end_date'] ?? null) ? ($data['end_date'] ?? null) : null,
            isValidDate($data['next_control_date'] ?? null) ? ($data['next_control_date'] ?? null) : null,
            sanitize($data['isg_katip_id'] ?? null),
            sanitize($data['energy_provider'] ?? null),
            sanitize($data['sebeke_tipi'] ?? null),
            sanitize($data['sebeke_voltage'] ?? null),
            toBool($data['has_project'] ?? 0),
            sanitize($data['project_details'] ?? null),
            toBool($data['has_risk_analysis'] ?? 0),
            sanitize($data['control_reason'] ?? null),
            sanitize($data['grounding_type'] ?? null),
            sanitize($data['building_type'] ?? null),
            sanitize($data['usage_purpose_yks_type'] ?? null),
            isValidDate($data['prev_control_date'] ?? null) ? ($data['prev_control_date'] ?? null) : null,
            sanitize($data['weather_condition'] ?? null),
            sanitize($data['ground_moisture'] ?? null),
            toBool($data['installation_change'] ?? 0),
            toBool($data['prev_label_exists'] ?? 0),
            sanitize($data['equipment_identification'] ?? null),
            sanitize($data['protection_system_type'] ?? null),
            sanitize($data['protection_level_eps'] ?? null),
            sanitize($data['building_usage_details'] ?? null),
            isValidInteger($data['thermal_camera_id'] ?? null) ? $data['thermal_camera_id'] : null,
            isValidInteger($data['device1_id'] ?? null) ? $data['device1_id'] : null,
            isValidInteger($data['device2_id'] ?? null) ? $data['device2_id'] : null,
            isValidInteger($data['authorized_person_id'] ?? null) ? $data['authorized_person_id'] : null,
            sanitize($data['defects'] ?? null),
            sanitize($data['notes'] ?? null),
            sanitize($data['result'] ?? null),
            sanitize($data['result_notes_selection'] ?? null),
            $id,
            $kurumId
        ]);
        
        // Save section4 checklist
        if (isset($data['section4']) && is_array($data['section4'])) {
            $pdo->prepare("DELETE FROM lightning_protection_section4 WHERE report_id = ?")->execute([$id]);
            $sec4Stmt = $pdo->prepare("INSERT INTO lightning_protection_section4 (report_id, question_key, answer) VALUES (?, ?, ?)");
            foreach ($data['section4'] as $item) {
                if (isset($item['question_key'], $item['answer'])) {
                    $sec4Stmt->execute([$id, sanitize($item['question_key']), sanitize($item['answer'])]);
                }
            }
        }

        $pdo->commit();
        jsonSuccess(null, 'Rapor başarıyla güncellendi.');

    } elseif ($method === 'DELETE') {
        if (!$id) jsonError('VALIDATION_ERROR', 'Rapor ID gereklidir.', 400);

        // Ownership check
        $stmt = $pdo->prepare("DELETE FROM lightning_protection_reports WHERE id = ? AND kurum_id = ?");
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
