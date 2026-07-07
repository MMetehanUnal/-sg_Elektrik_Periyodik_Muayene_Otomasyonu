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
    $stmt = $pdo->prepare("SELECT * FROM boyler_tanki_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$report_id, $kurum_id]);
    $report = $stmt->fetch();
    if (!$report) {
        redirect('boyler_tanki_kontrol.php'); // Invalid ID
    }
}

// Fetch Institution Defaults
$stmt = $pdo->prepare("SELECT * FROM institutions WHERE id = ?");
$stmt->execute([$kurum_id]);
$kurum = $stmt->fetch();

// Fetch Facility Info Defaults
$stmt = $pdo->prepare("SELECT * FROM facility_info WHERE kurum_id = ?");
$stmt->execute([$kurum_id]);
$facility_info = $stmt->fetch();

// Fetch Authorized Persons
$stmt = $pdo->prepare("SELECT * FROM authorized_persons");
$stmt->execute();
$authorized_persons = $stmt->fetchAll();

// Tank Donanimlari List
$donanim_keys = [
    'd1' => 'Manometre',
    'd2' => 'Su Seviye Göstergesi',
    'd3' => 'Basınç Ayar Otomatiği (presostat)',
    'd4' => 'Güvenlik Ventili Açma Basıncı',
    'd5' => 'Su Seviye Otomatiği',
    'd6' => 'Ana Vana Valfleri',
    'd7' => 'Blöf Vanası'
];

$tank_donanimlari = [];
if (!empty($report['tank_donanimlari'])) {
    $tank_donanimlari = json_decode($report['tank_donanimlari'], true);
}

// Questions
$questions = [
    'q1' => '1. Manometre çalışıyor ve tüzüğe uygun mu ?',
    'q2' => '2. Güvenlik ventili çalışıyor ve tüzüğe uygun mu ?',
    'q3' => '3. Basınç ayar otomatiği (presostat) çalışıyor ve tüzüğe uygun mu ?',
    'q4' => '4. Blöf vanası çalışıyor ve tüzüğe uygun mu ?',
    'q5' => '1. Tağdiye cihazı bağlantısı tekniğe uygun mu ?',
    'q6' => '2. Yapılan bakım ve onarımlar sicil defterine işleniyor mu ?',
    'q7' => '3. Hidrofor tankı üretim tekniği;',
    'q8' => '3.1 Kaynak dikişleri uygun mu ?',
    'q9' => '3.2 Hidrofor tankı malzemesi uygun mu ?',
    'q10' => '3.3 Hidrofor tankında kalıcı deformasyon var mı ?',
    'q11' => '4. Hidrofor tankının beslenme suyu üzerinde çek valfi var mı ?'
];

