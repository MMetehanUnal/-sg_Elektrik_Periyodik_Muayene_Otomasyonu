<?php
/**
 * Yetkili Kişiler (Authorized Persons) API Endpoint
 * 
 * GET    /api/yetkili-kisiler.php          - Tüm yetkili kişileri listele
 * GET    /api/yetkili-kisiler.php?id=X     - Tekil yetkili kişi detayı
 * POST   /api/yetkili-kisiler.php          - Yeni yetkili kişi oluştur
 * PUT    /api/yetkili-kisiler.php?id=X     - Yetkili kişi güncelle
 * DELETE /api/yetkili-kisiler.php?id=X     - Yetkili kişi sil
 * 
 * Tablo: authorized_persons
 * Kapsam: GLOBAL (user_id filtresi yok)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/helpers/auth_middleware.php';
require_once __DIR__ . '/helpers/validator.php';

authenticateRequest();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        case 'GET':
            $id = getIdParam();

            if ($id) {
                // Tekil yetkili kişi detayı
                $stmt = $pdo->prepare("SELECT * FROM authorized_persons WHERE id = ?");
                $stmt->execute([$id]);
                $kisi = $stmt->fetch();

                if (!$kisi) {
                    jsonError('NOT_FOUND', 'Yetkili kişi bulunamadı', 404);
                }

                jsonSuccess($kisi, 'Yetkili kişi detayı');
            } else {
                // Tüm yetkili kişileri listele
                $stmt = $pdo->query("SELECT * FROM authorized_persons ORDER BY adi_soyadi ASC");
                $kisiler = $stmt->fetchAll();

                jsonSuccess($kisiler, 'Yetkili kişiler listelendi');
            }
            break;

        case 'POST':
            $data = getJsonBody();
            requireFields($data, ['adi_soyadi', 'meslegi']);
            $data = sanitize($data);
            $data = nullifyEmpty($data, ['kayit_no']);

            $stmt = $pdo->prepare(
                "INSERT INTO authorized_persons (adi_soyadi, meslegi, kayit_no)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([
                $data['adi_soyadi'],
                $data['meslegi'],
                $data['kayit_no'] ?? null
            ]);

            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM authorized_persons WHERE id = ?");
            $stmt->execute([$newId]);
            $kisi = $stmt->fetch();

            jsonSuccess($kisi, 'Yetkili kişi başarıyla oluşturuldu', 201);
            break;

        case 'PUT':
            $id = getIdParam();
            if (!$id) {
                jsonError('VALIDATION_ERROR', 'Yetkili kişi ID gereklidir', 422);
            }

            // Varlık kontrolü
            $stmt = $pdo->prepare("SELECT id FROM authorized_persons WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                jsonError('NOT_FOUND', 'Yetkili kişi bulunamadı', 404);
            }

            $data = getJsonBody();
            requireFields($data, ['adi_soyadi', 'meslegi']);
            $data = sanitize($data);
            $data = nullifyEmpty($data, ['kayit_no']);

            $stmt = $pdo->prepare(
                "UPDATE authorized_persons SET adi_soyadi = ?, meslegi = ?, kayit_no = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $data['adi_soyadi'],
                $data['meslegi'],
                $data['kayit_no'] ?? null,
                $id
            ]);

            $stmt = $pdo->prepare("SELECT * FROM authorized_persons WHERE id = ?");
            $stmt->execute([$id]);
            $kisi = $stmt->fetch();

            jsonSuccess($kisi, 'Yetkili kişi başarıyla güncellendi');
            break;

        case 'DELETE':
            $id = getIdParam();
            if (!$id) {
                jsonError('VALIDATION_ERROR', 'Yetkili kişi ID gereklidir', 422);
            }

            $stmt = $pdo->prepare("SELECT id FROM authorized_persons WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                jsonError('NOT_FOUND', 'Yetkili kişi bulunamadı', 404);
            }

            $stmt = $pdo->prepare("DELETE FROM authorized_persons WHERE id = ?");
            $stmt->execute([$id]);

            jsonSuccess(null, 'Yetkili kişi başarıyla silindi');
            break;

        default:
            requireMethod(['GET', 'POST', 'PUT', 'DELETE']);
            break;
    }
} catch (PDOException $e) {
    jsonError('DB_ERROR', 'Veritabanı hatası: ' . $e->getMessage(), 500);
}
