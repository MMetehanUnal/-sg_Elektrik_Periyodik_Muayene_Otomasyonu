<?php
require_once 'includes/db.php';

echo "<h2>Yangın Algılama Fotoğraf Tablosu Kurulumu</h2>";

try {
    $sql = "CREATE TABLE IF NOT EXISTS `fire_detection_photos` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `report_id` int(11) NOT NULL,
      `file_path` varchar(500) NOT NULL,
      `description` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `report_id` (`report_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $pdo->exec($sql);
    echo "<p style='color:green;'>✓ 'fire_detection_photos' tablosu başarıyla oluşturuldu!</p>";
    echo "<h3 style='color:blue;'>Kurulum Başarıyla Tamamlandı! Lütfen güvenlik için bu dosyayı sunucudan siliniz.</h3>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
