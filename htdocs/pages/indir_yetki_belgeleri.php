<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Compile list of files to merge
$files_to_merge = [];

// 1. Company Documents (Ayarlar)
try {
    $stmt1 = $pdo->query("SELECT filename FROM company_documents");
    while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
        $file_path = realpath('../uploads/firma_belgeler/' . $row['filename']);
        if ($file_path && file_exists($file_path)) {
            $files_to_merge[] = ['path' => $file_path];
        }
    }
} catch (PDOException $e) {}

// 2. Authorized Persons (Yetkili Kişiler)
try {
    $stmt2 = $pdo->query("
        SELECT apd.filename 
        FROM authorized_person_documents apd
        JOIN authorized_persons ap ON apd.person_id = ap.id
    ");
    while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $file_path = realpath('../uploads/yetkili_belgeler/' . $row['filename']);
        if ($file_path && file_exists($file_path)) {
            $files_to_merge[] = ['path' => $file_path];
        }
    }
} catch (PDOException $e) {}

// 3. Devices (Cihazlar)
try {
    $stmt3 = $pdo->query("
        SELECT dcd.filename 
        FROM device_calibration_documents dcd
        JOIN measurement_devices md ON dcd.device_id = md.id
    ");
    while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
        $file_path = realpath('../uploads/cihaz_kalibrasyon_belgeler/' . $row['filename']);
        if ($file_path && file_exists($file_path)) {
            $files_to_merge[] = ['path' => $file_path];
        }
    }
} catch (PDOException $e) {}

if (count($files_to_merge) > 0) {
    // Run Python merger script via proc_open
    $python_path = 'python';
    $script_path = realpath('../services/merge_pdfs.py');
    
    $descriptorspec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"]  // stderr
    ];
    
    // Include system PATH for python location resolution and winsock DLL directories
    $env = [
        'SystemRoot' => getenv('SystemRoot'),
        'windir' => getenv('windir'),
        'PATH' => getenv('PATH')
    ];
    
    $process = proc_open("$python_path " . escapeshellarg($script_path), $descriptorspec, $pipes, null, $env);
    
    if (is_resource($process)) {
        // Write file list as JSON to stdin
        fwrite($pipes[0], json_encode($files_to_merge));
        fclose($pipes[0]);
        
        // Read output from stdout
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        // Read error if any
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        
        $return_value = proc_close($process);
        
        if ($stdout) {
            $response = json_decode($stdout, true);
            if (isset($response['success']) && $response['success'] === true && isset($response['output_path'])) {
                $output_pdf_path = $response['output_path'];
                if (file_exists($output_pdf_path)) {
                    $download_name = 'birlesik_yetki_ve_kalibrasyon_belgeleri_' . time() . '.pdf';
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="' . $download_name . '"');
                    header('Content-Length: ' . filesize($output_pdf_path));
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    readfile($output_pdf_path);
                    unlink($output_pdf_path);
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Belgeler Birleştirilemedi</title>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4 text-center" style="max-width: 450px;">
        <div class="text-warning mb-3">
            <i class="fas fa-exclamation-triangle fa-3x"></i>
        </div>
        <h4 class="mb-3">İndirilecek Belge Bulunamadı</h4>
        <p class="text-muted mb-4">Sistemde kayıtlı herhangi bir şirket yetki belgesi, yetkili kişi yetki belgesi veya cihaz kalibrasyon belgesi tespit edilemedi veya birleştirme işlemi başarısız oldu.</p>
        <a href="raporlar.php" class="btn btn-primary">Raporlar Sayfasına Geri Dön</a>
    </div>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
