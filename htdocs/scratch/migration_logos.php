<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // 1. Create uploaded_logos table
    $pdo->exec("CREATE TABLE IF NOT EXISTS uploaded_logos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 2. Insert new settings into system_settings
    $stmt1 = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('logo_type', 'text')");
    $stmt1->execute();
    
    $stmt2 = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('active_logo', '')");
    $stmt2->execute();
    
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
?>
