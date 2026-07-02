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
        $logoHtml = '<img src="../uploads/logos/' . htmlspecialchars($activeLogo) . '" style="max-height: 48px; max-width: 100%; object-fit: contain;">';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeneratör Yıllık Bakım Takip Formu</title>
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
            padding: 5mm 10mm;
            margin: 20px auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border-radius: 4px;
            box-sizing: border-box;
            position: relative;
        }

        .header-box {
            margin-bottom: 8px;
        }

        .logo-box {
            min-height: 50px;
            font-size: 0.75rem;
            color: #555;
            font-weight: 500;
        }

        .table-form {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 0.72rem;
        }

        .table-form th, .table-form td {
            border: 1px solid #000000;
            padding: 2px 5px;
            vertical-align: middle;
        }

        .table-form th {
            font-weight: bold;
            text-align: center;
            background-color: #f8f9fa;
        }

        .vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            display: inline-block;
            letter-spacing: 1px;
            font-size: 0.75rem;
        }

        .chk-box {
            display: inline-block;
            width: 22px;
            height: 14px;
            border: 1px solid #000000;
            margin-left: 5px;
            flex-shrink: 0;
            background-color: #ffffff;
        }

        .notes-cell {
            vertical-align: top;
            padding: 4px;
        }

        .footnote {
            font-style: italic;
            font-size: 0.75rem;
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
            .table-form {
                font-size: 9px !important;
            }
            .table-form th, .table-form td {
                border: 1px solid #000000 !important;
                padding: 1px 3px !important;
            }
            .chk-box {
                border: 1px solid #000000 !important;
                width: 18px !important;
                height: 12px !important;
            }
            /* A4 Portrait size settings */
            @page {
                size: A4 portrait;
                margin: 4mm 6mm;
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
            <small class="text-muted">Yıllık form (4 dönem) A4 dikey boyutuna göre tek sayfada kalacak şekilde optimize edilmiştir.</small>
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
    <div class="header-box">
        <div class="row g-0 border border-dark align-items-stretch">
            <div class="col-3 border-end border-dark d-flex align-items-center justify-content-center p-1 logo-box text-center">
                <?php echo $logoHtml; ?>
            </div>
            <div class="col-9 d-flex align-items-center justify-content-center p-2 text-center">
                <h4 class="mb-0 fw-bold tracking-wide text-uppercase" style="font-size: 1.15rem;">JENERATÖR YILLIK BAKIM TAKİP FORMU</h4>
            </div>
        </div>
        
        <div class="row g-0 mt-2 px-1" style="font-size: 0.8rem;">
            <div class="col-6 d-flex align-items-end">
                <span class="fw-bold me-2 text-nowrap">MARKA-MODEL:</span>
                <div class="border-bottom border-dark flex-grow-1" style="min-height: 14px;"></div>
            </div>
            <div class="col-6 d-flex align-items-end justify-content-end">
                <span class="fw-bold me-2 text-nowrap ms-3">KONTROL YILI:</span>
                <div class="border-bottom border-dark flex-grow-1" style="min-height: 14px; max-width: 150px;"></div>
            </div>
        </div>
    </div>

    <!-- Main Table Form -->
    <table class="table-form">
        <thead>
            <tr>
                <th style="width: 12%;" class="text-center">AY</th>
                <th style="width: 44%;">Rutin Kontrol İşlemleri</th>
                <th style="width: 25%;">Kontrol Sırasında Tespit Edilen Aksaklıklar</th>
                <th style="width: 19%;">Tarih / Kontrolü Yapan Personel İmza</th>
            </tr>
        </thead>
        <tbody>
            
            <!-- QUARTER 1 (OCAK - ŞUBAT - MART) -->
            <tr>
                <td rowspan="9" class="text-center fw-bold week-cell align-middle bg-light text-uppercase">
                    <span class="vertical-text">OCAK - ŞUBAT - MART</span>
                </td>
                <td class="bg-light fw-bold text-center py-1 text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                    Rutin Kontrol İşlemleri
                </td>
                <td rowspan="9" class="notes-cell"></td>
                <td rowspan="9" class="notes-cell"></td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Yakıt seviyesi kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Kontrol panosu ve göstergelerinin kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Jeneratörün bir elektrik kesintisi durumunda devreye giriş kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Jeneratörün 24 saat hizmet vermeye hazır olduğunun kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Akü kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Tüm boru, hortumlar ve elektrik aksamının kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Yağ seviyesi ve kaçağı kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Soğutma suyu ve kaçağı kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>

            <!-- QUARTER 2 (NİSAN - MAYIS - HAZİRAN) -->
            <tr>
                <td rowspan="9" class="text-center fw-bold week-cell align-middle border-top border-dark bg-light text-uppercase">
                    <span class="vertical-text">NİSAN - MAYIS - HAZİRAN</span>
                </td>
                <td class="bg-light fw-bold text-center py-1 text-uppercase border-top border-dark" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                    Rutin Kontrol İşlemleri
                </td>
                <td rowspan="9" class="notes-cell border-top border-dark"></td>
                <td rowspan="9" class="notes-cell border-top border-dark"></td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Yakıt seviyesi kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Kontrol panosu ve göstergelerinin kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Jeneratörün bir elektrik kesintisi durumunda devreye giriş kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Jeneratörün 24 saat hizmet vermeye hazır olduğunun kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Akü kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Tüm boru, hortumlar ve elektrik aksamının kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Yağ seviyesi ve kaçağı kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Soğutma suyu ve kaçağı kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>

            <!-- QUARTER 3 (TEMMUZ - AĞUSTOS - EYLÜL) -->
            <tr>
                <td rowspan="9" class="text-center fw-bold week-cell align-middle border-top border-dark bg-light text-uppercase">
                    <span class="vertical-text">TEMMUZ - AĞUSTOS - EYLÜL</span>
                </td>
                <td class="bg-light fw-bold text-center py-1 text-uppercase border-top border-dark" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                    Rutin Kontrol İşlemleri
                </td>
                <td rowspan="9" class="notes-cell border-top border-dark"></td>
                <td rowspan="9" class="notes-cell border-top border-dark"></td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Yakıt seviyesi kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Kontrol panosu ve göstergelerinin kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Jeneratörün bir elektrik kesintisi durumunda devreye giriş kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Jeneratörün 24 saat hizmet vermeye hazır olduğunun kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Akü kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Tüm boru, hortumlar ve elektrik aksamının kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Yağ seviyesi ve kaçağı kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Soğutma suyu ve kaçağı kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>

            <!-- QUARTER 4 (EKİM - KASIM - ARALIK) -->
            <tr>
                <td rowspan="9" class="text-center fw-bold week-cell align-middle border-top border-dark bg-light text-uppercase">
                    <span class="vertical-text">EKİM - KASIM - ARALIK</span>
                </td>
                <td class="bg-light fw-bold text-center py-1 text-uppercase border-top border-dark" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                    Rutin Kontrol İşlemleri
                </td>
                <td rowspan="9" class="notes-cell border-top border-dark"></td>
                <td rowspan="9" class="notes-cell border-top border-dark"></td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Yakıt seviyesi kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Kontrol panosu ve göstergelerinin kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Jeneratörün bir elektrik kesintisi durumunda devreye giriş kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Jeneratörün 24 saat hizmet vermeye hazır olduğunun kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Akü kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Tüm boru, hortumlar ve elektrik aksamının kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Yağ seviyesi ve kaçağı kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Soğutma suyu ve kaçağı kontrolü</span>
                        <span class="chk-box"></span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footnote mt-2 fw-bold">
        Not: Jeneratör devreye girdiği günlerde de jeneratör kontrol edilip bu form doldurulacaktır.
    </div>

</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
