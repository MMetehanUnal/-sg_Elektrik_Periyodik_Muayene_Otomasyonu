<?php
/**
 * Tesis Bilgileri (Facility Info) API Endpoint
 * 
 * GET    /api/tesis-bilgileri.php  - Aktif kurumun tesis bilgilerini getir
 * POST   /api/tesis-bilgileri.php  - Tesis bilgilerini oluştur veya güncelle (UPSERT)
 * 
 * Tablo: facility_info + institutions
 * Kapsam: kurum_id (requireInstitution header)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/helpers/auth_middleware.php';
require_once __DIR__ . '/helpers/validator.php';

authenticateRequest();

$kurumId = requireInstitution($pdo);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        case 'GET':
            $stmt = $pdo->prepare(
                "SELECT fi.*, i.start_date, i.end_date
                 FROM facility_info fi
                 RIGHT JOIN institutions i ON fi.kurum_id = i.id
                 WHERE i.id = ?"
            );
            $stmt->execute([$kurumId]);
            $bilgi = $stmt->fetch();

            if (!$bilgi) {
                jsonError('NOT_FOUND', 'Kurum bulunamadı', 404);
            }

            jsonSuccess($bilgi, 'Tesis bilgileri');
            break;

        case 'POST':
            $data = getJsonBody();
            $data = sanitize($data);

            // Geçerli şebeke tipleri
            $validSebekeTipleri = ['TT', 'IT', 'TN', 'TN-C', 'TN-S', 'TN-C-S'];
            if (!empty($data['sebeke_tipi']) && !in_array($data['sebeke_tipi'], $validSebekeTipleri)) {
                jsonError('VALIDATION_ERROR', 'Geçersiz şebeke tipi. Geçerli değerler: ' . implode(', ', $validSebekeTipleri), 422);
            }

            // Boolean alanları dönüştür
            $projeVarMi = toBool($data['proje_var_mi'] ?? 0);
            $semaVarMi = toBool($data['sema_var_mi'] ?? 0);

            // Opsiyonel alanları null yap
            $data = nullifyEmpty($data, [
                'enerji_saglayan', 'sebeke_tipi', 'sebeke_gerilimi', 'yapi_cinsi',
                'kullanim_amaci', 'sozlesme_id', 'son_kontrol_tarihi', 'weather_condition',
                'ground_moisture', 'grounding_type', 'control_reason', 'next_control_date'
            ]);

            $pdo->beginTransaction();

            // Mevcut kayıt var mı kontrol et
            $checkStmt = $pdo->prepare("SELECT id FROM facility_info WHERE kurum_id = ?");
            $checkStmt->execute([$kurumId]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                // UPDATE
                $stmt = $pdo->prepare(
                    "UPDATE facility_info SET
                        enerji_saglayan = ?, sebeke_tipi = ?, sebeke_gerilimi = ?,
                        proje_var_mi = ?, sema_var_mi = ?, yapi_cinsi = ?,
                        kullanim_amaci = ?, sozlesme_id = ?, son_kontrol_tarihi = ?,
                        weather_condition = ?, ground_moisture = ?, grounding_type = ?,
                        control_reason = ?, next_control_date = ?
                     WHERE kurum_id = ?"
                );
                $stmt->execute([
                    $data['enerji_saglayan'] ?? null,
                    $data['sebeke_tipi'] ?? null,
                    $data['sebeke_gerilimi'] ?? null,
                    $projeVarMi ? 1 : 0,
                    $semaVarMi ? 1 : 0,
                    $data['yapi_cinsi'] ?? null,
                    $data['kullanim_amaci'] ?? null,
                    $data['sozlesme_id'] ?? null,
                    $data['son_kontrol_tarihi'] ?? null,
                    $data['weather_condition'] ?? null,
                    $data['ground_moisture'] ?? null,
                    $data['grounding_type'] ?? null,
                    $data['control_reason'] ?? null,
                    $data['next_control_date'] ?? null,
                    $kurumId
                ]);
            } else {
                // INSERT
                $stmt = $pdo->prepare(
                    "INSERT INTO facility_info (kurum_id, enerji_saglayan, sebeke_tipi, sebeke_gerilimi,
                        proje_var_mi, sema_var_mi, yapi_cinsi, kullanim_amaci, sozlesme_id,
                        son_kontrol_tarihi, weather_condition, ground_moisture, grounding_type,
                        control_reason, next_control_date)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $kurumId,
                    $data['enerji_saglayan'] ?? null,
                    $data['sebeke_tipi'] ?? null,
                    $data['sebeke_gerilimi'] ?? null,
                    $projeVarMi ? 1 : 0,
                    $semaVarMi ? 1 : 0,
                    $data['yapi_cinsi'] ?? null,
                    $data['kullanim_amaci'] ?? null,
                    $data['sozlesme_id'] ?? null,
                    $data['son_kontrol_tarihi'] ?? null,
                    $data['weather_condition'] ?? null,
                    $data['ground_moisture'] ?? null,
                    $data['grounding_type'] ?? null,
                    $data['control_reason'] ?? null,
                    $data['next_control_date'] ?? null
                ]);
            }

            // institutions tablosunda start_date ve end_date güncelle
            if (isset($data['start_date']) || isset($data['end_date'])) {
                $startDate = $data['start_date'] ?? null;
                $endDate = $data['end_date'] ?? null;

                $updateStmt = $pdo->prepare(
                    "UPDATE institutions SET start_date = ?, end_date = ? WHERE id = ?"
                );
                $updateStmt->execute([$startDate, $endDate, $kurumId]);
            }

            $pdo->commit();

            // Güncel veriyi getir
            $stmt = $pdo->prepare(
                "SELECT fi.*, i.start_date, i.end_date
                 FROM facility_info fi
                 RIGHT JOIN institutions i ON fi.kurum_id = i.id
                 WHERE i.id = ?"
            );
            $stmt->execute([$kurumId]);
            $bilgi = $stmt->fetch();

            jsonSuccess($bilgi, 'Tesis bilgileri başarıyla kaydedildi');
            break;

        default:
            requireMethod(['GET', 'POST']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonError('DB_ERROR', 'Veritabanı hatası: ' . $e->getMessage(), 500);
}
