<?php
// ============================================================
// İç Tesisat Raporları Endpoint'i
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
                SELECT ir.*, 
                       ap.adi_soyadi as authorized_person_name,
                       d1.device_name as device1_name, d1.serial_no as device1_serial,
                       d2.device_name as device2_name, d2.serial_no as device2_serial,
                       tc.device_name as thermal_camera_name, tc.serial_no as thermal_camera_serial
                FROM internal_installation_reports ir 
                LEFT JOIN authorized_persons ap ON ir.authorized_person_id = ap.id 
                LEFT JOIN measurement_devices d1 ON ir.device1_id = d1.id
                LEFT JOIN measurement_devices d2 ON ir.device2_id = d2.id
                LEFT JOIN measurement_devices tc ON ir.thermal_camera_id = tc.id
                WHERE ir.id = ? AND ir.kurum_id = ?
            ");
            $stmt->execute([$id, $kurumId]);
            $report = $stmt->fetch();
            
            if (!$report) {
                jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);
            }
            jsonSuccess($report);
        } else {
            $stmt = $pdo->prepare("
                SELECT ir.id, ir.report_no, ir.report_date, ir.control_reason, ir.result, 
                       ap.adi_soyadi as authorized_person_name 
                FROM internal_installation_reports ir 
                LEFT JOIN authorized_persons ap ON ir.authorized_person_id = ap.id 
                WHERE ir.kurum_id = ? 
                ORDER BY ir.id DESC
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
        $reportNo = $instData['il_kodu'] . '-' . $instData['kurum_kodu'] . '-it-' . time();

        $stmt = $pdo->prepare("
            INSERT INTO internal_installation_reports (
                kurum_id, report_no, report_date, energy_provider, sebeke_tipi, proje_var_mi, sema_var_mi, 
                start_date, end_date, next_control_date, isg_katip_id, control_reason, grounding_type, 
                building_type, usage_purpose, prev_control_date, weather_condition, ground_moisture, 
                phase_count_type, conductor_type, grounding_resistance, additional_electrode_details, 
                system_grounding_conductor, main_equipotential_conductor, nominal_voltage_kV, nominal_frequency_Hz, 
                fault_current_kA, external_loop_impedance, main_rcd_rating, main_breaker_type, main_breaker_rating, 
                main_rcd_test_mA, main_rcd_test_ms, installation_change, has_spd, protection_measures, 
                prev_label_exists, thermal_camera_id, device1_id, device2_id, authorized_person_id, 
                defects, notes, result, result_notes_selection
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $kurumId,
            $reportNo,
            isValidDate($data['report_date'] ?? null) ? ($data['report_date'] ?? null) : null,
            sanitize($data['energy_provider'] ?? null),
            sanitize($data['sebeke_tipi'] ?? null),
            toBool($data['proje_var_mi'] ?? 0),
            toBool($data['sema_var_mi'] ?? 0),
            isValidDateTime($data['start_date'] ?? null) ? ($data['start_date'] ?? null) : null,
            isValidDateTime($data['end_date'] ?? null) ? ($data['end_date'] ?? null) : null,
            isValidDate($data['next_control_date'] ?? null) ? ($data['next_control_date'] ?? null) : null,
            sanitize($data['isg_katip_id'] ?? null),
            sanitize($data['control_reason'] ?? null),
            sanitize($data['grounding_type'] ?? null),
            sanitize($data['building_type'] ?? null),
            sanitize($data['usage_purpose'] ?? null),
            isValidDate($data['prev_control_date'] ?? null) ? ($data['prev_control_date'] ?? null) : null,
            sanitize($data['weather_condition'] ?? null),
            sanitize($data['ground_moisture'] ?? null),
            sanitize($data['phase_count_type'] ?? null),
            sanitize($data['conductor_type'] ?? null),
            sanitize($data['grounding_resistance'] ?? null),
            sanitize($data['additional_electrode_details'] ?? null),
            sanitize($data['system_grounding_conductor'] ?? null),
            sanitize($data['main_equipotential_conductor'] ?? null),
            sanitize($data['nominal_voltage_kV'] ?? null),
            sanitize($data['nominal_frequency_Hz'] ?? null),
            sanitize($data['fault_current_kA'] ?? null),
            sanitize($data['external_loop_impedance'] ?? null),
            sanitize($data['main_rcd_rating'] ?? null),
            sanitize($data['main_breaker_type'] ?? null),
            sanitize($data['main_breaker_rating'] ?? null),
            sanitize($data['main_rcd_test_mA'] ?? null),
            sanitize($data['main_rcd_test_ms'] ?? null),
            toBool($data['installation_change'] ?? 0),
            toBool($data['has_spd'] ?? 0),
            sanitize($data['protection_measures'] ?? null),
            toBool($data['prev_label_exists'] ?? 0),
            isValidInteger($data['thermal_camera_id'] ?? null) ? $data['thermal_camera_id'] : null,
            isValidInteger($data['device1_id'] ?? null) ? $data['device1_id'] : null,
            isValidInteger($data['device2_id'] ?? null) ? $data['device2_id'] : null,
            isValidInteger($data['authorized_person_id'] ?? null) ? $data['authorized_person_id'] : null,
            sanitize($data['defects'] ?? null),
            sanitize($data['notes'] ?? null),
            sanitize($data['result'] ?? null),
            sanitize($data['result_notes_selection'] ?? null)
        ]);
        
        jsonSuccess(['id' => $pdo->lastInsertId(), 'report_no' => $reportNo], 'İç tesisat raporu başarıyla oluşturuldu.', 201);

    } elseif ($method === 'PUT') {
        if (!$id) jsonError('VALIDATION_ERROR', 'Rapor ID gereklidir.', 400);

        // Ownership check
        $checkStmt = $pdo->prepare("SELECT id FROM internal_installation_reports WHERE id = ? AND kurum_id = ?");
        $checkStmt->execute([$id, $kurumId]);
        if (!$checkStmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı.', 404);

        $data = getJsonBody();

        $stmt = $pdo->prepare("
            UPDATE internal_installation_reports SET 
                report_date=?, energy_provider=?, sebeke_tipi=?, proje_var_mi=?, sema_var_mi=?, 
                start_date=?, end_date=?, next_control_date=?, isg_katip_id=?, control_reason=?, grounding_type=?, 
                building_type=?, usage_purpose=?, prev_control_date=?, weather_condition=?, ground_moisture=?, 
                phase_count_type=?, conductor_type=?, grounding_resistance=?, additional_electrode_details=?, 
                system_grounding_conductor=?, main_equipotential_conductor=?, nominal_voltage_kV=?, nominal_frequency_Hz=?, 
                fault_current_kA=?, external_loop_impedance=?, main_rcd_rating=?, main_breaker_type=?, main_breaker_rating=?, 
                main_rcd_test_mA=?, main_rcd_test_ms=?, installation_change=?, has_spd=?, protection_measures=?, 
                prev_label_exists=?, thermal_camera_id=?, device1_id=?, device2_id=?, authorized_person_id=?, 
                defects=?, notes=?, result=?, result_notes_selection=?
            WHERE id=? AND kurum_id=?
        ");

        $stmt->execute([
            isValidDate($data['report_date'] ?? null) ? ($data['report_date'] ?? null) : null,
            sanitize($data['energy_provider'] ?? null),
            sanitize($data['sebeke_tipi'] ?? null),
            toBool($data['proje_var_mi'] ?? 0),
            toBool($data['sema_var_mi'] ?? 0),
            isValidDateTime($data['start_date'] ?? null) ? ($data['start_date'] ?? null) : null,
            isValidDateTime($data['end_date'] ?? null) ? ($data['end_date'] ?? null) : null,
            isValidDate($data['next_control_date'] ?? null) ? ($data['next_control_date'] ?? null) : null,
            sanitize($data['isg_katip_id'] ?? null),
            sanitize($data['control_reason'] ?? null),
            sanitize($data['grounding_type'] ?? null),
            sanitize($data['building_type'] ?? null),
            sanitize($data['usage_purpose'] ?? null),
            isValidDate($data['prev_control_date'] ?? null) ? ($data['prev_control_date'] ?? null) : null,
            sanitize($data['weather_condition'] ?? null),
            sanitize($data['ground_moisture'] ?? null),
            sanitize($data['phase_count_type'] ?? null),
            sanitize($data['conductor_type'] ?? null),
            sanitize($data['grounding_resistance'] ?? null),
            sanitize($data['additional_electrode_details'] ?? null),
            sanitize($data['system_grounding_conductor'] ?? null),
            sanitize($data['main_equipotential_conductor'] ?? null),
            sanitize($data['nominal_voltage_kV'] ?? null),
            sanitize($data['nominal_frequency_Hz'] ?? null),
            sanitize($data['fault_current_kA'] ?? null),
            sanitize($data['external_loop_impedance'] ?? null),
            sanitize($data['main_rcd_rating'] ?? null),
            sanitize($data['main_breaker_type'] ?? null),
            sanitize($data['main_breaker_rating'] ?? null),
            sanitize($data['main_rcd_test_mA'] ?? null),
            sanitize($data['main_rcd_test_ms'] ?? null),
            toBool($data['installation_change'] ?? 0),
            toBool($data['has_spd'] ?? 0),
            sanitize($data['protection_measures'] ?? null),
            toBool($data['prev_label_exists'] ?? 0),
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
        
        jsonSuccess(null, 'Rapor başarıyla güncellendi.');

    } elseif ($method === 'DELETE') {
        if (!$id) jsonError('VALIDATION_ERROR', 'Rapor ID gereklidir.', 400);

        // Ownership check
        $stmt = $pdo->prepare("DELETE FROM internal_installation_reports WHERE id = ? AND kurum_id = ?");
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
