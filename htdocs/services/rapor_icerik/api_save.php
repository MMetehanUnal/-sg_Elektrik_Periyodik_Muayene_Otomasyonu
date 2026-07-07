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

$yurt_yoneticisi = isset($data['yurt_yoneticisi']) ? trim($data['yurt_yoneticisi']) : null;
$yatak_kapasitesi = isset($data['yatak_kapasitesi']) ? trim($data['yatak_kapasitesi']) : null;
$is_guvenligi_uzmani = isset($data['is_guvenligi_uzmani']) ? trim($data['is_guvenligi_uzmani']) : null;
$ada = isset($data['ada']) ? trim($data['ada']) : null;
$pafta = isset($data['pafta']) ? trim($data['pafta']) : null;
$parsel = isset($data['parsel']) ? trim($data['parsel']) : null;
$phone = isset($data['phone']) ? trim($data['phone']) : null;
$report_no = isset($data['report_no']) ? trim($data['report_no']) : null;
$report_date = !empty($data['report_date']) ? $data['report_date'] : null;
$control_date = !empty($data['control_date']) ? $data['control_date'] : null;
$next_control_date = !empty($data['next_control_date']) ? $data['next_control_date'] : null;
$mekanik_uzman_id = !empty($data['mekanik_uzman_id']) ? intval($data['mekanik_uzman_id']) : null;
$elektrik_uzman_id = !empty($data['elektrik_uzman_id']) ? intval($data['elektrik_uzman_id']) : null;

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

        $stmt = $pdo->prepare("UPDATE general_reports SET 
            title = ?, content = ?, yurt_yoneticisi = ?, yatak_kapasitesi = ?, 
            is_guvenligi_uzmani = ?, ada = ?, pafta = ?, parsel = ?, 
            phone = ?, report_no = ?, report_date = ?, control_date = ?, 
            next_control_date = ?, mekanik_uzman_id = ?, elektrik_uzman_id = ?, 
            updated_at = NOW() 
            WHERE id = ? AND kurum_id = ?");
        $stmt->execute([
            $title, $content, $yurt_yoneticisi, $yatak_kapasitesi,
            $is_guvenligi_uzmani, $ada, $pafta, $parsel,
            $phone, $report_no, $report_date, $control_date,
            $next_control_date, $mekanik_uzman_id, $elektrik_uzman_id,
            $reportId, $kurumId
        ]);

        echo json_encode([
            'success'   => true,
            'report_id' => $reportId
        ]);

    } else {
        // INSERT new report
        $stmt = $pdo->prepare("INSERT INTO general_reports 
            (kurum_id, title, content, yurt_yoneticisi, yatak_kapasitesi, 
            is_guvenligi_uzmani, ada, pafta, parsel, 
            phone, report_no, report_date, control_date, 
            next_control_date, mekanik_uzman_id, elektrik_uzman_id, 
            created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $kurumId, $title, $content, $yurt_yoneticisi, $yatak_kapasitesi,
            $is_guvenligi_uzmani, $ada, $pafta, $parsel,
            $phone, $report_no, $report_date, $control_date,
            $next_control_date, $mekanik_uzman_id, $elektrik_uzman_id
        ]);

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
