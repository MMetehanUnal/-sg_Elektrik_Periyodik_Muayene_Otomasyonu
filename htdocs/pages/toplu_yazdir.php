<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$reports_str = isset($_GET['reports']) ? trim($_GET['reports']) : '';
if (empty($reports_str)) {
    die("Yazdırılacak rapor seçilmedi.");
}

$kurum_id = $_SESSION['active_institution_id'] ?? 0;
if (!$kurum_id) {
    die("Aktif kurum seçilmemiş.");
}

// Fetch institution details
$stmt_inst = $pdo->prepare("SELECT * FROM institutions WHERE id = ?");
$stmt_inst->execute([$kurum_id]);
$kurum = $stmt_inst->fetch();
if (!$kurum) {
    die("Kurum bulunamadı.");
}

// Cover page metadata
$stmt_cover = $pdo->prepare("SELECT * FROM general_reports WHERE kurum_id = ? ORDER BY id DESC LIMIT 1");
$stmt_cover->execute([$kurum_id]);
$cover_data = $stmt_cover->fetch();

$yurt_yoneticisi = $cover_data['yurt_yoneticisi'] ?? '';
$yatak_kapasitesi = $cover_data['yatak_kapasitesi'] ?? '';
$is_guvenligi_uzmani = $cover_data['is_guvenligi_uzmani'] ?? '';
$ada = $cover_data['ada'] ?? '0';
$pafta = $cover_data['pafta'] ?? '6';
$parsel = $cover_data['parsel'] ?? '446';
$phone = $cover_data['phone'] ?? '';
$report_no = $cover_data['report_no'] ?? '';
$report_date = $cover_data['report_date'] ?? date('Y-m-d');
$control_date = $cover_data['control_date'] ?? date('Y-m-d');
$next_control_date = $cover_data['next_control_date'] ?? date('Y-m-d', strtotime('+1 year -2 days'));
$mekanik_uzman_id = $cover_data['mekanik_uzman_id'] ?? null;
$elektrik_uzman_id = $cover_data['elektrik_uzman_id'] ?? null;

// Fallback search if metadata is empty
if (empty($yurt_yoneticisi) || empty($phone)) {
    try {
        $stmt_def = $pdo->prepare("SELECT phone, next_control_date, kurum_yoneticisi, kurum_kapasitesi FROM sihhi_tesisat_reports WHERE kurum_id = ? ORDER BY id DESC LIMIT 1");
        $stmt_def->execute([$kurum_id]);
        $def_rep = $stmt_def->fetch();
        if ($def_rep) {
            if (empty($phone)) $phone = $def_rep['phone'];
            if (empty($yurt_yoneticisi)) $yurt_yoneticisi = $def_rep['kurum_yoneticisi'];
            if (empty($yatak_kapasitesi)) $yatak_kapasitesi = $def_rep['kurum_kapasitesi'];
        }
    } catch (Exception $ex) {}
}

$mekanik = null;
if (!empty($mekanik_uzman_id)) {
    $stmt_mek = $pdo->prepare("SELECT * FROM authorized_persons WHERE id = ?");
    $stmt_mek->execute([$mekanik_uzman_id]);
    $mekanik = $stmt_mek->fetch();
}
$elektrik = null;
if (!empty($elektrik_uzman_id)) {
    $stmt_elk = $pdo->prepare("SELECT * FROM authorized_persons WHERE id = ?");
    $stmt_elk->execute([$elektrik_uzman_id]);
    $elektrik = $stmt_elk->fetch();
}