$inspection_results = [];
if (!empty($report['inspection_results'])) {
    $inspection_results = json_decode($report['inspection_results'], true);
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
    $phone = cleanInput($_POST['phone'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $mevzuat = cleanInput($_POST['mevzuat'] ?? '');

    // Technical specifications
    $brand = cleanInput($_POST['brand'] ?? '');
    $serial_no = cleanInput($_POST['serial_no'] ?? '');
    $model = cleanInput($_POST['model'] ?? '');
    $operating_pressure = cleanInput($_POST['operating_pressure'] ?? '');
    $production_year = cleanInput($_POST['production_year'] ?? '');
    $test_pressure = cleanInput($_POST['test_pressure'] ?? '');
    $capacity = cleanInput($_POST['capacity'] ?? '');

    // Donanim answers
    $donanim_post = $_POST['donanim'] ?? [];
    $donanim_json = json_encode($donanim_post);

    // Checklist answers
    $answers = $_POST['q'] ?? [];
    $json_results = json_encode($answers);

    // Text fields
    $hydrostatic_test = cleanInput($_POST['hydrostatic_test'] ?? '');
    $defects = cleanInput($_POST['defects'] ?? '');
    $result_text = cleanInput($_POST['result_text'] ?? '');
    $result = cleanInput($_POST['result'] ?? 'UYGUNDUR');
    $authorized_person_id = cleanInput($_POST['authorized_person_id']);

    try {
        if ($report_id) {
            // Update
            $sql = "UPDATE boyler_tanki_reports SET 
                report_date=?, start_date=?, end_date=?, next_control_date=?, isg_katip_id=?,
                firma_adi_eki=?, control_reason=?, phone=?, email=?, mevzuat=?,
                brand=?, serial_no=?, model=?, operating_pressure=?, production_year=?, test_pressure=?, capacity=?,
                tank_donanimlari=?, inspection_results=?, hydrostatic_test=?, defects=?, result_text=?, result=?,
                authorized_person_id=? WHERE id=? AND kurum_id=?";
            $params = [
                $report_date, $start_date, $end_date, $next_control_date, $isg_katip_id,
                $firma_adi_eki, $control_reason, $phone, $email, $mevzuat,
                $brand, $serial_no, $model, $operating_pressure, $production_year, $test_pressure, $capacity,
                $donanim_json, $json_results, $hydrostatic_test, $defects, $result_text, $result,
                $authorized_person_id, $report_id, $kurum_id
            ];
        } else {
            // Insert
            $stmt = $pdo->prepare("SELECT il_kodu, kurum_kodu FROM institutions WHERE id = ?");
            $stmt->execute([$kurum_id]);
            $k_codes = $stmt->fetch();
            $report_no = $k_codes['il_kodu'] . '-' . $k_codes['kurum_kodu'] . '-btank-' . time();

            $sql = "INSERT INTO boyler_tanki_reports 
                (kurum_id, report_no, report_date, start_date, end_date, next_control_date, isg_katip_id, 
                firma_adi_eki, control_reason, phone, email, mevzuat,
                brand, serial_no, model, operating_pressure, production_year, test_pressure, capacity,
                tank_donanimlari, inspection_results, hydrostatic_test, defects, result_text, result, authorized_person_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $kurum_id, $report_no, $report_date, $start_date, $end_date, $next_control_date, $isg_katip_id,
                $firma_adi_eki, $control_reason, $phone, $email, $mevzuat,
                $brand, $serial_no, $model, $operating_pressure, $production_year, $test_pressure, $capacity,
                $donanim_json, $json_results, $hydrostatic_test, $defects, $result_text, $result, $authorized_person_id
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if (!$report_id) {
            $report_id = $pdo->lastInsertId();
        }

        redirect("../results/boyler_tanki_sonuclar.php?report_id=$report_id&status=success");

    } catch (PDOException $e) {
        $save_error = "Hata: " . $e->getMessage();
    }
}

// Generate Default Textarea values
$default_hydro = "Boyler Tankının bütün bağlantıları kapatıldı. Tank 20 °C su ile " . (!empty($report['test_pressure']) ? htmlspecialchars($report['test_pressure']) : "12") . " Bar basınç altında 1/2 saat bekletildi. Boyler Tankında deformasyon ve sızıntıların olmadığı görüldü.";
$default_res_text = "Yukarıda teknik özellikleri verilen ve kontrol tarihinde durumu belirtilen Boyler Tankının testi " . (!empty($report['test_pressure']) ? htmlspecialchars($report['test_pressure']) : "12") . " Bar basınç altında yapılmış olup bir sonraki kontrol tarihine kadar kullanılmasında İŞÇİ SAĞLIĞI ve İŞ GÜVENLİĞİ açısından risk taşımamaktadır. İşletmesi UYGUNDUR.";

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $report_id ? 'Rapor Düzenle' : 'Yeni Rapor Oluştur'; ?>: Boyler Tankı Güvenliği</h1>
    <a href="/pages/raporlar.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Vazgeç ve Raporlara Dön
    </a>
</div>

<?php if (isset($save_error)): ?>
    <div class="alert alert-danger"><?php echo $save_error; ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="accordion shadow-sm mb-4" id="accordionForm">
        
        <!-- 1. Rapor / Tesis Bilgileri -->
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
                            <label class="form-label fw-bold">Bölümü (Firma Eki)</label>
                            <input type="text" class="form-control" name="firma_adi_eki" id="firma_adi_eki"
                                value="<?php echo htmlspecialchars($report['firma_adi_eki'] ?? ''); ?>" placeholder="Örn: Kazan Dairesi">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Telefon</label>
                            <input type="text" class="form-control" name="phone"
                                value="<?php echo htmlspecialchars($report['phone'] ?? $kurum['telefon'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">E-posta</label>
                            <input type="email" class="form-control" name="email"
                                value="<?php echo htmlspecialchars($report['email'] ?? $kurum['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">İSG-KATİP Sözleşme ID</label>
                            <input type="text" class="form-control" name="isg_katip_id"
                                value="<?php echo htmlspecialchars($report['isg_katip_id'] ?? $kurum['isg_katip_id'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kontrol Nedeni</label>
                            <select class="form-select" name="control_reason">
                                <option value="Periyodik Kontrol" <?php echo (isset($report['control_reason']) && $report['control_reason'] == 'Periyodik Kontrol') ? 'selected' : ''; ?>>Periyodik Kontrol</option>
                                <option value="İlk Kontrol" <?php echo (isset($report['control_reason']) && $report['control_reason'] == 'İlk Kontrol') ? 'selected' : ''; ?>>İlk Kontrol</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Rapor Tarihi (Kontrol Tarihi)</label>
                            <input type="date" class="form-control" name="report_date" required
                                value="<?php echo $report['report_date'] ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Kontrol Başlangıç Zamanı</label>
                            <input type="datetime-local" class="form-control" name="start_date"
                                value="<?php echo isset($report['start_date']) ? date('Y-m-d\TH:i', strtotime($report['start_date'])) : date('Y-m-d\T09:00'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Kontrol Bitiş Zamanı</label>
                            <input type="datetime-local" class="form-control" name="end_date"
                                value="<?php echo isset($report['end_date']) ? date('Y-m-d\TH:i', strtotime($report['end_date'])) : date('Y-m-d\T17:00'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Bir Sonraki Kontrol Tarihi</label>
                            <input type="date" class="form-control" name="next_control_date" required
                                value="<?php echo $report['next_control_date'] ?? date('Y-m-d', strtotime('+1 year -2 days')); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">İlgili Mevzuat ve Standartlar</label>
                            <input type="text" class="form-control" name="mevzuat"
                                value="<?php echo htmlspecialchars($report['mevzuat'] ?? 'İş Ekipmanlarının Kullanımında Sağlık ve Güv. Şartları Yön. Sayısı: 28628 / 25.04.2013, TS EN 13445-5'); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Teknik Özellikler -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c2">
                    2. Teknik Özellikler
                </button>
            </h2>
            <div id="c2" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Markası</label>
                            <input type="text" class="form-control" name="brand" value="<?php echo htmlspecialchars($report['brand'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Seri No</label>
                            <input type="text" class="form-control" name="serial_no" value="<?php echo htmlspecialchars($report['serial_no'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Modeli</label>
                            <input type="text" class="form-control" name="model" value="<?php echo htmlspecialchars($report['model'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">İşletme Basıncı (Bar)</label>
                            <input type="text" class="form-control" name="operating_pressure" value="<?php echo htmlspecialchars($report['operating_pressure'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">İmal Yılı</label>
                            <input type="text" class="form-control" name="production_year" value="<?php echo htmlspecialchars($report['production_year'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Deneme Basıncı (Bar)</label>
                            <input type="text" class="form-control" name="test_pressure" id="test_pressure" value="<?php echo htmlspecialchars($report['test_pressure'] ?? '12'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Kapasitesi (lt)</label>
                            <input type="text" class="form-control" name="capacity" value="<?php echo htmlspecialchars($report['capacity'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Tank Donanımları -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c3">
                    3. Tank Donanımları
                </button>
            </h2>
            <div id="c3" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Donanım Elemanı</th>
                                    <th class="text-center" style="width: 30%;">Durum (var/yok)</th>
                                    <th class="text-center" style="width: 30%;">Miktar (adet/hacim)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($donanim_keys as $key => $label): 
                                    $status = $tank_donanimlari[$key]['status'] ?? 'Var';
                                    $amount = $tank_donanimlari[$key]['amount'] ?? '1';
                                ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($label); ?></td>
                                        <td class="text-center">
                                            <select class="form-select form-select-sm" name="donanim[<?php echo $key; ?>][status]">
                                                <option value="Var" <?php echo $status == 'Var' ? 'selected' : ''; ?>>Var</option>
                                                <option value="Yok" <?php echo $status == 'Yok' ? 'selected' : ''; ?>>Yok</option>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <input type="text" class="form-control form-control-sm text-center" name="donanim[<?php echo $key; ?>][amount]" value="<?php echo htmlspecialchars($amount); ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Test ve Kontroller -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c4">
                    4. Test ve Kontroller
                </button>
            </h2>
            <div id="c4" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70%;">Soru / Kontrol Kriteri</th>
                                    <th style="width: 30%;" class="text-center">Değerlendirme</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $key => $text): 
                                    $val = $inspection_results[$key] ?? 'UYGUN';
                                ?>
                                    <tr>
                                        <td class="<?php echo (strpos($text, '3.') === 0 && strpos($text, '3.1') === false && strpos($text, '3.2') === false && strpos($text, '3.3') === false) ? 'fw-bold bg-light' : ''; ?>">
                                            <?php echo htmlspecialchars($text); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (strpos($text, '3.') === 0 && strpos($text, '3.1') === false && strpos($text, '3.2') === false && strpos($text, '3.3') === false): ?>
                                                <!-- Heading item, no evaluation selection needed -->
                                                <input type="hidden" name="q[<?php echo $key; ?>]" value="-">
                                                -
                                            <?php else: ?>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <input type="radio" class="btn-check" name="q[<?php echo $key; ?>]" id="radio_u_<?php echo $key; ?>" value="UYGUN" <?php echo ($val == 'UYGUN' || $val == 'Evet') ? 'checked' : ''; ?>>
                                                    <label class="btn btn-sm btn-outline-success px-2" for="radio_u_<?php echo $key; ?>">UYGUN / EVET</label>

                                                    <input type="radio" class="btn-check" name="q[<?php echo $key; ?>]" id="radio_ud_<?php echo $key; ?>" value="UYGUN DEĞİL" <?php echo ($val == 'UYGUN DEĞİL' || $val == 'Hayır') ? 'checked' : ''; ?>>
                                                    <label class="btn btn-sm btn-outline-danger px-2" for="radio_ud_<?php echo $key; ?>">UYGUN DEĞİL</label>

                                                    <input type="radio" class="btn-check" name="q[<?php echo $key; ?>]" id="radio_un_<?php echo $key; ?>" value="UYGULANMAZ" <?php echo ($val == 'UYGULANMAZ') ? 'checked' : ''; ?>>
                                                    <label class="btn btn-sm btn-outline-secondary px-2" for="radio_un_<?php echo $key; ?>">UYGULANMAZ</label>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Test Detayları, Sonuç ve Yetkili Kişi -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c5">
                    5. Test Detayları, Sonuç ve Yetkili Kişi
                </button>
            </h2>
            <div id="c5" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Hidrostatik Test Açıklaması</label>
                            <textarea class="form-control" name="hydrostatic_test" rows="4"><?php echo htmlspecialchars($report['hydrostatic_test'] ?? $default_hydro); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Notlar ve Öneriler</label>
                            <textarea class="form-control" name="defects" rows="4" placeholder="Notlar ve eksiklikler..."><?php echo htmlspecialchars($report['defects'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Sonuç Açıklama Metni (Baskıda çıkacak sonuç yazısı)</label>
                            <textarea class="form-control" name="result_text" rows="4"><?php echo htmlspecialchars($report['result_text'] ?? $default_res_text); ?></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Karar / Sonuç</label>
                            <div class="border p-3 rounded bg-light">
                                <?php $resVal = $report['result'] ?? 'UYGUNDUR'; ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="result" id="res_y" value="UYGUNDUR" <?php echo ($resVal == 'UYGUNDUR') ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-success fw-bold" for="res_y">UYGUNDUR</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="result" id="res_yd" value="UYGUN DEĞİLDİR" <?php echo ($resVal == 'UYGUN DEĞİLDİR') ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-danger fw-bold" for="res_yd">UYGUN DEĞİLDİR</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Yetkili Kontrolör (Kişi)</label>
                            <select class="form-select" name="authorized_person_id" required>
                                <option value="">Seçiniz...</option>
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

    </div>

    <div class="mt-4 mb-5 d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg">Kaydet ve İlerle</button>
    </div>
</form>

<script>
// Dynamic updates when test pressure changes
document.getElementById('test_pressure').addEventListener('input', function() {
    var val = this.value;
    var hydroBox = document.getElementsByName('hydrostatic_test')[0];
    var resBox = document.getElementsByName('result_text')[0];
    
    hydroBox.value = "Boyler Tankının bütün bağlantıları kapatıldı. Tank 20 °C su ile " + val + " Bar basınç altında 1/2 saat bekletildi. Boyler Tankında deformasyon ve sızıntıların olmadığı görüldü.";
    resBox.value = "Yukarıda teknik özellikleri verilen ve kontrol tarihinde durumu belirtilen Boyler Tankının testi " + val + " Bar basınç altında yapılmış olup bir sonraki kontrol tarihine kadar kullanılmasında İŞÇİ SAĞLIĞI ve İŞ GÜVENLİĞİ açısından risk taşımamaktadır. İşletmesi UYGUNDUR.";
});
</script>

<?php include '../../includes/footer.php'; ?>
