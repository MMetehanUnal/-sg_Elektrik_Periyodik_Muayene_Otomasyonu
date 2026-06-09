<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

if (!isset($_SESSION['active_institution_id'])) {
    redirect('/pages/tesis_secimi.php');
}

$kurum_id = $_SESSION['active_institution_id'];

// Check if editing
$report_id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
$report = [];

if ($report_id) {
    $stmt = $pdo->prepare("SELECT * FROM internal_installation_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$report_id, $kurum_id]);
    $report = $stmt->fetch();
    if (!$report)
        redirect('ic_tesisat_kontrol.php'); // Invalid ID
}

// Fetch Institution Defaults
$stmt = $pdo->prepare("SELECT * FROM institutions WHERE id = ?");
$stmt->execute([$kurum_id]);
$kurum = $stmt->fetch();

// Fetch Devices
$stmt = $pdo->prepare("SELECT * FROM measurement_devices WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$all_devices = $stmt->fetchAll();

$thermal_cameras = array_filter($all_devices, function ($d) {
    return $d['is_thermal_camera'] == 1;
});
$measuring_devices = array_filter($all_devices, function ($d) {
    return $d['is_thermal_camera'] == 0;
});

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic fields
    $report_date = cleanInput($_POST['report_date']);
    $energy_provider = cleanInput($_POST['energy_provider'] ?? '');
    $sebeke_tipi = cleanInput($_POST['sebeke_tipi'] ?? '');
    $proje_var_mi = isset($_POST['proje_var_mi']) ? (int) $_POST['proje_var_mi'] : 0;
    $sema_var_mi = isset($_POST['sema_var_mi']) ? (int) $_POST['sema_var_mi'] : 0;
    $start_date = cleanInput($_POST['start_date']);
    $end_date = cleanInput($_POST['end_date']);
    $next_control_date = cleanInput($_POST['next_control_date']);
    $isg_katip_id = cleanInput($_POST['isg_katip_id']);

    // Section 2.1
    $control_reason = cleanInput($_POST['control_reason'] ?? '');
    $grounding_type = cleanInput($_POST['grounding_type'] ?? '');
    $building_type = cleanInput($_POST['building_type'] ?? '');
    $usage_purpose = cleanInput($_POST['usage_purpose'] ?? '');
    $prev_control_date = !empty($_POST['prev_control_date']) ? cleanInput($_POST['prev_control_date']) : null;
    $phase_count_type = cleanInput($_POST['phase_count_type'] ?? '');
    $conductor_type = cleanInput($_POST['conductor_type'] ?? '');
    $weather_condition = cleanInput($_POST['weather_condition'] ?? '');
    $ground_moisture = cleanInput($_POST['ground_moisture'] ?? '');
    $grounding_resistance = cleanInput($_POST['grounding_resistance'] ?? '');
    $additional_electrode_details = cleanInput($_POST['additional_electrode_details'] ?? '');
    $system_grounding_conductor = cleanInput($_POST['system_grounding_conductor'] ?? '');
    $main_equipotential_conductor = cleanInput($_POST['main_equipotential_conductor'] ?? '');
    $nominal_voltage_kV = cleanInput($_POST['nominal_voltage_kV'] ?? '');
    $nominal_frequency_Hz = cleanInput($_POST['nominal_frequency_Hz'] ?? '');
    $fault_current_kA = cleanInput($_POST['fault_current_kA'] ?? '');
    $external_loop_impedance = cleanInput($_POST['external_loop_impedance'] ?? '');
    $main_rcd_rating = cleanInput($_POST['main_rcd_rating'] ?? '');
    $main_breaker_type = cleanInput($_POST['main_breaker_type'] ?? '');
    $main_breaker_rating = cleanInput($_POST['main_breaker_rating'] ?? '');
    $main_rcd_test_mA = cleanInput($_POST['main_rcd_test_mA'] ?? '');
    $main_rcd_test_ms = cleanInput($_POST['main_rcd_test_ms'] ?? '');

    // Section 2.2
    $installation_change = isset($_POST['installation_change']) ? cleanInput($_POST['installation_change']) : 0;
    $has_spd = isset($_POST['has_spd']) ? cleanInput($_POST['has_spd']) : 0;
    $protection_measures = isset($_POST['protection_measures']) ? implode(',', $_POST['protection_measures']) : '';
    $prev_label_exists = isset($_POST['prev_label_exists']) ? cleanInput($_POST['prev_label_exists']) : 0;

    // Section 3 & 4
    $thermal_camera_id = !empty($_POST['thermal_camera_id']) ? cleanInput($_POST['thermal_camera_id']) : null;
    $device1_id = !empty($_POST['device1_id']) ? cleanInput($_POST['device1_id']) : null;
    $device2_id = !empty($_POST['device2_id']) ? cleanInput($_POST['device2_id']) : null;

    // Results
    $authorized_person_id = cleanInput($_POST['authorized_person_id'] ?? '');
    $defects = cleanInput($_POST['defects'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');
    $result = cleanInput($_POST['result'] ?? 'UYGUNDUR');
    $result_notes_selection = isset($_POST['result_notes']) ? implode(',', $_POST['result_notes']) : '';

    try {
        if ($report_id) {
            $sql = "UPDATE internal_installation_reports SET 
                report_date=?, energy_provider=?, sebeke_tipi=?, proje_var_mi=?, sema_var_mi=?, 
                start_date=?, end_date=?, next_control_date=?, isg_katip_id=?,
                control_reason=?, grounding_type=?, building_type=?, usage_purpose=?, prev_control_date=?,
                weather_condition=?, ground_moisture=?,
                phase_count_type=?, conductor_type=?, grounding_resistance=?, 
                additional_electrode_details=?, system_grounding_conductor=?, main_equipotential_conductor=?,
                nominal_voltage_kV=?, nominal_frequency_Hz=?,
                fault_current_kA=?, external_loop_impedance=?, main_rcd_rating=?, main_breaker_type=?,
                main_breaker_rating=?, main_rcd_test_mA=?, main_rcd_test_ms=?,
                installation_change=?, has_spd=?, protection_measures=?, prev_label_exists=?,
                thermal_camera_id=?, device1_id=?, device2_id=?,
                authorized_person_id=?, defects=?, notes=?, result=?, result_notes_selection=?
                WHERE id=? AND kurum_id=?";
            $params = [
                $report_date,
                $energy_provider,
                $sebeke_tipi,
                $proje_var_mi,
                $sema_var_mi,
                $start_date,
                $end_date,
                $next_control_date,
                $isg_katip_id,
                $control_reason,
                $grounding_type,
                $building_type,
                $usage_purpose,
                $prev_control_date,
                $weather_condition,
                $ground_moisture,
                $phase_count_type,
                $conductor_type,
                $grounding_resistance,
                $additional_electrode_details,
                $system_grounding_conductor,
                $main_equipotential_conductor,
                $nominal_voltage_kV,
                $nominal_frequency_Hz,
                $fault_current_kA,
                $external_loop_impedance,
                $main_rcd_rating,
                $main_breaker_type,
                $main_breaker_rating,
                $main_rcd_test_mA,
                $main_rcd_test_ms,
                $installation_change,
                $has_spd,
                $protection_measures,
                $prev_label_exists,
                $thermal_camera_id,
                $device1_id,
                $device2_id,
                $authorized_person_id,
                $defects,
                $notes,
                $result,
                $result_notes_selection,
                $report_id,
                $kurum_id
            ];
        } else {
            // Generate report number
            $stmt = $pdo->prepare("SELECT il_kodu, kurum_kodu FROM institutions WHERE id = ?");
            $stmt->execute([$kurum_id]);
            $k_codes = $stmt->fetch();
            $report_no = $k_codes['il_kodu'] . '-' . $k_codes['kurum_kodu'] . '-it-' . time();

            $sql = "INSERT INTO internal_installation_reports 
                (kurum_id, report_no, report_date, energy_provider, sebeke_tipi, proje_var_mi, sema_var_mi, 
                start_date, end_date, next_control_date, isg_katip_id, 
                control_reason, grounding_type, building_type, usage_purpose, 
                prev_control_date, weather_condition, ground_moisture,
                phase_count_type, conductor_type, grounding_resistance, 
                additional_electrode_details, system_grounding_conductor, main_equipotential_conductor, 
                nominal_voltage_kV, nominal_frequency_Hz, fault_current_kA, external_loop_impedance, 
                main_rcd_rating, main_breaker_type, main_breaker_rating, main_rcd_test_mA, main_rcd_test_ms, 
                installation_change, has_spd, protection_measures, prev_label_exists, 
                thermal_camera_id, device1_id, device2_id, authorized_person_id, defects, notes, result, result_notes_selection)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $kurum_id,
                $report_no,
                $report_date,
                $energy_provider,
                $sebeke_tipi,
                $proje_var_mi,
                $sema_var_mi,
                $start_date,
                $end_date,
                $next_control_date,
                $isg_katip_id,
                $control_reason,
                $grounding_type,
                $building_type,
                $usage_purpose,
                $prev_control_date,
                $weather_condition,
                $ground_moisture,
                $phase_count_type,
                $conductor_type,
                $grounding_resistance,
                $additional_electrode_details,
                $system_grounding_conductor,
                $main_equipotential_conductor,
                $nominal_voltage_kV,
                $nominal_frequency_Hz,
                $fault_current_kA,
                $external_loop_impedance,
                $main_rcd_rating,
                $main_breaker_type,
                $main_breaker_rating,
                $main_rcd_test_mA,
                $main_rcd_test_ms,
                $installation_change,
                $has_spd,
                $protection_measures,
                $prev_label_exists,
                $thermal_camera_id,
                $device1_id,
                $device2_id,
                $authorized_person_id,
                $defects,
                $notes,
                $result,
                $result_notes_selection
            ];
        }

        $pdo->prepare($sql)->execute($params);
        if (!$report_id)
            $report_id = $pdo->lastInsertId();

        redirect("ic_tesisat_kontrol.php?id=$report_id&status=success");
    } catch (PDOException $e) {
        $save_error = "Hata: " . $e->getMessage();
    }
}

// Fetch Authorized Persons
$stmt = $pdo->prepare("SELECT * FROM authorized_persons");
$stmt->execute();
$authorized_persons = $stmt->fetchAll();

// Facility info defaults
$stmt = $pdo->prepare("SELECT * FROM facility_info WHERE kurum_id = ?");
$stmt->execute([$kurum_id]);
$facility_info = $stmt->fetch();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">İç Tesisat Kontrol Formu</h1>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <span>Rapor başarıyla kaydedildi.</span>
        <a href="../ic_tesisat_yazdir.php?id=<?php echo $_GET['id']; ?>" target="_blank" class="btn btn-success btn-sm">
            <i class="fas fa-print"></i> Raporu Yazdır
        </a>
    </div>
<?php endif; ?>

<?php if (isset($save_error)): ?>
    <div class="alert alert-danger">
        <?php echo $save_error; ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <div class="accordion" id="accordionForm">

        <!-- 1. Bölüm: Firma Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#c1">1. Firma Bilgileri</button></h2>
            <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="row">
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
                            <label class="form-label">Enerji Sağlayan Kuruluş</label>
                            <input type="text" class="form-control" name="energy_provider"
                                value="<?php echo $report['energy_provider'] ?? $facility_info['enerji_saglayan'] ?? ''; ?>">
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
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Gelecek Kontrol</label>
                            <input type="date" class="form-control" name="next_control_date"
                                value="<?php echo $report['next_control_date'] ?? $facility_info['next_control_date'] ?? $kurum['next_control_date'] ?? date('Y-m-d', strtotime('+1 year')); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Bölüm: Ekipman Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c2">2. Ekipman Bilgileri</button></h2>
            <div id="c2" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <h5>2.1 Detay Bilgiler</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Şebeke Tipi</label><br>
                            <?php $val = $report['sebeke_tipi'] ?? $facility_info['sebeke_tipi'] ?? ''; ?>
                            <?php foreach (['TT', 'IT', 'TN', 'TN-CS', 'TN-C', 'TN-S'] as $opt): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="sebeke_tipi"
                                        value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo $opt; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Şebeke Gerilimi</label>
                            <input type="text" class="form-control" name="nominal_voltage_kV"
                                value="<?php echo $report['nominal_voltage_kV'] ?? $facility_info['sebeke_gerilimi'] ?? ''; ?>"
                                placeholder="kV">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Nominal Frekans (Hz)</label>
                            <input type="text" class="form-control" name="nominal_frequency_Hz"
                                value="<?php echo $report['nominal_frequency_Hz'] ?? '50'; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tesise ait proje var mı?</label><br>
                            <?php $val = $report['proje_var_mi'] ?? $facility_info['proje_var_mi'] ?? 0; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="proje_var_mi" value="1" <?php echo ($val == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Var</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="proje_var_mi" value="0" <?php echo ($val == 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Yok</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tek hat şeması var mı?</label><br>
                            <?php $val = $report['sema_var_mi'] ?? $facility_info['sema_var_mi'] ?? 0; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="sema_var_mi" value="1" <?php echo ($val == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Var</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="sema_var_mi" value="0" <?php echo ($val == 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Yok</label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kontrol Nedeni</label><br>
                            <?php $val = $report['control_reason'] ?? $facility_info['control_reason'] ?? ''; ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="control_reason"
                                    value="Periyodik Kontrol" <?php echo ($val == 'Periyodik Kontrol') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Periyodik Kontrol</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="control_reason" value="İlk Kontrol"
                                    <?php echo ($val == 'İlk Kontrol') ? 'checked' : ''; ?>>
                                <label class="form-check-label">İlk Kontrol</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Topraklayıcı Tipi</label><br>
                            <?php $val = $report['grounding_type'] ?? $facility_info['grounding_type'] ?? ''; ?>
                            <?php foreach (['Ring', 'Yüzeysel', 'Derin', 'Belirlenemedi', 'Temel'] as $opt): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="grounding_type"
                                        value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo $opt; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Yapı Cinsi</label><br>
                            <?php $val = $report['building_type'] ?? $facility_info['yapi_cinsi'] ?? ''; ?>
                            <?php foreach (['Ev', 'Ticari', 'Endüstri', 'Diğer'] as $opt): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="building_type"
                                        value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo $opt; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ekipmanın kullanım amacı</label>
                            <input type="text" class="form-control" name="usage_purpose"
                                value="<?php echo $report['usage_purpose'] ?? $facility_info['kullanim_amaci'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Hava durumu ve sıcaklık</label>
                            <input type="text" class="form-control" name="weather_condition"
                                value="<?php echo $report['weather_condition'] ?? $facility_info['weather_condition'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Zemin nem durumu</label>
                            <input type="text" class="form-control" name="ground_moisture"
                                value="<?php echo $report['ground_moisture'] ?? $facility_info['ground_moisture'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row mb-3 border-top pt-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">İletken Tipi</label><br>
                            <?php $val = $report['conductor_type'] ?? ''; ?>
                            <?php foreach (['DA', '2 kutup', '3 kutup', 'Diğer'] as $opt): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="conductor_type"
                                        value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo $opt; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Faz iletkenlerinin sayısı ve tipi</label><br>
                            <?php $val = $report['phase_count_type'] ?? ''; ?>
                            <?php foreach (['AA', '1 faz, 2 tel', '1 faz, 3 tel', '2 faz, 3 tel', '3 faz, 3 tel', '3 faz, 4 tel'] as $opt): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="phase_count_type"
                                        value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo $opt; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Temel topraklama direnci (Ω)</label>
                                <input type="text" class="form-control" name="grounding_resistance"
                                    value="<?php echo $report['grounding_resistance'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">İlave topraklama elektrotu detayları (varsa)</label>
                                <input type="text" class="form-control" name="additional_electrode_details"
                                    value="<?php echo $report['additional_electrode_details'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Sistem topraklama iletkeni ve kesiti</label>
                                <input type="text" class="form-control" name="system_grounding_conductor"
                                    value="<?php echo $report['system_grounding_conductor'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ana eşpotansiyel iletkeni ve kesiti</label>
                                <input type="text" class="form-control" name="main_equipotential_conductor"
                                    value="<?php echo $report['main_equipotential_conductor'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Son kontrol tarihi</label>
                                <input type="date" class="form-control" name="prev_control_date"
                                    value="<?php echo $report['prev_control_date'] ?? $facility_info['son_kontrol_tarihi'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Besleme Kaynağı Karakteristikleri</label>
                            <div class="mb-2">
                                <small>Hata akımı olasılığı IF (kA)</small>
                                <input type="text" class="form-control form-control-sm" name="fault_current_kA"
                                    value="<?php echo $report['fault_current_kA'] ?? ''; ?>">
                            </div>
                            <div class="mb-2">
                                <small>Dış çevrim empedansı ZE (Ω)</small>
                                <input type="text" class="form-control form-control-sm" name="external_loop_impedance"
                                    value="<?php echo $report['external_loop_impedance'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">TT-TN-S Şebeke Ana RCD</label>
                            <div class="mb-2">
                                <small>Anma akımı (In)</small>
                                <input type="text" class="form-control form-control-sm" name="main_rcd_rating"
                                    value="<?php echo $report['main_rcd_rating'] ?? ''; ?>">
                            </div>
                            <div class="mb-2">
                                <small>Test akımı (mA) ve süresi (ms)</small>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" name="main_rcd_test_mA"
                                        value="<?php echo $report['main_rcd_test_mA'] ?? ''; ?>" placeholder="mA">
                                    <input type="text" class="form-control" name="main_rcd_test_ms"
                                        value="<?php echo $report['main_rcd_test_ms'] ?? ''; ?>" placeholder="ms">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ana Kesici Karakteristikleri</label>
                            <div class="mb-2">
                                <small>Tip</small>
                                <input type="text" class="form-control form-control-sm" name="main_breaker_type"
                                    value="<?php echo $report['main_breaker_type'] ?? ''; ?>">
                            </div>
                            <div class="mb-2">
                                <small>Nominal Akım (In)</small>
                                <input type="text" class="form-control form-control-sm" name="main_breaker_rating"
                                    value="<?php echo $report['main_breaker_rating'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5>2.2 Tespit Edilen Bilgiler</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tesisatta kapsamlı değişiklik var mı? (%20)</label><br>
                            <?php $val = $report['installation_change'] ?? 0; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="installation_change" value="1" <?php echo ($val == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Var</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="installation_change" value="0" <?php echo ($val == 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Yok</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Aşırı gerilim koruma cihazları (DKD/SPD)?</label><br>
                            <?php $val = $report['has_spd'] ?? 0; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="has_spd" value="1" <?php echo ($val == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Evet</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="has_spd" value="0" <?php echo ($val == 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Hayır</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Doğrudan dokunmaya karşı koruma önlemleri</label>
                        <?php $selected_ms = isset($report['protection_measures']) ? explode(',', $report['protection_measures']) : []; ?>
                        <?php $ms_opts = ['Gerilim altındaki bölümlerin yalıtılması', 'Muhafaza (IPXY, pano kilidi, uyarı vb.)', 'Engel', 'El ulaşma uzaklığı dışına yerleştirme', 'İlave koruma', '30 mA RCD']; ?>
                        <div class="row">
                            <?php foreach ($ms_opts as $opt): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="protection_measures[]"
                                            value="<?php echo $opt; ?>" <?php echo in_array($opt, $selected_ms) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">
                                            <?php echo $opt; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Bir önceki periyodik kontrol etiketi var mı?</label><br>
                        <?php $val = $report['prev_label_exists'] ?? 0; ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="prev_label_exists" value="1" <?php echo ($val == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Var</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="prev_label_exists" value="0" <?php echo ($val == 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Yok</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Bölüm: Termal Kamera Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c3">3. Termal Kamera Bilgileri</button></h2>
            <div id="c3" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Termal Kamera Seçiniz</label>
                        <select class="form-select" name="thermal_camera_id">
                            <option value="">Seçiniz</option>
                            <?php foreach ($thermal_cameras as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo (isset($report['thermal_camera_id']) && $report['thermal_camera_id'] == $d['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['device_name'] . ' (' . $d['serial_no'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small><a href="../cihazlar.php" target="_blank">Cihaz Listesini Düzenle (Termal Kamera
                                İşaretleyin)</a></small>
                    </div>
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
                            <label class="form-label">1. Cihaz</label>
                            <select class="form-select" name="device1_id">
                                <option value="">Seçiniz</option>
                                <?php foreach ($measuring_devices as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo (isset($report['device1_id']) && $report['device1_id'] == $d['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['device_name'] . ' (' . $d['serial_no'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">2. Cihaz (Varsa)</label>
                            <select class="form-select" name="device2_id">
                                <option value="">Seçiniz</option>
                                <?php foreach ($measuring_devices as $d): ?>
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

        <!-- 5. Bölüm: Gözle Muayene -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c5">5. Gözle Muayene</button></h2>
            <div id="c5" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <p class="text-muted text-center p-3">Bu bölüm (Gözle Muayene) gelecek güncellemelerde aktif
                        edilecektir.</p>
                </div>
            </div>
        </div>

        <!-- 6. Bölüm: Test Değerleri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c6">6. Test Değerleri</button></h2>
            <div id="c6" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <p class="text-muted text-center p-3">Bu bölüm (Test Değerleri) gelecek güncellemelerde aktif
                        edilecektir.</p>
                </div>
            </div>
        </div>

        <!-- 7. Bölüm: Kusur Açıklamaları -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c7">7. Kusur Açıklamaları</button></h2>
            <div id="c7" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kusur Açıklamaları</label>
                        <textarea class="form-control" name="defects" rows="4"
                            placeholder="Tespit edilen kusurları buraya yazınız..."><?php echo $report['defects'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 8. Bölüm: Ekipman Fotoğrafları -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c8">8. Ekipman Fotoğrafları</button></h2>
            <div id="c8" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <p class="text-muted text-center p-3">Bu bölüm (Fotoğraf Yükleme) gelecek güncellemelerde aktif
                        edilecektir.</p>
                </div>
            </div>
        </div>

        <!-- 9. Bölüm: Notlar -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c9">9. Notlar</button></h2>
            <div id="c9" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notlar</label>
                        <textarea class="form-control" name="notes" rows="4"
                            placeholder="Eklemek istediğiniz notları buraya yazınız..."><?php echo $report['notes'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 10. Bölüm: Sonuç ve Kanaat -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c10">10. Sonuç ve Kanaat</button></h2>
            <div id="c10" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body bg-light">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Genel Sonuç Değerlendirmesi</label>
                        <select class="form-select" name="result">
                            <option value="UYGUNDUR" <?php echo ($report['result'] ?? '') == 'UYGUNDUR' ? 'selected' : ''; ?>>Kullanımı UYGUNDUR</option>
                            <option value="UYGUN DEGILDIR" <?php echo ($report['result'] ?? '') == 'UYGUN DEGILDIR' ? 'selected' : ''; ?>>Kullanımı UYGUN DEĞİLDİR</option>
                        </select>
                        <small class="text-muted d-block mt-2">Bu seçim rapor çıktısındaki "Sonuç ve Kanaat" metnini
                            (ağır kusur tanımları dahil) otomatik olarak güncelleyecektir.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 11. Bölüm: Yetkili Kişi Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c11">11. Yetkili Kişi Bilgileri ve Onay</button></h2>
            <div id="c11" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kontrolü Yapan Yetkili Kişi</label>
                        <select class="form-select" name="authorized_person_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($authorized_persons as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo (isset($report['authorized_person_id']) && $report['authorized_person_id'] == $p['id']) ? 'selected' : ''; ?>>
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