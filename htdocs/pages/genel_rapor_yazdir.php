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
                <h1><?php echo htmlspecialchars($data['title']); ?></h1>
            </td>
            <td style="width: 30%; border:none;">
                <table class="doc-info" style="border:1px solid black; width:100%;">
                    <tr>
                        <td colspan="2" style="border:1px solid black; text-align:center; font-weight:bold; background-color:#daeef3;">GENEL RAPOR</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black; width:50%;">Rapor No</td>
                        <td style="border:1px solid black;">GR-<?php echo str_pad($data['id'], 4, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black;">Oluşturulma</td>
                        <td style="border:1px solid black;"><?php echo date('d.m.Y', strtotime($data['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black;">Güncelleme</td>
                        <td style="border:1px solid black;"><?php echo date('d.m.Y', strtotime($data['updated_at'])); ?></td>
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

        @media print {
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
