<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
if (!$id)
    die("Rapor ID gerekli.");

// Fetch Internal Installation Report Data
$stmt = $pdo->prepare("
    SELECT r.*, 
           i.firma_adi, i.adresi, i.sgk_sicil_no, i.il_kodu, i.kurum_kodu,
           fi.enerji_saglayan as fi_enerji, fi.sebeke_gerilimi as fi_gerilim, fi.kullanim_amaci as fi_amac, fi.sozlesme_id, 
           fi.sebeke_tipi as fi_tip, fi.proje_var_mi as fi_proje, fi.sema_var_mi as fi_sema, fi.yapi_cinsi as fi_yapi,
           d1.device_name as d1_name, d1.serial_no as d1_serial, d1.cal_date as d1_cal, d1.validity_date as d1_val, d1.cal_no as d1_cal_no,
           d2.device_name as d2_name, d2.serial_no as d2_serial, d2.cal_date as d2_cal, d2.validity_date as d2_val, d2.cal_no as d2_cal_no,
           dt.device_name as dt_name, dt.serial_no as dt_serial, dt.cal_date as dt_cal, dt.validity_date as dt_val, dt.cal_no as dt_cal_no,
           ap.adi_soyadi, ap.meslegi, ap.kayit_no
    FROM internal_installation_reports r
    JOIN institutions i ON r.kurum_id = i.id
    LEFT JOIN facility_info fi ON i.id = fi.kurum_id
    LEFT JOIN measurement_devices d1 ON r.device1_id = d1.id
    LEFT JOIN measurement_devices d2 ON r.device2_id = d2.id
    LEFT JOIN measurement_devices dt ON r.thermal_camera_id = dt.id
    LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data)
    die("Rapor bulunamadı.");

// Fetch panels for this report
$pstmt = $pdo->prepare("SELECT * FROM ic_tesisat_panels WHERE report_id=? ORDER BY panel_order");
$pstmt->execute([$id]);
$panels = $pstmt->fetchAll();

// Section 5 questions
$s5_questions = [
    'PANO VE DİĞER DONANIMLARA GİRİŞİN UYGUNLUĞU' => [
        'kablo_sebeke' => 'Kablo şebeke tarafı',
        'kablo_donanim' => 'Kablo donanım tarafı',
        'pano_sabitleme' => 'Pano sabitlenmesi (Depreme dayanıklılık)',
        'dis_darbe' => 'Dış darbelere karşı koruma önlemi',
        'yabanci_malzeme' => 'Elektrik panosu etrafında yabancı malzemeler',
        'zemin_izolasyon' => 'Zemin izolasyonu',
    ],
    'TOPRAKLANMIŞ POTANSİYEL DENGELEME VE BESLEMENİN OTOMATİK KESİLMESİ, ELEKTRİK ÇARPMASINA KARŞI KORUMA' => [
        'topraklama_iletken' => 'Topraklama iletkeni',
        'ana_pot_iletken' => 'Ana potansiyel dengeleme iletkeni',
        'ek_pot_iletken' => 'Ek Potansiyel dengeleme İletkeni (Tamamlayıcı pot.den)',
        'kapak_6mm' => 'Pano kapak bağlantısı kontrolü 6 mm²',
    ],
    'KARŞILIKLI ZARARLI ETKİLERİN ÖNLENMESİ' => [
        'elektriksel_olmayan' => 'Elektriksel olmayan tesislere yaklaşma ve diğer etkilerin kontrolü',
        'bant_ayirma' => 'Bant I ve Bant II ayrılması, Bant II yalıtımı',
        'guvenlik_devre' => 'Güvenlik devre ayrılması',
        'pano_kapak_erisim' => 'Pano iç kapak, faza erişim engeli veya pleksi koruma',
    ],
    'TANIMLAMA' => [
        'semalar' => 'Şemalar, talimatlar, devre çizimleri ve kısa bilgiler',
        'koruma_etiket' => 'Koruma cihaz ve terminal etiket',
        'tehlike_isaretleri' => 'Tehlike işaretleri ve diğer uyarı işaretleri',
    ],
    'KABLO ve İLETKENLER' => [
        'kablo_yolu' => 'Kablo yollarının uygunluğu ve mekanik koruma',
        'kablo_renk' => 'Kablo renk kodları Nötr: Mavi Toprak: Sarı/Yeşil',
        'tesisat_yontemi' => 'Tesisat yöntemi',
        'yangin_engeli' => 'Yangın engeli, uygun kilitleme ve sıcaklık etkisine karşı koruma',
    ],
    'TERMAL KAMERA' => [
        'fotograf_tarihi' => 'Fotoğraf tarihi',
        'kontak_gevsekligi' => 'Kontak gevşekliği ısınması',
        'fotograf_no' => 'Fotoğraf no.',
        'asiri_yuk_isinma' => 'Aşırı yük ısınması PVC kablolar için >70 derece',
    ],
    'GENEL DEĞERLENDİRMELER' => [
        'yangin_sondurme' => 'Ekipman yakınında elektriksel ekipman yangın söndürme tertibatı',
        'ekipman_temizlik' => 'Ekipman temizlik/bakım durumu',
        'korozyon' => 'Pano içi ve bağlantılarının korozyon kontrolü',
        'acil_aydinlatma' => 'Ekipman içi veya yakınında acil durum aydınlatma tertibatı',
    ],
];

// Fetch section 6 header
$s6h = $pdo->prepare("SELECT * FROM ic_tesisat_section6_header WHERE report_id=?");
$s6h->execute([$id]);
$s6hdr = $s6h->fetch();

// Fetch section 6.2, 6.3
$s62stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_2_rows WHERE report_id=? ORDER BY id");
$s62stmt->execute([$id]);
$s62rows = $s62stmt->fetchAll();

$s63stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_3_rows WHERE report_id=? ORDER BY id");
$s63stmt->execute([$id]);
$s63rows = $s63stmt->fetchAll();