// Maps type to PHP print script
$url_map = [
    'topraklama' => 'rapor_yazdir.php',
    'ic_tesisat' => 'ic_tesisat_yazdir.php',
    'yildirim' => 'yildirimdan_korunma_yazdir.php',
    'yangin' => 'yangin_algilama_yazdir.php',
    'sihhi_tesisat' => 'sihhi_tesisat_yazdir.php',
    'gaz_tesisat' => 'gaz_tesisat_yazdir.php',
    'isinma_tesisat' => 'isinma_tesisat_yazdir.php',
    'genlesme_tanki' => 'genlesme_tanki_yazdir.php',
    'engelli_rampasi' => 'engelli_rampasi_yazdir.php',
    'boyler_tanki' => 'boyler_tanki_yazdir.php',
    'jenarator' => 'jenarator_yazdir.php',
    'kamera_bakim' => 'kamera_bakim_yazdir.php',
    'yangin_tesisat' => 'yangin_tesisat_yazdir.php',
    'katodik_koruma' => 'katodik_koruma_yazdir.php'
];

// Helper function to fetch report page HTML using multi-strategy fallbacks
function fetchReportHtml($print_script, $kurum_id, $id, $php_path) {
    // Strategy 1: Local In-Process Eval Sandboxing (Fastest, 100% compatible, avoids "Cannot redeclare" function clashing)
    global $pdo;
    
    $old_get = $_GET;
    $_GET['id'] = $id;
    
    ob_start();
    try {
        if (file_exists($print_script)) {
            $code = file_get_contents($print_script);
            
            // Strip open and close PHP tags
            $code = preg_replace('/^\s*<\?php/i', '', $code);
            $code = preg_replace('/\?>\s*$/', '', $code);
            
            // Define unique function prefix for this file (e.g. rapor_yazdir)
            $prefix = str_replace('.php', '', basename($print_script));
            
            // Rename function definitions (chk and renderHeader) to prevent redeclaration clash
            $code = preg_replace('/function\s+chk\s*\(/i', 'function ' . $prefix . '_chk(', $code);
            $code = preg_replace('/function\s+renderHeader\s*\(/i', 'function ' . $prefix . '_renderHeader(', $code);
            
            // Rename function invocations (chk( and renderHeader() )
            $code = preg_replace('/(?<![a-zA-Z0-9_\$])chk\s*\(/i', $prefix . '_chk(', $code);
            $code = preg_replace('/(?<![a-zA-Z0-9_\$])renderHeader\s*\(/i', $prefix . '_renderHeader(', $code);
            
            eval($code);
        } else {
            echo "Error: print script $print_script not found.";
        }
    } catch (Throwable $e) {
        echo "Error rendering $print_script: " . $e->getMessage() . " on line " . $e->getLine();
    }
    $html = ob_get_clean();
    
    $_GET = $old_get;
    
    if ($html !== false && strlen(trim($html)) > 0) {
        return $html;
    }

    // Strategy 2: Local HTTP Request via cURL (Backup)
    if (function_exists('curl_init') && isset($_SERVER['HTTP_HOST'])) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $dir = dirname($_SERVER['REQUEST_URI']);
        $url = $protocol . '://' . $host . $dir . '/' . $print_script . '?id=' . $id;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Short timeout to fail fast if loopback blocks
        
        // Pass current cookies (including PHPSESSID) to maintain login session
        $cookies = [];
        foreach ($_COOKIE as $name => $value) {
            $cookies[] = $name . '=' . urlencode($value);
        }
        if (!empty($cookies)) {
            curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookies));
        }
        
        // Disable SSL certificate verification (useful for development and self-signed certificates)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($html !== false && $http_code === 200) {
            return $html;
        }
    }

    // Strategy 3: Local HTTP Request via file_get_contents (Backup)
    if (ini_get('allow_url_fopen') && isset($_SERVER['HTTP_HOST'])) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $dir = dirname($_SERVER['REQUEST_URI']);
        $url = $protocol . '://' . $host . $dir . '/' . $print_script . '?id=' . $id;
        
        $cookie_str = '';
        foreach ($_COOKIE as $name => $value) {
            $cookie_str .= $name . '=' . urlencode($value) . '; ';
        }
        
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Cookie: " . $cookie_str . "\r\n",
                'timeout' => 5, // Short timeout
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        
        $context = stream_context_create($opts);
        $html = @file_get_contents($url, false, $context);
        if ($html !== false) {
            return $html;
        }
    }

    // Strategy 4: proc_open PHP CLI (Good fallback for XAMPP / Local systems)
    if (function_exists('proc_open')) {
        $descriptorspec = [
           0 => ["pipe", "r"], // stdin
           1 => ["pipe", "w"], // stdout
           2 => ["pipe", "w"]  // stderr
        ];
        
        $env = getenv();
        if (empty($env)) {
            $env = $_SERVER;
        }
        if (!isset($env['SystemRoot'])) {
            $env['SystemRoot'] = getenv('SystemRoot') ?: 'C:\\Windows';
        }
        if (!isset($env['windir'])) {
            $env['windir'] = getenv('windir') ?: 'C:\\Windows';
        }
        $env['ACTIVE_INSTITUTION_ID'] = $kurum_id;
        $env['REPORT_ID'] = $id;
        
        $process = @proc_open('"' . $php_path . '" ' . escapeshellarg($print_script), $descriptorspec, $pipes, dirname(__FILE__), $env);
        
        if (is_resource($process)) {
            $html = stream_get_contents($pipes[1]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            if ($html) {
                return $html;
            }
        }
    }

    return '';
}

