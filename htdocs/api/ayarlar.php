<?php
/**
 * Ayarlar (Settings) API Endpoint
 * 
 * GET /api/ayarlar.php - Mevcut ayarları ve logoları getir
 * PUT /api/ayarlar.php - Ayarları güncelle (logo_text, logo_type)
 * 
 * Tablo: system_settings, uploaded_logos
 * Not: Logo yükleme ve yönetimi upload/logo.php endpoint'inde yapılır
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/helpers/auth_middleware.php';
require_once __DIR__ . '/helpers/validator.php';

authenticateRequest();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        case 'GET':
            $settings = [
                'logo_text'   => getSetting('logo_text'),
                'logo_type'   => getSetting('logo_type'),
                'active_logo' => getSetting('active_logo'),
            ];

            // Yüklü logoları getir
            $stmt = $pdo->query("SELECT * FROM uploaded_logos ORDER BY id DESC");
            $settings['logos'] = $stmt->fetchAll();

            jsonSuccess($settings, 'Ayarlar');
            break;

        case 'PUT':
            $data = getJsonBody();
            $data = sanitize($data);

            $updated = [];

            if (isset($data['logo_text'])) {
                setSetting('logo_text', $data['logo_text']);
                $updated['logo_text'] = $data['logo_text'];
            }

            if (isset($data['logo_type'])) {
                setSetting('logo_type', $data['logo_type']);
                $updated['logo_type'] = $data['logo_type'];
            }

            if (empty($updated)) {
                jsonError('VALIDATION_ERROR', 'Güncellenecek alan belirtilmedi. Geçerli alanlar: logo_text, logo_type', 422);
            }

            jsonSuccess($updated, 'Ayarlar başarıyla güncellendi');
            break;

        default:
            requireMethod(['GET', 'PUT']);
            break;
    }
} catch (PDOException $e) {
    jsonError('DB_ERROR', 'Veritabanı hatası: ' . $e->getMessage(), 500);
}
