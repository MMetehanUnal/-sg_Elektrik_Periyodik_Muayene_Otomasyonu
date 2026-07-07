<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

// Auto-create default fields in facility_info if not exists
try {
    $pdo->exec("ALTER TABLE facility_info ADD COLUMN default_authorized_person_id INT DEFAULT NULL");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE facility_info ADD COLUMN default_device_id INT DEFAULT NULL");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE facility_info ADD COLUMN default_thermal_device_id INT DEFAULT NULL");
} catch (PDOException $e) {}

// Check if institution is selected
if (!isset($_SESSION['active_institution_id'])) {
    redirect('/pages/tesis_secimi.php');
}

$kurum_id = $_SESSION['active_institution_id'];
$success_msg = '';
$warning_msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enerji_saglayan = cleanInput($_POST['enerji_saglayan'] ?? '');
    $sebeke_tipi = cleanInput($_POST['sebeke_tipi'] ?? '');
    $sebeke_gerilimi = cleanInput($_POST['sebeke_gerilimi'] ?? '');
    $proje_var_mi = isset($_POST['proje_var_mi']) ? 1 : 0;
    $sema_var_mi = isset($_POST['sema_var_mi']) ? 1 : 0;
    $yapi_cinsi = cleanInput($_POST['yapi_cinsi'] ?? '');
    $kullanim_amaci = cleanInput($_POST['kullanim_amaci'] ?? '');
    $sozlesme_id = cleanInput($_POST['sozlesme_id'] ?? '');
    $son_kontrol_tarihi = !empty($_POST['son_kontrol_tarihi']) ? $_POST['son_kontrol_tarihi'] : null;
    $weather_condition = cleanInput($_POST['weather_condition'] ?? '');
    $ground_moisture = cleanInput($_POST['ground_moisture'] ?? '');
    $grounding_type = cleanInput($_POST['grounding_type'] ?? '');
    $control_reason = cleanInput($_POST['control_reason'] ?? '');
    $next_control_date = !empty($_POST['next_control_date']) ? $_POST['next_control_date'] : null;
    
    $default_authorized_person_id = !empty($_POST['default_authorized_person_id']) ? intval($_POST['default_authorized_person_id']) : null;
    $default_device_id = !empty($_POST['default_device_id']) ? intval($_POST['default_device_id']) : null;
    $default_thermal_device_id = !empty($_POST['default_thermal_device_id']) ? intval($_POST['default_thermal_device_id']) : null;

    // New: Update Institution Start/End Dates (Defaults)
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    if ($start_date || $end_date) {
        $formatted_start = $start_date ? date('Y-m-d H:i:s', strtotime($start_date)) : null;
        $formatted_end = $end_date ? date('Y-m-d H:i:s', strtotime($end_date)) : null;
        $stmt = $pdo->prepare("UPDATE institutions SET start_date=?, end_date=? WHERE id=?");
        $stmt->execute([$formatted_start, $formatted_end, $kurum_id]);
    }

    // Check if record exists
    $stmt = $pdo->prepare("SELECT id FROM facility_info WHERE kurum_id = ?");
    $stmt->execute([$kurum_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE facility_info SET 
            enerji_saglayan=?, sebeke_tipi=?, sebeke_gerilimi=?, proje_var_mi=?, sema_var_mi=?, 
            yapi_cinsi=?, kullanim_amaci=?, sozlesme_id=?, son_kontrol_tarihi=?,
            weather_condition=?, ground_moisture=?, grounding_type=?, control_reason=?, next_control_date=?,
            default_authorized_person_id=?, default_device_id=?, default_thermal_device_id=?
            WHERE kurum_id=?");
        $stmt->execute([
            $enerji_saglayan,
            $sebeke_tipi,
            $sebeke_gerilimi,
            $proje_var_mi,
            $sema_var_mi,
            $yapi_cinsi,
            $kullanim_amaci,
            $sozlesme_id,
            $son_kontrol_tarihi,
            $weather_condition,
            $ground_moisture,
            $grounding_type,
            $control_reason,
            $next_control_date,
            $default_authorized_person_id,
            $default_device_id,
            $default_thermal_device_id,
            $kurum_id
        ]);
        $success_msg = "Bilgiler güncellendi.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO facility_info 
            (kurum_id, enerji_saglayan, sebeke_tipi, sebeke_gerilimi, proje_var_mi, sema_var_mi, 
            yapi_cinsi, kullanim_amaci, sozlesme_id, son_kontrol_tarihi,
            weather_condition, ground_moisture, grounding_type, control_reason, next_control_date,
            default_authorized_person_id, default_device_id, default_thermal_device_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $kurum_id,
            $enerji_saglayan,
            $sebeke_tipi,
            $sebeke_gerilimi,
            $proje_var_mi,
            $sema_var_mi,
            $yapi_cinsi,
            $kullanim_amaci,
            $sozlesme_id,
            $son_kontrol_tarihi,
            $weather_condition,
            $ground_moisture,
            $grounding_type,
            $control_reason,
            $next_control_date,
            $default_authorized_person_id,
            $default_device_id,
            $default_thermal_device_id
        ]);
        $success_msg = "Bilgiler kaydedildi.";
    }
}