$php_path = 'php'; // Default fallback
if (file_exists('C:\\xampp\\php\\php.exe')) {
    $php_path = 'C:\\xampp\\php\\php.exe';
}

$report_items = explode(',', $reports_str);
$pages_html = [];
$extracted_styles = '';

foreach ($report_items as $item) {
    $parts = explode('_', $item);
    if (count($parts) < 2) continue;
    
    // Support types containing underscores e.g. sihhi_tesisat_15
    $id = intval(array_pop($parts));
    $type = implode('_', $parts);
    
    if (!isset($url_map[$type])) continue;
    
    $print_script = $url_map[$type];
    
    $html = fetchReportHtml($print_script, $kurum_id, $id, $php_path);
    
    if ($html) {
        // Extract styles
        preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $style_matches);
        if (!empty($style_matches[1])) {
            $extracted_styles .= implode("\n", $style_matches[1]) . "\n";
        }
        
        // Extract body
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $body_matches)) {
            $body_content = $body_matches[1];
        } else {
            $body_content = $html;
        }
        
        // Strip out printing navigation / buttons
        $body_content = preg_replace('/<div class="no-print"[^>]*>.*?<\/div>/is', '', $body_content);
        
        $pages_html[] = $body_content;
    }
}

// Logo loading for cover page
$logoText = getSetting($pdo, 'logo_text', 'LOGO');
$logoType = getSetting($pdo, 'logo_type', 'text');
$activeLogo = getSetting($pdo, 'active_logo', '');
$logoHtml = htmlspecialchars($logoText);
if ($logoType === 'image' && !empty($activeLogo)) {
    $logoPath = "../uploads/logos/" . $activeLogo;
    if (file_exists($logoPath)) {
        $logoHtml = '<img src="../uploads/logos/' . htmlspecialchars($activeLogo) . '" style="max-height: 55px; max-width: 150px; object-fit: contain;">';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Toplu Rapor Yazdır</title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        /* Cover Page Styling */
        .cover-page {
            width: 210mm !important;
            margin: 0 auto 20px auto !important;
            background: white !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
            padding: 15mm 20mm !important;
            font-family: Arial, sans-serif !important;
            color: #000 !important;
            box-sizing: border-box !important;
        }
        .cover-page table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 8px !important;
        }
        .cover-page td, .cover-page th {
            border: 1px solid #000 !important;
            padding: 5px 8px !important;
            font-size: 10px !important;
            vertical-align: middle !important;
            line-height: 1.25 !important;
        }
        .cover-page-title-section {
            background-color: #b4c6e7 !important;
            font-weight: bold !important;
            text-align: center !important;
            padding: 4px !important;
            font-size: 10.5px !important;
            border: 1px solid #000 !important;
            margin-top: 8px !important;
            margin-bottom: 4px !important;
            text-transform: uppercase !important;
        }
        .cover-page h2 {
            font-size: 14px !important;
            font-weight: bold !important;
            margin: 0 !important;
            text-transform: uppercase !important;
        }
        .text-red {
            color: red !important;
        }
        .header-bg {
            background-color: #f2f2f2 !important;
            font-weight: bold !important;
        }
        .text-center {
            text-align: center !important;
        }
        .fw-bold {
            font-weight: bold !important;
        }

        .toplu-page-wrapper .main-report-table {
            margin: 0 auto 20px auto !important;
        }

        @page {
            size: A4;
            margin: 15mm 15mm 15mm 15mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .cover-page {
                width: 100% !important;
                margin: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                padding: 0 !important;
                page-break-after: always;
            }
            .toplu-page-wrapper .main-report-table {
                margin: 0 auto !important;
            }
            .toplu-page-wrapper {
                page-break-after: always;
            }
            .toplu-page-wrapper:last-child {
                page-break-after: avoid;
            }
        }
        
        /* Inject dynamically extracted styles from individual pages */
        <?php echo $extracted_styles; ?>
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 14px; font-weight: bold;">
            🖨️ Seçilenleri Yazdır / PDF Kaydet
        </button>
    </div>

    <!-- PAGE 1: COVER PAGE -->
    <div class="cover-page">
        <!-- Header -->
        <table style="border: 1px solid black; margin-bottom: 12px;">
            <tr style="border: 1px solid black;">
                <td style="width: 25%; text-align: center; height: 60px;">
                    <div style="font-weight: 800; font-size: 16px; color: #1f4e79; font-style: italic; line-height: 1;">
                        ISG <span style="font-size: 9px; font-weight: normal; color: #595959; font-style: normal; text-transform: lowercase;">yol haritası</span>
                    </div>
                    <div style="font-size: 6.5px; color: #595959; margin-top: 2px;">iş güvenliğinde kurumsal hafıza</div>
                </td>
                <td style="width: 45%; text-align: center; font-weight: bold; font-size: 11px;">
                    ÖZEL ÖĞRENCİ BARINMA YURDU<br>
                    ELEKTRİK - MEKANİK TESİSAT<br>
                    GÜVENLİĞİ RAPORU
                </td>
                <td style="width: 30%; text-align: center;">
                    <div><?php echo $logoHtml; ?></div>
                </td>
            </tr>
        </table>

        <!-- Institution Large Title -->
        <div style="text-align: center; margin-top: 30px; margin-bottom: 25px;">
            <h2 style="font-size: 14px; font-weight: bold; margin: 0; text-transform: uppercase;">
                <?php echo htmlspecialchars($kurum['firma_adi'] ?? ''); ?>
            </h2>
            <div style="font-size: 11px; font-weight: bold; margin-top: 8px;">
                <?php echo !empty($report_date) ? date('d.m.Y', strtotime($report_date)) : date('d.m.Y'); ?>
            </div>
        </div>

        <!-- GENEL BİLGİLER -->
        <div class="cover-page-title-section">GENEL BİLGİLER</div>
        <table>
            <tr>
                <td style="width: 18%;" class="header-bg fw-bold">Yurt Adı</td>
                <td style="width: 42%;"><?php echo htmlspecialchars($kurum['firma_adi'] ?? ''); ?></td>
                <td style="width: 18%;" class="header-bg fw-bold">Rapor No</td>
                <td style="width: 22%;"><?php echo htmlspecialchars($report_no ?? ''); ?></td>
            </tr>
            <tr>
                <td class="header-bg fw-bold">Yurt Adresi</td>
                <td><?php echo htmlspecialchars($kurum['adresi'] ?? '-'); ?></td>
                <td class="header-bg fw-bold">Rapor Tarihi</td>
                <td><?php echo !empty($report_date) ? date('d.m.Y', strtotime($report_date)) : ''; ?></td>
            </tr>
            <tr>
                <td class="header-bg fw-bold">Yurt Yöneticisi</td>
                <td><?php echo htmlspecialchars($yurt_yoneticisi ?? '-'); ?></td>
                <td class="header-bg fw-bold">Kontrol Tarihi</td>
                <td><?php echo !empty($control_date) ? date('d.m.Y', strtotime($control_date)) : ''; ?></td>
            </tr>
            <tr>
                <td class="header-bg fw-bold">Yurt Yatak Kapasitesi</td>
                <td><?php echo htmlspecialchars($yatak_kapasitesi ?? '-'); ?></td>
                <td class="header-bg fw-bold text-red">Bir Sonraki Kontrol Tarihi</td>
                <td class="fw-bold text-red"><?php echo !empty($next_control_date) ? date('d.m.Y', strtotime($next_control_date)) : ''; ?></td>
            </tr>
            <tr>
                <td class="header-bg fw-bold">İş Güvenliği Uzmanı</td>
                <td><?php echo htmlspecialchars($is_guvenligi_uzmani ?? '-'); ?></td>
                <td class="header-bg fw-bold">Telefon</td>
                <td><?php echo htmlspecialchars($phone ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="header-bg fw-bold">Ada</td>
                <td colspan="3" style="padding: 0;">
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr style="border: none;">
                            <td style="border: none; border-right: 1px solid #000; width: 33.3%;"><?php echo htmlspecialchars($ada ?? '-'); ?></td>
                            <td style="border: none; border-right: 1px solid #000; width: 15%;" class="header-bg fw-bold">Pafta</td>
                            <td style="border: none; border-right: 1px solid #000; width: 18.3%;"><?php echo htmlspecialchars($pafta ?? '-'); ?></td>
                            <td style="border: none; border-right: 1px solid #000; width: 15%;" class="header-bg fw-bold">Parsel</td>
                            <td style="border: none; width: 18.3%;"><?php echo htmlspecialchars($parsel ?? '-'); ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- RAPOR İÇERİĞİ -->
        <div class="cover-page-title-section">RAPOR İÇERİĞİ</div>
        <table>
            <tr>
                <td class="fw-bold">Sıhhi Tesisatı Güvenliği</td>
                <td style="width: 25%;" class="text-center fw-bold">SAYFA -2</td>
            </tr>
            <tr>
                <td class="fw-bold">Gaz Tesisat Güvenliği</td>
                <td class="text-center fw-bold">SAYFA -3</td>
            </tr>
            <tr>
                <td class="fw-bold">Yangın Tesisatı Güvenliği</td>
                <td class="text-center fw-bold">SAYFA -4</td>
            </tr>
            <tr>
                <td class="fw-bold">Isınma Tesisatı Güvenliği</td>
                <td class="text-center fw-bold">SAYFA -5</td>
            </tr>
            <tr>
                <td class="fw-bold">Elektrik Tesisatı Güvenliği</td>
                <td class="text-center fw-bold">SAYFA -6</td>
            </tr>
            <tr>
                <td class="fw-bold">Yetki Belgeleri</td>
                <td class="text-center fw-bold">EK-1</td>
            </tr>
            <tr>
                <td class="fw-bold">Denetim Esnasında Görülen Evraklar</td>
                <td class="text-center fw-bold">EK-2</td>
            </tr>
            <tr>
                <td class="fw-bold">Denetim Fotoğrafları</td>
                <td class="text-center fw-bold">EK-3</td>
            </tr>
        </table>

        <!-- UZMANLAR -->
        <table style="margin-top: 12px; margin-bottom: 0;">
            <tr>
                <td style="width: 50%; padding: 0; vertical-align: top;">
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td colspan="2" class="header-bg fw-bold text-center" style="background-color: #b4c6e7; border-top: none; border-left: none; border-right: none;">MEKANİK TESİSAT KONTROL UZMANI</td>
                        </tr>
                        <tr>
                            <td style="width: 35%; border-left: none;" class="header-bg fw-bold">Ad Soyad / Mesleği</td>
                            <td style="width: 65%; border-right: none;" class="fw-bold"><?php echo htmlspecialchars($mekanik ? $mekanik['adi_soyadi'] : '-'); ?> / <?php echo htmlspecialchars($mekanik ? $mekanik['meslegi'] : '-'); ?></td>
                        </tr>
                        <tr>
                            <td style="border-left: none;" class="header-bg fw-bold">Diploma No / Tarih</td>
                            <td style="border-right: none;"><?php echo htmlspecialchars($mekanik ? $mekanik['diploma_no'] : '-'); ?></td>
                        </tr>
                        <tr>
                            <td style="border-left: none;" class="header-bg fw-bold">Oda Sicil No</td>
                            <td style="border-right: none;"><?php echo htmlspecialchars($mekanik ? $mekanik['oda_sicil_no'] : '-'); ?></td>
                        </tr>
                        <tr>
                            <td style="border-left: none;" class="header-bg fw-bold">Bakanlık Sicil No</td>
                            <td style="border-right: none;"><?php echo htmlspecialchars($mekanik ? $mekanik['kayit_no'] : '-'); ?></td>
                        </tr>
                        <tr>
                            <td style="border-left: none; border-bottom: none; height: 35px;" class="header-bg fw-bold">İmza</td>
                            <td style="border-right: none; border-bottom: none;"></td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%; padding: 0; vertical-align: top;">
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td colspan="2" class="header-bg fw-bold text-center" style="background-color: #b4c6e7; border-top: none; border-left: none; border-right: none;">ELEKTRİK TESİSAT KONTROL UZMANI</td>
                        </tr>
                        <tr>
                            <td style="width: 35%; border-left: none;" class="header-bg fw-bold">Ad Soyad / Mesleği</td>
                            <td style="width: 65%; border-right: none;" class="fw-bold"><?php echo htmlspecialchars($elektrik ? $elektrik['adi_soyadi'] : '-'); ?> / <?php echo htmlspecialchars($elektrik ? $elektrik['meslegi'] : '-'); ?></td>
                        </tr>
                        <tr>
                            <td style="border-left: none;" class="header-bg fw-bold">Diploma No / Tarih</td>
                            <td style="border-right: none;"><?php echo htmlspecialchars($elektrik ? $elektrik['diploma_no'] : '-'); ?></td>
                        </tr>
                        <tr>
                            <td style="border-left: none;" class="header-bg fw-bold">Oda Sicil No</td>
                            <td style="border-right: none;"><?php echo htmlspecialchars($elektrik ? $elektrik['oda_sicil_no'] : '-'); ?></td>
                        </tr>
                        <tr>
                            <td style="border-left: none;" class="header-bg fw-bold">Bakanlık Sicil No</td>
                            <td style="border-right: none;"><?php echo htmlspecialchars($elektrik ? $elektrik['kayit_no'] : '-'); ?></td>
                        </tr>
                        <tr>
                            <td style="border-left: none; border-bottom: none; height: 35px;" class="header-bg fw-bold">İmza</td>
                            <td style="border-right: none; border-bottom: none;"></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- ONAY -->
        <table style="margin-top: 10px; margin-bottom: 0;">
            <tr>
                <td class="header-bg fw-bold text-center" style="background-color: #b4c6e7;">ONAY</td>
            </tr>
            <tr>
                <td style="height: 40px;"></td>
            </tr>
        </table>
    </div>

    <!-- SELECTED PAGES CONTENT -->
    <?php foreach ($pages_html as $page_content): ?>
        <div class="toplu-page-wrapper">
            <?php echo $page_content; ?>
        </div>
        <div style="page-break-after: always;" class="no-print"></div>
    <?php endforeach; ?>

</body>
</html>
