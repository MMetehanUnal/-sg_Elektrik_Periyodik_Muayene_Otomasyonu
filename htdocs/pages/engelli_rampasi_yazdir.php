<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
if (!$id) {
    die("Rapor ID gerekli.");
}

// Fetch Engelli Rampasi Report Data
$stmt = $pdo->prepare("
    SELECT r.*, 
           i.firma_adi, i.adresi, i.sgk_sicil_no, i.il_kodu, i.kurum_kodu,
           ap.adi_soyadi, ap.meslegi, ap.kayit_no, ap.diploma_no, ap.oda_sicil_no
    FROM engelli_rampasi_reports r
    JOIN institutions i ON r.kurum_id = i.id
    LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Rapor bulunamadı.");
}

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
                <h1 style="font-size: 14px; margin: 0; line-height: 1.3;">ENGELLİ RAMPASI<br>PERİYODİK KONTROL RAPORU</h1>
            </td>
            <td style="width: 30%; border:none; padding: 0;">
                <table class="doc-info" style="border:1px solid black; width:100%; margin: 0;">
                    <tr>
                        <td style="border:1px solid black; width:50%; padding: 2px 4px; font-size: 9px;">Doküman Kodu</td>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">ERPKR01</td>
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
    <title>Engelli Rampası Kontrol Raporu: <?php echo htmlspecialchars($data['report_no']); ?></title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .content-box {
            border: 1.5px solid #000;
            padding: 30px 20px;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.6;
            margin-top: 30px;
            margin-bottom: 40px;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fff;
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
            padding: 4px 6px;
            font-size: 9.5px;
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
            table, .content-box, .signature-table-wrapper {
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
                        
                        <!-- metadata table -->
                        <table>
                            <tr>
                                <td style="width: 20%;" class="header-bg fw-bold">Rapor No</td>
                                <td style="width: 30%;"><?php echo htmlspecialchars($data['report_no'] ?? '-'); ?></td>
                                <td style="width: 20%;" class="header-bg fw-bold">Rapor Tarihi</td>
                                <td style="width: 30%;"><?php echo date('d.m.Y', strtotime($data['report_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Kontrol Nedeni</td>
                                <td><?php echo htmlspecialchars($data['control_reason'] ?? 'Periyodik Kontrol'); ?></td>
                                <td class="header-bg fw-bold">Bir Sonraki Kontrol</td>
                                <td><?php echo date('d.m.Y', strtotime($data['next_control_date'])); ?></td>
                            </tr>
                        </table>

                        <!-- Certificate Content Box -->
                        <div class="content-box">
                            <?php echo nl2br(htmlspecialchars($data['report_text'])); ?>
                        </div>

                        <!-- Inspector credentials -->
                        <!-- KONTROLÜ GERÇEKLEŞTİREN YETKİLİ KİŞİ BİLGİLERİ VE ONAY -->
                        <div class="fw-bold" style="margin-top: 15px; margin-bottom: 2px; font-size: 11px;">KONTROLÜ GERÇEKLEŞTİREN YETKİLİ KİŞİ BİLGİLERİ VE ONAY :</div>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 4px;">
                            <tr>
                                <td class="header-bg fw-bold" style="width: 25%; border: 1px solid #000; padding: 4px 6px;">Adı Soyadı / Unvanı</td>
                                <td style="width: 45%; border: 1px solid #000; padding: 4px 6px;"><?php echo htmlspecialchars($data['adi_soyadi'] ?? '-'); ?> / <?php echo htmlspecialchars($data['meslegi'] ?? '-'); ?></td>
                                <td rowspan="4" style="width: 30%; vertical-align: middle; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #fafafa; padding: 4px 6px;">İMZA / ONAY</td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold" style="border: 1px solid #000; padding: 4px 6px;">Diploma No / Tarih</td>
                                <td style="border: 1px solid #000; padding: 4px 6px;"><?php echo htmlspecialchars($data['diploma_no'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold" style="border: 1px solid #000; padding: 4px 6px;">Oda Sicil No</td>
                                <td style="border: 1px solid #000; padding: 4px 6px;"><?php echo htmlspecialchars($data['oda_sicil_no'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold" style="border: 1px solid #000; padding: 4px 6px;">Bakanlık Sicil No (Kayıt No)</td>
                                <td style="border: 1px solid #000; padding: 4px 6px;"><?php echo htmlspecialchars($data['kayit_no'] ?? '-'); ?></td>
                            </tr>
                        </table>

                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
