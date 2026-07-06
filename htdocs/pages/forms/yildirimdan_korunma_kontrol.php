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
    $stmt = $pdo->prepare("SELECT * FROM lightning_protection_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$report_id, $kurum_id]);
    $report = $stmt->fetch();
    if (!$report)
        redirect('yildirimdan_korunma_kontrol.php'); // Invalid ID
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

$s4_answers = [];
if ($report_id) {
    $stmt = $pdo->prepare("SELECT question_key, answer FROM lightning_protection_section4 WHERE report_id = ?");
    $stmt->execute([$report_id]);
    foreach ($stmt->fetchAll() as $row) {
        $s4_answers[$row['question_key']] = $row['answer'];
    }
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
    $start_date = cleanInput($_POST['start_date']);
    $end_date = cleanInput($_POST['end_date']);
    $next_control_date = cleanInput($_POST['next_control_date']);
    $isg_katip_id = cleanInput($_POST['isg_katip_id'] ?? '');
    $firma_adi_eki = cleanInput($_POST['firma_adi_eki'] ?? '');

    // Section 2.1
    $energy_provider = cleanInput($_POST['energy_provider'] ?? '');
    $sebeke_tipi = cleanInput($_POST['sebeke_tipi'] ?? '');
    $sebeke_voltage = cleanInput($_POST['sebeke_voltage'] ?? '');
    $has_project = cleanInput($_POST['has_project'] ?? 'Yok');
    $project_details = cleanInput($_POST['project_details'] ?? '');
    $has_risk_analysis = cleanInput($_POST['has_risk_analysis'] ?? 'Yok');
    $control_reason = cleanInput($_POST['control_reason'] ?? '');
    $grounding_type = cleanInput($_POST['grounding_type'] ?? '');
    $building_type = cleanInput($_POST['building_type'] ?? '');
    $usage_purpose_yks_type = cleanInput($_POST['usage_purpose_yks_type'] ?? '');
    $prev_control_date = !empty($_POST['prev_control_date']) ? cleanInput($_POST['prev_control_date']) : null;
    $weather_condition = cleanInput($_POST['weather_condition'] ?? '');
    $ground_moisture = cleanInput($_POST['ground_moisture'] ?? '');

    // Section 2.2
    $installation_change = cleanInput($_POST['installation_change'] ?? 'Yok');
    $prev_label_exists = cleanInput($_POST['prev_label_exists'] ?? 'Yok');
    $equipment_identification = cleanInput($_POST['equipment_identification'] ?? '');
    $protection_system_type = cleanInput($_POST['protection_system_type'] ?? '');
    $protection_level_eps = cleanInput($_POST['protection_level_eps'] ?? '');
    $building_usage_details = cleanInput($_POST['building_usage_details'] ?? '');

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
            $sql = "UPDATE lightning_protection_reports SET 
                report_date=?, firma_adi_eki=?, start_date=?, end_date=?, next_control_date=?, isg_katip_id=?,
                energy_provider=?, sebeke_tipi=?, sebeke_voltage=?, has_project=?, project_details=?,
                has_risk_analysis=?, control_reason=?, grounding_type=?, building_type=?, 
                usage_purpose_yks_type=?, prev_control_date=?, weather_condition=?, ground_moisture=?,
                installation_change=?, prev_label_exists=?, equipment_identification=?,
                protection_system_type=?, protection_level_eps=?, building_usage_details=?,
                thermal_camera_id=?, device1_id=?, device2_id=?,
                authorized_person_id=?, defects=?, notes=?, result=?, result_notes_selection=?
                WHERE id=? AND kurum_id=?";
            $params = [
                $report_date,
                $firma_adi_eki,
                $start_date,
                $end_date,
                $next_control_date,
                $isg_katip_id,
                $energy_provider,
                $sebeke_tipi,
                $sebeke_voltage,
                $has_project,
                $project_details,
                $has_risk_analysis,
                $control_reason,
                $grounding_type,
                $building_type,
                $usage_purpose_yks_type,
                $prev_control_date,
                $weather_condition,
                $ground_moisture,
                $installation_change,
                $prev_label_exists,
                $equipment_identification,
                $protection_system_type,
                $protection_level_eps,
                $building_usage_details,
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
            $report_no = $k_codes['il_kodu'] . '-' . $k_codes['kurum_kodu'] . '-yk-' . time();

            $sql = "INSERT INTO lightning_protection_reports 
                (kurum_id, report_no, report_date, firma_adi_eki, start_date, end_date, next_control_date, isg_katip_id, 
                energy_provider, sebeke_tipi, sebeke_voltage, has_project, project_details, 
                has_risk_analysis, control_reason, grounding_type, building_type, 
                usage_purpose_yks_type, prev_control_date, weather_condition, ground_moisture, 
                installation_change, prev_label_exists, equipment_identification, 
                protection_system_type, protection_level_eps, building_usage_details, 
                thermal_camera_id, device1_id, device2_id, 
                authorized_person_id, defects, notes, result, result_notes_selection)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $kurum_id,
                $report_no,
                $report_date,
                $firma_adi_eki,
                $start_date,
                $end_date,
                $next_control_date,
                $isg_katip_id,
                $energy_provider,
                $sebeke_tipi,
                $sebeke_voltage,
                $has_project,
                $project_details,
                $has_risk_analysis,
                $control_reason,
                $grounding_type,
                $building_type,
                $usage_purpose_yks_type,
                $prev_control_date,
                $weather_condition,
                $ground_moisture,
                $installation_change,
                $prev_label_exists,
                $equipment_identification,
                $protection_system_type,
                $protection_level_eps,
                $building_usage_details,
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

        // Section 4 Answers Saving
        if (isset($_POST['s4'])) {
            $pdo->prepare("DELETE FROM lightning_protection_section4 WHERE report_id = ?")->execute([$report_id]);
            $s4stmt = $pdo->prepare("INSERT INTO lightning_protection_section4 (report_id, question_key, answer) VALUES (?, ?, ?)");
            foreach ($_POST['s4'] as $key => $val) {
                if ($val !== '') {
                    $s4stmt->execute([$report_id, $key, $val]);
                }
            }
        }

        redirect("yildirimdan_korunma_kontrol.php?id=$report_id&status=success");
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

// Fetch unique firma_adi_eki from internal_installation_reports for datalist autocomplete
$stmt_ekler = $pdo->prepare("SELECT DISTINCT firma_adi_eki FROM internal_installation_reports WHERE kurum_id = ? AND firma_adi_eki IS NOT NULL AND firma_adi_eki != '' ORDER BY firma_adi_eki ASC");
$stmt_ekler->execute([$kurum_id]);
$ic_tesisat_ekleri = $stmt_ekler->fetchAll(PDO::FETCH_COLUMN);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Yıldırımdan Korunma Kontrol Formu</h1>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <span>Rapor başarıyla kaydedildi.</span>
        <a href="../yildirimdan_korunma_yazdir.php?id=<?php echo $_GET['id']; ?>" target="_blank"
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
                            <label class="form-label">Firma Adı Eki</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="firma_adi_eki" id="firma_adi_eki" list="ic_tesisat_ekleri"
                                    value="<?php echo htmlspecialchars($report['firma_adi_eki'] ?? ''); ?>"
                                    placeholder="Örn: Kuzey Sahası">
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
                        <div class="col-md-3 mb-4">
                            <label class="form-label">Bir Sonraki Periyodik Kontrol Tarihi</label>
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
                    <h5>2.1 Etiket ve Detay Bilgiler</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Enerji sağlayan kuruluş</label>
                            <input type="text" class="form-control" name="energy_provider"
                                value="<?php echo $report['energy_provider'] ?? $facility_info['enerji_saglayan'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Şebeke tipi</label><br>
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
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Şebeke gerilimi</label>
                            <input type="text" class="form-control" name="sebeke_voltage"
                                value="<?php echo $report['sebeke_voltage'] ?? $facility_info['sebeke_gerilimi'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tesise ait kapsama alanı projesi var mı?</label><br>
                            <?php $val = $report['has_project'] ?? 'Yok'; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="has_project" value="Var" <?php echo ($val == 'Var') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Var</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="has_project" value="Yok" <?php echo ($val == 'Yok') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Yok</label>
                            </div>
                            <input type="text" class="form-control mt-2" name="project_details"
                                placeholder="Proje detayları" value="<?php echo $report['project_details'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Risk analizi var mı?</label><br>
                            <?php $val = $report['has_risk_analysis'] ?? 'Yok'; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="has_risk_analysis" value="Var" <?php echo ($val == 'Var') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Var</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="has_risk_analysis" value="Yok" <?php echo ($val == 'Yok') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Yok</label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kontrol nedeni</label><br>
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
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Topraklayıcı tipi</label><br>
                            <?php $val = $report['grounding_type'] ?? $facility_info['grounding_type'] ?? ''; ?>
                            <?php foreach (['Ring', 'Yüzeysel', 'Temel', 'Derin', 'Belirlenemedi'] as $opt): ?>
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
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Yapı cinsi</label><br>
                            <?php $val = $report['building_type'] ?? $facility_info['yapi_cinsi'] ?? ''; ?>
                            <?php foreach (['Ev', 'Ticari', 'Endüstri', 'Diğer'] as $opt): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="building_type"
                                        value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo $opt; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Ekipmanın kullanım amacı ve YKS cinsi</label><br>
                            <?php $val = $report['usage_purpose_yks_type'] ?? ''; ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="usage_purpose_yks_type"
                                    value="Ayrılmış YKS" <?php echo ($val == 'Ayrılmış YKS') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Ayrılmış YKS</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="usage_purpose_yks_type"
                                    value="Ayrılmamış (Eşpotansiyel) YKS" <?php echo ($val == 'Ayrılmamış (Eşpotansiyel) YKS') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Ayrılmamış (Eşpotansiyel) YKS</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Son kontrol tarihi</label>
                            <input type="date" class="form-control" name="prev_control_date"
                                value="<?php echo $report['prev_control_date'] ?? $facility_info['son_kontrol_tarihi'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Hava durumu ve sıcaklığı</label>
                            <input type="text" class="form-control" name="weather_condition"
                                value="<?php echo $report['weather_condition'] ?? $facility_info['weather_condition'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Zemin nem durumu</label>
                            <input type="text" class="form-control" name="ground_moisture"
                                value="<?php echo $report['ground_moisture'] ?? $facility_info['ground_moisture'] ?? ''; ?>">
                        </div>
                    </div>

                    <hr>
                    <h5>2.2 Tespit Edilen Bilgiler</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tesisatta kapsamlı değişiklik var mı?</label><br>
                            <?php $val = $report['installation_change'] ?? 'Yok'; ?>
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
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Önceki periyodik kontrol etiketi var mı?</label><br>
                            <?php $val = $report['prev_label_exists'] ?? 'Yok'; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="prev_label_exists" value="Var" <?php echo ($val == 'Var') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Var</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="prev_label_exists" value="Yok" <?php echo ($val == 'Yok') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Yok</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Ekipman tanımlaması</label>
                            <input type="text" class="form-control" name="equipment_identification"
                                value="<?php echo $report['equipment_identification'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Yıldırımdan korunma tesisatı tipi</label><br>
                        <?php $val = $report['protection_system_type'] ?? ''; ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="protection_system_type"
                                        value="ESE (Aktif-Radyoaktif) Paratoner" <?php echo ($val == 'ESE (Aktif-Radyoaktif) Paratoner') ? 'checked' : ''; ?>>
                                    <label class="form-check-label">ESE (Aktif-Radyoaktif) Paratoner</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="protection_system_type"
                                        value="Faraday kafesi: FARADAY" <?php echo ($val == 'Faraday kafesi: FARADAY') ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Faraday kafesi: FARADAY</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="protection_system_type"
                                        value="Gerilmiş Tel" <?php echo ($val == 'Gerilmiş Tel') ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Gerilmiş Tel</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="protection_system_type"
                                        value="Franklin çubuğu: FRANKLİN" <?php echo ($val == 'Franklin çubuğu: FRANKLİN') ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Franklin çubuğu: FRANKLİN</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="protection_system_type"
                                        value="Doğal Bileşenler (Betonarme donatı, çelik yapı): DOĞAL" <?php echo ($val == 'Doğal Bileşenler (Betonarme donatı, çelik yapı): DOĞAL') ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Doğal Bileşenler (Betonarme donatı, çelik yapı):
                                        DOĞAL</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Koruma seviyesi (EPS)</label>
                            <input type="text" class="form-control" name="protection_level_eps"
                                value="<?php echo $report['protection_level_eps'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Yapı kullanım amacı, yapıya ait detaylar</label>
                        <textarea class="form-control" name="building_usage_details"
                            rows="2"><?php echo $report['building_usage_details'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Bölüm: Ölçüm Aletleri Bilgileri (Side-by-side format logic is handled in print, form is standard) -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c3">3. Ölçüm Aletleri Bilgileri</button></h2>
            <div id="c3" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Termal Kamera (Varsa)</label>
                        <select class="form-select" name="thermal_camera_id">
                            <option value="">Seçiniz</option>
                            <?php foreach ($thermal_cameras as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo (isset($report['thermal_camera_id']) && $report['thermal_camera_id'] == $d['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['device_name'] . ' (' . $d['serial_no'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">1. Ölçüm Cihazı</label>
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
                            <label class="form-label fw-bold">2. Ölçüm Cihazı (Varsa)</label>
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

        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c4">4. Kontrol Kriterleri ve Testler</button></h2>
            <div id="c4" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">Kapsama Alanı Uygunluğu</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label d-block">Risk analizi ve kapsama alanı projesi var mı?</label>
                                    <?php $ans = $s4_answers['risk_analizi_varmi'] ?? 'Uygun'; ?>
                                    <div class="d-flex gap-3">
                                        <?php foreach (['Uygun', 'Uygun Değil', 'Uygulanmaz'] as $opt): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="s4[risk_analizi_varmi]"
                                                    value="<?php echo $opt; ?>" <?php echo ($ans == $opt) ? 'checked' : ''; ?>>
                                                <label class="form-check-label"><?php echo $opt; ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label d-block">Kapsama alanı binayı kapsıyor mu?</label>
                                    <?php $ans = $s4_answers['kapsama_uygunmu'] ?? 'Uygun'; ?>
                                    <div class="d-flex gap-3">
                                        <?php foreach (['Uygun', 'Uygun Değil', 'Uygulanmaz'] as $opt): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="s4[kapsama_uygunmu]"
                                                    value="<?php echo $opt; ?>" <?php echo ($ans == $opt) ? 'checked' : ''; ?>>
                                                <label class="form-check-label"><?php echo $opt; ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-secondary text-white">Ölçüm Metodu</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <?php foreach (['Çevrim empedansı', '3 Uçlu topraklama', 'Klamp metodu'] as $opt): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="s4[measurement_method]"
                                                value="<?php echo $opt; ?>" <?php echo ($s4_answers['measurement_method'] ?? '') == $opt ? 'checked' : ''; ?>>
                                            <label class="form-check-label"><?php echo $opt; ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="s4Tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#eseTab"
                                type="button">ESE Paratoner</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#faTab" type="button">Faraday
                                Kafesi</button>
                        </li>
                    </ul>
                    <div class="tab-content border-start border-end border-bottom p-3">
                        <div class="tab-pane fade show active" id="eseTab">
                            <div class="mb-3 mt-1 d-flex gap-2">
                                <button type="button" class="btn btn-xs btn-outline-primary btn-sm" onclick="markAllRadios('eseTab', 'Uygun')">
                                    <i class="fas fa-check-circle me-1"></i> Hepsini Uygun Yap
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-sm" onclick="markAllRadios('eseTab', 'Uygulanmaz')">
                                    <i class="fas fa-ban me-1"></i> Hepsini Uygulanmaz Yap
                                </button>
                            </div>
                            <?php foreach ($ese_questions as $group => $qs): ?>
                                <h6 class="mt-3 text-primary border-bottom pb-1"><?php echo $group; ?></h6>
                                <div class="row">
                                    <?php foreach ($qs as $key => $label): ?>
                                        <div class="col-md-6 mb-2">
                                            <label class="small fw-bold d-block"><?php echo $label; ?></label>
                                            <?php $ans = $s4_answers[$key] ?? 'Uygun'; ?>
                                            <div class="d-flex gap-2">
                                                <?php foreach (['Uygun', 'Uygun Değil', 'Uygulanmaz'] as $opt): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="s4[<?php echo $key; ?>]"
                                                            value="<?php echo $opt; ?>" <?php echo ($ans == $opt) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label small"><?php echo $opt; ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="tab-pane fade" id="faTab">
                            <div class="mb-3 mt-1 d-flex gap-2">
                                <button type="button" class="btn btn-xs btn-outline-success btn-sm" onclick="markAllRadios('faTab', 'Uygun')">
                                    <i class="fas fa-check-circle me-1"></i> Hepsini Uygun Yap
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-sm" onclick="markAllRadios('faTab', 'Uygulanmaz')">
                                    <i class="fas fa-ban me-1"></i> Hepsini Uygulanmaz Yap
                                </button>
                            </div>
                            <?php foreach ($faraday_questions as $group => $qs): ?>
                                <h6 class="mt-3 text-success border-bottom pb-1"><?php echo $group; ?></h6>
                                <div class="row">
                                    <?php foreach ($qs as $key => $label): ?>
                                        <div class="col-md-6 mb-2">
                                            <label class="small fw-bold d-block"><?php echo $label; ?></label>
                                            <?php $ans = $s4_answers[$key] ?? 'Uygun'; ?>
                                            <div class="d-flex gap-2">
                                                <?php foreach (['Uygun', 'Uygun Değil', 'Uygulanmaz'] as $opt): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="s4[<?php echo $key; ?>]"
                                                            value="<?php echo $opt; ?>" <?php echo ($ans == $opt) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label small"><?php echo $opt; ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
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
                        <label class="form-label fw-bold">Kusur Açıklamaları</label>
                        <textarea class="form-control" name="defects" rows="4"
                            placeholder="Tespit edilen kusurları buraya yazınız..."><?php echo $report['defects'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Bölüm: Notlar -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c6">6. Notlar</button></h2>
            <div id="c6" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notlar</label>
                        <textarea class="form-control" name="notes" rows="4"
                            placeholder="Eklemek istediğiniz notları buraya yazınız..."><?php echo $report['notes'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. Bölüm: Sonuç ve Kanaat -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c7">7. Sonuç ve Kanaat</button></h2>
            <div id="c7" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body bg-light">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Genel Sonuç Değerlendirmesi</label>
                        <select class="form-select" name="result">
                            <option value="UYGUNDUR" <?php echo ($report['result'] ?? '') == 'UYGUNDUR' ? 'selected' : ''; ?>>Kullanımı UYGUNDUR</option>
                            <option value="UYGUN DEGILDIR" <?php echo ($report['result'] ?? '') == 'UYGUN DEGILDIR' ? 'selected' : ''; ?>>Kullanımı UYGUN DEĞİLDİR</option>
                        </select>
                        <small class="text-muted d-block mt-2">Bu seçim rapor çıktısındaki "Sonuç ve Kanaat" metnini
                            otomatik olarak güncelleyecektir.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 8. Bölüm: Yetkili Kişi Bilgileri -->
        <div class="accordion-item">
            <h2 class="accordion-header"><button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#c8">8. Yetkili Kişi Bilgileri ve Onay</button></h2>
            <div id="c8" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
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

<script>
function markAllRadios(tabId, value) {
    const tab = document.getElementById(tabId);
    if (!tab) return;
    const radios = tab.querySelectorAll(`input[type="radio"][value="${value}"]`);
    radios.forEach(radio => {
        radio.checked = true;
    });
}

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