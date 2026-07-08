<?php
require_once __DIR__ . '/../htdocs/includes/db.php';

$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    // Check if table has rows
    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    if ($count == 0) continue;
    
    // Get columns
    $colStmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $colStmt->fetchAll();
    
    // Find text/varchar columns
    $text_cols = [];
    foreach ($columns as $col) {
        $type = strtolower($col['Type']);
        if (strpos($type, 'char') !== false || strpos($type, 'text') !== false || strpos($type, 'json') !== false) {
            $text_cols[] = $col['Field'];
        }
    }
    
    if (empty($text_cols)) continue;
    
    // Select them
    $select_fields = implode(', ', array_map(function($c) { return "`$c`"; }, $text_cols));
    $dataStmt = $pdo->query("SELECT * FROM `$table` LIMIT 10");
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $row) {
        foreach ($row as $k => $v) {
            if (empty($v)) continue;
            // Check if it contains "112" or "yang" or "proje" or "GÜVENLİ" or "guvenli"
            $v_lower = strtolower($v);
            if (strpos($v_lower, 'yang') !== false || strpos($v_lower, '112') !== false || strpos($v_lower, 'sond') !== false) {
                echo "Table: $table, Column: $k\n";
                echo "  Value: " . substr(strip_tags($v), 0, 500) . "\n\n";
            }
        }
    }
}
