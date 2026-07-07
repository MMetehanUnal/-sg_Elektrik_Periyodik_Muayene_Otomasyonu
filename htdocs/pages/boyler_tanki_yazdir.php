<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
if (!$id) {
    die("Rapor ID gerekli.");
}

// Fetch Boyler Tankı Report Data
$stmt = $pdo->prepare("
    SELECT r.*, 
           i.firma_adi, i.adresi, i.sgk_sicil_no, i.il_kodu, i.kurum_kodu,
           ap.adi_soyadi, ap.meslegi, ap.kayit_no, ap.diploma_no, ap.oda_sicil_no
    FROM boyler_tanki_reports r
    JOIN institutions i ON r.kurum_id = i.id
    LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Rapor bulunamadı.");
}

// Parse Donanim and Checklist
$donanim_keys = [
    'd1' => 'Manometre',
    'd2' => 'Su Seviye Göstergesi',
    'd3' => 'Basınç Ayar Otomatiği (presostat)',
    'd4' => 'Güvenlik Ventili Açma Basıncı',
    'd5' => 'Su Seviye Otomatiği',
    'd6' => 'Ana Vana Valfleri',
    'd7' => 'Blöf Vanası'
];
$tank_donanimlari = json_decode($data['tank_donanimlari'] ?? '{}', true);

