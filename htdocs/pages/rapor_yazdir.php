<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
if (!$id)
    die("Rapor ID gerekli.");

// Fetch All Data
// We need to fetch report data AND facility info.
// Note: facility_info is 1-to-1 with institution.
$stmt = $pdo->prepare("
    SELECT r.*, 
           i.firma_adi, i.adresi, i.sgk_sicil_no, i.il_kodu, i.kurum_kodu,
           fi.enerji_saglayan, fi.sebeke_gerilimi, fi.kullanim_amaci, fi.sozlesme_id, 
           fi.sebeke_tipi, fi.proje_var_mi, fi.sema_var_mi, fi.yapi_cinsi,
           d1.device_name as d1_name, d1.serial_no as d1_serial, d1.cal_date as d1_cal, d1.validity_date as d1_val, d1.cal_no as d1_cal_no,
           d2.device_name as d2_name, d2.serial_no as d2_serial, d2.cal_date as d2_cal, d2.validity_date as d2_val, d2.cal_no as d2_cal_no,
           ap.adi_soyadi, ap.meslegi, ap.kayit_no
    FROM grounding_reports r
    JOIN institutions i ON r.kurum_id = i.id
    LEFT JOIN facility_info fi ON i.id = fi.kurum_id
    LEFT JOIN measurement_devices d1 ON r.device1_id = d1.id
    LEFT JOIN measurement_devices d2 ON r.device2_id = d2.id
    LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data)
    die("Rapor bulunamadı.");

// Fetch 5.1
$stmt = $pdo->prepare("SELECT * FROM measurements_5_1 WHERE report_id = ? ORDER BY point_no ASC");
$stmt->execute([$id]);
$m51 = $stmt->fetchAll();

// Fetch 5.2
$stmt = $pdo->prepare("SELECT * FROM measurements_5_2 WHERE report_id = ? ORDER BY row_no ASC");
$stmt->execute([$id]);
$m52 = $stmt->fetchAll();

// Helper for checkboxes/radios (visual only)
function chk($val, $target)
{
    return ($val == $target) ? '<b>[X]</b>' : '[ ]';
}

