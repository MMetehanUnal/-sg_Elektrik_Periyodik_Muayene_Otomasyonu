<?php
/**
 * Cihazlar (Measurement Devices) API Endpoint
 * 
 * GET    /api/cihazlar.php          - Kullanıcıya ait tüm ölçüm cihazlarını listele
 * GET    /api/cihazlar.php?id=X     - Tekil cihaz detayı
 * POST   /api/cihazlar.php          - Yeni cihaz oluştur
 * PUT    /api/cihazlar.php?id=X     - Cihaz güncelle
 * DELETE /api/cihazlar.php?id=X     - Cihaz sil
 * 
 * Tablo: measurement_devices
 * Kapsam: user_id (oturum açan kullanıcı)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/helpers/auth_middleware.php';
require_once __DIR__ . '/helpers/validator.php';

authenticateRequest();

$user = getApiUser();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        case 'GET':
            $id = getIdParam();

            if ($id) {
                // Tekil cihaz detayı
                $stmt = $pdo->prepare("SELECT * FROM measurement_devices WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user['user_id']]);
                $cihaz = $stmt->fetch();

                if (!$cihaz) {
                    jsonError('NOT_FOUND', 'Cihaz bulunamadı', 404);
                }

                jsonSuccess($cihaz, 'Cihaz detayı');
            } else {
                // Tüm cihazları listele
                $stmt = $pdo->prepare("SELECT * FROM measurement_devices WHERE user_id = ? ORDER BY id DESC");
                $stmt->execute([$user['user_id']]);
                $cihazlar = $stmt->fetchAll();

                jsonSuccess($cihazlar, 'Cihazlar listelendi');
            }
            break;

        case 'POST':
            $data = getJsonBody();
            requireFields($data, ['device_name', 'serial_no', 'cal_date', 'validity_date', 'cal_no']);
            $data = sanitize($data);

            $isThermalCamera = toBool($data['is_thermal_camera'] ?? 0);

            $stmt = $pdo->prepare(
                "INSERT INTO measurement_devices (user_id, device_name, serial_no, cal_date, validity_date, cal_no, is_thermal_camera)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $user['user_id'],
                $data['device_name'],
                $data['serial_no'],
                $data['cal_date'],
                $data['validity_date'],
                $data['cal_no'],
                $isThermalCamera ? 1 : 0
            ]);

            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM measurement_devices WHERE id = ?");
            $stmt->execute([$newId]);
            $cihaz = $stmt->fetch();

            jsonSuccess($cihaz, 'Cihaz başarıyla oluşturuldu', 201);
            break;

        case 'PUT':
            $id = getIdParam();
            if (!$id) {
                jsonError('VALIDATION_ERROR', 'Cihaz ID gereklidir', 422);
            }

            // Sahiplik kontrolü
            $stmt = $pdo->prepare("SELECT id FROM measurement_devices WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['user_id']]);
            if (!$stmt->fetch()) {
                jsonError('NOT_FOUND', 'Cihaz bulunamadı', 404);
            }

            $data = getJsonBody();
            requireFields($data, ['device_name', 'serial_no', 'cal_date', 'validity_date', 'cal_no']);
            $data = sanitize($data);

            $isThermalCamera = toBool($data['is_thermal_camera'] ?? 0);

            $stmt = $pdo->prepare(
                "UPDATE measurement_devices SET device_name = ?, serial_no = ?, cal_date = ?, validity_date = ?, cal_no = ?, is_thermal_camera = ?
                 WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([
                $data['device_name'],
                $data['serial_no'],
                $data['cal_date'],
                $data['validity_date'],
                $data['cal_no'],
                $isThermalCamera ? 1 : 0,
                $id,
                $user['user_id']
            ]);

            $stmt = $pdo->prepare("SELECT * FROM measurement_devices WHERE id = ?");
            $stmt->execute([$id]);
            $cihaz = $stmt->fetch();

            jsonSuccess($cihaz, 'Cihaz başarıyla güncellendi');
            break;

        case 'DELETE':
            $id = getIdParam();
            if (!$id) {
                jsonError('VALIDATION_ERROR', 'Cihaz ID gereklidir', 422);
            }

            // Sahiplik kontrolü
            $stmt = $pdo->prepare("SELECT id FROM measurement_devices WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['user_id']]);
            if (!$stmt->fetch()) {
                jsonError('NOT_FOUND', 'Cihaz bulunamadı', 404);
            }

            $stmt = $pdo->prepare("DELETE FROM measurement_devices WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['user_id']]);

            jsonSuccess(null, 'Cihaz başarıyla silindi');
            break;

        default:
            requireMethod(['GET', 'POST', 'PUT', 'DELETE']);
            break;
    }
} catch (PDOException $e) {
    jsonError('DB_ERROR', 'Veritabanı hatası: ' . $e->getMessage(), 500);
}
