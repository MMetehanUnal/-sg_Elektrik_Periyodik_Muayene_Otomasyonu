<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
if (!$id) {
    die("Rapor ID gerekli.");
}

// Fetch Katodik Koruma Report Data
$stmt = $pdo->prepare("
    SELECT r.*, 
           i.firma_adi, i.adresi, i.sgk_sicil_no, i.il_kodu, i.kurum_kodu,
           ap.adi_soyadi, ap.meslegi, ap.kayit_no, ap.diploma_no, ap.oda_sicil_no
    FROM katodik_koruma_reports r
    JOIN institutions i ON r.kurum_id = i.id
    LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Rapor bulunamadı.");
}

// Fetch associated measurements
$stmt_m = $pdo->prepare("SELECT * FROM katodik_koruma_measurements WHERE report_id = ? ORDER BY id ASC");
$stmt_m->execute([$id]);
$measurements = $stmt_m->fetchAll();

// Header Function
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
    <table style="border:none; width: 100%; margin-top: 0; margin-bottom: 0;">
        <tr style="border:none;">
            <td style="width: 20%; height: 60px; border:none; text-align:center; vertical-align:middle;">
                <!-- Logo Place Holder -->
                <div style="font-weight:bold; font-size:16px;"><?php echo $logoHtml; ?></div>
            </td>
            <td style="width: 50%; text-align: center; border:none;">
                <h1 style="font-size: 14px; text-align: center; margin: 0; line-height: 1.25;">GALVANİK ANOTLU KATODİK KORUMA<br>ÖLÇÜM RAPORU</h1>
            </td>
            <td style="width: 30%; border:none;">
                <table class="doc-info" style="border:1px solid black; width:100%; border-collapse: collapse; margin-top: 0; margin-bottom: 0;">
                    <tr>
                        <td style="border:1px solid black; width:50%; padding: 2px 4px;">Doküman Kodu</td>
                        <td style="border:1px solid black; padding: 2px 4px;">KKPR01</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; padding: 2px 4px;">Yayım Tarihi</td>
                        <td style="border:1px solid black; padding: 2px 4px;">18.07.2025</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; padding: 2px 4px;">Revizyon No</td>
                        <td style="border:1px solid black; padding: 2px 4px;">-</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; padding: 2px 4px;">Revizyon Tarihi</td>
                        <td style="border:1px solid black; padding: 2px 4px;">-</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; padding: 2px 4px;">Yürürlük Tarihi</td>
                        <td style="border:1px solid black; padding: 2px 4px;">01.09.2025</td>
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
    <title>Katodik Koruma Ölçüm Raporu: <?php echo htmlspecialchars($data['report_no']); ?></title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9.5px;
            line-height: 1.3;
            color: #000;
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
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
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

        /* Checkbox representations */
        .check-box {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #000;
            text-align: center;
            line-height: 9px;
            font-weight: bold;
            font-size: 9px;
            margin-right: 4px;
            vertical-align: middle;
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
            .section-title-bg, table, .result-box-compact {
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
        <tbody>
            <tr>
                <td style="border:none; padding:0;">
                    <div class="page">
                        
                        <?php renderHeader(); ?>

                        <!-- A- ÖLÇÜMÜ TALEP EDEN -->
                        <div class="section-title-bg">A- ÖLÇÜMÜ TALEP EDEN</div>
                        <table>
                            <tr>
                                <td style="width: 25%;" class="header-bg fw-bold">İLGİLİ KİŞİ</td>
                                <td style="width: 75%;" colspan="3"><?php echo htmlspecialchars($data['firma_adi'] . ' (' . ($data['firma_adi_eki'] ?: '-') . ')'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">ÖLÇÜM YAPILAN YERİN ADRESİ</td>
                                <td colspan="3"><?php echo htmlspecialchars($data['adresi']); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">ÖLÇÜM TARİHİ</td>
                                <td><?php echo date('d.m.Y', strtotime($data['report_date'])); ?></td>
                                <td class="header-bg fw-bold" style="width: 15%;">SÖZLEŞME ID</td>
                                <td><?php echo htmlspecialchars($data['isg_katip_id'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">RAPOR NO</td>
                                <td colspan="3"><?php echo htmlspecialchars($data['report_no']); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">ZEMİN</td>
                                <td colspan="3">
                                    <span class="check-box"><?php echo ($data['zemin'] == 'Deniz') ? 'X' : '&nbsp;'; ?></span> Deniz &nbsp;&nbsp;&nbsp;&nbsp;
                                    <span class="check-box"><?php echo ($data['zemin'] == 'Toprak') ? 'X' : '&nbsp;'; ?></span> Toprak &nbsp;&nbsp;&nbsp;&nbsp;
                                    <span class="check-box"><?php echo ($data['zemin'] == 'Beton') ? 'X' : '&nbsp;'; ?></span> Beton
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">TOPRAK DURUMU</td>
                                <td colspan="3">
                                    <span class="check-box"><?php echo ($data['toprak_durumu'] == 'Islak') ? 'X' : '&nbsp;'; ?></span> Islak &nbsp;&nbsp;&nbsp;&nbsp;
                                    <span class="check-box"><?php echo ($data['toprak_durumu'] == 'Nemli') ? 'X' : '&nbsp;'; ?></span> Nemli &nbsp;&nbsp;&nbsp;&nbsp;
                                    <span class="check-box"><?php echo ($data['toprak_durumu'] == 'Kuru') ? 'X' : '&nbsp;'; ?></span> Kuru
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">KONTROL NEDENİ</td>
                                <td colspan="3">
                                    <span class="check-box"><?php echo ($data['control_reason'] == 'Periyodik Kontrol') ? 'X' : '&nbsp;'; ?></span> Periyodik &nbsp;&nbsp;&nbsp;&nbsp;
                                    <span class="check-box"><?php echo ($data['control_reason'] == 'Tekrar Kontrol') ? 'X' : '&nbsp;'; ?></span> Tekrar &nbsp;&nbsp;&nbsp;&nbsp;
                                    <span class="check-box"><?php echo ($data['control_reason'] == 'Yeni Tesis') ? 'X' : '&nbsp;'; ?></span> Yeni Tesis
                                </td>
                            </tr>
                        </table>

                        <!-- B- TESİS BİLGİLERİ -->
                        <div class="section-title-bg">B- TESİS BİLGİLERİ</div>
                        <table>
                            <tr>
                                <td style="width: 25%;" class="header-bg fw-bold">TESİSE AİT PROJE VAR MI?</td>
                                <td style="width: 75%;">
                                    <span class="check-box"><?php echo ($data['tesis_proje_var_mi'] == 'Var') ? 'X' : '&nbsp;'; ?></span> Var &nbsp;&nbsp;&nbsp;&nbsp;
                                    <span class="check-box"><?php echo ($data['tesis_proje_var_mi'] == 'Yok') ? 'X' : '&nbsp;'; ?></span> Yok
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">ÖLÇÜ KUTUSU SAYISI</td>
                                <td><?php echo htmlspecialchars($data['olcu_kutusu_sayisi']); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">REFERANS ELEKTROT TİPİ</td>
                                <td><?php echo htmlspecialchars($data['referans_elektrot_tipi']); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">TESİSİN KULLANIM AMACI</td>
                                <td><?php echo htmlspecialchars($data['tesisin_kullanim_amaci']); ?></td>
                            </tr>
                        </table>

                        <!-- C- ÖLÇÜM ALETLERİ BİLGİLERİ -->
                        <div class="section-title-bg">C- ÖLÇÜM ALETLERİ BİLGİLERİ</div>
                        <table>
                            <tr>
                                <td class="header-bg fw-bold" style="width: 25%;">Cihaz adı</td>
                                <td style="width: 75%;" colspan="3"><?php echo htmlspecialchars($data['olcum_cihazi']); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold" style="width: 25%;">Seri No</td>
                                <td style="width: 75%;" colspan="3"><?php echo htmlspecialchars($data['seri_no']); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold" style="width: 25%;">Kalibrasyon Yapan Kurum</td>
                                <td style="width: 75%;" colspan="3"><?php echo htmlspecialchars($data['kalibrasyon_kurum']); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold" style="width: 25%;">Kal. No</td>
                                <td style="width: 25%;"><?php echo htmlspecialchars($data['kalibrasyon_tarih_sayi']); ?></td>
                                <td class="header-bg fw-bold" style="width: 25%;">Geçerlilik Tar.</td>
                                <td style="width: 25%;"><?php echo htmlspecialchars($data['gecerlilik_suresi']); ?></td>
                            </tr>
                        </table>

                        <!-- D- ÖLÇÜM SONUÇLARI -->
                        <div class="section-title-bg">D- ÖLÇÜM SONUÇLARI (GALVANİK SİSTEMLİ KORUMA ÖLÇÜM RAPORU)</div>
                        <table>
                            <thead>
                                <tr class="header-bg text-center">
                                    <th style="width: 15%;" class="text-center">Ölçü Kutusu No</th>
                                    <th style="width: 15%;" class="text-center">Sistem Gerilimi (mV)</th>
                                    <th style="width: 15%;" class="text-center">Teçhizat Gerilimi (mV)</th>
                                    <th style="width: 15%;" class="text-center">Anot Gerilimi (mV)</th>
                                    <th style="width: 15%;" class="text-center">Anot Akımı (mA)</th>
                                    <th style="width: 25%;" class="text-center">Notlar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($measurements as $m): ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo htmlspecialchars($m['box_no']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($m['system_voltage']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($m['pipe_voltage']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($m['anode_voltage']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($m['anode_current']); ?></td>
                                        <td><?php echo htmlspecialchars($m['notes']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- E- SONUÇ VE ÖNERİLER -->
                        <div class="section-title-bg">E- SONUÇ VE ÖNERİLER</div>
                        <div style="margin-bottom: 6px; padding: 4px 8px; border: 1px solid #000; background: #fff;">
                            <strong>Standart Açıklamalar:</strong>
                            <ol style="margin: 4px 0 6px 15px; padding: 0;">
                                <li>Ölçümler Cu/CuSO4 elektrot kullanılarak yapılmıştır.</li>
                                <li>TS 5141 EN 12954 ve TS EN 16299 standartlarına göre katodik koruma tesisleriniz ölçüm periyotlarında ölçülmelidir.</li>
                                <li>Koruma kriteri düşük olan yerlere anot ilavesi veya tesiste görülen hatalar yazılarak mal sahibi uyarılır.</li>
                            </ol>
                            
                            <?php if (!empty($data['defects'])): ?>
                                <div style="border-top: 1px dashed #000; padding-top: 4px; margin-top: 4px;">
                                    <strong>Muayene Bulguları ve Kusurlar:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($data['defects'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($data['notes'])): ?>
                                <div style="border-top: 1px dashed #000; padding-top: 4px; margin-top: 4px;">
                                    <strong>Ek Notlar:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($data['notes'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="border-top: 1px solid #000; padding-top: 4px; margin-top: 6px; font-weight: bold;">
                                Muayene Sonucu: 
                                <span style="text-decoration: underline; color: <?php echo ($data['result'] == 'UYGUNDUR') ? 'green' : 'red'; ?>;">
                                    Katodik koruma tesisatı yapılan ölçüm ve kontrollere göre <?php echo htmlspecialchars($data['result']); ?>.
                                </span>
                            </div>
                        </div>

                        <!-- F- PERİYODİK KONTROLLERİ YAPMAYA YETKİLİ KİŞİ BİLGİLERİ ve ONAY -->
                        <div class="section-title-bg">F- PERİYODİK KONTROLLERİ YAPMAYA YETKİLİ KİŞİ BİLGİLERİ ve ONAY</div>
                        <table style="margin-top: 5px; width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 20%;" class="header-bg fw-bold">Adı Soyadı</td>
                                <td style="width: 50%;"><?php echo htmlspecialchars($data['adi_soyadi'] ?? ''); ?></td>
                                <td rowspan="3" style="width: 30%; vertical-align: bottom; text-align: center; border: 1px solid #000; font-weight: bold;">İmza</td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Mesleği</td>
                                <td><?php echo htmlspecialchars($data['meslegi'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Kayıt No</td>
                                <td><?php echo htmlspecialchars($data['kayit_no'] ?? ''); ?></td>
                            </tr>
                        </table>
                        <div class="small-text" style="font-size: 8px; margin-top: 4px;">Bu rapor.......... (yazı (rakam)) nüsha olarak hazırlanmıştır.</div>

                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
