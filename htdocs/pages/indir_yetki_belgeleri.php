<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Filename sanitizer helper
function cleanFileName($string) {
    $tur = ['ç','ğ','ı','ö','ş','ü','Ç','Ğ','İ','Ö','Ş','Ü'];
    $eng = ['c','g','i','o','s','u','C','G','I','O','S','U'];
    $string = str_replace($tur, $eng, $string);
    $string = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $string);
    return trim($string, '_');
}

// Compile list of files to merge
$files_to_merge = [];

// 1. Company Documents (Ayarlar)
try {
    $stmt1 = $pdo->query("SELECT filename, title FROM company_documents");
    while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
        $file_path = realpath('../uploads/firma_belgeler/' . $row['filename']);
        if ($file_path && file_exists($file_path)) {
            $files_to_merge[] = [
                'path' => $file_path,
                'name' => 'firma_belgesi_' . cleanFileName($row['title']) . '.pdf'
            ];
        }
    }
} catch (PDOException $e) {}

// 2. Authorized Persons (Yetkili Kişiler)
try {
    $stmt2 = $pdo->query("
        SELECT apd.filename, ap.adi_soyadi 
        FROM authorized_person_documents apd
        JOIN authorized_persons ap ON apd.person_id = ap.id
    ");
    while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $file_path = realpath('../uploads/yetkili_belgeler/' . $row['filename']);
        if ($file_path && file_exists($file_path)) {
            $files_to_merge[] = [
                'path' => $file_path,
                'name' => 'yetkili_belgesi_' . cleanFileName($row['adi_soyadi']) . '.pdf'
            ];
        }
    }
} catch (PDOException $e) {}

// 3. Devices (Cihazlar)
try {
    $stmt3 = $pdo->query("
        SELECT dcd.filename, md.device_name 
        FROM device_calibration_documents dcd
        JOIN measurement_devices md ON dcd.device_id = md.id
    ");
    while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
        $file_path = realpath('../uploads/cihaz_kalibrasyon_belgeler/' . $row['filename']);
        if ($file_path && file_exists($file_path)) {
            $files_to_merge[] = [
                'path' => $file_path,
                'name' => 'cihaz_kalibrasyon_' . cleanFileName($row['device_name']) . '.pdf'
            ];
        }
    }
} catch (PDOException $e) {}

if (count($files_to_merge) > 0) {
    // Strategy 1: Try Python PDF Merger (Ideal for Local/Windows environments)
    if (function_exists('proc_open')) {
        $python_path = 'python';
        $script_path = realpath('../services/merge_pdfs.py');
        
        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"]  // stderr
        ];
        
        $env = [
            'SystemRoot' => getenv('SystemRoot'),
            'windir' => getenv('windir'),
            'PATH' => getenv('PATH')
        ];
        
        $process = @proc_open("$python_path " . escapeshellarg($script_path), $descriptorspec, $pipes, null, $env);
        
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
                        while (ob_get_level()) {
                            ob_end_clean();
                        }
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/pdf');
                        header('Content-Disposition: attachment; filename="' . $download_name . '"');
                        header('Content-Transfer-Encoding: binary');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($output_pdf_path));
                        readfile($output_pdf_path);
                        @unlink($output_pdf_path);
                        exit;
                    }
                }
            }
        }
    }
    
    // Strategy 2: FPDI Native PHP PDF Merger (For shared hosting without Python / proc_open)
    $pdf_merged = false;
    $output_pdf_path = tempnam(sys_get_temp_dir(), 'pdf_merge_');
    
    try {
        require_once '../includes/fpdf/fpdf.php';
        require_once '../includes/fpdi/autoload.php';
        
        $pdf = new \setasign\Fpdi\Fpdi();
        $pages_added = 0;
        
        foreach ($files_to_merge as $f) {
            $pageCount = $pdf->setSourceFile($f['path']);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
                $pages_added++;
            }
        }
        
        if ($pages_added > 0) {
            $pdf->Output('F', $output_pdf_path);
            if (file_exists($output_pdf_path) && filesize($output_pdf_path) > 0) {
                $pdf_merged = true;
            }
        }
    } catch (\Exception $e) {
        $pdf_merged = false;
        if (file_exists($output_pdf_path)) {
            @unlink($output_pdf_path);
        }
    }
    
    if ($pdf_merged && file_exists($output_pdf_path)) {
        $download_name = 'birlesik_yetki_ve_kalibrasyon_belgeleri_' . time() . '.pdf';
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $download_name . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($output_pdf_path));
        readfile($output_pdf_path);
        @unlink($output_pdf_path);
        exit;
    }
    
    // Strategy 3: ZipArchive Compression Fallback (If Python and FPDI both fail or are unsupported)
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        $zip_filename = tempnam(sys_get_temp_dir(), 'zip');
        
        if ($zip->open($zip_filename, ZipArchive::CREATE) === TRUE) {
            $added_files = 0;
            foreach ($files_to_merge as $idx => $f) {
                $alias = isset($f['name']) ? $f['name'] : basename($f['path']);
                // Ensure unique name or fallback to index prefixed name
                if ($zip->addFile($f['path'], $alias)) {
                    $added_files++;
                }
            }
            $zip->close();
            
            if ($added_files > 0 && file_exists($zip_filename)) {
                $download_name = 'yetki_ve_kalibrasyon_belgeleri_' . time() . '.zip';
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Description: File Transfer');
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $download_name . '"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($zip_filename));
                readfile($zip_filename);
                @unlink($zip_filename);
                exit;
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
