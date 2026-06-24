<?php
/**
 * Raporlar (Reports) API Endpoint
 * 
 * GET /api/raporlar.php - Tüm rapor türlerini birleştirerek listele
 * 
 * Tablo: grounding_reports, internal_installation_reports,
 *        lightning_protection_reports, fire_detection_reports
 * Kapsam: kurum_id (requireInstitution header)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/helpers/auth_middleware.php';
require_once __DIR__ . '/helpers/validator.php';

authenticateRequest();
requireMethod(['GET']);

$kurumId = requireInstitution($pdo);

// Rapor türü etiketleri (Türkçe)
$typeLabels = [
    'topraklama'  => 'Topraklama',
    'ic_tesisat'  => 'İç Tesisat',
    'yildirim'    => 'Yıldırımdan Korunma',
    'yangin'      => 'Yangın Algılama'
];

try {
    // UNION ALL ile tüm rapor türlerini birleştir
    $sql = "
        (SELECT id, report_no, report_date, control_reason, result, authorized_person_id, 'topraklama' as type
         FROM grounding_reports WHERE kurum_id = ?)
        UNION ALL
        (SELECT id, report_no, report_date, control_reason, result, authorized_person_id, 'ic_tesisat' as type
         FROM internal_installation_reports WHERE kurum_id = ?)
        UNION ALL
        (SELECT id, report_no, report_date, control_reason, result, authorized_person_id, 'yildirim' as type
         FROM lightning_protection_reports WHERE kurum_id = ?)
        UNION ALL
        (SELECT id, report_no, report_date, control_reason, result, authorized_person_id, 'yangin' as type
         FROM fire_detection_reports WHERE kurum_id = ?)
        ORDER BY report_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kurumId, $kurumId, $kurumId, $kurumId]);
    $raporlar = $stmt->fetchAll();

    // Yetkili kişi isimlerini toplu olarak çekmek için ID'leri topla
    $personIds = array_unique(array_filter(array_column($raporlar, 'authorized_person_id')));
    $personNames = [];

    if (!empty($personIds)) {
        $placeholders = implode(',', array_fill(0, count($personIds), '?'));
        $personStmt = $pdo->prepare("SELECT id, adi_soyadi FROM authorized_persons WHERE id IN ($placeholders)");
        $personStmt->execute(array_values($personIds));
        while ($person = $personStmt->fetch()) {
            $personNames[$person['id']] = $person['adi_soyadi'];
        }
    }

    // Her rapora yetkili kişi adını ve tür etiketini ekle
    foreach ($raporlar as &$rapor) {
        $rapor['authorized_person_name'] = $personNames[$rapor['authorized_person_id']] ?? null;
        $rapor['type_label'] = $typeLabels[$rapor['type']] ?? $rapor['type'];
    }
    unset($rapor);

    jsonSuccess($raporlar, 'Raporlar listelendi');

} catch (PDOException $e) {
    jsonError('DB_ERROR', 'Veritabanı hatası: ' . $e->getMessage(), 500);
}
