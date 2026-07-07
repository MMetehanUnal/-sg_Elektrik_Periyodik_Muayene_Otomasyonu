<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) die("Rapor ID gerekli.");

// Raporu çek
$stmt = $pdo->prepare("
    SELECT gr.*, i.firma_adi, i.adresi
    FROM general_reports gr
    JOIN institutions i ON gr.kurum_id = i.id
    WHERE gr.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) die("Rapor bulunamadı.");

// Yetkili uzmanları çek
$mekanik = null;
if (!empty($data['mekanik_uzman_id'])) {
    $stmt_mek = $pdo->prepare("SELECT * FROM authorized_persons WHERE id = ?");
    $stmt_mek->execute([$data['mekanik_uzman_id']]);
    $mekanik = $stmt_mek->fetch();
}

$elektrik = null;
if (!empty($data['elektrik_uzman_id'])) {
    $stmt_elk = $pdo->prepare("SELECT * FROM authorized_persons WHERE id = ?");
    $stmt_elk->execute([$data['elektrik_uzman_id']]);
    $elektrik = $stmt_elk->fetch();
}

// Antet fonksiyonu (diğer raporlarla aynı yapıda)
function renderHeader() {
    global $pdo, $data;
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
    <table style="border:none;">
        <tr style="border:none;">
            <td style="width: 20%; height: 60px; border:none; text-align:center; vertical-align:middle;">
                <!-- Logo -->
                <div style="font-weight:bold; font-size:16px;"><?php echo $logoHtml; ?></div>
            </td>
            <td style="width: 50%; text-align: center; border:none;">
                <h1 style="font-size: 14px; margin: 0; line-height: 1.3;"><?php echo htmlspecialchars($data['title']); ?></h1>
            </td>
            <td style="width: 30%; border:none;">
                <table class="doc-info" style="border:1px solid black; width:100%;">
                    <tr>
                        <td colspan="2" style="border:1px solid black; text-align:center; font-weight:bold; background-color:#daeef3; font-size: 9px; padding: 2px;">GENEL RAPOR</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; width:50%; font-size: 9px; padding: 2px;">Rapor No</td>
                        <td style="border:1px solid black; font-size: 9px; padding: 2px;">GR-<?php echo str_pad($data['id'], 4, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; font-size: 9px; padding: 2px;">Oluşturulma</td>
                        <td style="border:1px solid black; font-size: 9px; padding: 2px;"><?php echo date('d.m.Y', strtotime($data['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; font-size: 9px; padding: 2px;">Güncelleme</td>
                        <td style="border:1px solid black; font-size: 9px; padding: 2px;"><?php echo date('d.m.Y', strtotime($data['updated_at'])); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <hr style="border: 1px solid black; margin-top:5px; margin-bottom:5px;">
    <?php
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
    <title>Genel Rapor: <?php echo htmlspecialchars($data['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        /* Genel Rapor İçerik Stilleri */
        .report-content {
            padding: 4mm 2mm;
            font-size: 11px;
            line-height: 1.5;
        }
        .report-content h1 {
            font-size: 16px;
            text-align: left;
            margin: 12px 0 8px 0;
            border-bottom: 1px solid #333;
            padding-bottom: 4px;
        }
        .report-content h2 {
            font-size: 14px;
            text-align: left;
            margin: 10px 0 6px 0;
        }
        .report-content h3 {
            font-size: 12px;
            text-align: left;
            margin: 8px 0 4px 0;
        }
        .report-content p {
            margin: 4px 0;
            text-align: justify;
        }
        .report-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }
        .report-content table td,
        .report-content table th {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
            font-size: 10px;
        }
        .report-content table th {
            background-color: #daeef3;
            font-weight: bold;
        }
        .report-content img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 8px auto;
        }
        .report-content ul, .report-content ol {
            margin: 4px 0;
            padding-left: 20px;
        }
        .report-content li {
            margin: 2px 0;
        }
        .report-content blockquote {
            border-left: 3px solid #333;
            padding-left: 10px;
            margin: 8px 0;
            color: #555;
        }
        .report-content hr {
            border: none;
            border-top: 1px solid #000;
            margin: 8px 0;
        }

        /* Firma bilgi kutusu */
        .firma-info {
            background-color: #f9f9f9;
            border: 1px solid #000;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 10px;
        }

        /* Cover Page Styling */
        .cover-page {
            width: 100%;
            font-family: Arial, sans-serif;
            color: #000;
            box-sizing: border-box;
        }
        .cover-page table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .cover-page td, .cover-page th {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 10px;
            vertical-align: middle;
        }
        .cover-page-title-section {
            background-color: #b4c6e7;
            font-weight: bold;
            text-align: center;
            padding: 4px;
            font-size: 10.5px;
            border: 1px solid #000;
            margin-top: 8px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .text-red {
            color: red;
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
                page-break-after: always;
            }
            .report-content img {
                page-break-inside: avoid;
            }
            .report-content table {
                page-break-inside: auto;
            }
            .report-content tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>

<body>

    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 14px;">
            <b>🖨️ Yazdır / Kaydet PDF</b>
        </button>
        <a href="genel_rapor_duzenle.php?id=<?php echo $id; ?>"
            style="padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 14px; text-decoration: none; display: inline-block; margin-left: 5px;">
            ✏️ Düzenle
        </a>
        <a href="genel_rapor.php"
            style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 14px; text-decoration: none; display: inline-block; margin-left: 5px;">
            ← Geri
        </a>
    </div>

    <!-- COVER PAGE (PAGE 1) -->
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
                <?php echo htmlspecialchars($data['firma_adi'] ?? ''); ?>
            </h2>
            <div style="font-size: 11px; font-weight: bold; margin-top: 8px;">
                <?php echo !empty($data['report_date']) ? date('d.m.Y', strtotime($data['report_date'])) : date('d.m.Y'); ?>
            </div>
        </div>

        <!-- GENEL BİLGİLER -->
        <div class="cover-page-title-section">GENEL BİLGİLER</div>
        <table>
            <tr>
                <td style="width: 18%;" class="header-bg fw-bold">Yurt Adı</td>
                <td style="width: 42%;"><?php echo htmlspecialchars($data['firma_adi'] ?? ''); ?></td>
                <td style="width: 18%;" class="header-bg fw-bold">Rapor No</td>
                <td style="width: 22%;"><?php echo htmlspecialchars($data['report_no'] ?? ''); ?></td>
            </tr>
            <tr>
                <td class="header-bg fw-bold">Yurt Adresi</td>
                <td><?php echo htmlspecialchars($data['adresi'] ?? '-'); ?></td>
                <td class="header-bg fw-bold">Rapor Tarihi</td>
                <td><?php echo !empty($data['report_date']) ? date('d.m.Y', strtotime($data['report_date'])) : ''; ?></td>
            </tr>
            <tr>
                <td class="header-bg fw-bold">Yurt Yöneticisi</td>
                <td><?php echo htmlspecialchars($data['yurt_yoneticisi'] ?? '-'); ?></td>
                <td class="header-bg fw-bold">Kontrol Tarihi</td>
                <td><?php echo !empty($data['control_date']) ? date('d.m.Y', strtotime($data['control_date'])) : ''; ?></td>
            </tr>
            <tr>
                <td class="header-bg fw-bold">Yurt Yatak Kapasitesi</td>
                <td><?php echo htmlspecialchars($data['yatak_kapasitesi'] ?? '-'); ?></td>
                <td class="header-bg fw-bold text-red">Bir Sonraki Kontrol Tarihi</td>
                <td class="fw-bold text-red"><?php echo !empty($data['next_control_date']) ? date('d.m.Y', strtotime($data['next_control_date'])) : ''; ?></td>
            </tr>
            <tr>
                <td class="header-bg fw-bold">İş Güvenliği Uzmanı</td>
                <td><?php echo htmlspecialchars($data['is_guvenligi_uzmani'] ?? '-'); ?></td>
                <td class="header-bg fw-bold">Telefon</td>
                <td><?php echo htmlspecialchars($data['phone'] ?? '-'); ?></td>
            </tr>
            <tr>
                <td class="header-bg fw-bold">Ada</td>
                <td colspan="3" style="padding: 0;">
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr style="border: none;">
                            <td style="border: none; border-right: 1px solid #000; width: 33.3%;"><?php echo htmlspecialchars($data['ada'] ?? '-'); ?></td>
                            <td style="border: none; border-right: 1px solid #000; width: 15%;" class="header-bg fw-bold">Pafta</td>
                            <td style="border: none; border-right: 1px solid #000; width: 18.3%;"><?php echo htmlspecialchars($data['pafta'] ?? '-'); ?></td>
                            <td style="border: none; border-right: 1px solid #000; width: 15%;" class="header-bg fw-bold">Parsel</td>
                            <td style="border: none; width: 18.3%;"><?php echo htmlspecialchars($data['parsel'] ?? '-'); ?></td>
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

    <!-- MAIN CONTENT (PAGE 2 ONWARDS) -->
    <table class="main-report-table">
        <thead>
            <tr>
                <td>
                    <?php renderHeader(); ?>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="page">
                        <!-- Firma Bilgileri -->
                        <div class="firma-info">
                            <strong>Firma:</strong> <?php echo htmlspecialchars($data['firma_adi'] ?? ''); ?>
                            <?php if (!empty($data['adresi'])): ?>
                                &nbsp;&nbsp;|&nbsp;&nbsp;
                                <strong>Adres:</strong> <?php echo htmlspecialchars($data['adresi']); ?>
                            <?php endif; ?>
                        </div>

                        <!-- Rapor İçeriği -->
                        <div class="report-content">
                            <?php echo $data['content'] ?? '<p><em>İçerik henüz eklenmemiş.</em></p>'; ?>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

</body>

</html>
