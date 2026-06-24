<?php
/**
 * Genel Rapor (General Reports) API Endpoint
 * 
 * GET    /api/genel-rapor.php          - Kuruma ait genel raporları listele
 * GET    /api/genel-rapor.php?id=X     - Tekil rapor detayı (content dahil)
 * POST   /api/genel-rapor.php          - Yeni genel rapor oluştur
 * PUT    /api/genel-rapor.php?id=X     - Rapor güncelle
 * DELETE /api/genel-rapor.php?id=X     - Rapor sil (ilişkili dosyaları da temizle)
 * 
 * Tablo: general_reports, general_report_images
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
            $id = getIdParam();

            if ($id) {
                // Tekil rapor detayı (content dahil)
                $stmt = $pdo->prepare("SELECT * FROM general_reports WHERE id = ? AND kurum_id = ?");
                $stmt->execute([$id, $kurumId]);
                $rapor = $stmt->fetch();

                if (!$rapor) {
                    jsonError('NOT_FOUND', 'Rapor bulunamadı', 404);
                }

                jsonSuccess($rapor, 'Rapor detayı');
            } else {
                // Raporları listele (content hariç, performans için)
                $stmt = $pdo->prepare(
                    "SELECT id, title, created_at, updated_at FROM general_reports
                     WHERE kurum_id = ? ORDER BY updated_at DESC"
                );
                $stmt->execute([$kurumId]);
                $raporlar = $stmt->fetchAll();

                jsonSuccess($raporlar, 'Genel raporlar listelendi');
            }
            break;

        case 'POST':
            $data = getJsonBody();
            requireFields($data, ['title']);
            $data = sanitize($data);
            $data = nullifyEmpty($data, ['content']);

            $stmt = $pdo->prepare(
                "INSERT INTO general_reports (kurum_id, title, content)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([
                $kurumId,
                $data['title'],
                $data['content'] ?? null
            ]);

            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM general_reports WHERE id = ?");
            $stmt->execute([$newId]);
            $rapor = $stmt->fetch();

            jsonSuccess($rapor, 'Genel rapor başarıyla oluşturuldu', 201);
            break;

        case 'PUT':
            $id = getIdParam();
            if (!$id) {
                jsonError('VALIDATION_ERROR', 'Rapor ID gereklidir', 422);
            }

            // Sahiplik kontrolü (kurum_id)
            $stmt = $pdo->prepare("SELECT id FROM general_reports WHERE id = ? AND kurum_id = ?");
            $stmt->execute([$id, $kurumId]);
            if (!$stmt->fetch()) {
                jsonError('NOT_FOUND', 'Rapor bulunamadı', 404);
            }

            $data = getJsonBody();
            $data = sanitize($data);

            // En az bir alan güncellenmelidir
            if (!isset($data['title']) && !isset($data['content'])) {
                jsonError('VALIDATION_ERROR', 'Güncellenecek alan belirtilmedi (title ve/veya content)', 422);
            }

            // Dinamik güncelleme sorgusu oluştur
            $updateFields = [];
            $updateValues = [];

            if (isset($data['title'])) {
                $updateFields[] = 'title = ?';
                $updateValues[] = $data['title'];
            }
            if (isset($data['content'])) {
                $updateFields[] = 'content = ?';
                $updateValues[] = $data['content'];
            }

            $updateValues[] = $id;
            $updateValues[] = $kurumId;

            $sql = "UPDATE general_reports SET " . implode(', ', $updateFields) . " WHERE id = ? AND kurum_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);

            $stmt = $pdo->prepare("SELECT * FROM general_reports WHERE id = ?");
            $stmt->execute([$id]);
            $rapor = $stmt->fetch();

            jsonSuccess($rapor, 'Genel rapor başarıyla güncellendi');
            break;

        case 'DELETE':
            $id = getIdParam();
            if (!$id) {
                jsonError('VALIDATION_ERROR', 'Rapor ID gereklidir', 422);
            }

            // Sahiplik kontrolü (kurum_id)
            $stmt = $pdo->prepare("SELECT id FROM general_reports WHERE id = ? AND kurum_id = ?");
            $stmt->execute([$id, $kurumId]);
            if (!$stmt->fetch()) {
                jsonError('NOT_FOUND', 'Rapor bulunamadı', 404);
            }

            // İlişkili görsellerin dosyalarını temizle (CASCADE DB kaydını siler, dosyaları biz sileriz)
            $imgStmt = $pdo->prepare("SELECT filename FROM general_report_images WHERE report_id = ?");
            $imgStmt->execute([$id]);
            while ($img = $imgStmt->fetch()) {
                $filePath = __DIR__ . '/../uploads/genel_rapor/' . $img['filename'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Raporu sil (general_report_images CASCADE ile silinir)
            $stmt = $pdo->prepare("DELETE FROM general_reports WHERE id = ? AND kurum_id = ?");
            $stmt->execute([$id, $kurumId]);

            jsonSuccess(null, 'Genel rapor başarıyla silindi');
            break;

        default:
            requireMethod(['GET', 'POST', 'PUT', 'DELETE']);
            break;
    }
} catch (PDOException $e) {
    jsonError('DB_ERROR', 'Veritabanı hatası: ' . $e->getMessage(), 500);
}
