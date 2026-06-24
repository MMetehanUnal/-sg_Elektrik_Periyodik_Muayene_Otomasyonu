<?php
// ============================================================
// Yıldırımdan Korunma Sonuçları Endpoint'i
// ============================================================

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';

authenticateRequest();
$kurumId = requireInstitution($pdo);
$method = $_SERVER['REQUEST_METHOD'];

requireMethod(['GET']);

try {
    $stmt = $pdo->prepare("SELECT id, report_no, report_date, result FROM lightning_protection_reports WHERE kurum_id = ? ORDER BY id DESC");
    $stmt->execute([$kurumId]);
    jsonSuccess($stmt->fetchAll());
} catch (PDOException $e) {
    jsonError('SERVER_ERROR', 'Veritabanı hatası.', 500, ['db_error' => $e->getMessage()]);
}