$questions = [
    'q1' => '1. Manometre çalışıyor ve tüzüğe uygun mu ?',
    'q2' => '2. Güvenlik ventili çalışıyor ve tüzüğe uygun mu ?',
    'q3' => '3. Basınç ayar otomatiği (presostat) çalışıyor ve tüzüğe uygun mu ?',
    'q4' => '4. Blöf vanası çalışıyor ve tüzüğe uygun mu ?',
    'q5' => '1. Tağdiye cihazı bağlantısı tekniğe uygun mu ?',
    'q6' => '2. Yapılan bakım ve onarımlar sicil defterine işleniyor mu ?',
    'q7' => '3. Hidrofor tankı üretim tekniği;',
    'q8' => '3.1 Kaynak dikişleri uygun mu ?',
    'q9' => '3.2 Hidrofor tankı malzemesi uygun mu ?',
    'q10' => '3.3 Hidrofor tankında kalıcı deformasyon var mı ?',
    'q11' => '4. Hidrofor tankının beslenme suyu üzerinde çek valfi var mı ?'
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
                <h1 style="font-size: 14px; margin: 0; line-height: 1.3;">BOYLER TANKI<br>PERİYODİK KONTROL RAPORU</h1>
            </td>
            <td style="width: 30%; border:none; padding: 0;">
                <table class="doc-info" style="border:1px solid black; width:100%; margin: 0;">
                    <tr>
                        <td style="border:1px solid black; width:50%; padding: 2px 4px; font-size: 9px;">Doküman Kodu</td>
                        <td style="border:1px solid black; padding: 2px 4px; font-size: 9px;">BTPKR01</td>
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
    <title>Boyler Tankı Raporu: <?php echo htmlspecialchars($data['report_no']); ?></title>
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
            background-color: #fce4d6; /* Light Orange theme */
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
                                <td style="width: 45%;"><?php 
                                    $full_name = $data['firma_adi'] ?? '';
                                    echo htmlspecialchars($full_name); 
                                ?></td>
                                <td style="width: 15%;" class="header-bg fw-bold">Bölümü</td>
                                <td style="width: 25%;"><?php echo htmlspecialchars($data['firma_adi_eki'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Adresi</td>
                                <td><?php echo htmlspecialchars($data['adresi'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">Kontrol Tarihi</td>
                                <td><?php echo date('d.m.Y', strtotime($data['report_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Telefon</td>
                                <td><?php echo htmlspecialchars($data['phone'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">Bir Sonraki Kontrol</td>
                                <td><?php echo date('d.m.Y', strtotime($data['next_control_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">e-posta</td>
                                <td><?php echo htmlspecialchars($data['email'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">Rapor No</td>
                                <td><?php echo htmlspecialchars($data['report_no'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" style="font-size: 8.5px; font-style: italic; border-top: 1px solid #000; padding: 2px 5px;">
                                    <strong>İlgili Mevzuat ve Standartlar :</strong> <?php echo htmlspecialchars($data['mevzuat']); ?>
                                </td>
                            </tr>
                        </table>

                        <!-- 2. TEKNİK ÖZELLİKLER -->
                        <div class="section-title-bg">TEKNİK ÖZELLİKLER</div>
                        <table>
                            <tr>
                                <td style="width: 15%;" class="header-bg fw-bold">Markası</td>
                                <td style="width: 35%;"><?php echo htmlspecialchars($data['brand'] ?? '-'); ?></td>
                                <td style="width: 20%;" class="header-bg fw-bold">Seri No</td>
                                <td style="width: 30%;"><?php echo htmlspecialchars($data['serial_no'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Modeli</td>
                                <td><?php echo htmlspecialchars($data['model'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">İşletme Basıncı (Bar)</td>
                                <td><?php echo htmlspecialchars($data['operating_pressure'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">İmal Yılı</td>
                                <td><?php echo htmlspecialchars($data['production_year'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">Deneme Basıncı (Bar)</td>
                                <td><?php echo htmlspecialchars($data['test_pressure'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Kapasitesi (lt)</td>
                                <td colspan="3"><?php echo htmlspecialchars($data['capacity'] ?? '-'); ?></td>
                            </tr>
                        </table>

                        <!-- 3. TANK DONANIMLARI -->
                        <div class="section-title-bg">TANK DONANIMLARI</div>
                        <table>
                            <thead>
                                <tr class="header-bg">
                                    <th>Donanım Elemanı</th>
                                    <th class="text-center" style="width: 30%;">(var/yok)</th>
                                    <th class="text-center" style="width: 30%;">(adet/hacim)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($donanim_keys as $key => $label): 
                                    $status = $tank_donanimlari[$key]['status'] ?? 'Yok';
                                    $amount = $tank_donanimlari[$key]['amount'] ?? '0';
                                ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($label); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($status); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($amount); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- 4. TEST VE KONTROLLER -->
                        <div class="section-title-bg">TEST VE KONTROLLER</div>
                        <table>
                            <tbody>
                                <?php foreach ($questions as $key => $text): 
                                    $val = $inspection_results[$key] ?? 'UYGUN';
                                    $is_title = (strpos($text, '3.') === 0 && strpos($text, '3.1') === false && strpos($text, '3.2') === false && strpos($text, '3.3') === false);
                                ?>
                                    <tr>
                                        <td style="width: 80%;" class="<?php echo $is_title ? 'fw-bold bg-light' : ''; ?>">
                                            <?php echo htmlspecialchars($text); ?>
                                        </td>
                                        <td class="text-center fw-bold" style="width: 20%;">
                                            <?php 
                                                if ($is_title) {
                                                    echo '';
                                                } else {
                                                    if ($val == 'UYGUN') {
                                                        if (strpos($text, 'Yapılan bakım') !== false) echo 'Evet';
                                                        else echo 'Uygun';
                                                    } else if ($val == 'UYGUN DEĞİL') {
                                                        if (strpos($text, 'Yapılan bakım') !== false) echo 'Hayır';
                                                        else echo 'Uygun Değil';
                                                    } else {
                                                        echo htmlspecialchars($val);
                                                    }
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- 5. HİDROSTATİK TEST -->
                        <div class="fw-bold" style="margin-top: 5px; margin-bottom: 2px;">HİDROSTATİK TEST :</div>
                        <div class="result-box-compact" style="min-height: 20px; font-style: italic; padding: 4px 6px;">
                            <?php echo !empty($data['hydrostatic_test']) ? nl2br(htmlspecialchars($data['hydrostatic_test'])) : '-'; ?>
                        </div>

                        <!-- 6. NOTLAR VE ÖNERİLER -->
                        <div class="fw-bold" style="margin-top: 5px; margin-bottom: 2px;">NOTLAR VE ÖNERİLER :</div>
                        <div class="result-box-compact bg-light-gray" style="min-height: 20px; padding: 4px 6px;">
                            <?php echo !empty($data['defects']) ? nl2br(htmlspecialchars($data['defects'])) : 'Yapılan periyodik kontrolde herhangi bir eksiklik veya öneri tespit edilmemiştir.'; ?>
                        </div>

                        <!-- 7. SONUÇ -->
                        <div class="fw-bold" style="margin-top: 5px; margin-bottom: 2px;">SONUÇ :</div>
                        <div class="result-box-compact" style="padding: 4px 6px;">
                            <?php echo nl2br(htmlspecialchars($data['result_text'])); ?>
                        </div>

                        <!-- 8. KONTROLÜ YAPAN YETKİLİ KİŞİ -->
                        <table class="signature-table-wrapper" style="margin-top: 4px;">
                            <tr>
                                <td style="width: 65%; vertical-align: top; border: none;">
                                    <table class="compact-inner-table">
                                        <tr>
                                            <td colspan="2" class="header-bg fw-bold text-center" style="padding: 2px 4px; font-size: 8.5px;">Periyodik Kontrolü Yapmaya Yetkili Kişinin</td>
                                        </tr>
                                        <tr>
                                            <td style="width: 35%;" class="header-bg fw-bold">Ad Soyad / Mesleği</td>
                                            <td style="width: 65%;" class="fw-bold"><?php echo htmlspecialchars($data['adi_soyadi'] ?? '-'); ?> / <?php echo htmlspecialchars($data['meslegi'] ?? '-'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="header-bg fw-bold">Diploma Tarihi ve No</td>
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
                                <td style="width: 35%; text-align: center; vertical-align: middle; font-weight: bold; font-size: 10px; background-color: #fafafa; border: 1px solid #000;">
                                    İmzası
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
