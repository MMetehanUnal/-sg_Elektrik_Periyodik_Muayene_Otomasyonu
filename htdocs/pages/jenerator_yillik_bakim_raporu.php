<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Sistem ayarlarından aktif logoyu ve şirket adını çekelim
$logoText = getSetting($pdo, 'logo_text', 'FİRMA LOGO');
$logoType = getSetting($pdo, 'logo_type', 'text');
$activeLogo = getSetting($pdo, 'active_logo', '');

$logoHtml = '<span class="fw-bold small text-uppercase" style="font-size: 0.75rem; line-height: 1.2;">' . htmlspecialchars($logoText) . '</span>';
if ($logoType === 'image' && !empty($activeLogo)) {
    $logoPath = "../uploads/logos/" . $activeLogo;
    if (file_exists($logoPath)) {
        $logoHtml = '<img src="../uploads/logos/' . htmlspecialchars($activeLogo) . '" style="max-height: 55px; max-width: 100%; object-fit: contain;">';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeneratör Yıllık Bakım Raporu</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        /* A4 Page Preview Style */
        .document-container {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 8mm 12mm;
            margin: 20px auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border-radius: 4px;
            box-sizing: border-box;
            position: relative;
        }

        .table-form {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 0.82rem;
        }

        .table-form th, .table-form td {
            border: 1px solid #000000;
            padding: 5px 8px;
            vertical-align: middle;
        }

        .table-form th {
            font-weight: bold;
            text-align: center;
        }

        .vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            display: inline-block;
            letter-spacing: 3px;
            font-weight: bold;
            font-size: 0.95rem;
        }

        .chk-box {
            display: inline-block;
            width: 25px;
            height: 18px;
            border: 1px solid #000000;
            background-color: #ffffff;
        }

        .materials-box {
            border: 1px solid #000000;
            padding: 10px;
            margin-bottom: 12px;
            min-height: 200px;
            font-size: 0.82rem;
        }

        .materials-box .title {
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .signature-table th, .signature-table td {
            border: 1px solid #000000;
            padding: 6px;
            text-align: center;
        }

        .signature-table td {
            height: 90px;
            vertical-align: top;
        }

        /* Print Media CSS */
        @media print {
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .document-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                min-height: auto !important;
            }
            .table-form, .materials-box, .signature-table {
                font-size: 11px !important;
            }
            .table-form th, .table-form td {
                border: 1px solid #000000 !important;
                padding: 4px 6px !important;
            }
            .signature-table td {
                height: 80px !important;
            }
            .materials-box {
                min-height: 180px !important;
                border: 1px solid #000000 !important;
            }
            .chk-box {
                border: 1px solid #000000 !important;
                width: 20px !important;
                height: 14px !important;
            }
            /* A4 Portrait size settings */
            @page {
                size: A4 portrait;
                margin: 8mm 10mm;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid no-print py-3 bg-white border-bottom shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <a href="dokumanlar.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Dökümanlara Dön
            </a>
        </div>
        <div class="text-center">
            <h5 class="mb-0 fw-bold">Döküman Önizleme & Yazdırma</h5>
            <small class="text-muted">Rapor A4 dikey boyutuna göre optimize edilmiştir.</small>
        </div>
        <div>
            <button onclick="window.print();" class="btn btn-primary shadow-sm px-4">
                <i class="fas fa-print me-2"></i> Yazdır / PDF Kaydet
            </button>
        </div>
    </div>
</div>

<div class="document-container">
    
    <!-- Header Box Block -->
    <div class="header-box mb-3">
        <div class="row g-0 border border-dark align-items-stretch">
            <div class="col-3 border-end border-dark d-flex align-items-center justify-content-center p-1 logo-box text-center">
                <?php echo $logoHtml; ?>
            </div>
            <div class="col-6 border-end border-dark d-flex align-items-center justify-content-center p-2 text-center">
                <h4 class="mb-0 fw-bold tracking-wide text-uppercase" style="font-size: 1.25rem;">JENERATÖR BAKIM FORMU</h4>
            </div>
            <div class="col-3 d-flex flex-column justify-content-center p-2 text-start" style="font-size: 0.8rem;">
                <div class="fw-bold mb-1">Bakım Tarihi:</div>
                <div class="border-bottom border-dark" style="min-height: 18px;"></div>
            </div>
        </div>
    </div>

    <!-- Device Info Table -->
    <table class="table-form mb-3">
        <thead>
            <tr>
                <th colspan="4" class="bg-light text-center py-1 text-uppercase" style="font-size: 0.8rem; border: 1px solid #000;">CİHAZ BİLGİLERİ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 15%; font-weight: bold; background-color: #f8f9fa;">MARKASI</td>
                <td style="width: 35%;"></td>
                <td style="width: 15%; font-weight: bold; background-color: #f8f9fa;" rowspan="3">ADRESİ</td>
                <td style="width: 35%;" rowspan="3"></td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #f8f9fa;">MODELİ</td>
                <td></td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #f8f9fa;">SERİ NO</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Maintenance Checks Table -->
    <table class="table-form mb-3">
        <tbody>
            <tr>
                <td rowspan="10" style="width: 6%; text-align: center; background-color: #ffffff;" class="align-middle">
                    <span class="vertical-text">BAKIM</span>
                </td>
                <td style="width: 82%;">İŞÇİ SAĞLIĞI VE İŞ GÜVENLİĞİ TEDBİRLERİNİN ALINMASI</td>
                <td style="width: 12%; text-align: center;">
                    <span class="chk-box"></span>
                </td>
            </tr>
            <tr>
                <td>MOTORUN YAĞ DEĞİŞİMİ</td>
                <td style="text-align: center;">
                    <span class="chk-box"></span>
                </td>
            </tr>
            <tr>
                <td>ANTİFRİZ DEĞİŞİMİ</td>
                <td style="text-align: center;">
                    <span class="chk-box"></span>
                </td>
            </tr>
            <tr>
                <td>HAVA FİLTRESİ DEĞİŞİMİ</td>
                <td style="text-align: center;">
                    <span class="chk-box"></span>
                </td>
            </tr>
            <tr>
                <td>YAKIT VE SU AYIRICI DEĞİŞİMİ</td>
                <td style="text-align: center;">
                    <span class="chk-box"></span>
                </td>
            </tr>
            <tr>
                <td>AKÜMÜLATÖR DEĞİŞİMİ</td>
                <td style="text-align: center;">
                    <span class="chk-box"></span>
                </td>
            </tr>
            <tr>
                <td>YAĞ, SU, MAZOT SIZINTILARI KONTROLÜ</td>
                <td style="text-align: center;">
                    <span class="chk-box"></span>
                </td>
            </tr>
            <tr>
                <td>RADYATÖR PETEKLERİNİN TEMİZLİĞİ</td>
                <td style="text-align: center;">
                    <span class="chk-box"></span>
                </td>
            </tr>
            <tr>
                <td>V KAYIŞLARININ KONTROLÜ</td>
                <td style="text-align: center;">
                    <span class="chk-box"></span>
                </td>
            </tr>
            <tr>
                <td>TEMİZLİK</td>
                <td style="text-align: center;">
                    <span class="chk-box"></span>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Used Materials Box -->
    <div class="materials-box">
        <div class="title">KULLANILAN MALZEMELER:</div>
    </div>

    <!-- Signatures Table -->
    <table class="signature-table">
        <thead>
            <tr>
                <th style="width: 50%;">Bakım Yapan</th>
                <th style="width: 50%;">Teslim Edilen</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-start p-2" style="height: auto; vertical-align: top;">
                    <div class="mb-2 d-flex align-items-end">
                        <span style="width: 80px; font-weight: bold;">Adı Soyadı:</span>
                        <span class="border-bottom border-dark flex-grow-1" style="min-height: 15px;"></span>
                    </div>
                    <div class="mb-2 d-flex align-items-end">
                        <span style="width: 80px; font-weight: bold;">Mesleği:</span>
                        <span class="border-bottom border-dark flex-grow-1" style="min-height: 15px;"></span>
                    </div>
                    <div class="d-flex align-items-end">
                        <span style="width: 80px; font-weight: bold;">İmza:</span>
                        <span class="border-bottom border-dark flex-grow-1" style="min-height: 35px;"></span>
                    </div>
                </td>
                <td class="text-start p-2" style="height: auto; vertical-align: top;">
                    <div class="mb-2 d-flex align-items-end">
                        <span style="width: 80px; font-weight: bold;">Adı Soyadı:</span>
                        <span class="border-bottom border-dark flex-grow-1" style="min-height: 15px;"></span>
                    </div>
                    <div class="mb-2 d-flex align-items-end">
                        <span style="width: 80px; font-weight: bold;">Mesleği:</span>
                        <span class="border-bottom border-dark flex-grow-1" style="min-height: 15px;"></span>
                    </div>
                    <div class="d-flex align-items-end">
                        <span style="width: 80px; font-weight: bold;">İmza:</span>
                        <span class="border-bottom border-dark flex-grow-1" style="min-height: 35px;"></span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
