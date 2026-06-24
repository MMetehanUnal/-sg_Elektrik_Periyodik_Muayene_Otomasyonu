<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Yalnızca GET isteği kabul edilir.']);
    exit;
}

// Validate required parameter
if (!isset($_GET['report_id']) || empty($_GET['report_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'report_id parametresi gereklidir.']);
    exit;
}

$reportId = intval(cleanInput($_GET['report_id']));
$kurumId = $_SESSION['active_institution_id'] ?? null;

if (!$kurumId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Aktif kurum seçilmemiş.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, title, content FROM general_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$reportId, $kurumId]);
    $report = $stmt->fetch();

    if (!$report) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Rapor bulunamadı veya erişim yetkiniz yok.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'content' => $report['content'],
        'title'   => $report['title']
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
