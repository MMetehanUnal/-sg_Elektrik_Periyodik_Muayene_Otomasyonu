<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

if (!isset($_SESSION['active_institution_id'])) {
    redirect('/pages/tesis_secimi.php');
}

$kurum_id = $_SESSION['active_institution_id'];
$facility_defaults = getFacilityDefaults($pdo, $kurum_id);

// Check if editing
$report_id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
$report = [];

if ($report_id) {
    $stmt = $pdo->prepare("SELECT * FROM fire_detection_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$report_id, $kurum_id]);
    $report = $stmt->fetch();
    if (!$report)
        redirect('yangin_algilama_kontrol.php'); // Invalid ID
}

// Fetch Institution Defaults
$stmt = $pdo->prepare("SELECT * FROM institutions WHERE id = ?");
$stmt->execute([$kurum_id]);
$kurum = $stmt->fetch();

// Fetch Facility Info Defaults
$stmt = $pdo->prepare("SELECT * FROM facility_info WHERE kurum_id = ?");
$stmt->execute([$kurum_id]);
$facility_info = $stmt->fetch();

// Fetch Devices
$stmt = $pdo->prepare("SELECT * FROM measurement_devices WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$all_devices = $stmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle photo deletion request
    if (isset($_POST['delete_photo_id'])) {
        $del_photo_id = (int)$_POST['delete_photo_id'];
        $stmt_del = $pdo->prepare("SELECT file_path FROM fire_detection_photos WHERE id = ? AND report_id = ?");
        $stmt_del->execute([$del_photo_id, $report_id]);
        $photo_to_del = $stmt_del->fetch();
        if ($photo_to_del) {
            $full_del_path = __DIR__ . '/../../' . $photo_to_del['file_path'];
            if (file_exists($full_del_path)) {
                unlink($full_del_path);
            }
            $pdo->prepare("DELETE FROM fire_detection_photos WHERE id = ?")->execute([$del_photo_id]);
        }
        redirect("yangin_algilama_kontrol.php?id=$report_id&status=success_delete");
    }
    // Basic fields
    $report_date = cleanInput($_POST['report_date']);
    $start_date = cleanInput($_POST['start_date']);
    $end_date = cleanInput($_POST['end_date']);
    $next_control_date = cleanInput($_POST['next_control_date']);
    $isg_katip_id = cleanInput($_POST['isg_katip_id'] ?? '');
    $firma_adi_eki = cleanInput($_POST['firma_adi_eki'] ?? '');

    // Section 2.1
    $algilama_sistemi = cleanInput($_POST['algilama_sistemi'] ?? '');
    $uyari_sistemi = cleanInput($_POST['uyari_sistemi'] ?? '');
    $sistem_calisma_tipi = cleanInput($_POST['sistem_calisma_tipi'] ?? '');
    $proje_onay_kurumu = cleanInput($_POST['proje_onay_kurumu'] ?? '');
    $control_reason = cleanInput($_POST['control_reason'] ?? '');
    $proje_onay_bilgileri = cleanInput($_POST['proje_onay_bilgileri'] ?? '');
    $panel_marka_model = cleanInput($_POST['panel_marka_model'] ?? '');
    $ilk_kontrol_tarihi = !empty($_POST['ilk_kontrol_tarihi']) ? cleanInput($_POST['ilk_kontrol_tarihi']) : null;
    $prev_control_date = !empty($_POST['prev_control_date']) ? cleanInput($_POST['prev_control_date']) : null;
    $panel_seri_no = cleanInput($_POST['panel_seri_no'] ?? '');
    $panel_calisma_gerilimi = cleanInput($_POST['panel_calisma_gerilimi'] ?? '');
    $algilama_ekipmanlari = isset($_POST['algilama_ekipmanlari']) ? implode(',', $_POST['algilama_ekipmanlari']) : '';
    $panel_yeri = cleanInput($_POST['panel_yeri'] ?? '');
    $uyari_ekipmanlari = isset($_POST['uyari_ekipmanlari']) ? implode(',', $_POST['uyari_ekipmanlari']) : '';
    $sondurme_ekipmanlari = isset($_POST['sondurme_ekipmanlari']) ? implode(',', $_POST['sondurme_ekipmanlari']) : '';
    $weather_condition = cleanInput($_POST['weather_condition'] ?? '');
    $ground_moisture = cleanInput($_POST['ground_moisture'] ?? '');

    // Section 2.2
    $installation_change = cleanInput($_POST['installation_change'] ?? '');
    $prev_label_exists = cleanInput($_POST['prev_label_exists'] ?? '');
    $bina_kullanma_sinifi = cleanInput($_POST['bina_kullanma_sinifi'] ?? '');
    $bina_tehlike_sinifi = cleanInput($_POST['bina_tehlike_sinifi'] ?? '');
    $tehlike_kategorisi = cleanInput($_POST['tehlike_kategorisi'] ?? '');
    $toplam_alan = cleanInput($_POST['toplam_alan'] ?? '');
    $kat_sayisi = cleanInput($_POST['kat_sayisi'] ?? '');
    $bina_yuksekligi = cleanInput($_POST['bina_yuksekligi'] ?? '');
    $yapi_kullanma_izin_tarihi = !empty($_POST['yapi_kullanma_izin_tarihi']) ? cleanInput($_POST['yapi_kullanma_izin_tarihi']) : null;
    $bolum_sayisi = cleanInput($_POST['bolum_sayisi'] ?? '');
    $diger_tespitler = cleanInput($_POST['diger_tespitler'] ?? '');

    // Section 4
    $device1_id = !empty($_POST['device1_id']) ? cleanInput($_POST['device1_id']) : null;
    $device2_id = !empty($_POST['device2_id']) ? cleanInput($_POST['device2_id']) : null;

    // Results
    $authorized_person_id = cleanInput($_POST['authorized_person_id'] ?? '');
    $defects = cleanInput($_POST['defects'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');
    $result = cleanInput($_POST['result'] ?? 'UYGUNDUR');

    try {
        if ($report_id) {
            $sql = "UPDATE fire_detection_reports SET 
                report_date=?, firma_adi_eki=?, start_date=?, end_date=?, next_control_date=?, isg_katip_id=?,
                algilama_sistemi=?, uyari_sistemi=?, sistem_calisma_tipi=?, proje_onay_kurumu=?, 
                control_reason=?, proje_onay_bilgileri=?, panel_marka_model=?, ilk_kontrol_tarihi=?, 
                prev_control_date=?, weather_condition=?, ground_moisture=?,
                panel_seri_no=?, panel_calisma_gerilimi=?, algilama_ekipmanlari=?, 
                panel_yeri=?, uyari_ekipmanlari=?, sondurme_ekipmanlari=?,
                installation_change=?, prev_label_exists=?, bina_kullanma_sinifi=?, 
                bina_tehlike_sinifi=?, tehlike_kategorisi=?, toplam_alan=?, kat_sayisi=?, 
                bina_yuksekligi=?, yapi_kullanma_izin_tarihi=?, bolum_sayisi=?, diger_tespitler=?,
                device1_id=?, device2_id=?, authorized_person_id=?, defects=?, notes=?, result=?
                WHERE id=? AND kurum_id=?";
            $params = [
                $report_date,
                $firma_adi_eki,
                $start_date,
                $end_date,
                $next_control_date,
                $isg_katip_id,
                $algilama_sistemi,
                $uyari_sistemi,
                $sistem_calisma_tipi,
                $proje_onay_kurumu,
                $control_reason,
                $proje_onay_bilgileri,
                $panel_marka_model,
                $ilk_kontrol_tarihi,
                $prev_control_date,
                $weather_condition,
                $ground_moisture,
                $panel_seri_no,
                $panel_calisma_gerilimi,
                $algilama_ekipmanlari,
                $panel_yeri,
                $uyari_ekipmanlari,
                $sondurme_ekipmanlari,
                $installation_change,
                $prev_label_exists,
                $bina_kullanma_sinifi,
                $bina_tehlike_sinifi,
                $tehlike_kategorisi,
                $toplam_alan,
                $kat_sayisi,
                $bina_yuksekligi,
                $yapi_kullanma_izin_tarihi,
                $bolum_sayisi,
                $diger_tespitler,
                $device1_id,
                $device2_id,
                $authorized_person_id,
                $defects,
                $notes,
                $result,
                $report_id,
                $kurum_id
            ];
        } else {
            // Generate report number
            $stmt = $pdo->prepare("SELECT il_kodu, kurum_kodu FROM institutions WHERE id = ?");
            $stmt->execute([$kurum_id]);
            $k_codes = $stmt->fetch();
            $report_no = $k_codes['il_kodu'] . '-' . $k_codes['kurum_kodu'] . '-ya-' . time();

            $sql = "INSERT INTO fire_detection_reports 
                (kurum_id, report_no, report_date, firma_adi_eki, start_date, end_date, next_control_date, isg_katip_id, 
                algilama_sistemi, uyari_sistemi, sistem_calisma_tipi, proje_onay_kurumu, 
                control_reason, proje_onay_bilgileri, panel_marka_model, ilk_kontrol_tarihi, 
                prev_control_date, weather_condition, ground_moisture,
                panel_seri_no, panel_calisma_gerilimi, algilama_ekipmanlari, 
                panel_yeri, uyari_ekipmanlari, sondurme_ekipmanlari,
                installation_change, prev_label_exists, bina_kullanma_sinifi, 
                bina_tehlike_sinifi, tehlike_kategorisi, toplam_alan, kat_sayisi, 
                bina_yuksekligi, yapi_kullanma_izin_tarihi, bolum_sayisi, diger_tespitler,
                device1_id, device2_id, authorized_person_id, defects, notes, result)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $kurum_id,
                $report_no,
                $report_date,
                $firma_adi_eki,
                $start_date,
                $end_date,
                $next_control_date,
                $isg_katip_id,
                $algilama_sistemi,
                $uyari_sistemi,
                $sistem_calisma_tipi,
                $proje_onay_kurumu,
                $control_reason,
                $proje_onay_bilgileri,
                $panel_marka_model,
                $ilk_kontrol_tarihi,
                $prev_control_date,
                $weather_condition,
                $ground_moisture,
                $panel_seri_no,
                $panel_calisma_gerilimi,
                $algilama_ekipmanlari,
                $panel_yeri,
                $uyari_ekipmanlari,
                $sondurme_ekipmanlari,
                $installation_change,
                $prev_label_exists,
                $bina_kullanma_sinifi,
                $bina_tehlike_sinifi,
                $tehlike_kategorisi,
                $toplam_alan,
                $kat_sayisi,
                $bina_yuksekligi,
                $yapi_kullanma_izin_tarihi,
                $bolum_sayisi,
                $diger_tespitler,
                $device1_id,
                $device2_id,
                $authorized_person_id,
                $defects,
                $notes,
                $result
            ];
        }

        $pdo->prepare($sql)->execute($params);
        if (!$report_id)
            $report_id = $pdo->lastInsertId();

        // Handle Photo Uploads
        if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $uploadDir = __DIR__ . '/../../uploads/yangin_algilama/' . $report_id . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $files = $_FILES['photos'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $newFileName = 'photo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $targetPath = $uploadDir . $newFileName;
                        $dbPath = 'uploads/yangin_algilama/' . $report_id . '/' . $newFileName;
                        
                        if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                            if (function_exists('compressImage')) {
                                compressImage($targetPath, $targetPath, 80);
                            }
                            
                            $stmt_p = $pdo->prepare("INSERT INTO fire_detection_photos (report_id, file_path) VALUES (?, ?)");
                            $stmt_p->execute([$report_id, $dbPath]);
                        }
                    }
                }
            }
        }

        redirect("yangin_algilama_kontrol.php?id=$report_id&status=success");
    } catch (PDOException $e) {
        $save_error = "Hata: " . $e->getMessage();
    }
}

