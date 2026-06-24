<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Yalnızca POST isteği kabul edilir.']);
    exit;
}

$kurumId = $_SESSION['active_institution_id'] ?? null;

if (!$kurumId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Aktif kurum seçilmemiş.']);
    exit;
}

// Read JSON body
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz JSON verisi.']);
    exit;
}

$reportId = isset($data['report_id']) ? intval($data['report_id']) : null;
$title    = isset($data['title']) ? trim($data['title']) : '';
$content  = isset($data['content']) ? $data['content'] : '';

// Title is required
if (empty($title)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Başlık (title) alanı zorunludur.']);
    exit;
}

try {
    if ($reportId) {
        // UPDATE: verify ownership first
        $checkStmt = $pdo->prepare("SELECT id FROM general_reports WHERE id = ? AND kurum_id = ?");
        $checkStmt->execute([$reportId, $kurumId]);

        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Rapor bulunamadı veya erişim yetkiniz yok.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE general_reports SET title = ?, content = ?, updated_at = NOW() WHERE id = ? AND kurum_id = ?");
        $stmt->execute([$title, $content, $reportId, $kurumId]);

        echo json_encode([
            'success'   => true,
            'report_id' => $reportId
        ]);

    } else {
        // INSERT new report
        $stmt = $pdo->prepare("INSERT INTO general_reports (kurum_id, title, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$kurumId, $title, $content]);

        $newId = (int) $pdo->lastInsertId();

        echo json_encode([
            'success'   => true,
            'report_id' => $newId
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
