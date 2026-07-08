<?php
require_once __DIR__ . '/../htdocs/includes/db.php';
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    if ($count > 0) {
        echo "$t: $count\n";
    }
}
