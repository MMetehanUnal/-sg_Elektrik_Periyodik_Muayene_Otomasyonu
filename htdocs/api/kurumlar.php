<?php
/**
 * Kurumlar (Institutions) API Endpoint
 * 
 * GET    /api/kurumlar.php          - Kullanıcıya ait tüm kurumları listele
 * GET    /api/kurumlar.php?id=X     - Tekil kurum detayı
 * POST   /api/kurumlar.php          - Yeni kurum oluştur
 * PUT    /api/kurumlar.php?id=X     - Kurum güncelle
 * DELETE /api/kurumlar.php?id=X     - Kurum sil
 * 
 * Tablo: institutions
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
                // Tekil kurum detayı
                $stmt = $pdo->prepare("SELECT * FROM institutions WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user['user_id']]);
                $kurum = $stmt->fetch();

                if (!$kurum) {
                    jsonError('NOT_FOUND', 'Kurum bulunamadı', 404);
                }

                jsonSuccess($kurum, 'Kurum detayı');
            } else {
                // Tüm kurumları listele
                $stmt = $pdo->prepare("SELECT * FROM institutions WHERE user_id = ? ORDER BY id DESC");
                $stmt->execute([$user['user_id']]);
                $kurumlar = $stmt->fetchAll();

                jsonSuccess($kurumlar, 'Kurumlar listelendi');
            }
            break;

        case 'POST':
            $data = getJsonBody();
            requireFields($data, ['firma_adi', 'adresi', 'il_kodu', 'kurum_kodu']);
            $data = sanitize($data);

            // il_kodu max 2, kurum_kodu max 3 karakter kontrolü
            if (mb_strlen($data['il_kodu']) > 2) {
                jsonError('VALIDATION_ERROR', 'İl kodu en fazla 2 karakter olabilir', 422);
            }
            if (mb_strlen($data['kurum_kodu']) > 3) {
                jsonError('VALIDATION_ERROR', 'Kurum kodu en fazla 3 karakter olabilir', 422);
            }

            $data = nullifyEmpty($data, ['sgk_sicil_no', 'isg_katip_id', 'report_date', 'start_date', 'end_date', 'next_control_date']);

            $stmt = $pdo->prepare(
                "INSERT INTO institutions (user_id, firma_adi, adresi, sgk_sicil_no, il_kodu, kurum_kodu, isg_katip_id, report_date, start_date, end_date, next_control_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $user['user_id'],
                $data['firma_adi'],
                $data['adresi'],
                $data['sgk_sicil_no'] ?? null,
                $data['il_kodu'],
                $data['kurum_kodu'],
                $data['isg_katip_id'] ?? null,
                $data['report_date'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['next_control_date'] ?? null
            ]);

            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM institutions WHERE id = ?");
            $stmt->execute([$newId]);
            $kurum = $stmt->fetch();

            jsonSuccess($kurum, 'Kurum başarıyla oluşturuldu', 201);
            break;

        case 'PUT':
            $id = getIdParam();
            if (!$id) {
                jsonError('VALIDATION_ERROR', 'Kurum ID gereklidir', 422);
            }

            // Sahiplik kontrolü
            $stmt = $pdo->prepare("SELECT id FROM institutions WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['user_id']]);
            if (!$stmt->fetch()) {
                jsonError('NOT_FOUND', 'Kurum bulunamadı', 404);
            }

            $data = getJsonBody();
            requireFields($data, ['firma_adi', 'adresi', 'il_kodu', 'kurum_kodu']);
            $data = sanitize($data);

            if (mb_strlen($data['il_kodu']) > 2) {
                jsonError('VALIDATION_ERROR', 'İl kodu en fazla 2 karakter olabilir', 422);
            }
            if (mb_strlen($data['kurum_kodu']) > 3) {
                jsonError('VALIDATION_ERROR', 'Kurum kodu en fazla 3 karakter olabilir', 422);
            }

            $data = nullifyEmpty($data, ['sgk_sicil_no', 'isg_katip_id', 'report_date', 'start_date', 'end_date', 'next_control_date']);

            $stmt = $pdo->prepare(
                "UPDATE institutions SET firma_adi = ?, adresi = ?, sgk_sicil_no = ?, il_kodu = ?, kurum_kodu = ?,
                 isg_katip_id = ?, report_date = ?, start_date = ?, end_date = ?, next_control_date = ?
                 WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([
                $data['firma_adi'],
                $data['adresi'],
                $data['sgk_sicil_no'] ?? null,
                $data['il_kodu'],
                $data['kurum_kodu'],
                $data['isg_katip_id'] ?? null,
                $data['report_date'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['next_control_date'] ?? null,
                $id,
                $user['user_id']
            ]);

            $stmt = $pdo->prepare("SELECT * FROM institutions WHERE id = ?");
            $stmt->execute([$id]);
            $kurum = $stmt->fetch();

            jsonSuccess($kurum, 'Kurum başarıyla güncellendi');
            break;

        case 'DELETE':
            $id = getIdParam();
            if (!$id) {
                jsonError('VALIDATION_ERROR', 'Kurum ID gereklidir', 422);
            }

            // Sahiplik kontrolü
            $stmt = $pdo->prepare("SELECT id FROM institutions WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['user_id']]);
            if (!$stmt->fetch()) {
                jsonError('NOT_FOUND', 'Kurum bulunamadı', 404);
            }

            $stmt = $pdo->prepare("DELETE FROM institutions WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['user_id']]);

            jsonSuccess(null, 'Kurum başarıyla silindi');
            break;

        default:
            requireMethod(['GET', 'POST', 'PUT', 'DELETE']);
            break;
    }
} catch (PDOException $e) {
    jsonError('DB_ERROR', 'Veritabanı hatası: ' . $e->getMessage(), 500);
}
