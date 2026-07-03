<?php
/**
 * DİKKAT: Güvenlik nedeniyle, bu dosyayı sunucuda çalıştırdıktan ve sonuçları 
 * kopyaladıktan sonra sunucunuzdan MUTLAKA siliniz!
 */

require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== SUNUCU VERİTABANI KONTROLÜ ===\n\n";

try {
    echo "=== KURUMLAR (INSTITUTIONS) ===\n";
    $stmt = $pdo->query("SELECT id, firma_adi FROM institutions");
    while ($row = $stmt->fetch()) {
        echo "Kurum ID: {$row['id']} | Firma Adı: {$row['firma_adi']}\n";
    }

    echo "\n=== İÇ TESİSAT RAPORLARI VE LİNYE VERİLERİ ===\n";
    $stmt = $pdo->query("SELECT id, report_no, report_date, kurum_id, firma_adi_eki FROM internal_installation_reports");
    while ($row = $stmt->fetch()) {
        // Pano sayısı
        $pStmt = $pdo->prepare("SELECT id FROM ic_tesisat_panels WHERE report_id = ?");
        $pStmt->execute([$row['id']]);
        $panels = $pStmt->fetchAll();
        
        // Linye satır sayısı
        $total_rows = 0;
        foreach ($panels as $p) {
            $rStmt = $pdo->prepare("SELECT COUNT(*) FROM ic_tesisat_section6_1_rows WHERE panel_id = ?");
            $rStmt->execute([$p['id']]);
            $total_rows += $rStmt->fetchColumn();
        }
        
        echo "Rapor ID: {$row['id']} | Kurum ID: {$row['kurum_id']} | Rapor No: {$row['report_no']} | Tarih: {$row['report_date']} | Eki: {$row['firma_adi_eki']} | Pano Sayısı: " . count($panels) . " | Linye Satır Sayısı: {$total_rows}\n";
    }
    echo "======================================================\n";
} catch (Exception $e) {
    echo "Bağlantı/Sorgu Hatası: " . $e->getMessage() . "\n";
}
?>