// Fetch existing data
$stmt = $pdo->prepare("SELECT * FROM facility_info WHERE kurum_id = ?");
$stmt->execute([$kurum_id]);
$info = $stmt->fetch();

// Fetch institution defaults
$stmt = $pdo->prepare("SELECT start_date, end_date FROM institutions WHERE id = ?");
$stmt->execute([$kurum_id]);
$kurum_defaults = $stmt->fetch() ?: [];

if (!$info) {
    // Default values
    $info = [
        'enerji_saglayan' => '',
        'sebeke_tipi' => '',
        'sebeke_gerilimi' => '',
        'proje_var_mi' => 0,
        'sema_var_mi' => 0,
        'yapi_cinsi' => '',
        'kullanim_amaci' => '',
        'sozlesme_id' => '',
        'son_kontrol_tarihi' => '',
        'weather_condition' => '',
        'ground_moisture' => '',
        'grounding_type' => '',
        'control_reason' => '',
        'next_control_date' => '',
        'default_authorized_person_id' => null,
        'default_device_id' => null,
        'default_thermal_device_id' => null
    ];
    $warning_msg = "Bilgilerin tamamlanması beklenmektedir.";
} else {
    // Check completion (simple check for empty fields)
    if (empty($info['enerji_saglayan']) || empty($info['sebeke_tipi'])) {
        $warning_msg = "Bilgilerin tamamlanması beklenmektedir.";
    } else {
        $success_msg = "Tebrikler! Form tamamlandı.";
    }
}

// Fetch all authorized persons for defaults dropdown
$stmt_ap = $pdo->query("SELECT id, adi_soyadi FROM authorized_persons ORDER BY adi_soyadi ASC");
$authorized_persons = $stmt_ap->fetchAll();

// Fetch all measurement devices for defaults dropdown
$stmt_dev = $pdo->prepare("SELECT id, device_name, serial_no, is_thermal_camera FROM measurement_devices WHERE user_id = ? ORDER BY device_name ASC");
$stmt_dev->execute([$_SESSION['user_id']]);
$all_devices = $stmt_dev->fetchAll();

