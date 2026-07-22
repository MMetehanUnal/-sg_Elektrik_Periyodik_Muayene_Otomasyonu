<?php
require_once 'includes/db.php';

echo "<h2>Katodik Koruma Veritabanı Kurulum Sihirbazı</h2>";

try {
    // 1. Create katodik_koruma_reports
    $sql1 = "CREATE TABLE IF NOT EXISTS `katodik_koruma_reports` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `kurum_id` int(11) NOT NULL,
      `report_no` varchar(100) NOT NULL UNIQUE,
      `report_date` date NOT NULL,
      `start_date` datetime DEFAULT NULL,
      `end_date` datetime DEFAULT NULL,
      `next_control_date` date DEFAULT NULL,
      `isg_katip_id` varchar(100) DEFAULT NULL,
      `firma_adi_eki` varchar(255) DEFAULT NULL,
      `control_reason` varchar(255) DEFAULT 'Periyodik Kontrol',
      `zemin` varchar(50) DEFAULT NULL,
      `toprak_durumu` varchar(50) DEFAULT NULL,
      `tesis_proje_var_mi` varchar(50) DEFAULT NULL,
      `olcu_kutusu_sayisi` varchar(50) DEFAULT NULL,
      `referans_elektrot_tipi` varchar(255) DEFAULT NULL,
      `tesisin_kullanim_amaci` varchar(255) DEFAULT NULL,
      `olcum_cihazi` varchar(255) DEFAULT NULL,
      `marka_model` varchar(255) DEFAULT NULL,
      `seri_no` varchar(100) DEFAULT NULL,
      `hata_sinifi` varchar(100) DEFAULT NULL,
      `olcum_yontemi` varchar(255) DEFAULT NULL,
      `kalibrasyon_kurum` varchar(255) DEFAULT NULL,
      `kalibrasyon_tarih_sayi` varchar(255) DEFAULT NULL,
      `gecerlilik_suresi` varchar(255) DEFAULT NULL,
      `device_id` int(11) DEFAULT NULL,
      `defects` text DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `result` varchar(50) DEFAULT 'UYGUNDUR',
      `authorized_person_id` int(11) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `kurum_id` (`kurum_id`),
      KEY `authorized_person_id` (`authorized_person_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $pdo->exec($sql1);
    echo "<p style='color:green;'>✓ 'katodik_koruma_reports' tablosu başarıyla oluşturuldu!</p>";
    
    // 2. Create katodik_koruma_measurements
    $sql2 = "CREATE TABLE IF NOT EXISTS `katodik_koruma_measurements` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `report_id` int(11) NOT NULL,
      `box_no` varchar(100) DEFAULT NULL,
      `system_voltage` varchar(100) DEFAULT NULL,
      `pipe_voltage` varchar(100) DEFAULT NULL,
      `anode_voltage` varchar(100) DEFAULT NULL,
      `anode_current` varchar(100) DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `report_id` (`report_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $pdo->exec($sql2);
    echo "<p style='color:green;'>✓ 'katodik_koruma_measurements' tablosu başarıyla oluşturuldu!</p>";
    
    echo "<h3 style='color:blue;'>Kurulum Başarıyla Tamamlandı! Lütfen güvenlik için bu dosyayı sunucudan siliniz.</h3>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
