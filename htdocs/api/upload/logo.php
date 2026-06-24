<?php
// ============================================================
// Logo Yükleme Endpoint'i
// ============================================================

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';

authenticateRequest();
requireApiAdmin(); // Sadece admin yükleyebilir
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            jsonError('VALIDATION_ERROR', 'Lütfen geçerli bir logo dosyası seçin.', 400);
        }

        $file = $_FILES['logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            jsonError('VALIDATION_ERROR', 'Sadece JPG, PNG, GIF veya WEBP formatlarına izin verilmektedir.', 400);
        }

        $uploadDir = __DIR__ . '/../../uploads/logos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $newFileName = 'logo_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Sıkıştırma (functions.php)
            compressImage($targetPath, $targetPath, 85);

            $stmt = $pdo->prepare("INSERT INTO uploaded_logos (filename, original_name) VALUES (?, ?)");
            $stmt->execute([$newFileName, sanitize($file['name'])]);
            
            jsonSuccess([
                'id' => $pdo->lastInsertId(),
                'filename' => $newFileName,
                'original_name' => $file['name']
            ], 'Logo başarıyla yüklendi.', 201);
        } else {
            jsonError('SERVER_ERROR', 'Dosya yüklenirken bir hata oluştu.', 500);
        }

    } elseif ($method === 'PUT') {
        $action = $_GET['action'] ?? null;
        $logoId = getIdParam('logo_id');

        if ($action === 'set_active' && $logoId) {
            $stmt = $pdo->prepare("SELECT filename FROM uploaded_logos WHERE id = ?");
            $stmt->execute([$logoId]);
            $logo = $stmt->fetch();

            if (!$logo) jsonError('NOT_FOUND', 'Logo bulunamadı.', 404);

            setSetting($pdo, 'active_logo', $logo['filename']);
            setSetting($pdo, 'logo_type', 'image');

            jsonSuccess(null, 'Aktif logo başarıyla değiştirildi.');
        } else {
            jsonError('VALIDATION_ERROR', 'Geçersiz işlem.', 400);
        }

    } elseif ($method === 'DELETE') {
        $id = getIdParam();
        if (!$id) jsonError('VALIDATION_ERROR', 'Logo ID gereklidir.', 400);

        $stmt = $pdo->prepare("SELECT filename FROM uploaded_logos WHERE id = ?");
        $stmt->execute([$id]);
        $logo = $stmt->fetch();

        if (!$logo) jsonError('NOT_FOUND', 'Logo bulunamadı.', 404);

        $filePath = __DIR__ . '/../../uploads/logos/' . $logo['filename'];
        if (file_exists($filePath)) unlink($filePath);

        $pdo->prepare("DELETE FROM uploaded_logos WHERE id = ?")->execute([$id]);

        $activeLogo = getSetting($pdo, 'active_logo');
        if ($activeLogo === $logo['filename']) {
            setSetting($pdo, 'active_logo', '');
        }

        jsonSuccess(null, 'Logo başarıyla silindi.');
    } else {
        requireMethod(['POST', 'PUT', 'DELETE']);
    }

} catch (PDOException $e) {
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
