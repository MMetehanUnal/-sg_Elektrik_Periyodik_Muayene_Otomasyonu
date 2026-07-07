<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
if (!$id) {
    die("Rapor ID gerekli.");
}

// Fetch Heating Report Data
$stmt = $pdo->prepare("
    SELECT r.*, 
           i.firma_adi, i.adresi, i.sgk_sicil_no, i.il_kodu, i.kurum_kodu,
           ap.adi_soyadi, ap.meslegi, ap.kayit_no, ap.diploma_no, ap.oda_sicil_no
    FROM isinma_tesisat_reports r
    JOIN institutions i ON r.kurum_id = i.id
    LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Rapor bulunamadı.");
}

// Parse Checklist Questions
$questions = [
    'q1' => 'Isınma sistemi projesi mevcut mu?',
    'q2' => 'Isınma cihazına ait periyodik kontrol raporu var mı?',
    'q3' => 'Kazancı / Ateşçi belgesi var mı?',
    'q4' => 'Yakıtların depolama koşulları uygun mu?',
    'q5' => 'Kazan dairesinin alt / üst havalandırması mevcut mu?',
    'q6' => 'Isı merkezi alanı uygun mu?',
    'q7' => 'Isı merkezine yetkisiz kişilerin erişimi engellenmiş mi?',
    'q8' => 'Isınma cihazı çalışma basıncı bina statik yüksekliği ile uyumlu mu?',
    'q9' => 'Kapalı genleşme tankı uygun mu?',
    'q10' => 'Yakıt türüne uygun baca uygulaması mevcut mu?',
    'q11' => 'Çelik bacalar için topraklama bağlantısı var mı?',
    'q12' => 'Gerekli noktalarda hava atıcıları mevcut mu?',
    'q13' => 'Elektrik bağlantısı uygun mu?',
    'q14' => 'Aydınlatma (Kapalı etanj ve üst havalandırmanın altında) uygun mu?',
    'q15' => 'Gaz alarm cihazı (Exproof) var mı?'
];

