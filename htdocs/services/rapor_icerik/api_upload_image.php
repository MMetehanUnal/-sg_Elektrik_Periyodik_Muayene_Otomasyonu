<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'data' => ['messages' => ['Yalnızca POST isteği kabul edilir.']]]);
    exit;
}

$kurumId = $_SESSION['active_institution_id'] ?? null;

if (!$kurumId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'data' => ['messages' => ['Aktif kurum seçilmemiş.']]]);
    exit;
}

$uploadDir = '../../uploads/genel_rapor/';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get optional report_id
$reportId = isset($_POST['report_id']) ? intval(cleanInput($_POST['report_id'])) : null;

// Check if files were uploaded
if (!isset($_FILES['images'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'data' => ['messages' => ['Dosya yüklenmedi. "images" alanı gereklidir.']]]);
    exit;
}

$files = $_FILES['images'];
$uploadedFiles = [];
$isImages = [];
$errors = [];

// Normalize to array format (handles both single and multiple uploads)
if (!is_array($files['name'])) {
    $files = [
        'name'     => [$files['name']],
        'type'     => [$files['type']],
        'tmp_name' => [$files['tmp_name']],
        'error'    => [$files['error']],
        'size'     => [$files['size']],
    ];
}

try {
    for ($i = 0; $i < count($files['name']); $i++) {
        $originalName = $files['name'][$i];
        $tmpName      = $files['tmp_name'][$i];
        $fileSize     = $files['size'][$i];
        $fileError    = $files['error'][$i];

        // Check for upload errors
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "Dosya yüklenirken hata oluştu: {$originalName}";
            continue;
        }

        // Validate file size
        if ($fileSize > $maxFileSize) {
            $errors[] = "Dosya boyutu çok büyük (max 5MB): {$originalName}";
            continue;
        }

        // Validate extension
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "Geçersiz dosya türü ({$extension}): {$originalName}. İzin verilen: " . implode(', ', $allowedExtensions);
            continue;
        }

        // Generate unique filename
        $newFilename = uniqid() . '.' . $extension;
        $destinationPath = $uploadDir . $newFilename;

        // Move uploaded file to temporary location first, then compress
        $tempPath = $uploadDir . 'tmp_' . $newFilename;
        if (!move_uploaded_file($tmpName, $tempPath)) {
            $errors[] = "Dosya taşınamadı: {$originalName}";
            continue;
        }

        // Compress image using the existing compressImage function
        $compressed = compressImage($tempPath, $destinationPath, 70, 800);

        if ($compressed) {
            // Remove temp file after successful compression
            @unlink($tempPath);
        } else {
            // If compression fails, use the original file as-is
            rename($tempPath, $destinationPath);
        }

        // Record in database if report_id is provided
        if ($reportId) {
            $stmt = $pdo->prepare("INSERT INTO general_report_images (report_id, filename, original_name, uploaded_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$reportId, $newFilename, cleanInput($originalName)]);
        }

        // Add to response arrays (web-accessible path)
        $uploadedFiles[] = '/uploads/genel_rapor/' . $newFilename;
        $isImages[] = true;
    }

    if (empty($uploadedFiles) && !empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'data'    => ['messages' => $errors]
        ]);
        exit;
    }

    // Return Jodit-compatible response format
    echo json_encode([
        'success' => true,
        'data'    => [
            'files'    => $uploadedFiles,
            'isImages' => $isImages,
            'baseurl'  => '',
            'message'  => !empty($errors) ? implode('; ', $errors) : ''
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => ['messages' => ['Veritabanı hatası: ' . $e->getMessage()]]]);
}
