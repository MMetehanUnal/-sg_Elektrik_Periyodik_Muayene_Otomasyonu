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
$measurements = [];

if ($report_id) {
    $stmt = $pdo->prepare("SELECT * FROM katodik_koruma_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$report_id, $kurum_id]);
    $report = $stmt->fetch();
    if (!$report) {
        redirect('katodik_koruma_kontrol.php'); // Invalid ID
    }
    
    // Fetch associated measurements
    $stmt_m = $pdo->prepare("SELECT * FROM katodik_koruma_measurements WHERE report_id = ? ORDER BY id ASC");
    $stmt_m->execute([$report_id]);
    $measurements = $stmt_m->fetchAll();
}

// Fetch Institution
$stmt = $pdo->prepare("SELECT * FROM institutions WHERE id = ?");
$stmt->execute([$kurum_id]);
$kurum = $stmt->fetch();

// Fetch Authorized Persons
$stmt = $pdo->prepare("SELECT * FROM authorized_persons");
$stmt->execute();
$authorized_persons = $stmt->fetchAll();

// Fetch Measurement Devices
$stmt = $pdo->prepare("SELECT * FROM measurement_devices WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$measurement_devices = $stmt->fetchAll();

// Ensure at least a few empty rows if creating new
if (empty($measurements)) {
    for ($i = 1; $i <= 3; $i++) {
        $measurements[] = [
            'box_no' => $i,
            'system_voltage' => '',
            'pipe_voltage' => '',
            'anode_voltage' => '',
            'anode_current' => '',
            'notes' => ''
        ];
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic fields
    $report_date = cleanInput($_POST['report_date']);
    $start_date = cleanInput($_POST['start_date']);
    $end_date = cleanInput($_POST['end_date']);
    $next_control_date = cleanInput($_POST['next_control_date']);
    $isg_katip_id = cleanInput($_POST['isg_katip_id'] ?? '');
    $firma_adi_eki = cleanInput($_POST['firma_adi_eki'] ?? '');
    $control_reason = cleanInput($_POST['control_reason'] ?? 'Periyodik Kontrol');
    
    // Tesis ve Ölçüm Genel Bilgileri
    $zemin = cleanInput($_POST['zemin'] ?? '');
    $toprak_durumu = cleanInput($_POST['toprak_durumu'] ?? '');
    $tesis_proje_var_mi = cleanInput($_POST['tesis_proje_var_mi'] ?? '');
    $olcu_kutusu_sayisi = cleanInput($_POST['olcu_kutusu_sayisi'] ?? '');
    $referans_elektrot_tipi = cleanInput($_POST['referans_elektrot_tipi'] ?? '');
    $tesisin_kullanim_amaci = cleanInput($_POST['tesisin_kullanim_amaci'] ?? '');
    
    // Cihaz Bilgileri
    $device_id = !empty($_POST['device_id']) ? (int)$_POST['device_id'] : null;
    $olcum_cihazi = cleanInput($_POST['olcum_cihazi'] ?? '');
    $marka_model = cleanInput($_POST['marka_model'] ?? '');
    $seri_no = cleanInput($_POST['seri_no'] ?? '');
    $hata_sinifi = cleanInput($_POST['hata_sinifi'] ?? '');
    $olcum_yontemi = cleanInput($_POST['olcum_yontemi'] ?? '');
    $kalibrasyon_kurum = cleanInput($_POST['kalibrasyon_kurum'] ?? '');
    $kalibrasyon_tarih_sayi = cleanInput($_POST['kalibrasyon_tarih_sayi'] ?? '');
    $gecerlilik_suresi = cleanInput($_POST['gecerlilik_suresi'] ?? '');
    
    // Recommendations, outcomes, and authorized person
    $defects = cleanInput($_POST['defects']);
    $notes = cleanInput($_POST['notes']);
    $result = cleanInput($_POST['result'] ?? 'UYGUNDUR');
    $authorized_person_id = cleanInput($_POST['authorized_person_id']);

    // Dynamic measurements data
    $meas_rows = $_POST['meas'] ?? [];

    try {
        $pdo->beginTransaction();
        
        if ($report_id) {
            // Update Report
            $sql = "UPDATE katodik_koruma_reports SET 
                report_date=?, start_date=?, end_date=?, next_control_date=?, isg_katip_id=?,
                firma_adi_eki=?, control_reason=?, zemin=?, toprak_durumu=?, tesis_proje_var_mi=?,
                olcu_kutusu_sayisi=?, referans_elektrot_tipi=?, tesisin_kullanim_amaci=?,
                device_id=?, olcum_cihazi=?, marka_model=?, seri_no=?, hata_sinifi=?, olcum_yontemi=?,
                kalibrasyon_kurum=?, kalibrasyon_tarih_sayi=?, gecerlilik_suresi=?,
                defects=?, notes=?, result=?, authorized_person_id=?
                WHERE id=? AND kurum_id=?";
            $params = [
                $report_date, $start_date, $end_date, $next_control_date, $isg_katip_id,
                $firma_adi_eki, $control_reason, $zemin, $toprak_durumu, $tesis_proje_var_mi,
                $olcu_kutusu_sayisi, $referans_elektrot_tipi, $tesisin_kullanim_amaci,
                $device_id, $olcum_cihazi, $marka_model, $seri_no, $hata_sinifi, $olcum_yontemi,
                $kalibrasyon_kurum, $kalibrasyon_tarih_sayi, $gecerlilik_suresi,
                $defects, $notes, $result, $authorized_person_id,
                $report_id, $kurum_id
            ];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // Insert Report
            $report_no = $kurum['il_kodu'] . '-' . $kurum['kurum_kodu'] . '-kk-' . time();
            
            $sql = "INSERT INTO katodik_koruma_reports 
                (kurum_id, report_no, report_date, start_date, end_date, next_control_date, isg_katip_id, 
                firma_adi_eki, control_reason, zemin, toprak_durumu, tesis_proje_var_mi,
                olcu_kutusu_sayisi, referans_elektrot_tipi, tesisin_kullanim_amaci,
                device_id, olcum_cihazi, marka_model, seri_no, hata_sinifi, olcum_yontemi,
                kalibrasyon_kurum, kalibrasyon_tarih_sayi, gecerlilik_suresi,
                defects, notes, result, authorized_person_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $kurum_id, $report_no, $report_date, $start_date, $end_date, $next_control_date, $isg_katip_id,
                $firma_adi_eki, $control_reason, $zemin, $toprak_durumu, $tesis_proje_var_mi,
                $olcu_kutusu_sayisi, $referans_elektrot_tipi, $tesisin_kullanim_amaci,
                $device_id, $olcum_cihazi, $marka_model, $seri_no, $hata_sinifi, $olcum_yontemi,
                $kalibrasyon_kurum, $kalibrasyon_tarih_sayi, $gecerlilik_suresi,
                $defects, $notes, $result, $authorized_person_id
            ];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $report_id = $pdo->lastInsertId();
        }

        // Delete existing measurements
        $pdo->prepare("DELETE FROM katodik_koruma_measurements WHERE report_id = ?")->execute([$report_id]);

        // Insert new measurements
        $stmt_ins = $pdo->prepare("INSERT INTO katodik_koruma_measurements 
            (report_id, box_no, system_voltage, pipe_voltage, anode_voltage, anode_current, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
            
        foreach ($meas_rows as $row) {
            if (empty($row['box_no'])) continue;
            $stmt_ins->execute([
                $report_id,
                cleanInput($row['box_no']),
                cleanInput($row['system_voltage'] ?? ''),
                cleanInput($row['pipe_voltage'] ?? ''),
                cleanInput($row['anode_voltage'] ?? ''),
                cleanInput($row['anode_current'] ?? ''),
                cleanInput($row['notes'] ?? '')
            ]);
        }
        
        $pdo->commit();
        redirect("../results/katodik_koruma_sonuclar.php?report_id=$report_id&status=success");

    } catch (PDOException $e) {
        $pdo->rollBack();
        $save_error = "Hata: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $report_id ? 'Rapor Düzenle' : 'Yeni Rapor Oluştur'; ?>: Katodik Koruma Ölçüm Raporu</h1>
    <a href="/pages/raporlar.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Vazgeç ve Raporlara Dön
    </a>
</div>

<?php if (isset($save_error)): ?>
    <div class="alert alert-danger"><?php echo $save_error; ?></div>
<?php endif; ?>

<form method="POST" action="" id="katodikForm">
    <div class="accordion shadow-sm mb-4" id="accordionForm">
        
        <!-- Section 1: Rapor / Tesis Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#c1">
                    1. Rapor / Tesis Bilgileri
                </button>
            </h2>
            <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kurum Adı</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($kurum['firma_adi']); ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Firma Adı Eki (Şube/Bölüm vb.)</label>
                            <input type="text" class="form-control" name="firma_adi_eki"
                                value="<?php echo htmlspecialchars($report['firma_adi_eki'] ?? ''); ?>" placeholder="Örn: B Blok / Depo">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">İSG-KATİP Sözleşme ID</label>
                            <input type="text" class="form-control" name="isg_katip_id"
                                value="<?php echo htmlspecialchars($report['isg_katip_id'] ?? $kurum['isg_katip_id'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Zemin Cinsi</label>
                            <select class="form-select" name="zemin">
                                <option value="Deniz" <?php echo (isset($report['zemin']) && $report['zemin'] == 'Deniz') ? 'selected' : ''; ?>>Deniz</option>
                                <option value="Toprak" <?php echo (!isset($report['zemin']) || $report['zemin'] == 'Toprak') ? 'selected' : ''; ?>>Toprak</option>
                                <option value="Beton" <?php echo (isset($report['zemin']) && $report['zemin'] == 'Beton') ? 'selected' : ''; ?>>Beton</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Toprak Durumu</label>
                            <select class="form-select" name="toprak_durumu">
                                <option value="Islak" <?php echo (isset($report['toprak_durumu']) && $report['toprak_durumu'] == 'Islak') ? 'selected' : ''; ?>>Islak</option>
                                <option value="Nemli" <?php echo (!isset($report['toprak_durumu']) || $report['toprak_durumu'] == 'Nemli') ? 'selected' : ''; ?>>Nemli</option>
                                <option value="Kuru" <?php echo (isset($report['toprak_durumu']) && $report['toprak_durumu'] == 'Kuru') ? 'selected' : ''; ?>>Kuru</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kontrol Nedeni</label>
                            <select class="form-select" name="control_reason">
                                <option value="Periyodik Kontrol" <?php echo (!isset($report['control_reason']) || $report['control_reason'] == 'Periyodik Kontrol') ? 'selected' : ''; ?>>Periyodik Kontrol</option>
                                <option value="Tekrar Kontrol" <?php echo (isset($report['control_reason']) && $report['control_reason'] == 'Tekrar Kontrol') ? 'selected' : ''; ?>>Tekrar Kontrol</option>
                                <option value="Yeni Tesis" <?php echo (isset($report['control_reason']) && $report['control_reason'] == 'Yeni Tesis') ? 'selected' : ''; ?>>Yeni Tesis</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tesis Projesi Var mı?</label>
                            <select class="form-select" name="tesis_proje_var_mi">
                                <option value="Var" <?php echo (!isset($report['tesis_proje_var_mi']) || $report['tesis_proje_var_mi'] == 'Var') ? 'selected' : ''; ?>>Var</option>
                                <option value="Yok" <?php echo (isset($report['tesis_proje_var_mi']) && $report['tesis_proje_var_mi'] == 'Yok') ? 'selected' : ''; ?>>Yok</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ölçü Kutusu Sayısı</label>
                            <input type="text" class="form-control" name="olcu_kutusu_sayisi"
                                value="<?php echo htmlspecialchars($report['olcu_kutusu_sayisi'] ?? '3'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Referans Elektrot Tipi</label>
                            <input type="text" class="form-control" name="referans_elektrot_tipi"
                                value="<?php echo htmlspecialchars($report['referans_elektrot_tipi'] ?? 'Cu/CuSO4'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tesisin Kullanım Amacı</label>
                            <input type="text" class="form-control" name="tesisin_kullanim_amaci"
                                value="<?php echo htmlspecialchars($report['tesisin_kullanim_amaci'] ?? 'Akaryakıt Depolama'); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Rapor Tarihi</label>
                            <input type="date" class="form-control" name="report_date" required
                                value="<?php echo $report['report_date'] ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Kontrol Başlangıç Tarihi ve Saati</label>
                            <input type="datetime-local" class="form-control" name="start_date"
                                value="<?php echo isset($report['start_date']) ? date('Y-m-d\TH:i', strtotime($report['start_date'])) : date('Y-m-d\T09:00'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Kontrol Bitiş Tarihi ve Saati</label>
                            <input type="datetime-local" class="form-control" name="end_date"
                                value="<?php echo isset($report['end_date']) ? date('Y-m-d\TH:i', strtotime($report['end_date'])) : date('Y-m-d\T17:00'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Bir Sonraki Kontrol Tarihi</label>
                            <input type="date" class="form-control" name="next_control_date" required
                                value="<?php echo $report['next_control_date'] ?? date('Y-m-d', strtotime('+1 year -2 days')); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section 2: Ölçüm & Cihaz Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c2">
                    2. Ölçüm Cihazı Kalibrasyon Bilgileri
                </button>
            </h2>
            <div id="c2" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3 col-md-6">
                        <label class="form-label fw-bold text-primary">Cihaz Listesinden Seçerek Doldur</label>
                        <select class="form-select" id="deviceSelect" name="device_id">
                            <option value="">-- Cihaz Seçin --</option>
                            <?php foreach ($measurement_devices as $dev): ?>
                                <option value="<?php echo $dev['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($dev['device_name']); ?>"
                                    data-serial="<?php echo htmlspecialchars($dev['serial_no']); ?>"
                                    data-calno="<?php echo htmlspecialchars($dev['cal_no']); ?>"
                                    data-caldate="<?php echo htmlspecialchars($dev['cal_date']); ?>"
                                    data-validity="<?php echo htmlspecialchars($dev['validity_date']); ?>"
                                    <?php echo (isset($report['device_id']) && $report['device_id'] == $dev['id']) || (!isset($report['device_id']) && $facility_defaults['default_device_id'] == $dev['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dev['device_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ölçüm Cihazı</label>
                            <input type="text" class="form-control" name="olcum_cihazi" id="olcum_cihazi"
                                value="<?php echo htmlspecialchars($report['olcum_cihazi'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Marka-Model</label>
                            <input type="text" class="form-control" name="marka_model" id="marka_model"
                                value="<?php echo htmlspecialchars($report['marka_model'] ?? 'Fluke'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Seri No</label>
                            <input type="text" class="form-control" name="seri_no" id="seri_no"
                                value="<?php echo htmlspecialchars($report['seri_no'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Hata Sınıfı</label>
                            <input type="text" class="form-control" name="hata_sinifi"
                                value="<?php echo htmlspecialchars($report['hata_sinifi'] ?? '± 1%'); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ölçüm Yöntemi</label>
                            <input type="text" class="form-control" name="olcum_yontemi"
                                value="<?php echo htmlspecialchars($report['olcum_yontemi'] ?? 'Doğrudan Gerilim / Akım Ölçümü'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Kalibrasyon Yapan Kurum</label>
                            <input type="text" class="form-control" name="kalibrasyon_kurum" id="kalibrasyon_kurum"
                                value="<?php echo htmlspecialchars($report['kalibrasyon_kurum'] ?? 'Kalibrasyon Laboratuvarı'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Kalibrasyon Tarih / Sayı</label>
                            <input type="text" class="form-control" name="kalibrasyon_tarih_sayi" id="kalibrasyon_tarih_sayi"
                                value="<?php echo htmlspecialchars($report['kalibrasyon_tarih_sayi'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Geçerlilik Süresi</label>
                            <input type="text" class="form-control" name="gecerlilik_suresi" id="gecerlilik_suresi"
                                value="<?php echo htmlspecialchars($report['gecerlilik_suresi'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section 3: Ölçüm Sonuçları Table -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c3">
                    3. Ölçüm Sonuçları (Galvanik Sistemli Koruma)
                </button>
            </h2>
            <div id="c3" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="measTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 15%;">Ölçü Kutusu No</th>
                                    <th style="width: 15%;">Sistem Gerilimi (mV)</th>
                                    <th style="width: 15%;">Boru Gerilimi (mV)</th>
                                    <th style="width: 15%;">Anot Gerilimi (mV)</th>
                                    <th style="width: 15%;">Anot Akımı (mA)</th>
                                    <th style="width: 20%;">Notlar</th>
                                    <th style="width: 5%;" class="text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($measurements as $idx => $m): ?>
                                    <tr>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="meas[<?php echo $idx; ?>][box_no]" value="<?php echo htmlspecialchars($m['box_no']); ?>" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="meas[<?php echo $idx; ?>][system_voltage]" value="<?php echo htmlspecialchars($m['system_voltage']); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="meas[<?php echo $idx; ?>][pipe_voltage]" value="<?php echo htmlspecialchars($m['pipe_voltage']); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="meas[<?php echo $idx; ?>][anode_voltage]" value="<?php echo htmlspecialchars($m['anode_voltage']); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="meas[<?php echo $idx; ?>][anode_current]" value="<?php echo htmlspecialchars($m['anode_current']); ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="meas[<?php echo $idx; ?>][notes]" value="<?php echo htmlspecialchars($m['notes']); ?>">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-del-row" onclick="deleteRow(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="btnAddRow">
                        <i class="fas fa-plus me-1"></i> Yeni Satır Ekle
                    </button>
                </div>
            </div>
        </div>

        <!-- Section 4: Sonuç, Öneriler ve Muayene Ekibi -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c4">
                    4. Sonuç, Öneriler ve Muayene Ekibi
                </button>
            </h2>
            <div id="c4" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Muayene Bulguları ve Kusurlar</label>
                        <textarea class="form-control" name="defects" rows="3" placeholder="Örn: 2 nolu ölçüm kutusundaki anot bağlantı kablosunda kopukluk tespit edilmiş olup düzeltilmesi gerekmektedir."><?php echo htmlspecialchars($report['defects'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notlar / Açıklamalar</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Varsa ek açıklama..."><?php echo htmlspecialchars($report['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Değerlendirme Sonucu</label>
                            <select class="form-select" name="result">
                                <option value="UYGUNDUR" <?php echo (!isset($report['result']) || $report['result'] == 'UYGUNDUR') ? 'selected' : ''; ?>>UYGUNDUR</option>
                                <option value="UYGUN DEĞİLDİR" <?php echo (isset($report['result']) && $report['result'] == 'UYGUN DEĞİLDİR') ? 'selected' : ''; ?>>UYGUN DEĞİLDİR</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kontrolü Gerçekleştiren Personel (Surveyor)</label>
                            <select class="form-select" name="authorized_person_id" required>
                                <option value="">-- Personel Seçin --</option>
                                <?php foreach ($authorized_persons as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"
                                        <?php echo (isset($report['authorized_person_id']) && $report['authorized_person_id'] == $p['id']) || (!isset($report['authorized_person_id']) && $facility_defaults['default_authorized_person_id'] == $p['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['adi_soyadi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100 mb-5">
        <i class="fas fa-save me-1"></i> Raporu Kaydet ve Tamamla
    </button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Device select prefill logic
    const devSelect = document.getElementById('deviceSelect');
    if (devSelect) {
        devSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (!opt.value) return;
            
            document.getElementById('olcum_cihazi').value = opt.getAttribute('data-name') || '';
            document.getElementById('seri_no').value = opt.getAttribute('data-serial') || '';
            document.getElementById('kalibrasyon_kurum').value = 'Kalibrasyon Laboratuvarı';
            
            // Format calibration number and date
            const calNo = opt.getAttribute('data-calno') || '';
            const calDate = opt.getAttribute('data-caldate') || '';
            if (calNo && calDate) {
                // Reformat YYYY-MM-DD to DD.MM.YYYY
                const parts = calDate.split('-');
                const formattedDate = parts.length === 3 ? `${parts[2]}.${parts[1]}.${parts[0]}` : calDate;
                document.getElementById('kalibrasyon_tarih_sayi').value = `${formattedDate} / ${calNo}`;
            } else {
                document.getElementById('kalibrasyon_tarih_sayi').value = '';
            }
            
            // Format validity date
            const validity = opt.getAttribute('data-validity') || '';
            if (validity) {
                const parts = validity.split('-');
                const formattedValidity = parts.length === 3 ? `${parts[2]}.${parts[1]}.${parts[0]}` : validity;
                document.getElementById('gecerlilik_suresi').value = formattedValidity;
            } else {
                document.getElementById('gecerlilik_suresi').value = '';
            }
        });
        
        // Trigger auto-fill initially on load if creating new report and defaults are set
        if (!<?php echo $report_id ? 'true' : 'false'; ?> && devSelect.value) {
            devSelect.dispatchEvent(new Event('change'));
        }
    }

    // Dynamic row addition
    let rowIndex = <?php echo count($measurements); ?>;
    const btnAdd = document.getElementById('btnAddRow');
    const tableBody = document.querySelector('#measTable tbody');

    if (btnAdd && tableBody) {
        btnAdd.addEventListener('click', function() {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <input type="text" class="form-control form-control-sm" name="meas[${rowIndex}][box_no]" value="${rowIndex + 1}" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="meas[${rowIndex}][system_voltage]" value="">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="meas[${rowIndex}][pipe_voltage]" value="">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="meas[${rowIndex}][anode_voltage]" value="">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="meas[${rowIndex}][anode_current]" value="">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="meas[${rowIndex}][notes]" value="">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-del-row" onclick="deleteRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
            rowIndex++;
        });
    }
});

function deleteRow(btn) {
    const row = btn.closest('tr');
    const tbody = row.parentNode;
    if (tbody.querySelectorAll('tr').length > 1) {
        row.remove();
    } else {
        alert("En az bir ölçüm satırı bulunmalıdır!");
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
