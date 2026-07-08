<?php
require_once __DIR__ . '/../htdocs/includes/db.php';

$search_term = '%112%';
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    // Get columns
    $colStmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($columns as $column) {
        try {
            $query = "SELECT * FROM `$table` WHERE `$column` LIKE ?";
            $searchStmt = $pdo->prepare($query);
            $searchStmt->execute([$search_term]);
            $results = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($results)) {
                echo "Found in Table: $table, Column: $column\n";
                foreach ($results as $row) {
                    echo "  Row ID: " . ($row['id'] ?? 'N/A') . "\n";
                    // print first 200 chars of matching value
                    echo "  Value: " . substr(strip_tags(json_encode($row)), 0, 300) . "...\n\n";
                }
            }
        } catch (Exception $e) {
            // Skip columns that can't be searched (e.g. spatial/etc)
        }
    }
}
