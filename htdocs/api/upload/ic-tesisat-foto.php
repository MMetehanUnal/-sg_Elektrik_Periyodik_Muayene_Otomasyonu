<?php
// ============================================================
// İç Tesisat Fotoğraf Yükleme Endpoint'i
// ============================================================

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';

authenticateRequest();
$kurumId = requireInstitution($pdo);
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            jsonError('VALIDATION_ERROR', 'Lütfen geçerli bir fotoğraf dosyası seçin.', 400);
        }

        $panelId = $_POST['panel_id'] ?? null;
        $photoType = $_POST['photo_type'] ?? 'normal'; // 'normal' veya 'termal'

        if (!$panelId) jsonError('VALIDATION_ERROR', 'panel_id gereklidir.', 400);

        // Pano sahipliği doğrula
        $stmt = $pdo->prepare("
            SELECT p.id, p.report_id 
            FROM ic_tesisat_panels p 
            JOIN internal_installation_reports r ON p.report_id = r.id 
            WHERE p.id = ? AND r.kurum_id = ?
        ");
        $stmt->execute([$panelId, $kurumId]);
        $panel = $stmt->fetch();

        if (!$panel) jsonError('NOT_FOUND', 'Pano bulunamadı veya yetkiniz yok.', 404);

        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            jsonError('VALIDATION_ERROR', 'Sadece JPG, PNG veya WEBP formatlarına izin verilmektedir.', 400);
        }

        $uploadDir = __DIR__ . '/../../uploads/ic_tesisat/' . $panel['report_id'] . '/' . $panelId . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $newFileName = 'photo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $newFileName;
        $dbPath = 'uploads/ic_tesisat/' . $panel['report_id'] . '/' . $panelId . '/' . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            compressImage($targetPath, $targetPath, 80);

            $insStmt = $pdo->prepare("INSERT INTO ic_tesisat_photos (panel_id, photo_type, file_path) VALUES (?, ?, ?)");
            $insStmt->execute([$panelId, sanitize($photoType), $dbPath]);
            
            jsonSuccess([
                'id' => $pdo->lastInsertId(),
                'file_path' => $dbPath,
                'photo_type' => $photoType
            ], 'Fotoğraf başarıyla yüklendi.', 201);
        } else {
            jsonError('SERVER_ERROR', 'Dosya yüklenirken bir hata oluştu.', 500);
        }

    } elseif ($method === 'DELETE') {
        $id = getIdParam();
        if (!$id) jsonError('VALIDATION_ERROR', 'Fotoğraf ID gereklidir.', 400);

        // Sahiplik doğrula
        $stmt = $pdo->prepare("
            SELECT ph.file_path 
            FROM ic_tesisat_photos ph
            JOIN ic_tesisat_panels p ON ph.panel_id = p.id
            JOIN internal_installation_reports r ON p.report_id = r.id
            WHERE ph.id = ? AND r.kurum_id = ?
        ");
        $stmt->execute([$id, $kurumId]);
        $photo = $stmt->fetch();

        if (!$photo) jsonError('NOT_FOUND', 'Fotoğraf bulunamadı veya yetkiniz yok.', 404);

        $fullPath = __DIR__ . '/../../' . $photo['file_path'];
        if (file_exists($fullPath)) unlink($fullPath);

        $pdo->prepare("DELETE FROM ic_tesisat_photos WHERE id = ?")->execute([$id]);

        jsonSuccess(null, 'Fotoğraf başarıyla silindi.');
    } else {
        requireMethod(['POST', 'DELETE']);
    }

} catch (PDOException $e) {
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