$inspection_results = [];
if (!empty($data['inspection_results'])) {
    $inspection_results = json_decode($data['inspection_results'], true);
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
                <h1 style="font-size: 15px; margin: 0; line-height: 1.3;">ISINMA TESİSATI GÜVENLİĞİ<br>PERİYODİK KONTROL RAPORU</h1>
            </td>
            <td style="width: 30%; border:none; padding: 0;">
                <table class="doc-info" style="border:1px solid black; width:100%; margin: 0;">
                    <tr>
                        <td style="border:1px solid black; width:50%; padding: 2px 4px; font-size: 9px;">Doküman Kodu</td>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">ITPKR01</td>
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
    <title>Isınma Tesisatı Raporu: <?php echo htmlspecialchars($data['report_no']); ?></title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
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
            background-color: #dce6f1; /* Light Blue theme for Heating */
            font-weight: bold;
            text-align: left;
            padding: 4px 8px;
            border: 1px solid black;
            margin-top: 8px;
            margin-bottom: 4px;
            font-size: 10px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: left;
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
            padding: 6px 8px;
            margin-bottom: 6px;
            font-size: 9.5px;
            background-color: #fff;
        }

        .result-badge {
            font-weight: bold;
            text-decoration: underline;
        }

        .result-badge.safe {
            color: green;
        }

        .result-badge.unsafe {
            color: red;
        }

        .signature-table-wrapper {
            margin-top: 6px;
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
            padding: 3px 5px;
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
            margin: 10mm 12mm 10mm 12mm;
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
                        <div class="section-title-bg">1. FİRMA BİLGİLERİ</div>
                        <table>
                            <tr>
                                <td style="width: 20%;" class="header-bg">Kurum Adı</td>
                                <td colspan="3" style="width: 80%;"><?php 
                                    $full_name = $data['firma_adi'] ?? '';
                                    if (!empty($data['firma_adi_eki'])) {
                                        $full_name .= ' - ' . $data['firma_adi_eki'];
                                    }
                                    echo htmlspecialchars($full_name); 
                                ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kurum Adresi</td>
                                <td colspan="3"><?php echo htmlspecialchars($data['adresi'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td style="width: 20%;" class="header-bg">Kurum Yöneticisi</td>
                                <td style="width: 30%;"><?php echo htmlspecialchars($data['kurum_yoneticisi'] ?? '-'); ?></td>
                                <td style="width: 20%;" class="header-bg">Kurum Kapasitesi</td>
                                <td style="width: 30%;"><?php echo htmlspecialchars($data['kurum_kapasitesi'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Rapor Numarası</td>
                                <td><?php echo htmlspecialchars($data['report_no'] ?? '-'); ?></td>
                                <td class="header-bg">Rapor Tarihi</td>
                                <td><?php echo date('d.m.Y', strtotime($data['report_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Bir Sonraki Kontrol Tarihi</td>
                                <td><?php echo date('d.m.Y', strtotime($data['next_control_date'])); ?></td>
                                <td class="header-bg">İSG-KATİP Sözleşme ID</td>
                                <td><?php echo htmlspecialchars($data['isg_katip_id'] ?? '-'); ?></td>
                            </tr>
                        </table>

                        <!-- 2. TESPİT ve DEĞERLENDİRME SORULARI -->
                        <div class="section-title-bg">2. TESPİT ve DEĞERLENDİRME SORULARI</div>
                        <table>
                            <thead>
                                <tr class="header-bg">
                                    <th style="width: 5%;" class="text-center">NO</th>
                                    <th style="width: 75%;">SORU</th>
                                    <th style="width: 20%;" class="text-center">DEĞERLENDİRME</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $idx = 1;
                                foreach ($questions as $key => $text): 
                                    $val = $inspection_results[$key] ?? 'UYGUN';
                                ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo $idx++; ?></td>
                                        <td><?php echo htmlspecialchars($text); ?></td>
                                        <td class="text-center fw-bold"><?php echo htmlspecialchars($val); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- 3. ÖNERİLER -->
                        <div class="section-title-bg">3. ÖNERİLER</div>
                        <div class="result-box-compact bg-light-gray" style="min-height: 25px; padding: 4px 6px;">
                            <?php echo !empty($data['defects']) ? nl2br(htmlspecialchars($data['defects'])) : 'Isınma tesisatı yönünden herhangi bir uygunsuzluk tespit edilmemiştir.'; ?>
                        </div>

                        <!-- 4. SONUÇ -->
                        <div class="section-title-bg">4. SONUÇ</div>
                        <div class="result-box-compact" style="padding: 4px 6px;">
                            Yukarıda tespit ve değerlendirme sorularına göre adı geçen tesis Isınma Tesisatı yönünden 
                            <?php if ($data['result'] == 'GÜVENLİDİR' || $data['result'] == 'UYGUNDUR'): ?>
                                <span class="result-badge safe">UYGUNDUR.</span>
                            <?php else: ?>
                                <span class="result-badge unsafe">UYGUN DEĞİLDİR.</span>
                            <?php endif; ?>
                            <?php if (!empty($data['notes'])): ?>
                                <span style="margin-left: 10px; font-style: italic;"><?php echo htmlspecialchars($data['notes']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- 5. KONTROLÜ GERÇEKLEŞTİREN VE İMZA -->
                        <div class="section-title-bg">5. KONTROLÜ GERÇEKLEŞTİREN</div>
                        <table class="signature-table-wrapper" style="margin-top: 2px;">
                            <tr>
                                <td style="width: 65%; vertical-align: top; border: none;">
                                    <table class="compact-inner-table">
                                        <tr>
                                            <td style="width: 30%;" class="header-bg fw-bold">Ad Soyad / Mesleği</td>
                                            <td style="width: 70%;" class="fw-bold"><?php echo htmlspecialchars($data['adi_soyadi'] ?? '-'); ?> / <?php echo htmlspecialchars($data['meslegi'] ?? '-'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="header-bg fw-bold">Diploma No</td>
                                            <td><?php echo htmlspecialchars($data['diploma_no'] ?? '-'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="header-bg fw-bold">Oda Sicil No</td>
                                            <td><?php echo htmlspecialchars($data['oda_sicil_no'] ?? '-'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="header-bg fw-bold">Bakanlık Sicil No</td>
                                            <td><?php echo htmlspecialchars($data['kayit_no'] ?? '-'); ?></td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width: 35%; text-align: center; vertical-align: middle; font-weight: bold; font-size: 11px; background-color: #fafafa; border: 1px solid #000;">
                                    İMZA / ONAY
                                    <br><br><br>
                                </td>
                            </tr>
                        </table>

                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