// Map Notes
$notes_map = [
    '1' => 'Not-1: Uygun.',
    '2' => 'Not-2: Güvenlik şartı sağlanamadığından uygun değildir. (Ağır kusur)',
    '3' => 'Not-3: Topraklama bağlantısı yok kontrol edilmelidir. (Ağır kusur)',
    '4' => 'Not-4: Artık akım anahtarı kullanıldığı ve faal olduğu için uygundur.',
    '5' => 'Not-5: TT veya TN (TN-S veya TN-CS\'nin S bölümü) şebeke sistemlerinde... (Ağır kusur)',
    '6' => 'Not-6: 32 A üzerindeki devrelerde... (Ağır kusur)',
    '7' => 'Not-7: RCD gecikmeli tip değil...',
    '8' => 'Not-8: Nötr-toprak geriliminin yüksek olması... (Ağır kusur)',
    '9' => 'Not-9: TN-S ve TN-CS PE ve N birleştirilmesi... (Ağır kusur)',
    '10' => 'Not-10: Priz üzerinde Nötr-Toprak birleşikliği... (Ağır kusur)',
    '11' => 'Not-11: Pano gövde–kapak köprüsü olmadığından yetersizdir. (Ağır kusur)'
];
$selected_notes = explode(',', $data['result_notes_selection'] ?? '');

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
    <table style="border:none;">
        <tr style="border:none;">
            <td style="width: 20%; height: 60px; border:none; text-align:center; vertical-align:middle;">
                <!-- Logo Place Holder -->
                <div style="font-weight:bold; font-size:16px;"><?php echo $logoHtml; ?></div>
            </td>
            <td style="width: 50%; text-align: center; border:none;">
                <h1>ALÇAK GERİLİM TOPRAKLAMA<br>TESİSATI PERİYODİK KONTROL<br>RAPORU</h1>
            </td>
            <td style="width: 30%; border:none;">
                <table class="doc-info" style="border:1px solid black; width:100%;">
                    <tr>
                        <td style="border:1px solid black; width:50%;">Doküman Kodu</td>
                        <td style="border:1px solid black;">ZPKR01</td>
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
    <title>Rapor: <?php echo htmlspecialchars($data['report_no']); ?></title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        .header-bg {
            background-color: #daeef3;
            font-weight: bold;
        }
        .section-title {
            background-color: #daeef3;
            font-weight: bold;
            text-align: left;
            padding: 4px;
            border: 1px solid black;
            margin-top: 8px;
            font-size: 11px;
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
                    <!-- Page 1 -->
                    <div class="page">

                        <div class="section-title">1. FİRMA BİLGİLERİ</div>
                        <table style="margin-top: 0; width: 100%; border-collapse: collapse;">
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
                                <td>
                                    <?php echo $data['end_date'] ? date('d.m.Y H:i', strtotime($data['end_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>SGK Sicil Numarası</td>
                                <td><?php echo htmlspecialchars($data['sgk_sicil_no'] ?? ''); ?></td>
                                <td>Bir Sonraki Periyodik Kontrol Tarihi</td>
                                <td><?php echo $data['next_control_date'] ? date('d.m.Y', strtotime($data['next_control_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Periyodik Kontrol Metodu ve Kapsamı</td>
                                <td colspan="3" style="font-size: 9px;">
                                    <ul style="margin: 0; padding-left: 15px;">
                                        <li>TS HD 60364-4-41 Alçak Gerilim Elektrik Tesisleri – Bölüm 4: Güvenlik İçin
                                            Koruma – Bölüm
                                            41: Elektrik Çarpmasına Karşı Koruma</li>
                                        <li>TS HD 60364-6 Alçak Gerilim Elektrik Tesisatları – Bölüm 6: Doğrulama</li>
                                        <li>İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği</li>
                                        <li>Elektrik Tesislerinde Topraklamalar Yönetmeliği</li>
                                        <li>Elektrik İç Tesisleri Yönetmeliği</li>
                                    </ul>
                                </td>
                            </tr>
                        </table>

                        <div class="section-title">2. EKİPMAN BİLGİLERİ</div>
                        <div class="header-bg" style="border: 1px solid black; border-bottom: none; padding: 2px;">2.1.
                            ETİKET VE DETAY
                            BİLGİLERİ</div>
                        <table style="margin-top: 0;">
                            <tr>
                                <td style="width: 18%;">Enerji sağlayan kuruluş</td>
                                <td style="width: 27%;"><?php echo htmlspecialchars($data['enerji_saglayan'] ?? ''); ?>
                                </td>
                                <td style="width: 10%;">Şebeke tipi</td>
                                <td colspan="3">
                                    <?php $st = $data['sebeke_tipi'] ?? ''; ?>
                                    <div class="checkbox-group">
                                        <span class="radio-item"><?php echo chk($st, 'TT'); ?> TT</span>
                                        <span class="radio-item"><?php echo chk($st, 'IT'); ?> IT</span>
                                        <span class="radio-item"><?php echo chk($st, 'TN'); ?> TN</span>
                                    </div>
                                    <div class="checkbox-group" style="margin-top:2px; margin-left: 20px;">
                                        <span class="radio-item"><?php echo chk($st, 'TN-CS'); ?> TN-CS</span>
                                        <span class="radio-item"><?php echo chk($st, 'TN-C'); ?> TN-C</span>
                                        <span class="radio-item"><?php echo chk($st, 'TN-S'); ?> TN-S</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Şebeke gerilimi</td>
                                <td><?php echo htmlspecialchars($data['sebeke_gerilimi'] ?? ''); ?></td>
                                <td>Tesise ait proje var mı?</td>
                                <td>
                                    <?php $pv = $data['proje_var_mi'] ?? 0; ?>
                                    <span class="radio-item"><?php echo chk($pv, 1); ?> Var</span>
                                    <span class="radio-item"><?php echo chk($pv, 0); ?> Yok</span>
                                </td>
                                <td>Tek hat şeması var mı?</td>
                                <td>
                                    <?php $sv = $data['sema_var_mi'] ?? 0; ?>
                                    <span class="radio-item"><?php echo chk($sv, 1); ?> Var</span>
                                    <span class="radio-item"><?php echo chk($sv, 0); ?> Yok</span>
                                </td>
                            </tr>
                            <tr>
                                <td rowspan="2">Kontrol nedeni</td>
                                <td rowspan="2">
                                    <?php $cr = $data['control_reason'] ?? ''; ?>
                                    <span class="radio-item"><?php echo chk($cr, 'Periyodik Kontrol'); ?> Periyodik
                                        Kontrol</span><br>
                                    <span class="radio-item"><?php echo chk($cr, 'İlk Kontrol'); ?> İlk Kontrol</span>
                                </td>
                                <td>Proje bilgileri</td>
                                <td colspan="3"><?php echo htmlspecialchars($data['project_info'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td>Topraklayıcı tipi</td>
                                <td colspan="3">
                                    <?php $gt = $data['grounding_type'] ?? ''; ?>
                                    <div class="checkbox-group">
                                        <span class="radio-item"><?php echo chk($gt, 'Ring'); ?> Ring</span>
                                        <span class="radio-item"><?php echo chk($gt, 'Yüzeysel'); ?> Yüzeysel</span>
                                        <span class="radio-item"><?php echo chk($gt, 'Temel'); ?> Temel</span>
                                    </div>
                                    <div class="checkbox-group" style="margin-top:2px;">
                                        <span class="radio-item"><?php echo chk($gt, 'Derin'); ?> Derin</span>
                                        <span class="radio-item"><?php echo chk($gt, 'Belirlenemedi'); ?>
                                            Belirlenemedi</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Yapı cinsi</td>
                                <td>
                                    <?php $yc = $data['yapi_cinsi'] ?? ''; ?>
                                    <span class="radio-item"><?php echo chk($yc, 'Ev'); ?> Ev</span><br>
                                    <span class="radio-item"><?php echo chk($yc, 'Ticari'); ?> Ticari</span><br>
                                    <span class="radio-item"><?php echo chk($yc, 'Endüstri'); ?> Endüstri</span><br>
                                    <span class="radio-item"><?php echo chk($yc, 'Diğer'); ?> Diğer</span>
                                </td>
                                <td>Ekipmanın kullanım amacı</td>
                                <td><?php echo htmlspecialchars($data['kullanim_amaci'] ?? ''); ?></td>
                                <td>Son kontrol tarihi</td>
                                <td><?php echo !empty($data['prev_control_date']) ? date('d.m.Y', strtotime($data['prev_control_date'])) : ''; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Dolaylı dokunmaya karşı koruma önlemi</td>
                                <td colspan="5" style="font-size: 9px;">
                                    <?php $pm = $data['protection_measure'] ?? ''; ?>
                                    <div class="checkbox-group">
                                        <span
                                            class="radio-item"><?php echo chk($pm, 'Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN, IT)'); ?>
                                            Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN,
                                            IT)</span><br>
                                        <span
                                            class="radio-item"><?php echo chk($pm, 'Koruyucu yalıtma (Sınıf II veya zemin yalıtımı)'); ?>
                                            Koruyucu yalıtma (Sınıf II veya zemin yalıtımı)</span>
                                    </div>
                                    <div class="checkbox-group" style="margin-top: 3px;">
                                        <span
                                            class="radio-item"><?php echo chk($pm, 'Koruyucu ayırma (İzolasyon trafosu)'); ?>
                                            Koruyucu
                                            ayırma (İzolasyon trafosu)</span><br>
                                        <span class="radio-item"><?php echo chk($pm, 'Küçük gerilim <50 V'); ?> Küçük
                                            gerilim &lt; 50
                                            V</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Hava durumu ve sıcaklığı</td>
                                <td colspan="2"><?php echo htmlspecialchars($data['weather'] ?? ''); ?></td>
                                <td>Zemin nem durumu</td>
                                <td colspan="2"><?php echo htmlspecialchars($data['soil_moisture'] ?? ''); ?></td>
                            </tr>
                        </table>

                        <div class="header-bg"
                            style="border: 1px solid black; border-bottom: none; border-top:none; padding: 2px;">2.2.
                            TESPİT EDİLEN BİLGİLER</div>
                        <table>
                            <tr>
                                <td>Değişiklik var mı?</td>
                                <td><?php echo chk($data['changes_exist'] ?? 0, 1); ?> Var
                                    <?php echo chk($data['changes_exist'] ?? 0, 0); ?> Yok
                                </td>
                                <td>Önceki etiket var mı?</td>
                                <td><?php echo chk($data['prev_label_exists'] ?? 0, 1); ?> Var
                                    <?php echo chk($data['prev_label_exists'] ?? 0, 0); ?> Yok
                                </td>
                                <td>Pano ID</td>
                                <td><?php echo htmlspecialchars($data['panel_id'] ?? ''); ?></td>
                            </tr>
                        </table>

                        <div class="section-title">3. ÖLÇÜM ALETLERİ BİLGİLERİ</div>
                        <table>
                            <tr>
                                <td class="header-bg" style="width: 20%;">Cihaz adı</td>
                                <td style="width: 30%;"><?php echo htmlspecialchars($data['d1_name'] ?? ''); ?></td>

                                <?php if (!empty($data['device2_id'])): ?>
                                    <td class="header-bg" style="width: 20%;">Cihaz adı</td>
                                    <td style="width: 30%;"><?php echo htmlspecialchars($data['d2_name'] ?? ''); ?></td>
                                <?php else: ?>
                                    <td colspan="2" style="background: #f9f9f9;"></td>
                                <?php endif; ?>
                            </tr>
                            <tr>
                                <td class="header-bg">Seri No</td>
                                <td><?php echo htmlspecialchars($data['d1_serial'] ?? ''); ?></td>
                                <?php if (!empty($data['device2_id'])): ?>
                                    <td class="header-bg">Seri No</td>
                                    <td><?php echo htmlspecialchars($data['d2_serial'] ?? ''); ?></td>
                                <?php else: ?>
                                    <td colspan="2"></td>
                                <?php endif; ?>
                            </tr>
                            <tr>
                                <td class="header-bg">Kal. Tarihi</td>
                                <td><?php echo $data['d1_cal'] ?? ''; ?></td>
                                <?php if (!empty($data['device2_id'])): ?>
                                    <td class="header-bg">Kal. Tarihi</td>
                                    <td><?php echo $data['d2_cal'] ?? ''; ?></td>
                                <?php else: ?>
                                    <td colspan="2"></td>
                                <?php endif; ?>
                            </tr>
                            <tr>
                                <td class="header-bg">Geçerlilik Tar.</td>
                                <td><?php echo $data['d1_val'] ?? ''; ?></td>
                                <?php if (!empty($data['device2_id'])): ?>
                                    <td class="header-bg">Geçerlilik Tar.</td>
                                    <td><?php echo $data['d2_val'] ?? ''; ?></td>
                                <?php else: ?>
                                    <td colspan="2"></td>
                                <?php endif; ?>
                            </tr>
                            <tr>
                                <td class="header-bg">Kal. No</td>
                                <td><?php echo htmlspecialchars($data['d1_cal_no'] ?? ''); ?></td>
                                <?php if (!empty($data['device2_id'])): ?>
                                    <td class="header-bg">Kal. No</td>
                                    <td><?php echo htmlspecialchars($data['d2_cal_no'] ?? ''); ?></td>
                                <?php else: ?>
                                    <td colspan="2"></td>
                                <?php endif; ?>
                            </tr>
                        </table>

                        <div class="section-title">4. TEST DEĞERLERİ</div>
                        <div style="border: 1px solid black; padding: 5px; font-size: 8px; line-height: 1.2;">
                            <b>Zx:</b> Ölçülen çevrim empedansı.<br>
                            <b>Zs:</b> Aşırı akım koruma cihazının açma akımına göre hesaplanan sınır çevrim
                            empedansı.<br>
                            <b>Rx:</b> Ölçülen topraklama direnci.<br>
                            <b>RA:</b> Aşırı akım koruma cihazının açma akımına göre hesaplanan sınır topraklama
                            direnci.<br>
                            <b>Ik:</b> Devredeki toprak çevrim empedansına göre hesaplanan ya da ölçülen faz-toprak hata
                            akımı.<br>
                            <b>Ia:</b> Aşırı akım koruma cihazının açma eğrisi tipine göre hesaplanan ani açma ya da
                            otomatik açma
                            akımı. RCD'ler için IΔn<br>
                            <b>IΔn:</b> RCD için beyan açma akımı<br>
                            <b>IΔ:</b> RCD için test açma akımı<br>
                            <b>TΔ:</b> RCD için test açma zamanı-max: 200 ms.<br>
                            <b>Zs<230V /Ia:</b> TN şebekeler için hesaplanan çevrim empedansı sınır değeri.<br>
                                    <b>RA<50V /Ia:</b> TT şebekeler için hesaplanan topraklama direnci sınır değeri.
                        </div>

                        <div class="section-title">5. KONTROL KRİTERLERİ VE TESTLER</div>
                        <table>
                            <tr>

                                <td class="bold center" colspan="2">ÖLÇÜM METODU</td>
                            </tr>
                            <tr>
                                <td style="background: #e0e0e0; width:40%;">Ölçüm ve doğrulama metodu</td>
                                <td>
                                    <?php $mm = $data['measurement_method'] ?? ''; ?>
                                    <?php echo chk($mm, 'Cevrim empedansi'); ?> Çevrim empedansı<br>
                                    <?php echo chk($mm, '3 Uclu topraklama'); ?> 3 Uçlu topraklama<br>
                                    <?php echo chk($mm, 'Klamp metodu'); ?> Klamp metodu
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="page-break"></div>

                    <!-- Page 2 -->
                    <div class="page">

                        <div class="section-title">5.1. SON TÜKETİM NOKTALARINDA DOLAYLI DOKUNMAYA KARŞI KORUMA
                            YETERLİLİĞİ KONTROLÜ
                        </div>
                        <table>
                            <thead class="header-bg center small-text">
                                <tr>
                                    <th rowspan="2" style="width:25px;">No</th>
                                    <th rowspan="2">Ölçüm noktası /<br>Etiketi veya kodu</th>
                                    <th colspan="3">Koruma Elemanının</th>
                                    <th rowspan="2">Toprak<br>kısa devre<br>akımı<br>Ik1 (A)</th>
                                    <th colspan="2">Ölçüm</th>
                                    <th rowspan="2">RCD tipi, dayanma akımı ve açma akımı In(A) / IΔn(mA)</th>
                                    <th colspan="2">RCD Testi</th>
                                    <th rowspan="2">Sonuç<br>(Uygunluk notu)</th>
                                </tr>
                                <tr>
                                    <th style="width:40px;">In(A)</th>
                                    <th style="width:45px;">Açma eğrisi tipi</th>
                                    <th style="width:45px;">Açma akımı Ia(A)</th>
                                    <th style="width:60px;">Ölçülen değer Zx/Rx(Ω)</th>
                                    <th style="width:60px;">Sınır değer Zs /RA(Ω)</th>
                                    <th style="width:45px;">Açma akımı IΔ(mA)</th>
                                    <th style="width:45px;">Açma zamanı TΔ(ms)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($m51 as $row): ?>
                                    <tr>
                                        <td><?php echo $row['point_no']; ?></td>
                                        <td><?php echo htmlspecialchars($row['point_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['prot_in']); ?></td>
                                        <td><?php echo htmlspecialchars($row['prot_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['prot_ia']); ?></td>
                                        <td><?php echo htmlspecialchars($row['prot_ik1']); ?></td>
                                        <td><?php echo htmlspecialchars($row['measured_zx_rx']); ?></td>
                                        <td><?php echo htmlspecialchars($row['limit_zs_ra']); ?></td>
                                        <td><?php echo htmlspecialchars($row['rcd_type_limits']); ?></td>
                                        <td><?php echo htmlspecialchars($row['rcd_test_ia']); ?></td>
                                        <td><?php echo htmlspecialchars($row['rcd_test_ta']); ?></td>
                                        <td><?php echo htmlspecialchars($row['result']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="section-title">5.2. ARTIK AKIM ANAHTARLARI (RCD) SELEKTİVİTE KONTROLÜ</div>
                        <table>
                            <thead class="header-bg center small-text">
                                <tr>
                                    <th rowspan="2" style="width:30px;">No</th>
                                    <th rowspan="2">Son tüketim noktasını besleyen panodan N önceki panonun adı</th>
                                    <th colspan="4">Kullanılan RCD Etiket Bilgileri</th>
                                    <th rowspan="2">Son tüketim noktasını besleyen pano adı</th>
                                    <th colspan="3">Kullanılan RCD</th>
                                    <th rowspan="2">Sonuç<br>(Uygunluk notu)</th>
                                </tr>
                                <tr>
                                    <th style="width:60px;">RCD Tipi</th>
                                    <th style="width:45px;">Dayanma akımı In(A)</th>
                                    <th style="width:45px;">Açma akımı IΔn(mA)</th>
                                    <th style="width:45px;">Açma zamanı gecikmesi(ms)</th>
                                    <th style="width:60px;">RCD Tipi</th>
                                    <th style="width:45px;">Açma akımı IΔn(mA)</th>
                                    <th style="width:45px;">Test açma zamanı TΔ(ms)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($m52 as $row): ?>
                                    <tr>
                                        <td>N=<?php echo $row['row_no']; ?></td>
                                        <td><?php echo htmlspecialchars($row['upstream_panel']); ?></td>
                                        <td><?php echo htmlspecialchars($row['upstream_rcd_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['upstream_rcd_in']); ?></td>
                                        <td><?php echo htmlspecialchars($row['upstream_rcd_idn']); ?></td>
                                        <td><?php echo htmlspecialchars($row['upstream_rcd_dt']); ?></td>
                                        <td><?php echo htmlspecialchars($row['downstream_panel']); ?></td>
                                        <td><?php echo htmlspecialchars($row['downstream_rcd_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['downstream_rcd_idn']); ?></td>
                                        <td><?php echo htmlspecialchars($row['downstream_rcd_t']); ?></td>
                                        <td><?php echo htmlspecialchars($row['result']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="section-title">6. KUSURLAR</div>
                        <div style="border: 1px solid black; min-height: 50px; padding: 5px;">
                            <?php echo nl2br(htmlspecialchars($data['defects'] ?? '')); ?>
                        </div>
                        <div class="small-text">Kusur derecesi "*" hafif, "**" ağır kusurludur.</div>

                        <div class="section-title">7. NOTLAR</div>
                        <div style="border: 1px solid black; min-height: 50px; padding: 5px;">
                            <?php echo nl2br(htmlspecialchars($data['notes'] ?? '')); ?>
                        </div>

                        <div class="section-title">8. SONUÇ VE KANAAT</div>
                        <div style="border: 1px solid black; padding: 5px;">
                            Periyodik kontrol tarihi itibariyle yukarıda teknik özellikleri belirtilen <b>AG Topraklama
                                Tesisatı</b>
                            muayenesi sonrasında mevcut şartlar altında <b>kullanımı 1 yıl süreyle;</b><br>
                            <div style="margin: 5px 0; font-size: 14px; font-weight: bold;">
                                <?php if (isset($data['result']) && $data['result'] == 'UYGUNDUR'): ?>
                                    KULLANIMI UYGUNDUR
                                <?php else: ?>
                                    KULLANIMI UYGUN DEĞİLDİR
                                <?php endif; ?>
                            </div>
                            Tespit edilen hafif kusurların bir sonraki periyodik kontrol tarihine kadar giderilmesi
                            gereklidir.<br>
                            (*)Bu not, sadece hafif kusur tespit edilmesi durumunda yazılacaktır.<br><br>

                            <b>Uygunluk notu ve ağır kusur açıklamaları:</b><br>
                            <span class="small-text">
                                <?php
                                if (!empty($selected_notes)) {
                                    foreach ($selected_notes as $note_idx) {
                                        if (isset($notes_map[$note_idx])) {
                                            // Bold "Ağır kusur"
                                            $txt = str_replace('(Ağır kusur)', '(<b>Ağır kusur</b>)', htmlspecialchars($notes_map[$note_idx]));
                                            echo "<i>$txt</i><br>";
                                        }
                                    }
                                }
                                ?>
                            </span>
                        </div>

                        <div class="section-title">9. PERİYODİK KONTROLLERİ YAPMAYA YETKİLİ KİŞİ BİLGİLERİ ve ONAY</div>
                        <table>
                            <tr>
                                <td style="width: 20%; background-color:#e0e0e0;">Adı Soyadı</td>
                                <td style="width: 50%;"><?php echo htmlspecialchars($data['adi_soyadi'] ?? ''); ?></td>
                                <td rowspan="3" style="width: 30%; vertical-align: bottom; text-align: center;">İmza
                                </td>
                            </tr>
                            <tr>
                                <td style="background-color:#e0e0e0;">Mesleği</td>
                                <td><?php echo htmlspecialchars($data['meslegi'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <td style="background-color:#e0e0e0;">Kayıt No</td>
                                <td><?php echo htmlspecialchars($data['kayit_no'] ?? ''); ?></td>
                            </tr>
                        </table>
                        <div class="small-text">Bu rapor.......... (yazı (rakam)) nüsha olarak hazırlanmıştır.</div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>