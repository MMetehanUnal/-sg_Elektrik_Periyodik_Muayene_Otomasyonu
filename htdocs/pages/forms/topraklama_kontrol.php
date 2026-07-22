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
    $stmt = $pdo->prepare("SELECT * FROM grounding_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$report_id, $kurum_id]);
    $report = $stmt->fetch();
    if (!$report)
        redirect('topraklama_kontrol.php'); // Invalid ID
}

// Fetch Institution Defaults
$stmt = $pdo->prepare("SELECT * FROM institutions WHERE id = ?");
$stmt->execute([$kurum_id]);
$kurum = $stmt->fetch();

// Fetch Devices
$stmt = $pdo->prepare("SELECT * FROM measurement_devices WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$devices = $stmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic fields
    $report_date = cleanInput($_POST['report_date']);
    $start_date = cleanInput($_POST['start_date']);
    $end_date = cleanInput($_POST['end_date']);
    $next_control_date = cleanInput($_POST['next_control_date']);
    $isg_katip_id = cleanInput($_POST['isg_katip_id']);
    $firma_adi_eki = cleanInput($_POST['firma_adi_eki'] ?? '');

    // 2.1
    $control_reason = cleanInput($_POST['control_reason']);
    $grounding_type = cleanInput($_POST['grounding_type']);
    $weather = cleanInput($_POST['weather']);
    $soil_moisture = cleanInput($_POST['soil_moisture']);
    $sebeke_tipi = cleanInput($_POST['sebeke_tipi']);
    $proje_var_mi = isset($_POST['proje_var_mi']) ? cleanInput($_POST['proje_var_mi']) : 0;
    $sema_var_mi = isset($_POST['sema_var_mi']) ? cleanInput($_POST['sema_var_mi']) : 0;
    $yapi_cinsi = cleanInput($_POST['yapi_cinsi']);
    $protection_measure = cleanInput($_POST['protection_measure']);

    // 2.2
    $changes_exist = isset($_POST['changes_exist']) ? cleanInput($_POST['changes_exist']) : 0;
    $prev_label_exists = isset($_POST['prev_label_exists']) ? cleanInput($_POST['prev_label_exists']) : 0;
    $panel_id = cleanInput($_POST['panel_id']);

    // 3. Devices
    $device1_id = !empty($_POST['device1_id']) ? cleanInput($_POST['device1_id']) : null;
    $device2_id = !empty($_POST['device2_id']) ? cleanInput($_POST['device2_id']) : null;

    // 5. Method
    $measurement_method = cleanInput($_POST['measurement_method']);

    // New fields
    $project_info = cleanInput($_POST['project_info']);
    $prev_control_date = !empty($_POST['prev_control_date']) ? cleanInput($_POST['prev_control_date']) : null;

    // 6, 7, 8
    $defects = cleanInput($_POST['defects']);
    $notes = cleanInput($_POST['notes']);
    $result = cleanInput($_POST['result']);
    $result_notes_selection = isset($_POST['result_notes']) ? implode(',', $_POST['result_notes']) : '';

    // 9
    $authorized_person_id = cleanInput($_POST['authorized_person_id']);

    // Determine insert or update
    try {
        // 1. Update Facility Info (General fields)
        $stmt_fac = $pdo->prepare("UPDATE facility_info SET 
            sebeke_tipi=?, proje_var_mi=?, sema_var_mi=?, yapi_cinsi=? 
            WHERE kurum_id=?");
        $stmt_fac->execute([$sebeke_tipi, $proje_var_mi, $sema_var_mi, $yapi_cinsi, $kurum_id]);

        if ($report_id) {
            // 2a. Update Grounding Report
            $sql = "UPDATE grounding_reports SET 
                report_date=?, start_date=?, end_date=?, next_control_date=?, isg_katip_id=?,
                firma_adi_eki=?,
                control_reason=?, grounding_type=?, weather=?, soil_moisture=?, 
                protection_measure=?,
                changes_exist=?, prev_label_exists=?, panel_id=?, 
                device1_id=?, device2_id=?, 
                measurement_method=?, 
                project_info=?, prev_control_date=?,
                defects=?, notes=?, result=?, result_notes_selection=?, authorized_person_id=?
                WHERE id=? AND kurum_id=?";
            $params = [
                $report_date,
                $start_date,
                $end_date,
                $next_control_date,
                $isg_katip_id,
                $firma_adi_eki,
                $control_reason,
                $grounding_type,
                $weather,
                $soil_moisture,
                $protection_measure,
                $changes_exist,
                $prev_label_exists,
                $panel_id,
                $device1_id,
                $device2_id,
                $measurement_method,
                $project_info,
                $prev_control_date,
                $defects,
                $notes,
                $result,
                $result_notes_selection,
                $authorized_person_id,
                $report_id,
                $kurum_id
            ];
        } else {
            // 2b. Insert Grounding Report
            $stmt = $pdo->prepare("SELECT il_kodu, kurum_kodu FROM institutions WHERE id = ?");
            $stmt->execute([$kurum_id]);
            $k_codes = $stmt->fetch();
            $report_no = $k_codes['il_kodu'] . '-' . $k_codes['kurum_kodu'] . '-t-' . time(); // Simple unique suffix

            $sql = "INSERT INTO grounding_reports 
                (kurum_id, report_no, report_date, start_date, end_date, next_control_date, isg_katip_id, 
                firma_adi_eki,
                control_reason, grounding_type, weather, soil_moisture, 
                protection_measure, changes_exist, prev_label_exists, panel_id, 
                device1_id, device2_id, measurement_method, 
                project_info, prev_control_date,
                defects, notes, result, result_notes_selection, authorized_person_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $kurum_id,
                $report_no,
                $report_date,
                $start_date,
                $end_date,
                $next_control_date,
                $isg_katip_id,
                $firma_adi_eki,
                $control_reason,
                $grounding_type,
                $weather,
                $soil_moisture,
                $protection_measure,
                $changes_exist,
                $prev_label_exists,
                $panel_id,
                $device1_id,
                $device2_id,
                $measurement_method,
                $project_info,
                $prev_control_date,
                $defects,
                $notes,
                $result,
                $result_notes_selection,
                $authorized_person_id
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if (!$report_id)
            $report_id = $pdo->lastInsertId();

        redirect("topraklama_olcumler_5_1.php?report_id=$report_id");

    } catch (PDOException $e) {
        $save_error = "Hata: " . $e->getMessage();
    }
}


// Fetch Authorized Persons
$stmt = $pdo->prepare("SELECT * FROM authorized_persons");
$stmt->execute();
$authorized_persons = $stmt->fetchAll();

// Fetch Facility Info for defaults
$stmt = $pdo->prepare("SELECT * FROM facility_info WHERE kurum_id = ?");
$stmt->execute([$kurum_id]);
$facility_info = $stmt->fetch();

// Fetch unique firma_adi_eki from internal_installation_reports for datalist autocomplete
$stmt_ekler = $pdo->prepare("SELECT DISTINCT firma_adi_eki FROM internal_installation_reports WHERE kurum_id = ? AND firma_adi_eki IS NOT NULL AND firma_adi_eki != '' ORDER BY firma_adi_eki ASC");
$stmt_ekler->execute([$kurum_id]);
$ic_tesisat_ekleri = $stmt_ekler->fetchAll(PDO::FETCH_COLUMN);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Topraklama Kontrol Formu</h1>
    <a href="/pages/raporlar.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Vazgeç ve Raporlara Dön
    </a>
</div>

<?php if (isset($save_error)): ?>
    <div class="alert alert-danger"><?php echo $save_error; ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="accordion" id="accordionForm">

        <!-- 1. Firma Bilgileri (Automatic & Dates) -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="h1"><button class="accordion-button" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c1">1. Firma Bilgileri</button></h2>
            <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Firma Adı Eki</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="firma_adi_eki" id="firma_adi_eki" list="ic_tesisat_ekleri"
                                    value="<?php echo htmlspecialchars($report['firma_adi_eki'] ?? ''); ?>"
                                    placeholder="Örn: A Blok, 2. Kısım">
                                <button class="btn btn-outline-secondary" type="button" id="btn_fetch_ic_tesisat" title="İç Tesisattan Bilgileri Getir">
                                    <i class="fas fa-sync-alt"></i> Getir
                                </button>
                            </div>
                            <datalist id="ic_tesisat_ekleri">
                                <?php foreach ($ic_tesisat_ekleri as $ek): ?>
                                    <option value="<?php echo htmlspecialchars($ek); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
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
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Gelecek Kontrol</label>
                            <input type="date" class="form-control" name="next_control_date"
                                value="<?php echo $report['next_control_date'] ?? $facility_info['next_control_date'] ?? $kurum['next_control_date'] ?? date('Y-m-d', strtotime('+1 year')); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Ekipman Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="h2"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c2">2. Ekipman Bilgileri</button></h2>
            <div id="c2" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">

                    <h5>2.1 Etiket ve Detay Bilgileri</h5>
                    <!-- Şebeke Tipi -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Şebeke Tipi</label><br>
                        <?php $val = $report['sebeke_tipi'] ?? $facility_info['sebeke_tipi'] ?? ''; ?>
                        <?php foreach (['TT', 'IT', 'TN', 'TN-CS', 'TN-C', 'TN-S'] as $opt): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="sebeke_tipi" value="<?php echo $opt; ?>"
                                    <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                <label class="form-check-label"><?php echo $opt; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Proje & Şema -->
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

                    <!-- Kontrol Nedeni & Topraklayıcı Tipi -->
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
                            <?php foreach (['Ring', 'Yüzeysel', 'Temel', 'Derin', 'Belirlenemedi'] as $opt): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="grounding_type"
                                        value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                    <label class="form-check-label"><?php echo $opt; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Yapı Cinsi -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Yapı Cinsi</label><br>
                        <?php $val = $report['yapi_cinsi'] ?? $facility_info['yapi_cinsi'] ?? ''; ?>
                        <?php foreach (['Ev', 'Ticari', 'Endüstri', 'Diğer'] as $opt): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="yapi_cinsi" value="<?php echo $opt; ?>"
                                    <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                <label class="form-check-label"><?php echo $opt; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Protection Measure -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dolaylı dokunmaya karşı koruma önlemi</label><br>
                        <?php $val = $report['protection_measure'] ?? ''; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="protection_measure"
                                value="Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN, IT)" <?php echo ($val == 'Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN, IT)') ? 'checked' : ''; ?>>
                            <label class="form-check-label">Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi
                                (TT, TN, IT)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="protection_measure"
                                value="Koruyucu yalıtma (Sınıf II veya zemin yalıtımı)" <?php echo ($val == 'Koruyucu yalıtma (Sınıf II veya zemin yalıtımı)') ? 'checked' : ''; ?>>
                            <label class="form-check-label">Koruyucu yalıtma (Sınıf II veya zemin yalıtımı)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="protection_measure"
                                value="Koruyucu ayırma (İzolasyon trafosu)" <?php echo ($val == 'Koruyucu ayırma (İzolasyon trafosu)') ? 'checked' : ''; ?>>
                            <label class="form-check-label">Koruyucu ayırma (İzolasyon trafosu)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="protection_measure"
                                value="Küçük gerilim <50 V" <?php echo ($val == 'Küçük gerilim <50 V') ? 'checked' : ''; ?>>
                            <label class="form-check-label">Küçük gerilim < 50 V</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hava Durumu</label>
                            <input type="text" class="form-control" name="weather"
                                value="<?php echo $report['weather'] ?? $facility_info['weather_condition'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Toprak Nem Durumu</label>
                            <input type="text" class="form-control" name="soil_moisture"
                                value="<?php echo $report['soil_moisture'] ?? $facility_info['ground_moisture'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Proje Bilgisi</label>
                        <input type="text" class="form-control" name="project_info"
                            placeholder="Proje tarih, no vb. bilgileri giriniz"
                            value="<?php echo $report['project_info'] ?? ''; ?>">
                    </div>

                    <hr>
                    <h5>2.2 Tespit Edilen Bilgiler</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tesisatta değişiklik var mı?</label><br>
                            <?php $val = $report['changes_exist'] ?? 0; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="changes_exist" value="1" <?php echo ($val == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Var</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="changes_exist" value="0" <?php echo ($val == 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Yok</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Önceki etiket var mı?</label><br>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Pano/Ekipman ID</label>
                            <input type="text" class="form-control" name="panel_id"
                                value="<?php echo $report['panel_id'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Son Kontrol Tarihi</label>
                            <input type="date" class="form-control" name="prev_control_date"
                                value="<?php echo $report['prev_control_date'] ?? $facility_info['son_kontrol_tarihi'] ?? ''; ?>">
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- 3. Ölçüm Aletleri -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="h3"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c3">3. Ölçüm Aletleri</button></h2>
            <div id="c3" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">1. Cihaz</label>
                            <select class="form-select" name="device1_id">
                                <option value="">Seçiniz</option>
                                <?php foreach ($devices as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo ((isset($report['device1_id']) && $report['device1_id'] == $d['id']) || (empty($report) && !empty($facility_defaults['default_device_id']) && $facility_defaults['default_device_id'] == $d['id'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['device_name'] . ' (' . $d['serial_no'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small><a href="../cihazlar.php" target="_blank">Yeni Cihaz Ekle</a></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">2. Cihaz (Varsa)</label>
                            <select class="form-select" name="device2_id">
                                <option value="">Seçiniz</option>
                                <?php foreach ($devices as $d): ?>
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

        <!-- 4. Test Değerleri (Info) -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="h4"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c4">4. Test Değerleri (Bilgi)</button></h2>
            <div id="c4" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="alert alert-secondary text-center small">
                        Bu bölümde veri girişi yapılmaz. Raporda yer alan kısaltmaların açıklamaları yer alır.
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Metod -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="h5"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c5">5. Ölçüm Metodu</button></h2>
            <div id="c5" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ölçüm Metodu</label><br>
                        <?php $val = $report['measurement_method'] ?? 'Cevrim empedansi'; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="measurement_method"
                                value="Cevrim empedansi" <?php echo ($val == 'Cevrim empedansi') ? 'checked' : ''; ?>>
                            <label class="form-check-label">Çevrim empedansı</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="measurement_method"
                                value="3 Uclu topraklama" <?php echo ($val == '3 Uclu topraklama') ? 'checked' : ''; ?>>
                            <label class="form-check-label">3 Uçlu topraklama</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="measurement_method" value="Klamp metodu"
                                <?php echo ($val == 'Klamp metodu') ? 'checked' : ''; ?>>
                            <label class="form-check-label">Klamp metodu (Çoklu topraklayıcılı)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6, 7, 8 Sonuç -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="h6"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c6">6-8. Sonuç ve Kanaat</button></h2>
            <div id="c6" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label">6. Kusurlar</label>
                        <textarea class="form-control" name="defects"
                            rows="3"><?php echo $report['defects'] ?? ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">7. Notlar</label>
                        <textarea class="form-control" name="notes"
                            rows="3"><?php echo $report['notes'] ?? ''; ?></textarea>
                    </div>
                    <div class="mb-3 border p-3 bg-light">
                        <label class="form-label fw-bold">8. Sonuç</label>
                        <p class="small">Periyodik kontrol tarihi itibariyle yukarıda teknik özellikleri belirtilen AG
                            Topraklama Tesisatı muayenesi sonrasında mevcut şartlar altında kullanımı 1 yıl süreyle;</p>

                        <select class="form-select mb-3" name="result">
                            <?php $sel = $report['result'] ?? ''; ?>
                            <option value="UYGUNDUR" <?php echo ($sel == 'UYGUNDUR' || $sel == 'GÜVENLİDİR') ? 'selected' : ''; ?>>UYGUNDUR</option>
                            <option value="UYGUN DEGILDIR" <?php echo ($sel == 'UYGUN DEGILDIR' || $sel == 'UYGUN DEĞİLDİR' || $sel == 'GÜVENLİ DEĞİLDİR') ? 'selected' : ''; ?>>UYGUN
                                DEĞİLDİR</option>
                        </select>

                        <p class="small">Tespit edilen hafif kusurların bir sonraki periyodik kontrol tarihine kadar
                            giderilmesi gereklidir.</p>

                        <label class="form-label fw-bold mt-2">Ağır Kusur Açıklamaları / Not Seçimi:</label>
                        <div class="alert alert-warning small">
                            <?php
                            $selected_notes = isset($report['result_notes_selection']) ? explode(',', $report['result_notes_selection']) : [];
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="1" <?php echo in_array('1', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-1: Uygun.</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="2" <?php echo in_array('2', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-2: Güvenlik şartı sağlanamadığından uygun değildir.
                                    (Ağır kusur)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="3" <?php echo in_array('3', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-3: Topraklama bağlantısı yok kontrol edilmelidir.
                                    (Ağır kusur)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="4" <?php echo in_array('4', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-4: Artık akım anahtarı kullanıldığı ve faal olduğu
                                    için uygundur.</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="5" <?php echo in_array('5', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-5: TT/TN şebekede RCD zorunluluğu... (Ağır
                                    kusur)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="6" <?php echo in_array('6', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-6: 32A üzeri devrelerde doğal kaçak akım tahkiki...
                                    (Ağır kusur)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="7" <?php echo in_array('7', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-7: RCD gecikmeli tip değil.</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="8" <?php echo in_array('8', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-8: Nötr-toprak gerilimi yüksek. (Ağır kusur)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="9" <?php echo in_array('9', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-9: TN-S/TN-CS PE ve N birleştirilmesi uygun değil.
                                    (Ağır kusur)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="10" <?php echo in_array('10', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-10: Priz üzerinde Nötr-Toprak birleşikliği... (Ağır
                                    kusur)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="result_notes[]" value="11" <?php echo in_array('11', $selected_notes) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Not-11: Pano gövde–kapak köprüsü olmadığından
                                    yetersizdir. (Ağır kusur)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 9. Yetkili Kişi -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="h9"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c9">9. Yetkili Kişi</button></h2>
            <div id="c9" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label">Yetkili Kişi Seçiniz</label>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnFetch = document.getElementById('btn_fetch_ic_tesisat');
    const inputFirmaEki = document.getElementById('firma_adi_eki');

    if (btnFetch && inputFirmaEki) {
        btnFetch.addEventListener('click', function() {
            const firmaEki = inputFirmaEki.value.trim();
            if (!firmaEki) {
                alert('Lütfen sorgulamak istediğiniz Firma Adı Eki bilgisini girin.');
                return;
            }

            // Disable button and show spinner
            const originalHtml = btnFetch.innerHTML;
            btnFetch.disabled = true;
            btnFetch.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(`get_ic_tesisat_firma_bilgileri.php?firma_adi_eki=${encodeURIComponent(firmaEki)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Ağ hatası oluştu.');
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success && result.data) {
                        const data = result.data;
                        
                        // Map values to fields
                        if (data.report_date) {
                            const reportDateEl = document.querySelector('input[name="report_date"]');
                            if (reportDateEl) reportDateEl.value = data.report_date;
                        }
                        if (data.isg_katip_id) {
                            const isgKatipEl = document.querySelector('input[name="isg_katip_id"]');
                            if (isgKatipEl) isgKatipEl.value = data.isg_katip_id;
                        }
                        if (data.start_date) {
                            const startDateEl = document.querySelector('input[name="start_date"]');
                            if (startDateEl) startDateEl.value = data.start_date;
                        }
                        if (data.end_date) {
                            const endDateEl = document.querySelector('input[name="end_date"]');
                            if (endDateEl) endDateEl.value = data.end_date;
                        }
                        if (data.next_control_date) {
                            const nextControlEl = document.querySelector('input[name="next_control_date"]');
                            if (nextControlEl) nextControlEl.value = data.next_control_date;
                        }

                        // Success notification / visually highlight the inputs
                        const inputsToHighlight = ['report_date', 'isg_katip_id', 'start_date', 'end_date', 'next_control_date'];
                        inputsToHighlight.forEach(name => {
                            const el = document.querySelector(`input[name="${name}"]`);
                            if (el) {
                                el.style.transition = 'background-color 0.5s';
                                el.style.backgroundColor = '#d1e7dd'; // light green
                                setTimeout(() => {
                                    el.style.backgroundColor = '';
                                }, 1500);
                            }
                        });
                    } else {
                        alert(result.error || 'İç tesisat raporu bulunamadı.');
                    }
                })
                .catch(error => {
                    alert('Bir hata oluştu: ' + error.message);
                })
                .finally(() => {
                    btnFetch.disabled = false;
                    btnFetch.innerHTML = originalHtml;
                });
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>