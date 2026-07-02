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
        $logoHtml = '<img src="../uploads/logos/' . htmlspecialchars($activeLogo) . '" style="max-height: 50px; max-width: 100%; object-fit: contain;">';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSB Jeneratör Yıllık Bakım Formu</title>
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
            min-height: 55px;
            font-size: 0.75rem;
            color: #555;
            font-weight: 500;
        }

        .table-form {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 0.7rem;
        }

        .table-form th, .table-form td {
            border: 1px solid #000000;
            padding: 2px 4px;
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
            letter-spacing: 2px;
            font-weight: bold;
            font-size: 0.75rem;
        }

        .chk-box {
            display: inline-block;
            width: 18px;
            height: 12px;
            border: 1px solid #000000;
            background-color: #ffffff;
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
                font-size: 8.5px !important;
            }
            .table-form th, .table-form td {
                border: 1px solid #000000 !important;
                padding: 1px 3px !important;
            }
            .chk-box {
                border: 1px solid #000000 !important;
                width: 14px !important;
                height: 10px !important;
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
            <small class="text-muted">GSB Yıllık Bakım Formu A4 dikey boyutuna göre optimize edilmiştir.</small>
        </div>
        <div>
            <button onclick="window.print();" class="btn btn-primary shadow-sm px-4">
                <i class="fas fa-print me-2"></i> Yazdır / PDF Kaydet
            </button>
        </div>
    </div>
</div>

<div class="document-container">
    
    <!-- Top Header Layout Table -->
    <div class="header-box">
        <div class="row g-0 border border-dark align-items-stretch">
            <div class="col-3 border-end border-dark d-flex align-items-center justify-content-center p-1 logo-box text-center">
                <?php echo $logoHtml; ?>
            </div>
            <div class="col-6 border-end border-dark d-flex align-items-center justify-content-center p-2 text-center">
                <h4 class="mb-0 fw-bold tracking-wide text-uppercase text-danger" style="font-size: 1.15rem; color: #dc3545 !important;">JENERATÖR YILLIK BAKIM FORMU</h4>
            </div>
            <div class="col-3 d-flex flex-column justify-content-between p-0" style="font-size: 0.65rem; line-height: 1.2;">
                <div class="d-flex border-bottom border-dark flex-grow-1 align-items-center">
                    <div class="fw-bold border-end border-dark px-2 py-0.5 text-nowrap" style="width: 50%;">Döküman No</div>
                    <div class="px-2 py-0.5 text-truncate" style="font-weight: 500;">:YP.FR.03</div>
                </div>
                <div class="d-flex border-bottom border-dark flex-grow-1 align-items-center">
                    <div class="fw-bold border-end border-dark px-2 py-0.5" style="width: 50%;">Yayın Tarihi</div>
                    <div class="px-2 py-0.5">:20.11.2018</div>
                </div>
                <div class="d-flex border-bottom border-dark flex-grow-1 align-items-center">
                    <div class="fw-bold border-end border-dark px-2 py-0.5 text-nowrap" style="width: 50%;">Revizyon Tarihi</div>
                    <div class="px-2 py-0.5">:-</div>
                </div>
                <div class="d-flex border-bottom border-dark flex-grow-1 align-items-center">
                    <div class="fw-bold border-end border-dark px-2 py-0.5 text-nowrap" style="width: 50%;">Revizyon No</div>
                    <div class="px-2 py-0.5">:00</div>
                </div>
                <div class="d-flex flex-grow-1 align-items-center">
                    <div class="fw-bold border-end border-dark px-2 py-0.5 text-nowrap" style="width: 50%;">Sayfa Sayısı</div>
                    <div class="px-2 py-0.5">:01</div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info & Date Layout -->
    <div class="row g-0 mb-2">
        <div class="col-6">
            <table class="table-form mb-0">
                <tbody>
                    <tr>
                        <td style="width: 35%; font-weight: bold; background-color: #a0a1e2;">SİSTEM BİLGİLERİ</td>
                        <td style="width: 65%; font-weight: bold; background-color: #a0a1e2;"></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; background-color: #a0a1e2;">Firma Adı</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; background-color: #a0a1e2;">Adres:</td>
                        <td></td>
                    <tr>
                        <td style="font-weight: bold; background-color: #a0a1e2;">MARKA</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; background-color: #a0a1e2;">SERİ NUMARASI</td>
                        <td></td>
                    </tr>
                    
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="col-6 d-flex align-items-center justify-content-end px-3">
            <div class="fw-bold" style="font-size: 0.78rem;">
                Bakım Tarihi: <span class="border-bottom border-dark d-inline-block text-center font-monospace" style="width: 150px; min-height: 15px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </div>
        </div>
    </div>

    <!-- Pre-Maintenance Status -->
    <table class="table-form mb-2">
        <thead>
            <tr>
                <th colspan="3" class="bg-light text-center py-1 text-uppercase" style="font-size: 0.75rem; border: 1px solid #000;">BAKIM ÖNCESİ DURUM</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center" style="width: 33%;">
                    NORMAL ÇALIŞIYOR <span class="chk-box ms-2 align-middle"></span>
                </td>
                <td class="text-center" style="width: 33%;">
                    ARIZALI <span class="chk-box ms-2 align-middle"></span>
                </td>
                <td class="text-center" style="width: 34%;">
                    ÇALIŞMIYOR <span class="chk-box ms-2 align-middle"></span>
                </td>
            </tr>
            <tr>
                <td colspan="3" class="p-1 text-start align-top" style="min-height: 35px;">
                    <strong>BAKIM ÖNCESİ TESPİTLER:</strong>
                    <div style="min-height: 25px;"></div>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Main Checks Table -->
    <table class="table-form mb-2">
        <thead>
            <tr>
                <th colspan="3" class="bg-light text-center py-1 text-uppercase" style="font-size: 0.75rem; border: 1px solid #000;">CİHAZ LİSTESİ ve BAKIM / KONTROL NOKTALARI</th>
            </tr>
            <tr style="background-color: #a0a1e2;">
                <th style="width: 8%;" class="text-center">YILLIK</th>
                <th style="width: 67%;">KONTROL NOKTALARI</th>
                <th style="width: 25%;">AÇIKLAMA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="23" class="text-center align-middle fw-bold bg-white">
                    <span class="vertical-text">YILLIK</span>
                </td>
                <td>Göstergelerin sağlamlık kontrolünü yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Motor yağ seviyesini kontrol et</td>
                <td></td>
            </tr>
            <tr>
                <td>Su seviye kontrolünü yap</td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Akü Şarj redresörünü ölç</span>
                        <span class="small font-monospace text-muted" style="font-size: 0.65rem;">VDC: ____________</span>
                    </div>
                </td>
                <td></td>
            </tr>
            <tr>
                <td>Taze hava giriş menfez kontrolünü yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Yağ, yakıt sızıntı kontrolünü yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Radyatör sızıntı kontrolünü yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Makine blok ve alternatör ısıtıcıları kontrolünü yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Jeneratör genel temizliğini yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Jeneratör oda temizliğini yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Jeneratör kontrol panosunda otomat ve cam sigorta kontrolünü yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Jeneratörün boşta 15 dk çalıştır</td>
                <td></td>
            </tr>
            <tr>
                <td>Anormal ses ve vibrasyon kontrolünü yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Akü su seviyesini kontrol et ve temizliğini yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Elektrolit yoğunluk ölçümü yap</td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Akü yükte gerilim kontrolünü yap, (Min:27 V olmalı)</span>
                        <span class="small font-monospace text-muted" style="font-size: 0.65rem;">VDC: ________ V</span>
                    </div>
                </td>
                <td></td>
            </tr>
            <tr>
                <td>Hortum ve boru bağlantısını kontrol et</td>
                <td></td>
            </tr>
            <tr>
                <td>Kumanda panosunun temizliğini yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Yağ değişimi yap.</td>
                <td></td>
            </tr>
            <tr>
                <td>Antifriz değişimi yap</td>
                <td></td>
            </tr>
            <tr>
                <td>Filtrelerin değişimini yap</td>
                <td></td>
            </tr>
            <tr>
                <td>V Kayış kontrolü gerekirse değişimini yap</td>
                <td></td>
            </tr>
            <tr class="fw-bold bg-light">
                <td colspan="2" class="py-1 text-center" style="font-size: 0.65rem; font-style: italic;">
                    Not: Majör uygunsuzlukta amirine bilgi ver, servis çağır.
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Post-Maintenance Status -->
    <table class="table-form mb-2">
        <thead>
            <tr>
                <th colspan="3" class="bg-light text-center py-1 text-uppercase" style="font-size: 0.75rem; border: 1px solid #000;">BAKIM SONRASI DURUM</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center" style="width: 33%;">
                    NORMAL ÇALIŞIYOR <span class="chk-box ms-2 align-middle"></span>
                </td>
                <td class="text-center" style="width: 33%;">
                    ARIZASI VAR <span class="chk-box ms-2 align-middle"></span>
                </td>
                <td class="text-center" style="width: 34%;">
                    ÇALIŞMIYOR <span class="chk-box ms-2 align-middle"></span>
                </td>
            </tr>
            <tr>
                <td colspan="3" class="p-1 text-start align-top" style="min-height: 35px;">
                    <strong>AÇIKLAMA:</strong>
                    <div style="min-height: 25px;"></div>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Personnel Info -->
    <table class="table-form mb-0">
        <thead>
            <!-- <tr>
                <th colspan="3" class="bg-light text-center py-1 text-uppercase" style="font-size: 0.75rem; border: 1px solid #000;">PERSONEL BİLGİLERİ</th>
            </tr> -->
            <tr style="background-color: #a0a1e2;">
                <th style="width: 33%;">BAKIM YAPAN</th>
                <th style="width: 33%;">TESLİM ALAN</th>
                <!-- <th style="width: 34%;">ONAY</th> -->
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="p-2 text-start align-top" style="height: 55px;">
                    <div class="small fw-semibold text-muted mb-1" style="font-size: 0.65rem;">AD SOYAD / İMZA:</div>
                </td>
                <td class="p-2 text-start align-top" style="height: 55px;">
                    <div class="small fw-semibold text-muted mb-1" style="font-size: 0.65rem;">AD SOYAD / İMZA:</div>
                </td>
                <!-- <td class="p-2 text-start align-top" style="height: 55px;">
                    <div class="small fw-semibold text-muted mb-1" style="font-size: 0.65rem;">AD SOYAD / İMZA:</div>
                </td> -->
            </tr>
        </tbody>
    </table>

</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