// Fetch Authorized Persons
$stmt = $pdo->prepare("SELECT * FROM authorized_persons");
$stmt->execute();
$authorized_persons = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Yangın Algılama ve Uyarı Sistemleri Periyodik Kontrol Formu</h1>
    <a href="/pages/raporlar.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Vazgeç ve Raporlara Dön
    </a>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <span>Rapor başarıyla kaydedildi.</span>
        <a href="../yangin_algilama_yazdir.php?id=<?php echo $_GET['id']; ?>" target="_blank"
            class="btn btn-success btn-sm">
            <i class="fas fa-print"></i> Raporu Yazdır
        </a>
    </div>
<?php endif; ?>

<?php if (isset($save_error)): ?>
    <div class="alert alert-danger">
        <?php echo $save_error; ?>
    </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data">
    <div class="accordion" id="accordionForm">

        <!-- 1. Bölüm: Firma Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#c1">1. Firma Bilgileri</button></h2>
            <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Firma Adı Eki</label>
                            <input type="text" class="form-control" name="firma_adi_eki"
                                value="<?php echo htmlspecialchars($report['firma_adi_eki'] ?? ''); ?>"
                                placeholder="Örn: C Blok, Depo">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Rapor Tarihi</label>
                            <input type="date" class="form-control" name="report_date"
                                value="<?php echo $report['report_date'] ?? $kurum['report_date'] ?? date('Y-m-d'); ?>"
                                required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">İSG-KATİP Sözleşme ID</label>
                            <input type="text" class="form-control" name="isg_katip_id"
                                value="<?php echo $report['isg_katip_id'] ?? $facility_info['sozlesme_id'] ?? $kurum['isg_katip_id'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Başlangıç Tarihi ve Saati</label>
                            <?php
                            $s_val = $report['start_date'] ?? $kurum['start_date'] ?? null;
                            if ($s_val) {
                                $s_val = date('Y-m-d\TH:i', strtotime($s_val));
                            }
                            ?>
                            <input type="datetime-local" class="form-control" name="start_date"
                                value="<?php echo $s_val; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Bitiş Tarihi ve Saati</label>
                            <?php
                            $e_val = $report['end_date'] ?? $kurum['end_date'] ?? null;
                            if ($e_val) {
                                $e_val = date('Y-m-d\TH:i', strtotime($e_val));
                            }
                            ?>
                            <input type="datetime-local" class="form-control" name="end_date"
                                value="<?php echo $e_val; ?>">
                        </div>
                        <div class="col-md-3 mb-4">
                            <label class="form-label">Bir Sonraki Periyodik Kontrol Tarihi</label>
                            <input type="date" class="form-control" name="next_control_date"
                                value="<?php echo $report['next_control_date'] ?? $facility_info['next_control_date'] ?? $kurum['next_control_date'] ?? date('Y-m-d', strtotime('+1 year')); ?>">
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <strong>Periyodik Kontrol Metodu ve Kapsamı:</strong>
                        <ul>
                            <li>TSE CEN/TS 54-14: Yangın Algılama ve Yangın Alarm Sistemleri - Bölüm 14: Planlama,
                                Tasarım, Kurulum, Devreye Alma, Kullanım ve Bakım İçin Rehber</li>
                            <li>İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği</li>
                            <li>Binaların Yangından Korunması Hakkında Yönetmelik</li>
                            <li>Elektrik İç Tesisleri Yönetmeliği</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Bölüm: Tesis Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c2">2. Tesis Bilgileri</button></h2>
            <div id="c2" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <h5>2.1 Sistem Detay Bilgileri</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Yangın algılama sistemi</label><br>
                            <?php $val = $report['algilama_sistemi'] ?? ''; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="algilama_sistemi" value="Otomatik"
                                    <?php echo ($val == 'Otomatik') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Otomatik</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="algilama_sistemi" value="Manuel"
                                    <?php echo ($val == 'Manuel') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Manuel</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Yangın uyarı sistemi</label><br>
                            <?php $val = $report['uyari_sistemi'] ?? ''; ?>
                            <?php foreach (['Işıklı', 'Sesli', 'Işık+Ses', 'Anons', 'Diğer'] as $opt): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="uyari_sistemi"
                                        value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo $opt; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Sistem çalışma tipi</label><br>
                            <?php $val = $report['sistem_calisma_tipi'] ?? ''; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="sistem_calisma_tipi" value="Adresli"
                                    <?php echo ($val == 'Adresli') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Adresli</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="sistem_calisma_tipi"
                                    value="Konvansiyonel" <?php echo ($val == 'Konvansiyonel') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Konvansiyonel</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Proje onay kurumu</label>
                            <input type="text" class="form-control" name="proje_onay_kurumu"
                                value="<?php echo $report['proje_onay_kurumu'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kontrol nedeni</label><br>
                            <?php $val = $report['control_reason'] ?? $facility_info['control_reason'] ?? ''; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="control_reason"
                                    value="Periyodik Kontrol" <?php echo ($val == 'Periyodik Kontrol') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Periyodik Kontrol</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="control_reason" value="İlk Kontrol"
                                    <?php echo ($val == 'İlk Kontrol') ? 'checked' : ''; ?>>
                                <label class="form-check-label">İlk Kontrol</label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Proje onay tarih ve sayısı</label>
                            <input type="text" class="form-control" name="proje_onay_bilgileri"
                                value="<?php echo $report['proje_onay_bilgileri'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kontrol paneli marka/model</label>
                            <input type="text" class="form-control" name="panel_marka_model"
                                value="<?php echo $report['panel_marka_model'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">İlk kontrol tarihi</label>
                            <input type="date" class="form-control" name="ilk_kontrol_tarihi"
                                value="<?php echo $report['ilk_kontrol_tarihi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Son kontrol tarihi</label>
                            <input type="date" class="form-control" name="prev_control_date"
                                value="<?php echo $report['prev_control_date'] ?? $facility_info['son_kontrol_tarihi'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kontrol paneli seri no./imal yılı</label>
                            <input type="text" class="form-control" name="panel_seri_no"
                                value="<?php echo $report['panel_seri_no'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kontrol paneli çalışma gerilimi</label>
                            <input type="text" class="form-control" name="panel_calisma_gerilimi"
                                value="<?php echo $report['panel_calisma_gerilimi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Algılama ekipmanları</label><br>
                            <?php $val = explode(',', $report['algilama_ekipmanlari'] ?? ''); ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="algilama_ekipmanlari[]"
                                    value="Duman (optik) dedektörü" <?php echo in_array('Duman (optik) dedektörü', $val) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Duman (optik) dedektörü</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="algilama_ekipmanlari[]"
                                    value="Isı dedektörü" <?php echo in_array('Isı dedektörü', $val) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Isı dedektörü</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="algilama_ekipmanlari[]"
                                    value="İhbar butonu" <?php echo in_array('İhbar butonu', $val) ? 'checked' : ''; ?>>
                                <label class="form-check-label">İhbar butonu</label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kontrol paneli yeri</label>
                            <input type="text" class="form-control" name="panel_yeri"
                                value="<?php echo $report['panel_yeri'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Hava durumu ve sıcaklık</label>
                            <input type="text" class="form-control" name="weather_condition"
                                value="<?php echo $report['weather_condition'] ?? $facility_info['weather_condition'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Zemin nem durumu</label>
                            <input type="text" class="form-control" name="ground_moisture"
                                value="<?php echo $report['ground_moisture'] ?? $facility_info['ground_moisture'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Uyarı ekipmanları</label><br>
                            <?php $val = explode(',', $report['uyari_ekipmanlari'] ?? ''); ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="uyari_ekipmanlari[]" value="Siren"
                                    <?php echo in_array('Siren', $val) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Siren</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="uyari_ekipmanlari[]"
                                    value="Flaşör" <?php echo in_array('Flaşör', $val) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Flaşör</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Söndürme ekipmanları</label><br>
                        <?php $val = explode(',', $report['sondurme_ekipmanlari'] ?? ''); ?>
                        <?php foreach (['Otomatik söndürme', 'KKT Özellikli yangın tüpleri', 'CO2 Özellikli yangın tüpleri', 'Hidrantlar-Yangın dolapları'] as $opt): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="sondurme_ekipmanlari[]"
                                    value="<?php echo $opt; ?>" <?php echo in_array($opt, $val) ? 'checked' : ''; ?>>
                                <label class="form-check-label">
                                    <?php echo $opt; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>
                    <h5>2.2 Bina ile İlgili Tespit Edilen Bilgiler</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tesisatta kapsamlı değişiklik var mı?</label><br>
                            <?php $val = $report['installation_change'] ?? ''; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="installation_change" value="Var"
                                    <?php echo ($val == 'Var') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Var</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="installation_change" value="Yok"
                                    <?php echo ($val == 'Yok') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Yok</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="installation_change"
                                    value="Belirlenemedi" <?php echo ($val == 'Belirlenemedi') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Belirlenemedi</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bir önceki periyodik kontrol etiketi var mı?</label><br>
                            <?php $val = $report['prev_label_exists'] ?? ''; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="prev_label_exists" value="Var" <?php echo ($val == 'Var') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Var</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="prev_label_exists" value="Yok" <?php echo ($val == 'Yok') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Yok</label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bina kullanma sınıfı</label><br>
                            <?php $val = $report['bina_kullanma_sinifi'] ?? ''; ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php foreach (['Konut', 'Toplanma amaçlı bina', 'Depolama amaçlı tesis', 'Yüksek tehlikeli bina', 'Karışık kullanım amaçlı bina'] as $opt): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="bina_kullanma_sinifi"
                                                value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">
                                                <?php echo $opt; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-6">
                                    <?php foreach (['Endüstriyel yapı', 'Konaklama amaçlı bina', 'Kurumsal bina', 'Büro binası', 'Ticari'] as $opt): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="bina_kullanma_sinifi"
                                                value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">
                                                <?php echo $opt; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Bina tehlike sınıfı</label><br>
                            <?php $val = $report['bina_tehlike_sinifi'] ?? ''; ?>
                            <?php foreach (['Düşük tehlike', 'Orta tehlike', 'Yüksek tehlike'] as $opt): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="bina_tehlike_sinifi"
                                        value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo $opt; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tehlike kategorisi</label><br>
                            <?php $val = $report['tehlike_kategorisi'] ?? ''; ?>
                            <?php foreach (['1', '2', '3', '4'] as $opt): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tehlike_kategorisi"
                                        value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo $opt; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bina toplam kullanım alanı (m²)</label>
                            <input type="text" class="form-control" name="toplam_alan"
                                value="<?php echo $report['toplam_alan'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kat sayısı</label>
                            <input type="text" class="form-control" name="kat_sayisi"
                                value="<?php echo $report['kat_sayisi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bina yüksekliği/Yapı yüksekliği (m)</label>
                            <input type="text" class="form-control" name="bina_yuksekligi"
                                value="<?php echo $report['bina_yuksekligi'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Yapı kullanma izin tarihi</label>
                            <input type="date" class="form-control" name="yapi_kullanma_izin_tarihi"
                                value="<?php echo $report['yapi_kullanma_izin_tarihi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bölüm sayısı</label>
                            <input type="text" class="form-control" name="bolum_sayisi"
                                value="<?php echo $report['bolum_sayisi'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Varsa diğer tespitler</label>
                            <textarea class="form-control" name="diger_tespitler"
                                rows="2"><?php echo $report['diger_tespitler'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Bölüm: Test Değerleri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c3">3. Test Değerleri</button></h2>
            <div id="c3" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <p class="text-muted">Bu bölüm raporun çıktı sayfasında detaylandırılacaktır.</p>
                </div>
            </div>
        </div>

        <!-- 4. Bölüm: Ölçüm Aletleri Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c4">4. Ölçüm Aletleri Bilgileri</button></h2>
            <div id="c4" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">1. Ölçüm Cihazı</label>
                            <select class="form-select" name="device1_id">
                                <option value="">Seçiniz</option>
                                <?php foreach ($all_devices as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo ((isset($report['device1_id']) && $report['device1_id'] == $d['id']) || (empty($report) && !empty($facility_defaults['default_device_id']) && $facility_defaults['default_device_id'] == $d['id'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['device_name'] . ' (' . $d['serial_no'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">2. Ölçüm Cihazı (Varsa)</label>
                            <select class="form-select" name="device2_id">
                                <option value="">Seçiniz</option>
                                <?php foreach ($all_devices as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo (isset($report['device2_id']) && $report['device2_id'] == $d['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['device_name'] . ' (' . $d['serial_no'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Bölüm: Tespit ve Değerlendirmeler -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c5_results">5. Tespit ve Değerlendirmeler</button></h2>
            <div id="c5_results" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body text-center p-4">
                    <?php if ($report_id): ?>
                        <p>Kontrol kriterlerini değerlendirmek ve sonuçları girmek için aşağıdaki butonu kullanınız.</p>
                        <a href="yangin_algilama_sonuclar.php?report_id=<?php echo $report_id; ?>" class="btn btn-primary">
                            <i class="fas fa-list-check"></i> Tespit ve Değerlendirme Girişi Yap
                        </a>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Gözle muayene sonuçlarını girmek için önce raporu kaydetmelisiniz.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 5. Bölüm: Kusur Açıklamaları -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c5">5. Kusur Açıklamaları</button></h2>
            <div id="c5" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label">Kusur Açıklamaları</label>
                        <textarea class="form-control" name="defects"
                            rows="4"><?php echo $report['defects'] ?? ''; ?></textarea>
                        <small class="text-muted">Kusur derecesini (*) hafif veya (**) ağır olarak
                            belirtebilirsiniz.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Bölüm: Fotoğraflar -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c6">6. Fotoğraflar</button></h2>
            <div id="c6" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <?php
                    if ($report_id) {
                        $stmt_p_list = $pdo->prepare("SELECT * FROM fire_detection_photos WHERE report_id = ? ORDER BY id ASC");
                        $stmt_p_list->execute([$report_id]);
                        $photos = $stmt_p_list->fetchAll();
                    } else {
                        $photos = [];
                    }
                    ?>
                    
                    <?php if (!empty($photos)): ?>
                        <div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
                            <?php foreach ($photos as $ph): ?>
                                <div class="col">
                                    <div class="card h-100 shadow-sm border position-relative">
                                        <img src="../../<?php echo htmlspecialchars($ph['file_path']); ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Yangın Algılama Fotoğraf">
                                        <div class="card-body p-2 text-center">
                                            <form method="POST" onsubmit="return confirm('Bu fotoğrafı silmek istediğinize emin misiniz?');" style="display:inline;">
                                                <input type="hidden" name="delete_photo_id" value="<?php echo $ph['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                    <i class="fas fa-trash-alt me-1"></i> Sil
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-3"><i class="fas fa-image me-1"></i> Henüz fotoğraf yüklenmemiş.</p>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Yeni Fotoğraf(lar) Yükle</label>
                        <input type="file" class="form-control" name="photos[]" multiple accept="image/*">
                        <small class="text-muted">Birden fazla fotoğraf seçip yükleyebilirsiniz (JPG, JPEG, PNG, WEBP).</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. Bölüm: Notlar -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c7">7. Notlar</button></h2>
            <div id="c7" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea class="form-control" name="notes"
                            rows="3"><?php echo $report['notes'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 8. Bölüm: Sonuç ve Kanaat -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c8">8. Sonuç ve Kanaat</button></h2>
            <div id="c8" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3 border p-3 bg-light">
                        <select class="form-select" name="result">
                            <option value="UYGUNDUR" <?php echo (($report['result'] ?? '') == 'UYGUNDUR' || ($report['result'] ?? '') == 'GÜVENLİDİR') ? 'selected' : ''; ?>>UYGUNDUR</option>
                            <option value="UYGUN DEGILDIR" <?php echo (($report['result'] ?? '') == 'UYGUN DEGILDIR' || ($report['result'] ?? '') == 'UYGUN DEĞİLDİR' || ($report['result'] ?? '') == 'GÜVENLİ DEĞİLDİR') ? 'selected' : ''; ?>>UYGUN DEĞİLDİR</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- 9. Bölüm: Yetkili Kişi Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c9">9. Yetkili Kişi Bilgileri</button></h2>
            <div id="c9" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kontrolü Yapan Yetkili Kişi</label>
                        <select class="form-select" name="authorized_person_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($authorized_persons as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ((isset($report['authorized_person_id']) && $report['authorized_person_id'] == $p['id']) || (empty($report) && !empty($facility_defaults['default_authorized_person_id']) && $facility_defaults['default_authorized_person_id'] == $p['id'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['adi_soyadi'] . ' (' . $p['meslegi'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-4 mb-5 d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg">Kaydet ve İlerle</button>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>