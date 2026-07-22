<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
if (!$id) {
    die("Rapor ID gerekli.");
}

// Fetch Generator Report Data
$stmt = $pdo->prepare("
    SELECT r.*, 
           i.firma_adi, i.adresi, i.sgk_sicil_no, i.il_kodu, i.kurum_kodu,
           ap.adi_soyadi, ap.meslegi, ap.kayit_no, ap.diploma_no, ap.oda_sicil_no
    FROM jenarator_reports r
    JOIN institutions i ON r.kurum_id = i.id
    LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Rapor bulunamadı.");
}

$questions = [
    'q1' => 'Jeneratörün konulduğu yer ve alan uygun mu?',
    'q2' => 'Jeneratörün koruyucu kabini var mı?',
    'q3' => 'Motor suyu seviyesi uygun mu?',
    'q4' => 'Yağ seviyesi uygun mu?',
    'q5' => 'Su kaçağı var mı?',
    'q6' => 'Yağ kaçağı var mı?',
    'q7' => 'Yakıt kaçağı var mı?',
    'q8' => 'Yakıt seviyesi gösterme paneli uygun mu?',
    'q9' => 'Akü şarj ünitesi uygun mu?',
    'q10' => 'Aküde şişme ve sızıntı var mı?',
    'q11' => 'Kablolar uygun mu?',
    'q12' => 'Yetkili servis bakım kayıtları düzenli olarak tutuluyor mu?'
];
$inspection_results = json_decode($data['inspection_results'] ?? '{}', true);

