<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
if (!$id)
    die("Rapor ID gerekli.");

// Fetch Fire Detection Report Data
$stmt = $pdo->prepare("
    SELECT r.*, 
           i.firma_adi, i.adresi, i.sgk_sicil_no, i.il_kodu, i.kurum_kodu,
           d1.device_name as d1_name, d1.serial_no as d1_serial, d1.cal_date as d1_cal, d1.validity_date as d1_val, d1.cal_no as d1_cal_no,
           d2.device_name as d2_name, d2.serial_no as d2_serial, d2.cal_date as d2_cal, d2.validity_date as d2_val, d2.cal_no as d2_cal_no,
           ap.adi_soyadi, ap.meslegi, ap.kayit_no
    FROM fire_detection_reports r
    JOIN institutions i ON r.kurum_id = i.id
    LEFT JOIN measurement_devices d1 ON r.device1_id = d1.id
    LEFT JOIN measurement_devices d2 ON r.device2_id = d2.id
    LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data)
    die("Rapor bulunamadı.");

// Fetch Section 5.2 Loops
$loop_stmt = $pdo->prepare("SELECT * FROM fire_detection_section5_2 WHERE report_id = ? ORDER BY loop_no, id");
$loop_stmt->execute([$id]);
$loops = [];
foreach ($loop_stmt->fetchAll() as $row) {
    $loops[$row['loop_no']][] = $row;
}

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
                <h1>YANGIN ALGILAMA VE UYARI SİSTEMLERİ<br>PERİYODİK KONTROL RAPORU</h1>
            </td>
            <td style="width: 30%; border:none;">
                <table class="doc-info" style="border:1px solid black; width:100%;">
                    <tr>
                        <td style="border:1px solid black; width:50%;">Doküman Kodu</td>
                        <td style="border:1px solid black;">YAZPKR01</td>
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
    <title>Yangın Algılama Raporu: <?php echo htmlspecialchars($data['report_no']); ?></title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        .header-bg {
            background-color: #f9e4e8;
            /* Light Red/Rose */
            font-weight: bold;
        }
        .section-title {
            background-color: #f9e4e8;
            /* Light Red/Rose */
            font-weight: bold;
            text-align: left;
            padding: 4px;
            border: 1px solid black;
            margin-top: 8px;
            font-size: 11px;
            text-transform: uppercase;
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
                <td><?php renderHeader(); ?></td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="page">
                        <div class="section-title">1. FİRMA BİLGİLERİ</div>
                        <table>
                            <tr>
                                <td style="width: 20%;">Firma Adı</td>
                                <td style="width: 40%;"><?php 
                                    $full_firma_adi = $data['firma_adi'] ?? '';
                                    if (!empty($data['firma_adi_eki'])) {
                                        $full_firma_adi .= '-' . $data['firma_adi_eki'];
                                    }
                                    echo htmlspecialchars($full_firma_adi); 
                                ?></td>
                                <td style="width: 20%;">Rapor Numarası</td>
                                <td style="width: 20%;"><?php echo htmlspecialchars($data['report_no'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td rowspan="4">Periyodik Kontrol Adresi</td>
                                <td rowspan="4"><?php echo nl2br(htmlspecialchars($data['adresi'] ?? '')); ?></td>
                                <td>Rapor Tarihi</td>
                                <td><?php echo date('d.m.Y', strtotime($data['report_date'])); ?></td>
                            </tr>
                            <tr>
                                <td>İSG-KATİP Sözleşme ID</td>
                                <td><?php echo htmlspecialchars($data['isg_katip_id'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td>Periyodik Kontrol Başlangıç Tarihi ve Saati</td>
                                <td><?php echo $data['start_date'] ? date('d.m.Y H:i', strtotime($data['start_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Periyodik Kontrol Bitiş Tarihi ve Saati</td>
                                <td><?php echo $data['end_date'] ? date('d.m.Y H:i', strtotime($data['end_date'])) : ''; ?>
                                </td>
                            </tr>

                            <tr>
                                <td>SGK Sicil Numarası</td>
                                <td><?php echo htmlspecialchars($data['sgk_sicil_no'] ?? ''); ?></td>
                                <td>Bir Sonraki Periyodik Kontrol Tarihi</td>
                                <td>
                                    <?php echo $data['next_control_date'] ? date('d.m.Y', strtotime($data['next_control_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Periyodik Kontrol Metodu ve Kapsamı</td>
                                <td colspan="3">
                                    <ul style="margin: 0; padding-left: 15px;">
                                        <li>TSE CEN/TS 54-14: Yangın Algılama ve Yangın Alarm Sistemleri - Bölüm 14:
                                            Planlama, Tasarım, Kurulum, Devreye Alma, Kullanım ve Bakım İçin Rehber</li>
                                        <li>İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği</li>
                                        <li>Binaların Yangından Korunması Hakkında Yönetmelik</li>
                                        <li>Elektrik İç Tesisleri Yönetmeliği</li>
                                    </ul>
                                </td>
                            </tr>
                        </table>

                        <div class="section-title">2. TESİS BİLGİLERİ</div>
                        <div class="header-bg" style="border: 1px solid black; border-bottom: none; padding: 2px;">2.1.
                            SİSTEM DETAY BİLGİLERİ</div>
                        <table style="margin-top: 0;">
                            <tr>
                                <td class="header-bg" style="width: 20%;">Yangın algılama sistemi</td>
                                <td>
                                    <span class="radio-item"><?php echo chk($data['algilama_sistemi'], 'Otomatik'); ?>
                                        Otomatik</span>
                                    <span class="radio-item"><?php echo chk($data['algilama_sistemi'], 'Manuel'); ?>
                                        Manuel</span>
                                </td>
                                <td class="header-bg" style="width: 20%;">Yangın uyarı sistemi</td>
                                <td>
                                    <?php foreach (['Işıklı', 'Sesli', 'Işık+Ses', 'Anons', 'Diğer'] as $opt): ?>
                                        <span class="radio-item"><?php echo chk($data['uyari_sistemi'], $opt); ?>
                                            <?php echo $opt; ?></span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Sistem çalışma tipi</td>
                                <td>
                                    <span class="radio-item"><?php echo chk($data['sistem_calisma_tipi'], 'Adresli'); ?>
                                        Adresli</span>
                                    <span
                                        class="radio-item"><?php echo chk($data['sistem_calisma_tipi'], 'Konvansiyonel'); ?>
                                        Konvansiyonel</span>
                                </td>
                                <td class="header-bg">Proje onay kurumu</td>
                                <td><?php echo htmlspecialchars($data['proje_onay_kurumu'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kontrol nedeni</td>
                                <td>
                                    <span
                                        class="radio-item"><?php echo chk($data['control_reason'], 'Periyodik Kontrol'); ?>
                                        Periyodik Kontrol</span>
                                    <span class="radio-item"><?php echo chk($data['control_reason'], 'İlk Kontrol'); ?>
                                        İlk Kontrol</span>
                                </td>
                                <td class="header-bg">Proje onay tarih ve sayısı</td>
                                <td><?php echo htmlspecialchars($data['proje_onay_bilgileri'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kontrol paneli marka/model</td>
                                <td><?php echo htmlspecialchars($data['panel_marka_model'] ?? ''); ?></td>
                                <td class="header-bg">İlk kontrol/devreye alma tarihi</td>
                                <td><?php echo $data['ilk_kontrol_tarihi'] ? date('d.m.Y', strtotime($data['ilk_kontrol_tarihi'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kontrol paneli seri no./imal yılı</td>
                                <td><?php echo htmlspecialchars($data['panel_seri_no'] ?? ''); ?></td>
                                <td class="header-bg">Son kontrol tarihi</td>
                                <td><?php echo $data['prev_control_date'] ? date('d.m.Y', strtotime($data['prev_control_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kontrol paneli çalışma gerilimi</td>
                                <td><?php echo htmlspecialchars($data['panel_calisma_gerilimi'] ?? ''); ?></td>
                                <td class="header-bg">Algılama ekipmanları</td>
                                <td>
                                    <span
                                        class="radio-item"><?php echo chk($data['algilama_ekipmanlari'], 'Duman (optik) dedektörü'); ?>
                                        Duman (optik)</span>
                                    <span
                                        class="radio-item"><?php echo chk($data['algilama_ekipmanlari'], 'Isı dedektörü'); ?>
                                        Isı dedektörü</span>
                                    <span
                                        class="radio-item"><?php echo chk($data['algilama_ekipmanlari'], 'İhbar butonu'); ?>
                                        İhbar butonu</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Kontrol paneli yeri</td>
                                <td><?php echo htmlspecialchars($data['panel_yeri'] ?? ''); ?></td>
                                <td class="header-bg">Uyarı ekipmanları</td>
                                <td>
                                    <span class="radio-item"><?php echo chk($data['uyari_ekipmanlari'], 'Siren'); ?>
                                        Siren</span>
                                    <span class="radio-item"><?php echo chk($data['uyari_ekipmanlari'], 'Flaşör'); ?>
                                        Flaşör</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Söndürme ekipmanları</td>
                                <td colspan="3">
                                    <?php foreach (['Otomatik söndürme', 'KKT Özellikli yangın tüpleri', 'CO2 Özellikli yangın tüpleri', 'Hidrantlar-Yangın dolapları'] as $opt): ?>
                                        <span class="radio-item"><?php echo chk($data['sondurme_ekipmanlari'], $opt); ?>
                                            <?php echo $opt; ?></span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        </table>

                        <div class="header-bg"
                            style="border: 1px solid black; border-bottom: none; border-top: none; padding: 2px;">2.2.
                            BİNA İLE İLGİLİ TESPİT EDİLEN BİLGİLER</div>
                        <table>
                            <tr>
                                <td class="header-bg" style="width: 16.66%;">Tesisatta kapsamlı değişiklik var mı?</td>
                                <td style="width: 16.66%;">
                                    <span class="radio-item"><?php echo chk($data['installation_change'], 'Var'); ?>
                                        Var</span>
                                    <span class="radio-item"><?php echo chk($data['installation_change'], 'Yok'); ?>
                                        Yok</span>
                                </td>
                                <td class="header-bg" style="width: 16.66%;">Periyodik kontrol etiketi var mı?</td>
                                <td colspan="3">
                                    <span class="radio-item"><?php echo chk($data['prev_label_exists'], 'Var'); ?>
                                        Var</span>
                                    <span class="radio-item"><?php echo chk($data['prev_label_exists'], 'Yok'); ?>
                                        Yok</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Bina kullanma sınıfı</td>
                                <td style="font-size: 8px;" colspan="3">
                                    <div style="display:flex; flex-wrap:wrap;">
                                        <?php foreach (['Konut', 'Toplanma amaçlı bina', 'Depolama amaçlı tesis', 'Yüksek tehlikeli bina', 'Karışık kullanım amaçlı bina', 'Endüstriyel yapı', 'Konaklama amaçlı bina', 'Kurumsal bina', 'Büro binası', 'Ticari'] as $opt): ?>
                                            <div style="width:50%;"><?php echo chk($data['bina_kullanma_sinifi'], $opt); ?>
                                                <?php echo $opt; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="header-bg">Bina tehlike sınıfı</td>
                                <td>
                                    <span
                                        class="radio-item"><?php echo chk($data['bina_tehlike_sinifi'], 'Düşük tehlike'); ?>
                                        Düşük</span><br>
                                    <span
                                        class="radio-item"><?php echo chk($data['bina_tehlike_sinifi'], 'Orta tehlike'); ?>
                                        Orta</span><br>
                                    <span
                                        class="radio-item"><?php echo chk($data['bina_tehlike_sinifi'], 'Yüksek tehlike'); ?>
                                        Yüksek</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Tehlike kategorisi</td>
                                <td colspan="5">
                                    <?php foreach (['1', '2', '3', '4'] as $opt): ?>
                                        <span class="radio-item"><?php echo chk($data['tehlike_kategorisi'], $opt); ?>
                                            <?php echo $opt; ?></span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="header-bg">Bina toplam kullanım alanı (m²)</td>
                                <td><?php echo htmlspecialchars($data['toplam_alan'] ?? ''); ?></td>
                                <td class="header-bg">Kat sayısı</td>
                                <td><?php echo htmlspecialchars($data['kat_sayisi'] ?? ''); ?></td>
                                <td class="header-bg">Bina yüksekliği/Yapı yüksekliği (m)</td>
                                <td><?php echo htmlspecialchars($data['bina_yuksekligi'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg">Yapı kullanma izin tarihi</td>
                                <td><?php echo $data['yapi_kullanma_izin_tarihi'] ? date('d.m.Y', strtotime($data['yapi_kullanma_izin_tarihi'])) : ''; ?>
                                </td>
                                <td class="header-bg">Bölüm sayısı</td>
                                <td><?php echo htmlspecialchars($data['bolum_sayisi'] ?? ''); ?></td>
                                <td class="header-bg">Varsa diğer tespitler</td>
                                <td><?php echo nl2br(htmlspecialchars($data['diger_tespitler'] ?? '')); ?></td>
                            </tr>
                        </table>

                        <div class="section-title">3. TEST DEĞERLERİ</div>
                        <div
                            style="border: 1px solid black; padding: 10px; min-height: 60px; text-align: center; color: #666;">
                            TEST DEĞERLERİ TABLOSU (Gelecek güncellemede aktif edilecektir)
                        </div>

                        <div class="section-title">4. ÖLÇÜM ALETLERİ BİLGİLERİ</div>
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
                                <td><?php echo htmlspecialchars($data['d1_cal_no'] ?? ''); ?></td>
                                <td class="header-bg">Kalibrasyon numarası</td>
                                <td><?php echo htmlspecialchars($data['d2_cal_no'] ?? '-'); ?></td>
                            </tr>
                        </table>
                    </div><!-- /.page p1 -->

                    <div class="page">
                        <?php
                        $i_results = json_decode($data['inspection_results'] ?? '{}', true);
                        $q_groups = [
                            'ÖN KONTROLLER' => [
                                ['personel_varmi', 'Yetkili ve eğitimli personel var mı?', 'anons_sistemi', 'Acil durum anons sistemi mevcudiyeti'],
                                ['sorumlular_belirlenmismi', 'Yangın güvenliği sorumluları belirlenmiş mi?', 'bakim_kayitlari', 'Bakım/servis kayıtları tutuluyor mu?'],
                                ['panel_durumu', 'Yangın alarm panelinin durumu (ekran-tuşlar-LED\'ler)', 'sistem_kutugu', 'Sistem kütüğü belgesi var mı?']
                            ],
                            'YANGIN ALGILAMA VE YANGIN UYARI SİSTEMİ VE TESİSATI' => [
                                ['panel_yerlesim', 'Kontrol paneli ve varsa tekrarlayıcı panellerin yerleşim durumu', 'kullanma_talimati', 'Kullanma talimatı var mı?'],
                                ['panel_izlenebilirlik', 'Kontrol paneli sürekli izlenebilir durumda mı?', 'aku_durumu', 'Akü kapasitesi, gerilimi ve fiziki durumu'],
                                ['adresleme_harita', 'Dedektör ve/veya buton adreslemesi veya yerleşim haritası var mı?', 'ortam_uyumu', 'Dedektörlerin çalışma ortamına uyumu ve yeterli olması'],
                                ['paralel_ihbar', 'Asma tavan, yükseltilmiş döşeme vb. içinde kalan dedektörlerin uyarılarının görülebilmesi için paralel ihbar lambaları var mı?', 'uyari_yerlesim', 'Sesli-siren/ışıklı-flaşör uyarılarının yerleşim durumu ve yeterli olması'],
                                ['koruma_devre', 'Çevrimlerde kısa devre ve açık devre koruması', 'kablo_uygunluk', 'Yangın alarm ve uyarı kablolarının uygunluğu'],
                                ['devre_ayrilmasi', 'Güvenlik devre ayrılması (Bant-I, Bant-II\'den ayırma/yalıtım)', '', '']
                            ],
                            'ACİL DURUM AYDINLATMA VE ACİL DURUM YÖNLENDİRME SİSTEMİ' => [
                                ['armatur_uygunluk', 'Acil durum aydınlatma armatürleri uygunluğu', 'panel_onu_lux', 'Acil durum aydınlatma sistemi varlığı, yeterliliği-panel önü (lux değeri TS EN 12464\'e göre)'],
                                ['aydinlatma_varlik', 'Acil durum aydınlatma sistemi varlığı, yeterliliği-diğer gerekli alanlar', 'cikis_hol_yonlendirme', 'Acil çıkış hollerinde acil durum yönlendirme işaretleri varlığı, yeterliliği'],
                                ['yonlendirme_isaretleri', 'Kaçış yollarında acil durum yönlendirme işaretleri varlığı, yeterliliği', 'aydinlatma_sure', 'Acil durum aydınlatma ünitelerinin aydınlatma sürelerinin uygunluğu'],
                                ['aydinlatma_seviye', 'Acil durum aydınlatma ünitelerinin aydınlatma seviyelerinin uygunluğu', 'otomatik_devreye_girme', 'Acil durum aydınlatması ve yönlendirmesi elektrik kesildiğinde otomatik devreye girmesi']
                            ],
                            'YANGIN ANINDA DİĞER MEKANİK, ELEKTRİK VE ELEKTRONİK SİSTEMLERLE ENTEGRASYON' => [
                                ['damper_izlenebilirlik', 'Duman damperleri açık/kapalı konum bilgilerinin doğrudan çevrimlere bağlı kontak izleme cihazlar ile izlenebilirliği', 'iklimlendirme_sinyal', 'İklimlendirme/havalandırma sistemi ve duman egzoz sistemi sinyal kontrolü'],
                                ['sondurme_entegrasyon', 'Yangın alarm sisteminin diğer otomatik söndürme sistemleri ile entegre olma durumu', 'akis_anahtari_izlenebilirlik', 'Yangın söndürme sistemi akış anahtarları, hat kesme vanaları, yangın pompaları çalışma fonksiyonları konum bilgisi izlenebilirliği'],
                                ['otomasyon_baglanti', 'Yangın algılama ve uyarı sisteminin bina otomasyon sistemi ile bağlantı ve haberleşme kontrolü', 'basinclandirma_kontrol', 'Yangın anında asansör kuyuları ve yangın merdiveni kovaları basınçlandırma sistemi kontrolleri'],
                                ['asansor_davranis', 'Asansörlerin yangın anında davranışları kontrolü', 'kapi_tutucu_kontrol', 'Yangın bölme kapıları elektromanyetik tutucuları kontrolü'],
                                ['kesici_yedek_enerji', 'Yangın anında elektrik tesisatında kesicilerin çalışıp çalışmadığı...', 'gaz_kesme_kontrol', 'Yangın anında patlayıcı gaz dağıtım sistemlerinin kontrolü']
                            ]
                        ];
                        ?>

                        <div class="section-title">5. TESPİT VE DEĞERLENDİRMELER</div>
                        <div style="font-weight: bold; padding: 5px; border: 1px solid black; border-bottom: none;">
                            5.1. GÖZLE MUAYENELER VE BELGE KONTROLLERİ
                        </div>
                        <table class="table-assessment">
                            <tr>
                                <th style="width: 40%;">Kontrol Kriteri</th>
                                <th style="width: 10%;">Değerlendirme</th>
                                <th style="width: 40%;">Kontrol Kriteri</th>
                                <th style="width: 10%;">Değerlendirme</th>
                            </tr>
                            <?php foreach ($q_groups as $g_title => $rows): ?>
                                <tr>
                                    <td colspan="4" class="header-bg" style="text-align: center; font-weight: bold;">
                                        <?php echo $g_title; ?>
                                    </td>
                                </tr>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?php echo $row[1]; ?></td>
                                        <td style="text-align: center; font-weight: bold;">
                                            <?php echo $i_results[$row[0]] ?? ''; ?>
                                        </td>
                                        <td><?php echo $row[3]; ?></td>
                                        <td style="text-align: center; font-weight: bold;">
                                            <?php echo $i_results[$row[2]] ?? ''; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </table>

                        <div class="header-bg"
                            style="font-weight: bold; padding: 5px; border: 1px solid black; border-bottom: none; margin-top: 10px;">
                            5.2. YANGIN ALGILAMA VE UYARI CİHAZLARI KONTROLÜ VE TESTLER (Örnekleme yapılmadan tüm
                            ekipmanlar)
                        </div>
                        <table class="table-assessment" style="margin-top: 0;">
                            <tr class="header-bg center">
                                <td rowspan="2" style="width: 8%;">Tanım/Kod</td>
                                <td rowspan="2" style="width: 15%;">Bölüm Adı/Tanımı</td>
                                <td rowspan="2" style="width: 20%;">Ekipman adı/Adedi</td>
                                <td colspan="7">Değerlendirme</td>
                            </tr>
                            <tr class="header-bg center" style="font-size: 7px;">
                                <td style="width: 8%;">Projede gösterilen yerde mi?</td>
                                <td style="width: 8%;">Erişim durumu</td>
                                <td style="width: 8%;">Montaj durumu</td>
                                <td style="width: 8%;">Test</td>
                                <td style="width: 8%;">Sesli uyarı yeterli mi?</td>
                                <td style="width: 8%;">Işıklı uyarı yeterli mi?</td>
                                <td style="width: 8%;">Adresleme doğru mu?</td>
                            </tr>
                            <?php if (empty($loops)): ?>
                                <tr>
                                    <td colspan="10" class="center">Veri girilmemiş</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($loops as $l_no => $items): ?>
                                    <?php foreach ($items as $index => $item): ?>
                                        <tr>
                                            <?php if ($index === 0): ?>
                                                <td rowspan="<?php echo count($items); ?>" class="center bold">
                                                    <?php echo htmlspecialchars($l_no); ?>
                                                </td>
                                            <?php endif; ?>
                                            <td><?php echo htmlspecialchars($item['bolum_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($item['ekipman_adi']); ?></td>
                                            <td class="center"><?php echo htmlspecialchars($item['projede_mi']); ?></td>
                                            <td class="center"><?php echo htmlspecialchars($item['erisim_durumu']); ?></td>
                                            <td class="center"><?php echo htmlspecialchars($item['montaj_durumu']); ?></td>
                                            <td class="center"><?php echo htmlspecialchars($item['test']); ?></td>
                                            <td class="center"><?php echo htmlspecialchars($item['sesli_uyari']); ?></td>
                                            <td class="center"><?php echo htmlspecialchars($item['isikli_uyari']); ?></td>
                                            <td class="center"><?php echo htmlspecialchars($item['adresleme']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="height: 5px; background: #eee;">
                                        <td colspan="10"></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </table>

                        <div class="section-title">6. KUSUR AÇIKLAMALARI</div>
                        <div style="border: 1px solid black; padding: 5px; min-height: 80px;">
                            <?php echo nl2br(htmlspecialchars($data['defects'] ?? '')); ?>
                        </div>
                        <div class="small-text" style="margin-top: 5px;">
                            Kusur derecesi; (*) hafif kusurlu ve (**) ağır kusurlu anlamında kullanılmaktadır.<br>
                            Değerlendirme “UYGUN (U)”, “UYGUN DEĞİL (UD)” ve “UYGULANMAZ (UG)” olarak yapılır.
                        </div>

                        <div class="section-title">FOTOĞRAFLAR</div>
                        <div
                            style="border: 1px solid black; padding: 5px; min-height: 120px; text-align: center; color: #ccc; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                            SİSTEM FOTOĞRAFLARI
                        </div>

                        <div class="section-title">7. NOTLAR</div>
                        <div style="border: 1px solid black; padding: 5px; min-height: 60px;">
                            <?php echo nl2br(htmlspecialchars($data['notes'] ?? '')); ?>
                        </div>

                        <div class="section-title">8. SONUÇ VE KANAAT</div>
                        <div style="border: 1px solid black; padding: 10px; line-height: 1.4;">
                            <p>Periyodik kontrol tarihi itibariyle yukarıda teknik özellikleri belirtilen Yangın
                                Algılama ve Uyarı Sisteminin periyodik muayenesi sonrasında mevcut şartlar altında
                                kullanımı 1 yıl süreyle; <br>
                                <strong><?php echo ($data['result'] == 'UYGUNDUR') ? 'UYGUNDUR' : 'UYGUN DEĞİLDİR'; ?></strong>
                            </p>

                            <p>Tespit edilen hafif kusurların bir sonraki periyodik kontrol tarihine kadar
                                giderilmesi
                                gereklidir.<br>
                                <span class="small-text">(Bu not, sadece hafif kusur tespit edilmesi durumunda
                                    yazılacaktır.)</span>
                            </p>

                            <p><strong>Ağır kusurlar tanımı:</strong><br>
                                a) Dedektörler, Yangın uyarı butonları ve sirenlerin test sonuçları yetersiz
                                ise,<br>
                                b) Yangın paneli gelen uyarıları algılamıyorsa,<br>
                                c) Kaçış yolları ve çıkış hollerinde acil aydınlatma düzenleri ve aydınlık seviyesi
                                yetersiz ise,<br>
                                d) Akü gerilimi düşükse,<br>
                                Ağır kusur olarak değerlendirilmelidir.
                            </p>

                            <p><strong>AÇIKLAMALAR:</strong><br>
                                1) Kontrol talep eden firmadan, kontrole gitmeden önce “Duman Dedektörü Test
                                Aparatı”
                                sağlaması istenir. Böyle bir aparat işyerinde mevcutsa duman dedektörlerinin
                                testleri
                                yapılır. Aksi takdirde, tehlikeli olacağından kâğıt veya bez yakarak test yapılmaz.
                                Bu
                                nedenle veya herhangi bir başka nedenle test yapılamamışsa notlar bölümünde
                                belirtilir.<br>
                                2) Isı dedektörlerinin kontrolü ”Isı Dedektörü Test Aparatı” ile yapılır. İşyerinde
                                böyle bir aparat yok ise bu testler fön cihazları ile yapılabilir.<br>
                                3) Yangın uyarı butonlarının testleri kendi üzerinden yapılır.<br>
                                4) Siren testleri “Binaların Yangından Korunması Hakkında Yönetmelik” Md.81-(5)
                                hükümleri gözetilerek yapılmalıdır.<br>
                                5) Periyodik kontrol, yangın algılama ve uyarı sistemi projesinin doğruluğunu
                                kapsamaz.
                                Onaylı projeyi temel alır. Proje bulunmaması durumunda yapılan kontrol durum
                                tespitine
                                yöneliktir. Yapılan tespitlerin uygunluğu proje ihtiyacını ortadan kaldırmaz.
                            </p>
                        </div>

                        <div class="section-title">9. PERİYODİK KONTROLLERİ YAPMAYA YETKİLİ KİŞİ BİLGİLERİ ve ONAY
                        </div>
                        <table>
                            <tr>
                                <td class="header-bg" style="width: 25%;">Adı Soyadı</td>
                                <td style="width: 50%;"><?php echo htmlspecialchars($data['adi_soyadi'] ?? ''); ?>
                                </td>
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
                        <div class="small-text" style="font-size: 8px;">Bu rapor ..........[yazı (rakam)] nüsha
                            olarak
                            hazırlanmıştır.</div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>