// Helper for checkboxes/radios
function chk($val, $target)
{
    return ($val == $target) ? '<b>[X]</b>' : '[ ]';
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
    <table style="border:none;">
        <tr style="border:none;">
            <td style="width: 20%; height: 60px; border:none; text-align:center; vertical-align:middle;">
                <div style="font-weight:bold; font-size:16px;"><?php echo $logoHtml; ?></div>
            </td>
            <td style="width: 50%; text-align: center; border:none;">
                <h1>ELEKTRİK İÇ TESİSAT<br>PERİYODİK KONTROL<br>RAPORU</h1>
            </td>
            <td style="width: 30%; border:none;">
                <table class="doc-info" style="border:1px solid black; width:100%;">
                    <tr>
                        <td style="border:1px solid black; width:50%;">Doküman Kodu</td>
                        <td style="border:1px solid black;">ZPKR02</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black;">Yayım Tarihi</td>
                        <td style="border:1px solid black;">18.07.2025</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black;">Revizyon No</td>
                        <td style="border:1px solid black;">-</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black;">Revizyon Tarihi</td>
                        <td style="border:1px solid black;">-</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid black;">Yürürlük Tarihi</td>
                        <td style="border:1px solid black;">01.09.2025</td>
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
    <title>İç Tesisat Raporu:
        <?php echo htmlspecialchars($data['report_no']); ?>
    </title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        .header-bg {
            background-color: #dce6f1 !important;
            font-weight: bold;
        }
        .section-title-bg {
            background-color: #fce4d6 !important;
            font-weight: bold;
            border: 1px solid black;
            padding: 5px;
            font-size: 11px;
            margin-top: 10px;
        }
        .group-title-bg {
            background-color: #ebf1de !important;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
    </style>
</head>

<body>

    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">Yazdır /
            Kaydet PDF</button>
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
                        <div class="section-title-bg">1. FİRMA BİLGİLERİ</div>
                        <table style="margin-top:0;">
                            <tr>
                                <td class="header-bg" style="width: 20%;">Firma Adı</td>
                                <td style="width: 40%;"><?php echo htmlspecialchars($data['firma_adi'] ?? ''); ?></td>
                                <td class="header-bg" style="width: 20%;">Rapor Numarası</td>
                                <td style="width: 20%;"><?php echo htmlspecialchars($data['report_no'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg" rowspan="4">Periyodik Kontrol Adresi</td>
                                <td rowspan="4"><?php echo nl2br(htmlspecialchars($data['adresi'] ?? '')); ?></td>
                                <td class="header-bg">Rapor Tarihi</td>
                                <td><?php echo date('d.m.Y', strtotime($data['report_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">İSG-KATİP Sözleşme ID</td>
                                <td><?php echo htmlspecialchars($data['isg_katip_id'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Periyodik Kontrol Başlangıç Tarihi ve Saati</td>
                                <td><?php echo $data['start_date'] ? date('d.m.Y H:i', strtotime($data['start_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Periyodik Kontrol Bitiş Tarihi ve Saati</td>
                                <td><?php echo $data['end_date'] ? date('d.m.Y H:i', strtotime($data['end_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">SGK Sicil Numarası</td>
                                <td><?php echo htmlspecialchars($data['sgk_sicil_no'] ?? ''); ?></td>
                                <td class="header-bg">Bir Sonraki Periyodik Kontrol Tarihi</td>
                                <td><?php echo $data['next_control_date'] ? date('d.m.Y', strtotime($data['next_control_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Periyodik Kontrol Metodu ve Kapsamı</td>
                                <td colspan="3">
                                    <ul style="margin: 0; padding-left: 15px;">
                                        <li>TS HD 60364-4-43 Alçak Gerilim Elektrik Tesisatları- Bölüm 4: Güvenlik İçin
                                            Koruma Grup 43 - Aşırı Akıma Karşı Koruma</li>
                                        <li>TS HD 60364-6 Alçak Gerilim Elektrik Tesisatları – Bölüm 6: Doğrulama</li>
                                        <li>İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği</li>
                                        <li>Elektrik İç Tesisleri Yönetmeliği</li>
                                        <li>Elektrik Tesislerinde Topraklamalar Yönetmeliği</li>
                                    </ul>
                                </td>
                            </tr>
                        </table>

                        <div class="section-title-bg">2. EKİPMAN BİLGİLERİ</div>
                        <div class="header-bg" style="border: 1px solid black; border-bottom: none; padding: 2px;">2.1.
                            DETAY BİLGİLER</div>
                        <table style="margin-top: 0; table-layout: fixed;">
                            <!-- Header row to define widths if needed, but table-layout:auto/fixed handles it -->
                            <tr>
                                <td class="header-bg" colspan="1" style="width: 18%;">Enerji sağlayan kuruluş</td>
                                <td colspan="2" style="width: 32%;">
                                    <?php echo htmlspecialchars($data['energy_provider'] ?? ''); ?>
                                </td>
                                <td class="header-bg" colspan="1" style="width: 15%;">Şebeke tipi</td>
                                <td colspan="2" style="width: 35%;">
                                    <?php foreach (['TT', 'IT', 'TN', 'TN-CS', 'TN-C', 'TN-S'] as $opt): ?>
                                        <span class="radio-item"
                                            style="font-size: 8px;"><?php echo chk($data['sebeke_tipi'], $opt); ?>
                                            <?php echo $opt; ?></span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg" colspan="1">Şebeke gerilimi</td>
                                <td colspan="1"><?php echo htmlspecialchars($data['nominal_voltage_kV'] ?? ''); ?> kV /
                                    <?php echo htmlspecialchars($data['nominal_frequency_Hz'] ?? ''); ?> Hz
                                </td>
                                <td class="header-bg" colspan="1">Tesise ait proje var mı?</td>
                                <td colspan="1">
                                    <span class="radio-item"><?php echo chk($data['proje_var_mi'], 1); ?> Var</span>
                                    <span class="radio-item"><?php echo chk($data['proje_var_mi'], 0); ?> Yok</span>
                                </td>
                                <td class="header-bg" colspan="1">Tek hat şeması var mı?</td>
                                <td colspan="1">
                                    <span class="radio-item"><?php echo chk($data['sema_var_mi'], 1); ?> Var</span>
                                    <span class="radio-item"><?php echo chk($data['sema_var_mi'], 0); ?> Yok</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg" colspan="1">Kontrol nedeni</td>
                                <td colspan="2">
                                    <span
                                        class="radio-item"><?php echo chk($data['control_reason'], 'Periyodik Kontrol'); ?>
                                        Periyodik Kontrol</span><br>
                                    <span class="radio-item"><?php echo chk($data['control_reason'], 'İlk Kontrol'); ?>
                                        İlk Kontrol</span>
                                </td>
                                <td class="header-bg" colspan="1">Topraklayıcı tipi</td>
                                <td colspan="2">
                                    <?php foreach (['Ring', 'Yüzeysel', 'Temel', 'Derin', 'Belirlenemedi'] as $opt): ?>
                                        <span class="radio-item"
                                            style="font-size: 8px;"><?php echo chk($data['grounding_type'], $opt); ?>
                                            <?php echo $opt; ?></span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg" colspan="1">Yapı cinsi</td>
                                <td colspan="1">
                                    <?php foreach (['Ev', 'Ticari', 'Endüstri', 'Diğer'] as $opt): ?>
                                        <span class="radio-item"><?php echo chk($data['building_type'], $opt); ?>
                                            <?php echo $opt; ?></span><br>
                                    <?php endforeach; ?>
                                </td>
                                <td class="header-bg" colspan="1">Ekipmanın kullanım amacı</td>
                                <td colspan="2"><?php echo htmlspecialchars($data['usage_purpose'] ?? ''); ?></td>
                                <td colspan="1">
                                    <span class="header-bg"
                                        style="display:block; margin:-3px; padding:3px; border-bottom:1px solid black;">Son
                                        kontrol tarihi</span>
                                    <div style="padding-top:10px; text-align:center;">
                                        <?php echo $data['prev_control_date'] ? date('d.m.Y', strtotime($data['prev_control_date'])) : ''; ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg" colspan="1">Faz iletkenlerinin sayısı ve tipi</td>
                                <td colspan="1">
                                    <?php foreach (['AA', '1 faz, 2 tel', '1 faz, 3 tel', '2 faz, 3 tel', '3 faz, 3 tel', '3 faz, 4 tel'] as $opt): ?>
                                        <span class="radio-item"
                                            style="font-size: 8px;"><?php echo chk($data['phase_count_type'], $opt); ?>
                                            <?php echo $opt; ?></span><br>
                                    <?php endforeach; ?>
                                </td>
                                <td colspan="1">
                                    <?php foreach (['DA', '2 kutup', '3 kutup', 'Diğer'] as $opt): ?>
                                        <span class="radio-item"
                                            style="font-size: 8px;"><?php echo chk($data['conductor_type'], $opt); ?>
                                            <?php echo $opt; ?></span><br>
                                    <?php endforeach; ?>
                                </td>
                                <td colspan="3">
                                    <table style="width: 100%; border: none; margin: -3px;">
                                        <tr style="border: none;">
                                            <td
                                                style="border: none; border-bottom: 1px solid black; border-right: 1px solid black;">
                                                Temel topraklama direnci (Ω)</td>
                                            <td style="border: none; border-bottom: 1px solid black;"
                                                class="bold center">
                                                <?php echo htmlspecialchars($data['grounding_resistance'] ?? ''); ?>
                                            </td>
                                        </tr>
                                        <tr style="border: none;">
                                            <td
                                                style="border: none; border-bottom: 1px solid black; border-right: 1px solid black; font-size: 8px;">
                                                İlave topraklama elektrotu detayları (varsa)</td>
                                            <td style="border: none; border-bottom: 1px solid black;"
                                                class="bold center">
                                                <?php echo htmlspecialchars($data['additional_electrode_details'] ?? ''); ?>
                                            </td>
                                        </tr>
                                        <tr style="border: none;">
                                            <td
                                                style="border: none; border-bottom: 1px solid black; border-right: 1px solid black;">
                                                Sistem topraklama iletkeni ve kesiti</td>
                                            <td style="border: none; border-bottom: 1px solid black;"
                                                class="bold center">
                                                <?php echo htmlspecialchars($data['system_grounding_conductor'] ?? ''); ?>
                                            </td>
                                        </tr>
                                        <tr style="border: none;">
                                            <td style="border: none; border-right: 1px solid black;">Ana eşpotansiyel
                                                iletkeni ve kesiti</td>
                                            <td style="border: none;" class="bold center">
                                                <?php echo htmlspecialchars($data['main_equipotential_conductor'] ?? ''); ?>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg" colspan="1">Besleme kaynağı karakteristikleri</td>
                                <td colspan="3">
                                    <div class="small-text">
                                        <?php echo !empty($data['nominal_voltage_kV']) ? '<b>[X]</b>' : '[ ]'; ?>
                                        Nominal gerilim, U/Uo(1) <span class="bold">
                                            <?php echo htmlspecialchars($data['nominal_voltage_kV'] ?? ''); ?>
                                        </span> kV &nbsp;&nbsp;&nbsp; (1. Fazdan alınan değer)<br>
                                        <?php echo !empty($data['nominal_frequency_Hz']) ? '<b>[X]</b>' : '[ ]'; ?>
                                        Nominal frekans, f (1) <span class="bold">
                                            <?php echo htmlspecialchars($data['nominal_frequency_Hz'] ?? ''); ?>
                                        </span> Hz<br>
                                        <?php echo !empty($data['fault_current_kA']) ? '<b>[X]</b>' : '[ ]'; ?> Hata
                                        Akımı Olasılığı, IF(1) <span class="bold">
                                            <?php echo htmlspecialchars($data['fault_current_kA'] ?? ''); ?>
                                        </span> kA<br>
                                        <?php echo !empty($data['external_loop_impedance']) ? '<b>[X]</b>' : '[ ]'; ?>
                                        Dış çevrim empedansı ZE <span class="bold">
                                            <?php echo htmlspecialchars($data['external_loop_impedance'] ?? ''); ?>
                                        </span> Ω
                                    </div>
                                </td>
                                <td class="header-bg" colspan="1">
                                    TT-TN-S Şebeke için ana RCD anma akımı
                                </td>
                                <td colspan="1" class="bold center">
                                    <?php echo htmlspecialchars($data['main_rcd_rating'] ?? ''); ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg" colspan="1">Ana kesici karakteristikleri</td>
                                <td colspan="3">
                                    <?php echo !empty($data['main_breaker_type']) ? '<b>[X]</b>' : '[ ]'; ?> Tip: <span
                                        class="bold"><?php echo htmlspecialchars($data['main_breaker_type'] ?? ''); ?></span><br>
                                    <?php echo !empty($data['main_breaker_rating']) ? '<b>[X]</b>' : '[ ]'; ?> Nominal
                                    Akım: <span
                                        class="bold"><?php echo htmlspecialchars($data['main_breaker_rating'] ?? ''); ?></span>
                                </td>
                                <td class="header-bg" colspan="1">
                                    TT-TNS Şebeke için ana RCD test akımı (mA) ve süresi (ms)
                                </td>
                                <td colspan="1" class="bold center">
                                    <?php echo htmlspecialchars($data['main_rcd_test_mA'] ?? ''); ?> mA
                                    /<br><?php echo htmlspecialchars($data['main_rcd_test_ms'] ?? ''); ?> ms
                                </td>
                            </tr>
                        </table>

                        <div class="header-bg"
                            style="border: 1px solid black; border-bottom: none; border-top: none; padding: 2px;">2.2.
                            TESPİT EDİLEN BİLGİLER</div>
                        <table style="margin-top: 0;">
                            <tr>
                                <td style="width: 50%;">Tesisatta kapsamlı değişiklik var mı? (>%20)</td>
                                <td style="width: 50%;">
                                    <span class="radio-item"><?php echo chk($data['installation_change'], 1); ?>
                                        Var</span>
                                    <span class="radio-item"><?php echo chk($data['installation_change'], 0); ?>
                                        Yok</span>
                                </td>
                            </tr>
                            <tr>
                                <td>Tesisatta aşırı gerilim koruma cihazları (DKD/SPD) kullanılmış mı?</td>
                                <td>
                                    <span class="radio-item"><?php echo chk($data['has_spd'], 1); ?> Evet</span>
                                    <span class="radio-item"><?php echo chk($data['has_spd'], 0); ?> Hayır</span>
                                </td>
                            </tr>
                            <tr>
                                <td>Tespit edilen bilgiler (Doğrudan dokunmaya karşı koruma önlemleri)</td>
                                <td>
                                    <?php
                                    $p_measures = explode(',', $data['protection_measures'] ?? '');
                                    $has_measure = function ($target) use ($p_measures) {
                                        foreach ($p_measures as $m)
                                            if (trim($m) === $target)
                                                return true;
                                        return false;
                                    };
                                    ?>
                                    <div style="font-size: 8px;">
                                        <span
                                            class="radio-item"><?php echo $has_measure('Gerilim altındaki bölümlerin yalıtılması') ? '[X]' : '[ ]'; ?>
                                            Gerilim altındaki bölümlerin yalıtılması (iç kapak veya pleksi
                                            koruma)</span><br>
                                        <span
                                            class="radio-item"><?php echo $has_measure('Muhafaza (IPXY, pano kilidi, uyarı vb.)') ? '[X]' : '[ ]'; ?>
                                            Mahfaza (IPXY, Pano kilidi, tehlike işareti vb.)</span><br>
                                        <span class="radio-item"><?php echo $has_measure('Engel') ? '[X]' : '[ ]'; ?>
                                            Engel</span><br>
                                        <span
                                            class="radio-item"><?php echo $has_measure('El ulaşma uzaklığı dışına yerleştirme') ? '[X]' : '[ ]'; ?>
                                            El ulaşma uzaklığı dışına yerleştirme</span><br>
                                        <span
                                            class="radio-item"><?php echo $has_measure('İlave koruma') ? '[X]' : '[ ]'; ?>
                                            İlave koruma</span><br>
                                        <span
                                            class="radio-item"><?php echo $has_measure('30 mA RCD') ? '[X]' : '[ ]'; ?>
                                            30 mA RCD (5xI için 40 ms açma zamanı); devre kesicisi <32 A devreler için
                                                (TS HD 60364-4-41)</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Bir önceki periyodik kontrol etiketi var mı?</td>
                                <td>
                                    <span class="radio-item"><?php echo chk($data['prev_label_exists'], 1); ?>
                                        Var</span>
                                    <span class="radio-item"><?php echo chk($data['prev_label_exists'], 0); ?>
                                        Yok</span>
                                </td>
                            </tr>
                        </table>

                        <div class="section-title-bg">3. TERMAL KAMERA BİLGİLERİ</div>
                        <table style="margin-top:0;">
                            <tr>
                                <td class="header-bg" style="width: 20%;">Cihaz adı</td>
                                <td style="width: 80%;"><?php echo htmlspecialchars($data['dt_name'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kalibrasyon tarihi</td>
                                <td><?php echo $data['dt_cal'] ?? ''; ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kalibrasyon geçerlilik tarihi</td>
                                <td><?php echo $data['dt_val'] ?? ''; ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Seri numarası</td>
                                <td><?php echo htmlspecialchars($data['dt_serial'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kalibrasyon numarası</td>
                                <td><?php echo htmlspecialchars($data['dt_cal_no'] ?? ''); ?></td>
                            </tr>
                        </table>

                        <div class="section-title-bg">4. ÖLÇÜM ALETLERİ BİLGİLERİ</div>
                        <table style="margin-top:0;">
                            <tr>
                                <td class="header-bg" style="width: 20%;">Cihaz adı</td>
                                <td style="width: 30%;"><?php echo htmlspecialchars($data['d1_name'] ?? ''); ?></td>
                                <td class="header-bg" style="width: 20%;">Cihaz adı</td>
                                <td style="width: 30%;"><?php echo htmlspecialchars($data['d2_name'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kalibrasyon tarihi</td>
                                <td><?php echo $data['d1_cal'] ?? ''; ?></td>
                                <td class="header-bg">Kalibrasyon tarihi</td>
                                <td><?php echo $data['d2_cal'] ?? '-'; ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kalibrasyon geçerlilik tarihi</td>
                                <td><?php echo $data['d1_val'] ?? ''; ?></td>
                                <td class="header-bg">Kalibrasyon geçerlilik tarihi</td>
                                <td><?php echo $data['d2_val'] ?? '-'; ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Seri numarası</td>
                                <td><?php echo htmlspecialchars($data['d1_serial'] ?? ''); ?></td>
                                <td class="header-bg">Seri numarası</td>
                                <td><?php echo htmlspecialchars($data['d2_serial'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kalibrasyon numarası</td>
                                <td><?php echo htmlspecialchars($data['d1_cal_no'] ?? ''); ?></td>
                                <td class="header-bg">Kalibrasyon numarası</td>
                                <td><?php echo htmlspecialchars($data['d2_cal_no'] ?? '-'); ?></td>
                            </tr>
                        </table>

                        <div
                            style="font-weight:bold; font-size:12px; text-align:center; padding:5px; border:1px solid black; border-bottom:none; margin-top:10px;">
                            TEST VE KONTROLLER</div>
                        <div class="section-title-bg">5. GÖZLE MUAYENE – KONTROL KRİTERLERİ VE TESTLER</div>
                        <?php if (empty($panels)): ?>
                            <div style="border:1px solid black; padding:5px; font-style:italic; color:#666;">Pano kaydı
                                bulunmamaktadır.</div>
                        <?php else: ?>
                            <?php foreach ($panels as $pnl):
                                $s5stmt = $pdo->prepare("SELECT question_key, answer FROM ic_tesisat_section5 WHERE panel_id=?");
                                $s5stmt->execute([$pnl['id']]);
                                $s5answers = [];
                                foreach ($s5stmt->fetchAll() as $row)
                                    $s5answers[$row['question_key']] = $row['answer'];

                                // Calculate panel index for fotograf_no automation
                                $current_panel_idx = 0;
                                foreach ($panels as $idx => $p) {
                                    if ($p['id'] == $pnl['id']) {
                                        $current_panel_idx = $idx + 1;
                                        break;
                                    }
                                }
                                ?>
                                <div
                                    style="border:1px solid black; border-bottom:none; padding:3px; background-color:white; font-size:10px; border-top:none;">
                                    Pano Adı / Ekipman Tanımlaması:
                                    <strong><?php echo htmlspecialchars($pnl['panel_name']); ?></strong>
                                </div>
                                <table style="margin-top:0; font-size:9px; border:1px solid black;">
                                    <tr class="header-bg" style="text-align:center;">
                                        <td style="width:35%">Kontrol Kriteri</td>
                                        <td style="width:15%">Değerlendirme</td>
                                        <td style="width:35%">Kontrol Kriteri</td>
                                        <td style="width:15%">Değerlendirme</td>
                                    </tr>
                                    <?php foreach ($s5_questions as $group => $items):
                                        $keys = array_keys($items);
                                        $labels = array_values($items);
                                        $half = ceil(count($keys) / 2);
                                        ?>
                                        <tr class="group-title-bg">
                                            <td colspan="4"><?php echo $group; ?></td>
                                        </tr>
                                        <?php for ($i = 0; $i < $half; $i++):
                                            $k1 = $keys[$i];
                                            $l1 = $labels[$i];
                                            $v1 = $s5answers[$k1] ?? '';
                                            $k2 = $keys[$i + $half] ?? null;
                                            $l2 = $labels[$i + $half] ?? null;
                                            $v2 = $k2 ? ($s5answers[$k2] ?? '') : '';
                                            ?>
                                            <tr>
                                                <td><?php echo $l1; ?></td>
                                                <td class="center" style="font-weight:bold;"><?php
                                                if ($k1 === 'fotograf_no') {
                                                    echo $current_panel_idx;
                                                } else {
                                                    if ($v1 === 'U') echo 'UYGUNDUR';
                                                    elseif ($v1 === 'UD') echo 'UYGUN DEĞİL';
                                                    elseif ($v1 === 'UG') echo 'UYGULANMAZ';
                                                    else echo htmlspecialchars($v1);
                                                }
                                                ?></td>
                                <?php if ($k2): ?>
                                    <td><?php echo $l2; ?></td>
                                    <td class="center" style="font-weight:bold;">
                                        <?php
                                        if ($k2 === 'fotograf_no') {
                                            echo $current_panel_idx;
                                        } else {
                                            if ($v2 === 'U')
                                                echo 'UYGUNDUR';
                                            elseif ($v2 === 'UD')
                                                echo 'UYGUN DEĞİL';
                                            elseif ($v2 === 'UG')
                                                echo 'UYGULANMAZ';
                                            else
                                                echo htmlspecialchars($v2);
                                        }
                                        ?>
                                    </td>
                                <?php else: ?>
                                    <td></td>
                                    <td></td><?php endif; ?>
                            </tr>
                        <?php endfor; ?>
                    <?php endforeach; ?>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="section-title-bg">6. FONKSİYON KONTROL KRİTERLERİ VE TESTLER</div>
    <table style="margin-top:0; border:1px solid black;">
        <tr>
            <td class="header-bg" style="width:35%;">Ölçüm ve doğrulama metodu</td>
            <td colspan="3">
                <?php $mm = $s6hdr['measurement_method'] ?? ''; ?>
                <span class="radio-item"><?php echo ($mm === 'Üç Uçlu Karşılaştırma') ? '<b>(X)</b>' : '( )'; ?>
                    Üç uçlu karşılaştırma</span>
                <span class="radio-item"><?php echo ($mm === 'Çevrim Empedansı') ? '<b>(X)</b>' : '( )'; ?>
                    Çevrim empedansı</span>
                <span class="radio-item"><?php echo ($mm === 'Klamp Yöntemi') ? '<b>(X)</b>' : '( )'; ?>
                    Klamp yöntemi</span>
            </td>
        </tr>
    </table>

    <?php if (!empty($panels)): ?>
        <?php
        $all_circuit_rows = [];
        foreach ($panels as $pnl):
            $s61data_stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_1 WHERE panel_id=?");
            $s61data_stmt->execute([$pnl['id']]);
            $s61 = $s61data_stmt->fetch() ?: [];
            $s61r_stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_1_rows WHERE panel_id=? ORDER BY id");
            $s61r_stmt->execute([$pnl['id']]);
            $s61rows = $s61r_stmt->fetchAll();

            foreach ($s61rows as $row) {
                $row['panel_display_name'] = $pnl['panel_name'];
                $all_circuit_rows[] = $row;
            }
            ?>
            <div style="page-break-inside: avoid; margin-top: 10px;">
                <div style="font-weight:bold; border:1px solid black; padding:4px; font-size:9px; border-bottom:none;">
                    6.1. AŞIRI AKIM CİHAZI / İLETKEN UYGUNLUĞU – RCD TESTLERİ - GERİLİM VE KISA DEVRE AKIMI
                    ÖLÇÜMLERİ- DKD KONTROLÜ
                </div>
                <div style="border:1px solid black; padding:3px; font-size:10px; border-bottom:none;">
                    Pano (Ekipman) Adı-Etiketi veya Kodu:
                    <strong><?php echo htmlspecialchars($pnl['panel_name']); ?></strong>
                </div>
                <table style="margin-top:0; font-size:8px; border:1px solid black; table-layout: fixed;">
                    <tr>
                        <td class="header-bg" style="width:25%;">Faz-toprak çevrim empedansı (Zx) [Ω]</td>
                        <td style="width:10%; text-align:center;"><?php echo $s61['zx'] ?? ''; ?></td>
                        <td class="header-bg" rowspan="3"
                            style="width:10%; text-align:center; font-weight:bold; vertical-align:middle; border-left: 2px solid black;">
                            Gerilimler</td>
                        <td class="header-bg" style="width:10%;">L-PE[V]</td>
                        <td style="width:10%; text-align:center;"><?php echo $s61['voltage_ff'] ?? ''; ?>
                        </td>
                        <td class="header-bg" style="width:25%; border-left: 2px solid black;">Aşırı gerilim
                            koruma (DKD) tipi</td>
                        <td style="width:10%; text-align:center;"><?php echo $s61['dkd_type'] ?? ''; ?></td>
                    </tr>
                    <tr>
                        <td class="header-bg">Faz-nötr çevrim empedansı (ZLN) [Ω]</td>
                        <td style="text-align:center;"><?php echo $s61['zln'] ?? ''; ?></td>
                        <td class="header-bg">L-N[V]</td>
                        <td style="text-align:center;"><?php echo $s61['voltage_ln'] ?? ''; ?></td>
                        <td class="header-bg" style="border-left: 2px solid black;">DKD dayanma akımı (kA)
                        </td>
                        <td style="text-align:center;"><?php echo $s61['dkd_current'] ?? ''; ?></td>
                    </tr>
                    <tr>
                        <td class="header-bg">Hesaplanan 3 fazlı kısa devre akımı Ik3 [kA]</td>
                        <td style="text-align:center;"><?php echo $s61['short_circuit_3ph'] ?? ''; ?></td>
                        <td class="header-bg">N-PE[V]</td>
                        <td style="text-align:center;"><?php echo $s61['voltage_npe'] ?? ''; ?></td>
                        <td colspan="2" style="border-left: 2px solid black;"></td>
                    </tr>
                </table>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($all_circuit_rows)): ?>
            <div style="margin-top: 15px;">
                <div
                    style="font-weight:bold; border:1px solid black; padding:5px; font-size:10px; border-bottom:none; background-color: #f0f0f0;">
                    LİNYE / BAĞLANTI LİSTESİ VE RCD TESTLERİ (TÜM PANOLAR)
                </div>
                <table style="margin-top:0; font-size:7.5px; border:1px solid black; text-align:center; width: 100%;">
                    <thead>
                        <tr class="header-bg">
                            <th rowspan="4" style="width:3%">No</th>
                            <th rowspan="4" style="width:18%">Pano / Linye Adı</th>
                            <th colspan="4">Aşırı Akım Koruma Cihazı</th>
                            <th colspan="4">İletken Uygunluğu</th>
                            <th rowspan="4" class="vertical-th" style="width:4.5%"><span class="vertical-text">Iz (A)</span>
                            </th>
                            <th colspan="2">RCD Testi</th>
                            <th rowspan="4" class="vertical-th" style="width:4.5%"><span class="vertical-text">Sonuç</span></th>
                        </tr>
                        <tr class="header-bg">
                            <th rowspan="3" class="vertical-th" style="width:4.5%"><span class="vertical-text">Açma Tipi</span>
                            </th>
                            <th rowspan="3" class="vertical-th" style="width:4.5%"><span class="vertical-text">Kutup S.</span>
                            </th>
                            <th rowspan="3" class="vertical-th" style="width:4.5%"><span class="vertical-text">In (A)</span>
                            </th>
                            <th rowspan="3" class="vertical-th" style="width:4.5%"><span class="vertical-text">Icu (kA)</span>
                            </th>
                            <th rowspan="3" class="vertical-th" style="width:4.5%"><span class="vertical-text">Faz Kesiti</span>
                            </th>
                            <th rowspan="3" class="vertical-th" style="width:4.5%"><span class="vertical-text">N/PEN
                                    Kesiti</span></th>
                            <th rowspan="3" class="vertical-th" style="width:4.5%"><span class="vertical-text">PE Kesiti</span>
                            </th>
                            <th rowspan="3" class="vertical-th" style="width:4.5%"><span class="vertical-text">Ib (A)</span>
                            </th>
                            <th rowspan="3" class="vertical-th" style="width:4.5%"><span class="vertical-text">IΔ (mA)</span>
                            </th>
                            <th rowspan="3" class="vertical-th" style="width:4.5%"><span class="vertical-text">TΔ (ms)</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_circuit_rows as $idx => $r): ?>
                            <tr>
                                <td><?php echo $idx + 1; ?></td>
                                <td style="text-align: left; padding-left: 3px;">
                                    <strong><?php echo htmlspecialchars($r['panel_display_name']); ?>:</strong>
                                    <?php echo htmlspecialchars($r['linye_adi']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($r['acma_egrisi']); ?></td>
                                <td><?php echo htmlspecialchars($r['kutup_sayisi']); ?></td>
                                <td><?php echo htmlspecialchars($r['in_a']); ?></td>
                                <td><?php echo htmlspecialchars($r['icu']); ?></td>
                                <td><?php echo htmlspecialchars($r['faz_kesiti']); ?></td>
                                <td><?php echo htmlspecialchars($r['npen_kesiti']); ?></td>
                                <td><?php echo htmlspecialchars($r['pe_kesiti']); ?></td>
                                <td><?php echo htmlspecialchars($r['ib_tasarim']); ?></td>
                                <td><?php echo htmlspecialchars($r['iz_kapasite']); ?></td>
                                <td><?php echo htmlspecialchars($r['rcd_ia']); ?></td>
                                <td><?php echo htmlspecialchars($r['rcd_ta']); ?></td>
                                <td><?php echo htmlspecialchars($r['sonuc']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- 6.2 -->
    <div class="section-title-bg">6.2. POTANSİYEL DENGELEME İLETKENLERİ KONTROLÜ</div>
    <table style="margin-top:0; font-size:8px; border:1px solid black; text-align:center;">
        <thead class="header-bg">
            <tr>
                <th style="width:5%">No.</th>
                <th style="width:25%">Potansiyel dengeleme yapılan ilgili bölüm</th>
                <th style="width:15%">Potansiyel dengeleme iletkeni kesiti (mm²)</th>
                <th style="width:15%">Potansiyel dengeleme iletkeni sürekliliği [Ω]</th>
                <th style="width:15%">Varsa tamamlayıcı potansiyel dengeleme iletkeni kesiti (mm²)
                </th>
                <th style="width:15%">Tamamlayıcı potansiyel dengeleme sürekliliği [Ω]</th>
                <th style="width:10%">Sonuç [U/UD]</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($s62rows)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; font-style:italic; color:#666;">Veri
                        girilmemiş</td>
                </tr>
            <?php else:
                foreach ($s62rows as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['no_col']); ?></td>
                        <td><?php echo htmlspecialchars($r['bolum']); ?></td>
                        <td><?php echo htmlspecialchars($r['pd_kesiti']); ?></td>
                        <td><?php echo htmlspecialchars($r['pd_sureklilik']); ?></td>
                        <td><?php echo htmlspecialchars($r['tpd_kesiti']); ?></td>
                        <td><?php echo htmlspecialchars($r['tpd_sureklilik']); ?></td>
                        <td><?php echo htmlspecialchars($r['sonuc']); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- 6.3 -->
    <div class="section-title-bg">6.3. ZEMİN İZOLASYONUNUN KONTROLÜ</div>
    <table style="margin-top:0; font-size:8px; border:1px solid black; text-align:center;">
        <thead class="header-bg">
            <tr>
                <th style="width:5%">No.</th>
                <th style="width:35%">İzolasyon halısının (Zemin yalıtımının) yeri</th>
                <th style="width:15%">Eni (m)</th>
                <th style="width:15%">Boyu (m)</th>
                <th style="width:15%">Zemin izolasyon direnci (kΩ)</th>
                <th style="width:15%">Sonuç (Uygunluk notu)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($s63rows)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; font-style:italic; color:#666;">Veri
                        girilmemiş</td>
                </tr>
            <?php else:
                foreach ($s63rows as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['no_col']); ?></td>
                        <td><?php echo htmlspecialchars($r['hali_yeri']); ?></td>
                        <td><?php echo htmlspecialchars($r['eni']); ?></td>
                        <td><?php echo htmlspecialchars($r['boyu']); ?></td>
                        <td><?php echo htmlspecialchars($r['direnc']); ?></td>
                        <td><?php echo htmlspecialchars($r['sonuc']); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="section-title-bg">7. KUSUR AÇIKLAMALARI</div>
    <div style="border: 1px solid black; padding: 5px; min-height: 80px; border-top: none;">
        <?php echo nl2br(htmlspecialchars($data['defects'] ?? '')); ?>
    </div>
    <div class="small-text" style="margin-top: 5px;">
        Kusur derecesi "*" hafif kusurlu ve "**" ağır kusurlu anlamında kullanılmaktadır.
        Değerlendirme "Uygun", "Uygun Değil" ve "Uygulanamaz" olarak yapılmıştır.
    </div>

    <div class="section-title-bg">8. EKİPMAN FOTOĞRAFLARI</div>
    <?php if (empty($panels)): ?>
        <div style="border:1px solid black; border-top:none; padding:5px; font-style:italic; color:#666;">
            Pano fotoğrafı bulunmamaktadır.</div>
    <?php else:
        $chunked_panels = array_chunk($panels, 2);
        foreach ($chunked_panels as $row_panels):
            ?>
            <table style="margin-top:5px; width:100%; border-collapse: collapse; table-layout: fixed;">
                <tbody>
                    <tr>
                        <?php for ($i = 0; $i < 2; $i++):
                            $pnl = $row_panels[$i] ?? null;
                            ?>
                            <td style="width: 50%; border:1px solid black; vertical-align: top; padding: 0;">
                                <?php if ($pnl):
                                    $phst = $pdo->prepare("SELECT * FROM ic_tesisat_photos WHERE panel_id=? ORDER BY photo_type, id");
                                    $phst->execute([$pnl['id']]);
                                    $pnlphotos = $phst->fetchAll();
                                    $nphots = array_values(array_filter($pnlphotos, fn($p) => $p['photo_type'] === 'normal'));
                                    $tphots = array_values(array_filter($pnlphotos, fn($p) => $p['photo_type'] === 'termal'));
                                    ?>
                                    <!-- Panel Name Header -->
                                    <div class="header-bg"
                                        style="border-bottom: 1px solid black; padding: 2px; text-align: center; font-weight: bold; font-size: 9px;">
                                        <?php
                                        $p_idx = 0;
                                        foreach ($panels as $idx => $orig_p)
                                            if ($orig_p['id'] == $pnl['id'])
                                                $p_idx = $idx + 1;
                                        echo $p_idx . '. ' . htmlspecialchars($pnl['panel_name']);
                                        ?>
                                    </div>
                                    <!-- Photos Container -->
                                    <div style="display: flex; min-height: 130px; align-items: stretch;">
                                        <!-- Normal Camera -->
                                        <div
                                            style="width: 50%; border-right: 1px solid #eee; padding: 2px; text-align: center; display: flex; flex-direction: column; justify-content: center;">
                                            <div style="font-size: 7px; color: #666; margin-bottom: 2px;">Normal
                                                Kamera</div>
                                            <div
                                                style="flex-grow: 1; display: flex; justify-content: center; align-items: center; gap: 2px; flex-wrap: wrap;">
                                                <?php if ($nphots):
                                                    foreach ($nphots as $ph): ?>
                                                        <img src="<?php echo htmlspecialchars($ph['file_path']); ?>"
                                                            style="max-height: 100px; max-width: 95%; object-fit: contain;">
                                                    <?php endforeach; else: ?>
                                                    <span style="color:#ccc; font-style:italic; font-size:8px;">Yok</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <!-- Thermal Camera -->
                                        <div
                                            style="width: 50%; padding: 2px; text-align: center; display: flex; flex-direction: column; justify-content: center;">
                                            <div style="font-size: 7px; color: #666; margin-bottom: 2px;">Termal
                                                Kamera</div>
                                            <div
                                                style="flex-grow: 1; display: flex; justify-content: center; align-items: center; gap: 2px; flex-wrap: wrap;">
                                                <?php if ($tphots):
                                                    foreach ($tphots as $ph): ?>
                                                        <img src="<?php echo htmlspecialchars($ph['file_path']); ?>"
                                                            style="max-height: 100px; max-width: 95%; object-fit: contain;">
                                                    <?php endforeach; else: ?>
                                                    <span style="color:#ccc; font-style:italic; font-size:8px;">Yok</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                </tbody>
            </table>
        <?php endforeach; endif; ?>

    <div class="section-title-bg">9. NOTLAR</div>
    <div style="border: 1px solid black; border-top:none; padding: 5px; min-height: 60px;">
        <?php echo nl2br(htmlspecialchars($data['notes'] ?? '')); ?>
    </div>

    <div class="section-title-bg">10. SONUÇ VE KANAAT</div>
    <div style="border: 1px solid black; border-top:none; padding: 10px; line-height: 1.4; font-size: 9px;">
        <p>Periyodik kontrol tarihi itibari ile yukarıda teknik özellikleri belirtilen Elektrik
            Tesisatının fonksiyon testleri muayenesi sonrasında mevcut şartlar altında
            <strong>kullanımı
                <?php echo ($data['result'] == 'UYGUNDUR') ? 'uygundur' : 'uygun değildir'; ?>.</strong>
            TS HD 60364 standardına göre kullanımı uygun olmayan tesisatlar aşağıdaki şekilde
            işaretlenir:<br>
            Tespit edilen hafif kusurların bir sonraki periyodik kontrol tarihine kadar giderilmesi
            gereklidir.<br>
            (*)Bu not, sadece hafif kusur tespit edilmesi durumunda yazılacaktır.
        </p>

        <p><strong>Ağır kusur tanımları:</strong><br>
            <strong>Gözle Kontrol Bölümü</strong><br>
            1. Faza erişim engeli IP2X koruma sınıfını sağlamıyorsa,<br>
            2. Kablo ek noktaları yalıtımlı değilse, pano içinde ucu açıkta iletken varsa,<br>
            3. Pano elemanları bağlantı noktalarında kontak gevşekliği (seri ark) tespit
            edilmişse,<br>
            4. PVC izoleli kablolarda ve pano elemanlarının dokunulabilen metal olmayan yüzeylerinde
            aşırı ısınma tespit edilmişse.
        </p>

        <p><strong>Fonksiyon Testleri Bölümü</strong><br>
            1. Hesaplanan 3 fazlı kısa devre akımı, pano içinde bulunan herhangi bir aşırı akım
            koruma cihazı etiketinde yazan kısa devre kesme kapasitesinden (Icu) fazla ise,<br>
            2. Aşırı akım koruma elemanı değerleri linye kesiti ile uyumsuz ise,<br>
            3. El ulaşma mesafesindeki metal bölümler, ekipmanın toprak barası veya toprak ucuyla eş
            potansiyel değilse,<br>
            4. RCD performans testi sonuçları yetersiz ise,<br>
            5. Kablo şalter koordinasyonunun aşağıdaki uygunsuzluğu;<br>
            Devre Tasarım Akımı (Ib), Devre Kesici Akımı (In) ve Kablo Akım Taşıma Kapasitesi (Iz)
            için Ib&lt;In&lt;Iz sağlanmadıysa,<br>
            6. Zemin yalıtımının aşağıdaki uygunsuzlukları;<br>
            b) El ulaşma mesafesinde bulunan zemin izolasyonu uygun boyutta değilse, (EİTY Md.
            33d)<br>
            c) Zemin izolasyon direnci 50 kΩ’dan büyük değilse, (EİTY Md. 48)<br>
            7. N/PEN iletkeni kesitinin ve kullanım yerinin aşağıdaki uygunsuzlukları;<br>
            a) N/PEN kesiti ile faz iletkeni kesiti eşit değilse. (EİTY Md 57b3-ii-1)<br>
            İstisna: N/PEN kesitinin faz iletkeninin kesitinden küçük olması durumunda (örneğin
            3x70+35 mm² gibi) faz iletkenlerini koruyan devre kesici anma akımının, N/PEN iletkeni
            kesitine göre (örnekteki 35mm²'ye göre) belirlenmesi kuralı ihlal edildiyse (EİTY Md
            57b3-ii-2)<br>
            b) PEN iletkeni kesiti >10 mm² değilse (EİTY Md 36)<br>
            c) PEN iletkeni yangın tehlikesi olan yerlerden geçirilmemesi kuralı ihlal edildiyse
            (EİTY Md 64)<br>
            d) PEN iletkeni Exproof tehkeliteli bölge sınırları içinden geçirilmemesi kuralı ihlal
            edildiyse (IEC 60079-14)<br>
            8. PE koruma iletkeni kesiti aşağıdaki uygunsuzluğu;<br>
            Koruma iletkeni kesiti ETTY Md. 9e1’ide verilen formülle yapılan hesap sonucuna ve ETTY
            Çizelge 8’de verilen tabloya uygun değilse,<br>
            9. PD potansiyel dengeleme iletkeni kesitinin aşağıdaki uygunsuzluğu;<br>
            6 mm² &lt; PD &lt; 25 mm² değilse,<br>
            10. Tamamlayıcı potansiyel dengeleme: PD > 4mm² değilse.<br>
            Ağır kusur olarak değerlendirilir.
        </p>

        <p style="font-weight: bold;">
            C1 – Tehlike mevcut. Yaralanma riski. Derhal düzeltici eylem gerekli.<br>
            C2 – Potansiyel olarak tehlikeli – acil düzeltici eylem gerekli.<br>
            C3 – İyileştirme önerilir.
        </p>

        <p>Bu rapor <strong>“Alçak Gerilim Topraklama Tesisatı Kontrol Raporu”</strong> ile birlikte
            geçerlidir.<br>
            Tespit edilen hafif kusurların bir sonraki periyodik kontrol tarihine kadar giderilmesi
            gereklidir. (Sadece hafif kusur tespit edilmesi durumunda yazılacaktır.)</p>
    </div>

    <div class="section-title-bg">11. PERİYODİK KONTROLLERİ YAPMAYA YETKİLİ KİŞİ BİLGİLERİ ve ONAY
    </div>
    <table style="margin-top:0; border-top: none;">
        <tr>
            <td class="header-bg" style="width: 25%;">Adı Soyadı</td>
            <td style="width: 50%; font-weight: bold;">
                <?php echo htmlspecialchars($data['adi_soyadi'] ?? ''); ?>
            </td>
            <td style="width: 25%; text-align: center;" class="header-bg">İmza</td>
        </tr>
        <tr>
            <td class="header-bg">Mesleği</td>
            <td><?php echo htmlspecialchars($data['meslegi'] ?? ''); ?></td>
            <td rowspan="2" style="text-align: center; vertical-align: bottom;"></td>
        </tr>
        <tr>
            <td class="header-bg">Yetkili Kişi Kayıt Numarası</td>
            <td><?php echo htmlspecialchars($data['kayit_no'] ?? ''); ?></td>
        </tr>
    </table>
    <div class="small-text" style="font-size: 8px;">Bu rapor ..........[yazı (rakam)] nüsha olarak
        hazırlanmıştır.</div>
    </div>
    </td>
    </tr>
    </tbody>
    </table>
</body>

</html>