function renderHeader()
{
    global $pdo;
    $logoText = getSetting($pdo, 'logo_text', 'LOGO');
    $logoType = getSetting($pdo, 'logo_type', 'text');
    $activeLogo = getSetting($pdo, 'active_logo', '');
    
    $logoHtml = htmlspecialchars($logoText);
    if ($logoType === 'image' && !empty($activeLogo)) {
        $logoPath = "../uploads/logos/" . $activeLogo;
        if (file_exists($logoPath)) {
            $logoHtml = '<img src="../uploads/logos/' . htmlspecialchars($activeLogo) . '" style="max-height: 60px; max-width: 150px; object-fit: contain;">';
        }
    }
    ?>
    <table style="border:none; margin: 0; padding: 0;">
        <tr style="border:none;">
            <td style="width: 20%; height: 60px; border:none; text-align:center; vertical-align:middle; padding: 0;">
                <div style="font-weight:bold; font-size:16px;"><?php echo $logoHtml; ?></div>
            </td>
            <td style="width: 50%; text-align: center; border:none; padding: 0; vertical-align:middle;">
                <h1 style="font-size: 14px; margin: 0; line-height: 1.3;">JENERATÖR<br>PERİYODİK KONTROL RAPORU</h1>
            </td>
            <td style="width: 30%; border:none; padding: 0;">
                <table class="doc-info" style="border:1px solid black; width:100%; margin: 0;">
                    <tr>
                        <td style="border:1px solid black; width:50%; padding: 2px 4px; font-size: 9px;">Doküman Kodu</td>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">JENPKR01</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">Yayım Tarihi</td>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">18.07.2025</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">Revizyon No</td>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">-</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">Revizyon Tarihi</td>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">-</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">Yürürlük Tarihi</td>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">01.09.2025</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <hr style="border: 1px solid black; margin-top:5px; margin-bottom:5px;">
    <?php
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Jeneratör Raporu: <?php echo htmlspecialchars($data['report_no']); ?></title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9.5px;
            line-height: 1.25;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }

        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }

        .no-print button {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            border-radius: 4px;
            font-size: 11px;
        }

        .header-bg {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .section-title-bg {
            background-color: #e2efda; /* Light Green theme */
            font-weight: bold;
            text-align: center;
            padding: 3px 6px;
            border: 1px solid black;
            margin-top: 6px;
            margin-bottom: 3px;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        th, td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: middle;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .result-box-compact {
            border: 1px solid #000;
            padding: 5px 7px;
            margin-bottom: 5px;
            font-size: 9px;
            background-color: #fff;
        }

        .signature-table-wrapper {
            margin-top: 4px;
        }

        .signature-table-wrapper td {
            padding: 0;
        }

        .compact-inner-table {
            width: 100%;
            margin: 0;
            border: none;
        }

        .compact-inner-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 9px;
        }

        .compact-inner-table tr:first-child td {
            border-top: none;
        }
        .compact-inner-table tr:last-child td {
            border-bottom: none;
        }
        .compact-inner-table tr td:first-child {
            border-left: none;
        }
        .compact-inner-table tr td:last-child {
            border-right: none;
        }

        /* A4 Page constraints */
        @page {
            size: A4;
            margin: 8mm 10mm 8mm 10mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .section-title-bg, table, .result-box-compact, .signature-table-wrapper {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">
            <i class="fas fa-print"></i> Yazdır / PDF Kaydet
        </button>
    </div>

    <table class="main-report-table" style="border:none; margin:0 auto;">
        <thead>
            <tr>
                <th style="border:none; padding:0;">
                    <?php renderHeader(); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border:none; padding:0;">
                    <div class="page">
                        
                        <!-- 1. FİRMA BİLGİLERİ -->
                        <table>
                            <tr>
                                <td style="width: 15%;" class="header-bg fw-bold">Firma Adı</td>
                                <td style="width: 45%;"><?php echo htmlspecialchars($data['firma_adi'] ?? ''); ?></td>
                                <td style="width: 15%;" class="header-bg fw-bold">Bölüm</td>
                                <td style="width: 25%;"><?php echo htmlspecialchars($data['firma_adi_eki'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Adres</td>
                                <td><?php echo htmlspecialchars($data['adresi'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">Kontrol Tarihi</td>
                                <td><?php echo date('d.m.Y', strtotime($data['report_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Rapor No</td>
                                <td><?php echo htmlspecialchars($data['report_no'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">Bir Sonraki Kontrol</td>
                                <td><?php echo date('d.m.Y', strtotime($data['next_control_date'])); ?></td>
                            </tr>
                        </table>

                        <!-- 2. TEKNİK ÖZELLİKLER -->
                        <div class="section-title-bg">TEKNİK ÖZELLİKLER</div>
                        <table>
                            <tr>
                                <td style="width: 20%;" class="header-bg fw-bold">Markası ve Modeli</td>
                                <td style="width: 30%;"><?php echo htmlspecialchars($data['brand_model'] ?? '-'); ?></td>
                                <td style="width: 20%;" class="header-bg fw-bold">Seri No</td>
                                <td style="width: 30%;"><?php echo htmlspecialchars($data['serial_no'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">İmal Yılı</td>
                                <td><?php echo htmlspecialchars($data['production_year'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">Kapasite (kVA)</td>
                                <td><?php echo htmlspecialchars($data['capacity'] ?? '-'); ?></td>
                            </tr>
                        </table>

                        <!-- 3. KONTROLLER -->
                        <div class="section-title-bg">KONTROLLER</div>
                        <table>
                            <tbody>
                                <?php 
                                foreach ($questions as $key => $text): 
                                    $val = $inspection_results[$key] ?? 'Evet';
                                ?>
                                    <tr>
                                        <td style="width: 80%;"><?php echo htmlspecialchars($text); ?></td>
                                        <td class="text-center fw-bold" style="width: 20%;">
                                            <?php 
                                                if ($val == 'Evet' || $val == 'UYGUN') echo 'EVET';
                                                elseif ($val == 'Hayır' || $val == 'UYGUN DEĞİL') echo 'HAYIR';
                                                else echo 'UYGULANMAZ';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- 4. İKAZ VE ÖNERİLER -->
                        <div class="fw-bold" style="margin-top: 5px; margin-bottom: 2px;">İKAZ VE ÖNERİLER :</div>
                        <div class="result-box-compact" style="min-height: 20px; padding: 4px 6px;">
                            <?php echo !empty($data['defects']) ? nl2br(htmlspecialchars($data['defects'])) : 'Periyodik bakım ve kontrolleri düzenli olarak yapılmalı ve kayıtları muhafaza edilmelidir.'; ?>
                        </div>

                        <!-- 5. SONUÇ -->
                        <div class="fw-bold" style="margin-top: 5px; margin-bottom: 2px;">SONUÇ :</div>
                        <div class="result-box-compact" style="padding: 4px 6px;">
                            <?php echo nl2br(htmlspecialchars($data['result_text'])); ?>
                        </div>

                        <!-- 6. KONTROLÜ YAPAN YETKİLİ KİŞİ -->
                        <div class="fw-bold" style="margin-top: 8px; margin-bottom: 2px;">6. KONTROLÜ GERÇEKLEŞTİREN YETKİLİ KİŞİ BİLGİLERİ VE ONAY :</div>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 4px;">
                            <tr>
                                <td class="header-bg fw-bold" style="width: 25%;">Adı Soyadı / Unvanı</td>
                                <td style="width: 45%;"><?php echo htmlspecialchars($data['adi_soyadi'] ?? '-'); ?> / <?php echo htmlspecialchars($data['meslegi'] ?? '-'); ?></td>
                                <td rowspan="4" style="width: 30%; vertical-align: middle; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #fafafa;">İMZA / ONAY</td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Diploma No / Tarih</td>
                                <td><?php echo htmlspecialchars($data['diploma_no'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Oda Sicil No</td>
                                <td><?php echo htmlspecialchars($data['oda_sicil_no'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Bakanlık Sicil No (Kayıt No)</td>
                                <td><?php echo htmlspecialchars($data['kayit_no'] ?? '-'); ?></td>
                            </tr>
                        </table>

                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
