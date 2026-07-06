<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
    exit;
}

if (!isset($_SESSION['active_institution_id'])) {
    echo json_encode(['success' => false, 'error' => 'Aktif kurum seçilmemiş.']);
    exit;
}

$kurum_id = $_SESSION['active_institution_id'];
$firma_adi_eki = isset($_GET['firma_adi_eki']) ? trim($_GET['firma_adi_eki']) : '';

if (empty($firma_adi_eki)) {
    echo json_encode(['success' => false, 'error' => 'Firma adı eki belirtilmedi.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT report_date, start_date, end_date, next_control_date, isg_katip_id 
        FROM internal_installation_reports 
        WHERE kurum_id = ? AND firma_adi_eki = ? 
        ORDER BY report_date DESC, id DESC 
        LIMIT 1
    ");
    $stmt->execute([$kurum_id, $firma_adi_eki]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Format dates for HTML input tags (YYYY-MM-DD for date, YYYY-MM-DDTHH:MM for datetime-local)
        if ($data['start_date']) {
            $data['start_date'] = date('Y-m-d\TH:i', strtotime($data['start_date']));
        }
        if ($data['end_date']) {
            $data['end_date'] = date('Y-m-d\TH:i', strtotime($data['end_date']));
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Bu firma adı ekine ait iç tesisat raporu bulunamadı.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