$measuring_devices = [];
$thermal_cameras = [];
foreach ($all_devices as $d) {
    if ($d['is_thermal_camera']) {
        $thermal_cameras[] = $d;
    } else {
        $measuring_devices[] = $d;
    }
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kurum Bilgileri</h1>
</div>

<?php if ($success_msg): ?>
    <div class="alert alert-success">
        <?php echo $success_msg; ?>
    </div>
<?php endif; ?>
<?php if ($warning_msg && !$success_msg): ?>
    <div class="alert alert-warning">
        <?php echo $warning_msg; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Enerji Sağlayan Kuruluş</label>
                    <input type="text" class="form-control" name="enerji_saglayan"
                        value="<?php echo htmlspecialchars($info['enerji_saglayan']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Şebeke Tipi</label>
                    <select class="form-select" name="sebeke_tipi">
                        <option value="">Seçiniz</option>
                        <?php
                        $types = ['TT', 'IT', 'TN', 'TN-C', 'TN-S', 'TN-C-S'];
                        foreach ($types as $type) {
                            $selected = ($info['sebeke_tipi'] == $type) ? 'selected' : '';
                            echo "<option value='$type' $selected>$type</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Şebeke Gerilimi</label>
                    <input type="text" class="form-control" name="sebeke_gerilimi"
                        value="<?php echo htmlspecialchars($info['sebeke_gerilimi']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Yapı Cinsi</label>
                    <select class="form-select" name="yapi_cinsi">
                        <option value="">Seçiniz</option>
                        <?php
                        $types = ['Ev', 'Ticari', 'Endüstri', 'Diğer'];
                        foreach ($types as $type) {
                            $selected = ($info['yapi_cinsi'] == $type) ? 'selected' : '';
                            echo "<option value='$type' $selected>$type</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Kullanım Amacı</label>
                    <input type="text" class="form-control" name="kullanim_amaci"
                        value="<?php echo htmlspecialchars($info['kullanim_amaci']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">İSG-KATİP Sözleşme ID</label>
                    <input type="text" class="form-control" name="sozlesme_id"
                        value="<?php echo htmlspecialchars($info['sozlesme_id']); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Varsayılan Kontrol Başlangıç Zamanı</label>
                    <?php
                    $s_val = $kurum_defaults['start_date'] ?? null;
                    if ($s_val && substr($s_val, 0, 4) != '0000') {
                        $s_val = date('Y-m-d\TH:i', strtotime($s_val));
                    } else {
                        $s_val = date('Y-m-d') . 'T08:00';
                    }
                    ?>
                    <input type="datetime-local" class="form-control" name="start_date" value="<?php echo $s_val; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Varsayılan Kontrol Bitiş Zamanı</label>
                    <?php
                    $e_val = $kurum_defaults['end_date'] ?? null;
                    if ($e_val && substr($e_val, 0, 4) != '0000') {
                        $e_val = date('Y-m-d\TH:i', strtotime($e_val));
                    } else {
                        $e_val = date('Y-m-d') . 'T18:00';
                    }
                    ?>
                    <input type="datetime-local" class="form-control" name="end_date" value="<?php echo $e_val; ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Varsayılan Yetkili Kişi</label>
                    <select class="form-select" name="default_authorized_person_id">
                        <option value="">Seçiniz (Varsayılan Yok)</option>
                        <?php foreach ($authorized_persons as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($info['default_authorized_person_id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['adi_soyadi']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Varsayılan Ölçüm Cihazı</label>
                    <select class="form-select" name="default_device_id">
                        <option value="">Seçiniz (Varsayılan Yok)</option>
                        <?php foreach ($measuring_devices as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo ($info['default_device_id'] == $d['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['device_name'] . ' (' . $d['serial_no'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Varsayılan Termal Cihaz</label>
                    <select class="form-select" name="default_thermal_device_id">
                        <option value="">Seçiniz (Varsayılan Yok)</option>
                        <?php foreach ($thermal_cameras as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($info['default_thermal_device_id'] == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['device_name'] . ' (' . $c['serial_no'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Hava Durumu ve Sıcaklık</label>
                    <input type="text" class="form-control" name="weather_condition"
                        value="<?php echo htmlspecialchars($info['weather_condition'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Zemin Nem Durumu</label>
                    <input type="text" class="form-control" name="ground_moisture"
                        value="<?php echo htmlspecialchars($info['ground_moisture'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label d-block">Topraklayıcı Tipi</label>
                    <?php
                    $g_types = ['Ring', 'Yüzeysel', 'Temel', 'Derin', 'Belirlenemedi'];
                    foreach ($g_types as $gt): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="grounding_type" id="gt_<?php echo $gt; ?>"
                                value="<?php echo $gt; ?>" <?php echo ($info['grounding_type'] == $gt) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="gt_<?php echo $gt; ?>"><?php echo $gt; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label d-block">Kontrol Nedeni</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="control_reason" id="cr_periyodik"
                            value="Periyodik Kontrol" <?php echo ($info['control_reason'] == 'Periyodik Kontrol') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cr_periyodik">Periyodik Kontrol</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="control_reason" id="cr_ilk"
                            value="İlk Kontrol" <?php echo ($info['control_reason'] == 'İlk Kontrol') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cr_ilk">İlk Kontrol</label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Son Kontrol Tarihi</label>
                    <input type="date" class="form-control" name="son_kontrol_tarihi"
                        value="<?php echo $info['son_kontrol_tarihi']; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bir Sonraki Periyodik Kontrol Tarihi</label>
                    <input type="date" class="form-control" name="next_control_date"
                        value="<?php echo $info['next_control_date']; ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3 pt-4">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="proje_var_mi" id="proje" value="1" <?php echo $info['proje_var_mi'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="proje">Tesise ait proje var mı?</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="sema_var_mi" id="sema" value="1" <?php echo $info['sema_var_mi'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sema">Tek hat şeması var mı?</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>