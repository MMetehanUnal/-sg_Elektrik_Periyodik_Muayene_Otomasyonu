<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['contract_pdf'])) {
    $file = $_FILES['contract_pdf'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Dosya yüklenirken hata oluştu. Hata kodu: ' . $file['error']]);
        exit;
    }
    
    $upload_dir = '../uploads/sozlesmeler/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = 'contract_' . time() . '_' . rand(1000, 9999) . '.pdf';
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Run Python extraction script
        $python_path = 'python';
        $script_path = __DIR__ . '/extract_pdf_data.py';
        
        $cmd = escapeshellcmd("$python_path " . escapeshellarg($script_path) . " " . escapeshellarg($target_path));
        $output = shell_exec($cmd);
        
        if ($output) {
            $parsed_data = json_decode($output, true);
            if (!isset($parsed_data['error'])) {
                $parsed_data['contract_pdf'] = $filename; // Add the permanent filename to response
                echo json_encode($parsed_data);
            } else {
                // Parse error, delete file
                if (file_exists($target_path)) {
                    unlink($target_path);
                }
                echo $output;
            }
        } else {
            if (file_exists($target_path)) {
                unlink($target_path);
            }
            echo json_encode(['error' => 'PDF verisi işlenemedi.']);
        }
    } else {
        echo json_encode(['error' => 'Dosya geçici olarak kaydedilemedi.']);
    }
} else {
    echo json_encode(['error' => 'Geçersiz istek metodu.']);
}
