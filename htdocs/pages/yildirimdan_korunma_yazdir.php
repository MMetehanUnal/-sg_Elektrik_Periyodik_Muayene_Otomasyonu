<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
if (!$id)
    die("Rapor ID gerekli.");

// Fetch Lightning Protection Report Data
$stmt = $pdo->prepare("
    SELECT r.*, 
           i.firma_adi, i.adresi, i.sgk_sicil_no, i.il_kodu, i.kurum_kodu,
           d1.device_name as d1_name, d1.serial_no as d1_serial, d1.cal_date as d1_cal, d1.validity_date as d1_val, d1.cal_no as d1_cal_no,
           d2.device_name as d2_name, d2.serial_no as d2_serial, d2.cal_date as d2_cal, d2.validity_date as d2_val, d2.cal_no as d2_cal_no,
           dt.device_name as dt_name, dt.serial_no as dt_serial, dt.cal_date as dt_cal, dt.validity_date as dt_val, dt.cal_no as dt_cal_no,
           ap.adi_soyadi, ap.meslegi, ap.kayit_no
    FROM lightning_protection_reports r
    JOIN institutions i ON r.kurum_id = i.id
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

// Fetch Section 4 Results
$s4stmt = $pdo->prepare("SELECT question_key, answer FROM lightning_protection_section4 WHERE report_id = ?");
$s4stmt->execute([$id]);
$s4answers = [];
foreach ($s4stmt->fetchAll() as $row) {
    $s4answers[$row['question_key']] = $row['answer'];
}

$ese_questions = [
    'A. KORUMA BORUSU' => [
        'ese_a1' => 'Koruma borusu tesis edilmiş midir?',
        'ese_a2' => 'Koruma borusu galvaniz mi?',
        'ese_a3' => 'Koruma borusunda oksitlenme var mı?',
        'ese_a4' => 'Koruma borusu çapı uygun mudur?',
        'ese_a5' => 'Koruma borusu duvara kelepçelerle tutturulmuş mudur?',
        'ese_a6' => 'Koruma borusu ağzı yalıtkan bir madde ile kaplanmış mıdır?',
        'ese_a7' => 'Koruma borusu içindeki iletkenler PVC boru/hortum içinde midir?',
        'ese_a8' => 'Koruma borusu >250 cm'
    ],
    'B. İNDİRME İLETKENLERİ' => [
        'ese_b1' => 'İndirme iletkenleri 2x50 mm² bakır veya eşdeğer iletken mi?',
        'ese_b2' => 'İndirme iletkenleri som bakır veya eşdeğer iletken mi?',
        'ese_b3' => 'İndirme iletkenleri tespit kroşeleri kızıl döküm',
        'ese_b4' => 'İndirme iletkenleri tespit kroşelerinde oksitlenme var mı?',
        'ese_b5' => 'İndirme iletkenleri köşe "S" yapmakta mıdır?',
        'ese_b6' => 'İndirme iletkenleri tespit elemanları arası mesafe ortalama 0,5-0,7 m',
        'ese_b7' => 'Gerilmiş tel ise her bir tel ucu için indirme iletkeni kullanılmış mı?'
    ],
    'C. MUAYENE KLEMENSİ' => [
        'ese_c1' => 'Muayene klemensi tesisi',
        'ese_c2' => 'Muayene klemensi oksitlenmeye karşı koruma alınmış mıdır?',
        'ese_c3' => 'Muayene klemensi zeminden 270 cm yukarıda mıdır?',
        'ese_c4' => 'Muayene klemensi ile koruma borusu arası mesafe 20 cm midir?'
    ],
    'D. ÇATI/TESİS ÜSTÜ' => [
        'ese_d1' => 'Çatı direği boyu ve çapı uygun mu? (Boy: 6-6,5 m Çap: 2")',
        'ese_d2' => 'Çatı direği üzerinde iletken tespit elemanları bulunmakta mıdır?',
        'ese_d3' => 'Çatı direği çatı üzerine sağlam tutturlulmuş mudur?',
        'ese_d4' => 'İniş iletkenleri çatı direğine uygun olarak irtibatlandırılmış mıdır?'
    ],
    'E. TOPRAKLAMA TESİSİ' => [
        'ese_e1' => 'İndirme iletkenleri topraklama elektrotlarına uygun bir şekilde tutturulmuş mudur?',
        'ese_e2' => 'İndirme iletkenleri koruma borusundan sonra zemin üzerinde midir?',
        'ese_e3' => 'İndirme iletkenlerinde sürekliliğin sağlandığı görülüyor mu?',
        'ese_e4' => 'Topraklama hattı tesis edilmiş midir? Bina topraklaması ile eşpotansiyel midir?',
        'ese_e5' => 'Topraklama tesisi direnci 10 Ω’dan küçük müdür?',
        'ese_e6' => 'AG parafudru (DKD) kullanılmış ise, koordineli olarak kullanılmış mı? Kullanılmamışsa ana pano ve diğer tali panoları besleyen kablolar ekranlı mı?'
    ]
];

$faraday_questions = [
    'A. ÇATIDA TERASTA AĞ' => [
        'fa_a1' => 'Ağ iletkenlerinin kesitleri standarta uygun mudur?',
        'fa_a2' => 'Ağ risk analizinde belirlenen genişlikte midir?',
        'fa_a3' => 'Ağ\'da varsa düşey yakalama çubukları uygun mudur?',
        'fa_a4' => 'Özellikle yanıcı, parlayıcı, patlayıcı madde bulunan binalarda düşey yakalama çubuklarının bulunmadığı veya tehlikeli bölge dışında bulunduğu kontrol edilmelidir.'
    ],
    'B. İNDİRME İLETKENLERİ' => [
        'fa_b1' => 'Yatay yakalama sistemi (ağ) için yeterli sayıda indiricilere bağlantı var mı? (en az 20 m\'de 1 indirici)',
        'fa_b2' => 'İndirme iletkenleri standarta uygun kesitte som bakır veya eşdeğer iletken mi?',
        'fa_b3' => 'İndirme iletkenleri tespit kroşeleri kızıl döküm',
        'fa_b4' => 'Doğal indirici metal yapılar kullanılmıyorsa indirme iletkenleri tespit kroşelerinde oksitlenme var mı?',
        'fa_b5' => 'Doğal indirici metal yapılar kullanılmıyorsa indirme iletkenleri köşe "S" yapmakta mıdır?',
        'fa_b6' => 'Doğal indirici metal yapılar kullanılmıyorsa indirme iletkenleri tespit kroşeleri arası mesafe ortalama 0,5-0,7 m'
    ],
    'C. TOPRAKLAMA TESİSİ' => [
        'fa_c1' => 'Yıldırıma karşı koruma topraklamalarına 20 m\'den daha küçük mesafede başka topraklayıcılar bulunuyorsa, bütün topraklayıcılar birbirleriyle eşpotansiyel midir?',
        'fa_c2' => 'Bina çatısına monte edilen düşey yakalama ucunun bağlı olduğu çatı direği, çelik dübellerle bina betonuna bağlandığından, topraklamasının bina ile eşpotansiyel midir?',
        'fa_c3' => 'Doğal metal yapılar indirici olarak kullanıldıysa bu yapılar temel topraklamasına bağlı olduğundan çatı ağının doğal bileşenlere bağlantı noktaları kontrol edilir.',
        'fa_c4' => 'Topraklama tesisi direnci 10 Ω’dan küçük müdür?'
    ],
    'D. İÇ YILDIRIMLIK TESİSİ' => [
        'fa_d1' => 'Ana dağıtım panosunda uygun parafudr tesis edilmiş mi?',
        'fa_d2' => 'Parafudr tipi'
    ]
];

// Helper for checkboxes/radios
function chk($val, $target)
{
    if (is_array($val)) {
        return in_array($target, $val) ? '<b>[X]</b>' : '[ ]';
    }
    // Handle string comma separated values
    if (strpos((string) $val, ',') !== false) {
        $parts = array_map('trim', explode(',', $val));
        return in_array($target, $parts) ? '<b>[X]</b>' : '[ ]';
    }
    return (trim((string) $val) === trim((string) $target)) ? '<b>[X]</b>' : '[ ]';
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
                <h1>YILDIRIMDAN KORUNMA TESİSATI<br>PERİYODİK KONTROL RAPORU</h1>
            </td>
            <td style="width: 30%; border:none;">
                <table class="doc-info" style="border:1px solid black; width:100%;">
                    <tr>
                        <td style="border:1px solid black; width:50%;">Doküman Kodu</td>
                        <td style="border:1px solid black;">ZPKR03</td>
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
    <title>Yıldırımdan Korunma Raporu: <?php echo htmlspecialchars($data['report_no']); ?></title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        .header-bg {
            background-color: #dce6f1;
            /* Light Blue */
            font-weight: bold;
        }

        .section-title-bg {
            background-color: #fce4d6;
            /* Pink/Orange */
            font-weight: bold;
            text-align: left;
            padding: 5px 10px;
            border: 1px solid black;
            margin-top: 15px;
            font-size: 11px;
            text-transform: uppercase;
        }

        .group-title-bg {
            background-color: #ebf1de;
            /* Light Green */
            font-weight: bold;
            text-align: center;
            padding: 3px;
            font-size: 9px;
            border: 1px solid black;
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
                <th style="border:none;">
                    <?php renderHeader(); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border:none;">
                    <div class="page">
                        <div class="section-title-bg">1. FİRMA BİLGİLERİ</div>
                        <table>
                            <tr>
                                <td style="width: 20%;" class="header-bg">Firma Adı</td>
                                <td style="width: 40%;"><?php echo htmlspecialchars($data['firma_adi'] ?? ''); ?></td>
                                <td style="width: 20%;" class="header-bg">Rapor Numarası</td>
                                <td style="width: 20%;"><?php echo htmlspecialchars($data['report_no'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td rowspan="5" class="header-bg">Periyodik Kontrol Adresi</td>
                                <td rowspan="5"><?php echo nl2br(htmlspecialchars($data['adresi'] ?? '')); ?></td>
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
                                <td class="header-bg">Bir Sonraki Periyodik Kontrol Tarihi</td>
                                <td><?php echo $data['next_control_date'] ? date('d.m.Y', strtotime($data['next_control_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">SGK Sicil Numarası</td>
                                <td><?php echo htmlspecialchars($data['sgk_sicil_no'] ?? ''); ?></td>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Periyodik Kontrol Metodu ve Kapsamı</td>
                                <td colspan="3">
                                    <ul style="margin: 0; padding-left: 15px;">
                                        <li>TS EN 62305-1 Yıldırımdan Korunma-Bölüm-1: Genel Kurallar</li>
                                        <li>TS EN 62305-2 Yıldırımdan korunma-Bölüm-2: Risk Yönetimi</li>
                                        <li>TS EN 62305-3 Yıldırımdan Korunma-Bölüm-3: Yapılarda Fiziksel Hasar ve
                                            Hayati Tehlike</li>
                                        <li>TS EN 62305-4 Yıldırımdan Korunma-Bölüm-4: Yapılarda Bulunan Elektrik ve
                                            Elektronik Sistemler</li>
                                        <li>TS 622 Yapıların Yıldırımdan Korunması Kuralları</li>
                                        <li>İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği</li>
                                        <li>Elektrik Tesislerinde Topraklamalar Yönetmeliği</li>
                                        <li>Elektrik İç Tesisleri Yönetmeliği</li>
                                    </ul>
                                </td>
                            </tr>
                        </table>

                        <div class="section-title-bg">2. EKİPMAN BİLGİLERİ</div>
                        <div class="header-bg"
                            style="font-weight: bold; padding: 5px; border: 1px solid black; margin-top: 10px;">2.1.
                            ETİKET VE DETAY BİLGİLERİ</div>
                        <table>
                            <tr>
                                <td style="width: 25%;" class="header-bg">Enerji sağlayan kuruluş</td>
                                <td style="width: 25%;"><?php echo htmlspecialchars($data['energy_provider'] ?? ''); ?>
                                </td>
                                <td style="width: 15%;" class="header-bg">Şebeke tipi</td>
                                <td colspan="3">
                                    <?php $st = $data['sebeke_tipi'] ?? ''; ?>
                                    <span class="radio-item"><?php echo chk($st, 'TT'); ?> TT</span>
                                    <span class="radio-item"><?php echo chk($st, 'IT'); ?> IT</span>
                                    <span class="radio-item"><?php echo chk($st, 'TN'); ?> TN</span><br>
                                    <span class="radio-item"><?php echo chk($st, 'TN-CS'); ?> TN-CS</span>
                                    <span class="radio-item"><?php echo chk($st, 'TN-C'); ?> TN-C</span>
                                    <span class="radio-item"><?php echo chk($st, 'TN-S'); ?> TN-S</span>
                                </td>
                            </tr>
                            <tr>
                                <td rowspan="2" class="header-bg">Şebeke gerilimi</td>
                                <td rowspan="2"><?php echo htmlspecialchars($data['sebeke_voltage'] ?? ''); ?></td>
                                <td class="header-bg">Tesise ait kapsama alanı projesi var mı?</td>
                                <td>
                                    <span class="radio-item"><?php echo chk($data['has_project'], 'Var'); ?> Var</span>
                                    <span class="radio-item"><?php echo chk($data['has_project'], 'Yok'); ?> Yok</span>
                                </td>
                                <td class="header-bg">Risk analizi var mı?</td>
                                <td>
                                    <span class="radio-item"><?php echo chk($data['has_risk_analysis'], 'Var'); ?>
                                        Var</span>
                                    <span class="radio-item"><?php echo chk($data['has_risk_analysis'], 'Yok'); ?>
                                        Yok</span>
                                </td>
                            </tr>
                            <tr>

                                <td class="header-bg">Proje detayları</td>
                                <td colspan="3">
                                    <?php echo htmlspecialchars($data['project_details'] ?? ''); ?>
                                </td>
                            </tr>
                            <tr>

                                <td class="header-bg">Kontrol nedeni</td>
                                <td>
                                    <?php $cr = $data['control_reason'] ?? ''; ?>
                                    <span class="radio-item"><?php echo chk($cr, 'Periyodik Kontrol'); ?> Per.
                                        Kontrol</span>
                                    <span class="radio-item"><?php echo chk($cr, 'İlk Kontrol'); ?> İlk Kontrol</span>
                                </td>
                                <td class="header-bg">Topraklayıcı tipi</td>
                                <td colspan="3">
                                    <?php $gt = $data['grounding_type'] ?? ''; ?>
                                    <span class="radio-item"><?php echo chk($gt, 'Ring'); ?> Ring</span>
                                    <span class="radio-item"><?php echo chk($gt, 'Yüzeysel'); ?> Yüz.</span>
                                    <span class="radio-item"><?php echo chk($gt, 'Temel'); ?> Temel</span><br>
                                    <span class="radio-item"><?php echo chk($gt, 'Derin'); ?> Derin</span>
                                    <span class="radio-item"><?php echo chk($gt, 'Belirlenemedi'); ?> Belir.</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Yapı cinsi</td>
                                <td>
                                    <?php $yc = $data['building_type'] ?? ''; ?>
                                    <span class="radio-item"><?php echo chk($yc, 'Ev'); ?> Ev</span>
                                    <span class="radio-item"><?php echo chk($yc, 'Ticari'); ?> Tic.</span>
                                    <span class="radio-item"><?php echo chk($yc, 'Endüstri'); ?> End.</span>
                                    <span class="radio-item"><?php echo chk($yc, 'Diğer'); ?> Diğer</span>
                                </td>
                                <td class="header-bg">Ekipmanın kullanım amacı ve YKS cinsi</td>
                                <td>
                                    <?php $usage = $data['usage_purpose_yks_type'] ?? ''; ?>
                                    <span class="radio-item"><?php echo chk($usage, 'Ayrılmış YKS'); ?>
                                        Ayrılmış</span><br>
                                    <span class="radio-item"><?php echo chk($usage, 'Ayrılmamış (Eşpotansiyel) YKS'); ?>
                                        Ayrılmamış</span>
                                </td>
                                <td class="header-bg">Son kontrol tarihi</td>
                                <td><?php echo $data['prev_control_date'] ? date('d.m.Y', strtotime($data['prev_control_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Hava durumu ve sıcaklığı</td>
                                <td><?php echo htmlspecialchars($data['weather_condition'] ?? ''); ?></td>
                                <td class="header-bg">Zemin nem durumu</td>
                                <td colspan="3"><?php echo htmlspecialchars($data['ground_moisture'] ?? ''); ?></td>
                            </tr>
                        </table>

                        <div class="header-bg"
                            style="font-weight: bold; padding: 5px; border: 1px solid black; margin-top: 10px;">2.2.
                            TESPİT EDİLEN BİLGİLER</div>
                        <table>
                            <tr>
                                <td style="width: 25%;" class="header-bg">Tesisatta kapsamlı değişiklik var mı?</td>
                                <td style="width: 15%;">
                                    <span class="radio-item"><?php echo chk($data['installation_change'], 'Var'); ?>
                                        Var</span>
                                    <span class="radio-item"><?php echo chk($data['installation_change'], 'Yok'); ?>
                                        Yok</span>
                                </td>
                                <td style="width: 25%;" class="header-bg">Bir önceki periyodik kontrol etiketi var mı?
                                </td>
                                <td style="width: 15%;">
                                    <span class="radio-item"><?php echo chk($data['prev_label_exists'], 'Var'); ?>
                                        Var</span>
                                    <span class="radio-item"><?php echo chk($data['prev_label_exists'], 'Yok'); ?>
                                        Yok</span>
                                </td>
                                <td style="width: 10%;" class="header-bg">Ekipman tanımlaması</td>
                                <td style="width: 10%;">
                                    <?php echo htmlspecialchars($data['equipment_identification'] ?? ''); ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Yıldırımdan korunma tesisatı tipi</td>
                                <td colspan="5">
                                    <?php $pt = $data['protection_system_type'] ?? ''; ?>
                                    <span class="radio-item"><?php echo chk($pt, 'ESE (Aktif-Radyoaktif) Paratoner'); ?>
                                        ESE Paratoner</span>
                                    <span class="radio-item"><?php echo chk($pt, 'Faraday kafesi: FARADAY'); ?> Faraday
                                        kafesi</span>
                                    <span class="radio-item"><?php echo chk($pt, 'Franklin çubuğu: FRANKLİN'); ?>
                                        Franklin çubuğu</span><br>
                                    <span class="radio-item"><?php echo chk($pt, 'Gerilmiş Tel'); ?> Gerilmiş Tel</span>
                                    <span
                                        class="radio-item"><?php echo chk($pt, 'Doğal Bileşenler (Betonarme donatı, çelik yapı): DOĞAL'); ?>
                                        Doğal Bileşenler</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Koruma seviyesi (EPS)</td>
                                <td colspan="5"><?php echo htmlspecialchars($data['protection_level_eps'] ?? ''); ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Yapı kullanım amacı, yapıya ait detaylar</td>
                                <td colspan="5">
                                    <?php echo nl2br(htmlspecialchars($data['building_usage_details'] ?? '')); ?>
                                </td>
                            </tr>
                        </table>

                        <div class="section-title-bg">3. ÖLÇÜM ALETLERİ BİLGİLERİ</div>
                        <table>
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
                    </div><!-- /.page p1 -->

                    <div class="page">
                        <div class="section-title-bg">4. KONTROL KRİTERLERİ VE TESTLER</div>
                        <table style="margin-top:5px;">
                            <tr>
                                <td colspan="4" class="group-title-bg">Yıldırımdan Korunma Sisteminin Koruma Yaptığı
                                    Kapsama Alanı Bağlamında Uygunluğu</td>
                            </tr>
                            <tr>
                                <td style="width:35%;" class="header-bg">Yıldırımdan korunma risk analizi ve kapsama
                                    alanı projesi var mı?</td>
                                <td style="width:15%;"><?php echo $s4answers['risk_analizi_varmi'] ?? ''; ?></td>
                                <td style="width:35%;" class="header-bg">Kapsama alanı binayı kapsıyor mu?</td>
                                <td style="width:15%;"><?php echo $s4answers['kapsama_uygunmu'] ?? ''; ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="group-title-bg">Yıldırımdan Koruma Sisteminin Tesisatının Fiziki
                                    Olarak Uygunluğu</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="header-bg center">ÖLÇÜM METODU</td>
                            </tr>
                            <tr>
                                <td class="header-bg">Ölçüm ve doğrulama metodu</td>
                                <td colspan="3">
                                    <?php $mm = $s4answers['measurement_method'] ?? ''; ?>
                                    <span class="radio-item"><?php echo chk($mm, 'Çevrim empedansı'); ?> Çevrim
                                        empedansı</span>
                                    <span class="radio-item"><?php echo chk($mm, '3 Uçlu topraklama'); ?> 3 Uçlu
                                        topraklama</span>
                                    <span class="radio-item"><?php echo chk($mm, 'Klamp metodu'); ?> Klamp metodu</span>
                                </td>
                            </tr>
                        </table>


                        <!-- ESE Sections -->
                        <table style="margin-top:5px;">
                            <tr>
                                <td colspan="4" class="header-bg center">ESE (Aktif-Radyoaktif) Paratoner</td>
                            </tr>
                            <tr class="header-bg center">
                                <td style="width:35%;">Kontrol Kriteri</td>
                                <td style="width:15%;">Değerlendirme</td>
                                <td style="width:35%;">Kontrol Kriteri</td>
                                <td style="width:15%;">Değerlendirme</td>
                            </tr>
                            <?php
                            $groups = array_keys($ese_questions);
                            for ($i = 0; $i < count($groups); $i += 2):
                                $g1 = $groups[$i];
                                $g2 = $groups[$i + 1] ?? null;
                                ?>
                                <tr>
                                    <td class="group-title-bg"><?php echo $g1; ?></td>
                                    <td class="group-title-bg"></td>
                                    <td class="group-title-bg"><?php echo $g2; ?></td>
                                    <td class="group-title-bg"></td>
                                </tr>
                                <?php
                                $keys1 = array_keys($ese_questions[$g1]);
                                $labels1 = array_values($ese_questions[$g1]);
                                $keys2 = $g2 ? array_keys($ese_questions[$g2]) : [];
                                $labels2 = $g2 ? array_values($ese_questions[$g2]) : [];
                                $max = max(count($keys1), count($keys2));
                                for ($j = 0; $j < $max; $j++):
                                    ?>
                                    <tr>
                                        <td><?php echo $labels1[$j] ?? ''; ?></td>
                                        <td class="center">
                                            <?php echo $j < count($keys1) ? (!empty($s4answers[$keys1[$j]]) ? $s4answers[$keys1[$j]] : 'Uygulanamaz') : ''; ?>
                                        </td>
                                        <td><?php echo $labels2[$j] ?? ''; ?></td>
                                        <td class="center">
                                            <?php echo $j < count($keys2) ? (!empty($s4answers[$keys2[$j]]) ? $s4answers[$keys2[$j]] : 'Uygulanamaz') : ''; ?>
                                        </td>
                                    </tr>
                                <?php endfor; endfor; ?>
                        </table>



                        <!-- Faraday Shell -->
                        <table style="margin-top:5px;">
                            <tr>
                                <td colspan="4" class="header-bg center">FARADAY KAFESİ</td>
                            </tr>
                            <tr class="header-bg center">
                                <td style="width:35%;">Kontrol Kriteri</td>
                                <td style="width:15%;">Değerlendirme</td>
                                <td style="width:35%;">Kontrol Kriteri</td>
                                <td style="width:15%;">Değerlendirme</td>
                            </tr>
                            <?php
                            $groups = array_keys($faraday_questions);
                            for ($i = 0; $i < count($groups); $i += 2):
                                $g1 = $groups[$i];
                                $g2 = $groups[$i + 1] ?? null;
                                ?>
                                <tr>
                                    <td class="group-title-bg"><?php echo $g1; ?></td>
                                    <td class="group-title-bg"></td>
                                    <td class="group-title-bg"><?php echo $g2; ?></td>
                                    <td class="group-title-bg"></td>
                                </tr>
                                <?php
                                $keys1 = array_keys($faraday_questions[$g1]);
                                $labels1 = array_values($faraday_questions[$g1]);
                                $keys2 = $g2 ? array_keys($faraday_questions[$g2]) : [];
                                $labels2 = $g2 ? array_values($faraday_questions[$g2]) : [];
                                $max = max(count($keys1), count($keys2));
                                for ($j = 0; $j < $max; $j++):
                                    ?>
                                    <tr>
                                        <td><?php echo $labels1[$j] ?? ''; ?></td>
                                        <td class="center">
                                            <?php echo $j < count($keys1) ? (!empty($s4answers[$keys1[$j]]) ? $s4answers[$keys1[$j]] : 'Uygulanamaz') : ''; ?>
                                        </td>
                                        <td><?php echo $labels2[$j] ?? ''; ?></td>
                                        <td class="center">
                                            <?php echo $j < count($keys2) ? (!empty($s4answers[$keys2[$j]]) ? $s4answers[$keys2[$j]] : 'Uygulanamaz') : ''; ?>
                                        </td>
                                    </tr>
                                <?php endfor; endfor; ?>
                        </table>
                    </div><!-- /.page p2 -->

                    <div class="page">
                        <div class="section-title-bg">5. KUSUR AÇIKLAMALARI</div>
                        <div style="border: 1px solid black; padding: 5px; min-height: 80px;">
                            <?php echo nl2br(htmlspecialchars($data['defects'] ?? '')); ?>
                        </div>
                        <div class="small-text" style="margin-top: 5px;">
                            Nokta sayısı fazla olan tesislerde birden fazla form kullanılabilir. Ya da formun sadece 5.
                            Bölümü çoğaltılabilir.<br>
                            Kusur derecesi "*" hafif kusurlu ve "**" ağır kusurlu anlamında kullanılmaktadır.
                            Değerlendirme "Uygun", "Uygun Değil" ve "Uygulanamaz" olarak yapılmıştır.
                        </div>

                        <div class="section-title-bg">6. NOTLAR</div>
                        <div style="border: 1px solid black; padding: 5px; min-height: 60px;">
                            <?php echo nl2br(htmlspecialchars($data['notes'] ?? '')); ?>
                        </div>

                        <div class="section-title-bg">7. SONUÇ VE KANAAT</div>
                        <div style="border: 1px solid black; padding: 10px; line-height: 1.4;">
                            <p>Periyodik kontrol tarihi itibari ile yukarıda teknik özellikleri belirtilen Yıldırımdan
                                Korunma Tesisatı muayenesi sonrasında mevcut şartlar altında <strong>kullanımı
                                    <?php echo ($data['result'] == 'UYGUNDUR') ? 'uygundur' : 'uygun değildir'; ?>.</strong>
                            </p>

                            <p><strong>Ağır kusurlar tanımı:</strong><br>
                                <strong>1. Yıldırımdan Korunma sisteminin koruma yaptığı kapsama alanının aşağıdaki
                                    uygunsuzluğu;</strong><br>
                                Yıldırım risk analizine göre hazırlanan yıldırımdan korunma kapsama alanı, binayı veya
                                binaları kapsamıyorsa.
                            </p>

                            <p><strong>2. ESE (Aktif-Radyoaktif) Paratoner Bölümünde yıldırımdan korunma tesisatındaki
                                    aşağıdaki fiziki uygunsuzlukları;</strong><br>
                                a) Koruma Borusu İçindeki İletkenler PVC hortum içinde değilse,<br>
                                b) Koruma Borusu >250 cm değilse,<br>
                                c) İndirme iletkenleri 2x50 mm² bakır veya eşdeğer iletken değilse,<br>
                                d) Topraklama hattı tesis edilmemesi ve bina topraklaması ile eşpotansiyel değilse,<br>
                                e) Topraklama tesis direnci 10 Ω’dan küçük değilse.
                            </p>

                            <p><strong>3. Faraday Kafesi Bölümünde yıldırımdan korunma tesisatındaki aşağıdaki fiziki
                                    uygunsuzlukları;</strong><br>
                                a) Çatıda ağ risk analizinde belirlenen genişlikten büyükse,<br>
                                b) Özellikle yanıcı, parlayıcı, patlayıcı madde bulunan binalarda tehlikeli bölge içinde
                                düşey yakalama çubukları olmaması kuralı ihlal edildiyse,<br>
                                c) Topraklama tesis direnci 10 Ω’dan küçük değilse.
                            </p>
                        </div>

                        <div class="section-title-bg">8. PERİYODİK KONTROLLERİ YAPMAYA YETKİLİ KİŞİ BİLGİLERİ ve ONAY
                        </div>
                        <table>
                            <tr>
                                <td class="header-bg" style="width: 25%;">Adı Soyadı</td>
                                <td style="width: 50%;"><?php echo htmlspecialchars($data['adi_soyadi'] ?? ''); ?></td>
                                <td style="width: 25%; text-align: center;" class="header-bg">İmzasl</td>
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