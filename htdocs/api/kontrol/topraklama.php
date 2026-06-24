<?php
// ============================================================
// Topraklama Raporları Endpoint'i
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
                SELECT gr.*, 
                       ap.adi_soyadi as authorized_person_name,
                       d1.device_name as device1_name, d1.serial_no as device1_serial,
                       d2.device_name as device2_name, d2.serial_no as device2_serial
                FROM grounding_reports gr 
                LEFT JOIN authorized_persons ap ON gr.authorized_person_id = ap.id 
                LEFT JOIN measurement_devices d1 ON gr.device1_id = d1.id
                LEFT JOIN measurement_devices d2 ON gr.device2_id = d2.id
                WHERE gr.id = ? AND gr.kurum_id = ?
            ");
            $stmt->execute([$id, $kurumId]);
            $report = $stmt->fetch();
            
            if (!$report) {
                jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);
            }
            jsonSuccess($report);
        } else {
            $stmt = $pdo->prepare("
                SELECT gr.id, gr.report_no, gr.report_date, gr.control_reason, gr.result, 
                       ap.adi_soyadi as authorized_person_name 
                FROM grounding_reports gr 
                LEFT JOIN authorized_persons ap ON gr.authorized_person_id = ap.id 
                WHERE gr.kurum_id = ? 
                ORDER BY gr.id DESC
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
        $reportNo = $instData['il_kodu'] . '-' . $instData['kurum_kodu'] . '-t-' . time();

        $stmt = $pdo->prepare("
            INSERT INTO grounding_reports (
                kurum_id, report_no, report_date, start_date, end_date, next_control_date, isg_katip_id, 
                control_reason, grounding_type, weather, soil_moisture, sebeke_tipi, proje_var_mi, sema_var_mi, 
                yapi_cinsi, protection_measure, changes_exist, prev_label_exists, panel_id, device1_id, device2_id, 
                measurement_method, project_info, prev_control_date, defects, notes, result, result_notes_selection, 
                authorized_person_id
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
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
            sanitize($data['control_reason'] ?? null),
            sanitize($data['grounding_type'] ?? null),
            sanitize($data['weather'] ?? null),
            sanitize($data['soil_moisture'] ?? null),
            sanitize($data['sebeke_tipi'] ?? null),
            toBool($data['proje_var_mi'] ?? 0),
            toBool($data['sema_var_mi'] ?? 0),
            sanitize($data['yapi_cinsi'] ?? null),
            sanitize($data['protection_measure'] ?? null),
            toBool($data['changes_exist'] ?? 0),
            toBool($data['prev_label_exists'] ?? 0),
            sanitize($data['panel_id'] ?? null),
            isValidInteger($data['device1_id'] ?? null) ? $data['device1_id'] : null,
            isValidInteger($data['device2_id'] ?? null) ? $data['device2_id'] : null,
            sanitize($data['measurement_method'] ?? null),
            sanitize($data['project_info'] ?? null),
            isValidDate($data['prev_control_date'] ?? null) ? ($data['prev_control_date'] ?? null) : null,
            sanitize($data['defects'] ?? null),
            sanitize($data['notes'] ?? null),
            sanitize($data['result'] ?? null),
            sanitize($data['result_notes_selection'] ?? null),
            isValidInteger($data['authorized_person_id'] ?? null) ? $data['authorized_person_id'] : null
        ]);
        
        jsonSuccess(['id' => $pdo->lastInsertId(), 'report_no' => $reportNo], 'Topraklama raporu başarıyla oluşturuldu.', 201);

    } elseif ($method === 'PUT') {
        if (!$id) jsonError('VALIDATION_ERROR', 'Rapor ID gereklidir.', 400);

        // Ownership check
        $checkStmt = $pdo->prepare("SELECT id FROM grounding_reports WHERE id = ? AND kurum_id = ?");
        $checkStmt->execute([$id, $kurumId]);
        if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);

        $data = getJsonBody();

        $stmt = $pdo->prepare("
            UPDATE grounding_reports SET 
                report_date=?, start_date=?, end_date=?, next_control_date=?, isg_katip_id=?, control_reason=?, 
                grounding_type=?, weather=?, soil_moisture=?, sebeke_tipi=?, proje_var_mi=?, sema_var_mi=?, 
                yapi_cinsi=?, protection_measure=?, changes_exist=?, prev_label_exists=?, panel_id=?, device1_id=?, 
                device2_id=?, measurement_method=?, project_info=?, prev_control_date=?, defects=?, notes=?, 
                result=?, result_notes_selection=?, authorized_person_id=?
            WHERE id=? AND kurum_id=?
        ");

        $stmt->execute([
            isValidDate($data['report_date'] ?? null) ? ($data['report_date'] ?? null) : null,
            isValidDateTime($data['start_date'] ?? null) ? ($data['start_date'] ?? null) : null,
            isValidDateTime($data['end_date'] ?? null) ? ($data['end_date'] ?? null) : null,
            isValidDate($data['next_control_date'] ?? null) ? ($data['next_control_date'] ?? null) : null,
            sanitize($data['isg_katip_id'] ?? null),
            sanitize($data['control_reason'] ?? null),
            sanitize($data['grounding_type'] ?? null),
            sanitize($data['weather'] ?? null),
            sanitize($data['soil_moisture'] ?? null),
            sanitize($data['sebeke_tipi'] ?? null),
            toBool($data['proje_var_mi'] ?? 0),
            toBool($data['sema_var_mi'] ?? 0),
            sanitize($data['yapi_cinsi'] ?? null),
            sanitize($data['protection_measure'] ?? null),
            toBool($data['changes_exist'] ?? 0),
            toBool($data['prev_label_exists'] ?? 0),
            sanitize($data['panel_id'] ?? null),
            isValidInteger($data['device1_id'] ?? null) ? $data['device1_id'] : null,
            isValidInteger($data['device2_id'] ?? null) ? $data['device2_id'] : null,
            sanitize($data['measurement_method'] ?? null),
            sanitize($data['project_info'] ?? null),
            isValidDate($data['prev_control_date'] ?? null) ? ($data['prev_control_date'] ?? null) : null,
            sanitize($data['defects'] ?? null),
            sanitize($data['notes'] ?? null),
            sanitize($data['result'] ?? null),
            sanitize($data['result_notes_selection'] ?? null),
            isValidInteger($data['authorized_person_id'] ?? null) ? $data['authorized_person_id'] : null,
            $id,
            $kurumId
        ]);
        
        jsonSuccess(null, 'Rapor başarıyla güncellendi.');

    } elseif ($method === 'DELETE') {
        if (!$id) jsonError('VALIDATION_ERROR', 'Rapor ID gereklidir.', 400);

        // Ownership check
        $stmt = $pdo->prepare("DELETE FROM grounding_reports WHERE id = ? AND kurum_id = ?");
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
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
