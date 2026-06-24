<?php
// ============================================================
// Genel Rapor Görsel Yükleme Endpoint'i
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
        if (!isset($_FILES['images'])) {
            jsonError('VALIDATION_ERROR', 'Lütfen yüklenecek görselleri seçin.', 400);
        }

        $reportId = $_POST['report_id'] ?? null;
        if (!$reportId) jsonError('VALIDATION_ERROR', 'report_id gereklidir.', 400);

        // Sahiplik doğrula
        $stmt = $pdo->prepare("SELECT id FROM general_reports WHERE id = ? AND kurum_id = ?");
        $stmt->execute([$reportId, $kurumId]);
        if (!$stmt->fetch()) jsonError('NOT_FOUND', 'Rapor bulunamadı veya yetkiniz yok.', 404);

        $uploadDir = __DIR__ . '/../../uploads/genel_rapor/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $uploadedFiles = [];
        
        $files = $_FILES['images'];
        $count = is_array($files['name']) ? count($files['name']) : 1;

        $pdo->beginTransaction();
        $insStmt = $pdo->prepare("INSERT INTO general_report_images (report_id, filename, original_name) VALUES (?, ?, ?)");

        for ($i = 0; $i < $count; $i++) {
            $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $fileTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            if ($fileError !== UPLOAD_ERR_OK) continue;

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) continue;

            $newFileName = 'gr_' . $reportId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $targetPath)) {
                compressImage($targetPath, $targetPath, 85);
                
                $insStmt->execute([$reportId, $newFileName, sanitize($fileName)]);
                $uploadedFiles[] = [
                    'id' => $pdo->lastInsertId(),
                    'filename' => $newFileName,
                    'url' => 'uploads/genel_rapor/' . $newFileName
                ];
            }
        }

        $pdo->commit();
        jsonSuccess($uploadedFiles, count($uploadedFiles) . ' görsel başarıyla yüklendi.', 201);

    } elseif ($method === 'DELETE') {
        $id = getIdParam();
        if (!$id) jsonError('VALIDATION_ERROR', 'Görsel ID gereklidir.', 400);

        // Sahiplik doğrula
        $stmt = $pdo->prepare("
            SELECT i.filename 
            FROM general_report_images i
            JOIN general_reports r ON i.report_id = r.id
            WHERE i.id = ? AND r.kurum_id = ?
        ");
        $stmt->execute([$id, $kurumId]);
        $image = $stmt->fetch();

        if (!$image) jsonError('NOT_FOUND', 'Görsel bulunamadı veya yetkiniz yok.', 404);

        $fullPath = __DIR__ . '/../../uploads/genel_rapor/' . $image['filename'];
        if (file_exists($fullPath)) unlink($fullPath);

        $pdo->prepare("DELETE FROM general_report_images WHERE id = ?")->execute([$id]);

        jsonSuccess(null, 'Görsel başarıyla silindi.');
    } else {
        requireMethod(['POST', 'DELETE']);
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
