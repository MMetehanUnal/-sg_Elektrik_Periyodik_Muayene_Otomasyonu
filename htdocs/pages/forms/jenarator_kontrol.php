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
    $stmt = $pdo->prepare("SELECT * FROM jenarator_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$report_id, $kurum_id]);
    $report = $stmt->fetch();
    if (!$report) {
        redirect('jenarator_kontrol.php'); // Invalid ID
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

// Questions
$questions = [
    'q1' => 'Jeneratörün konulduğu yer ve alan uygun mu?',
    'q2' => 'Jeneratörün koruyucu kabini var mı?',
    'q3' => 'Motor suyu seviyesi uygun mu?',
    'q4' => 'Yağ seviyesi uygun mu?',
    'q5' => 'Su kaçağı var mı?',
    'q6' => 'Yağ kaçağı var mı?',
    'q7' => 'Yakıt kaçağı var mı?',
    'q8' => 'Yakıt seviyesi gösterme paneli uygun mu?',
    'q9' => 'Akü şarj ünitesi uygun mu?',
    'q10' => 'Aküde şişme ve sızıntı var mı?',
    'q11' => 'Kablolar uygun mu?',
    'q12' => 'Yetkili servis bakım kayıtları düzenli olarak tutuluyor mu?'
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
    
    // Technical specs
    $brand_model = cleanInput($_POST['brand_model'] ?? '');
    $production_year = cleanInput($_POST['production_year'] ?? '');
    $capacity = cleanInput($_POST['capacity'] ?? '');
    $serial_no = cleanInput($_POST['serial_no'] ?? '');

    // Checklist answers
    $answers = $_POST['q'] ?? [];
    $json_results = json_encode($answers);

    // Text fields
    $defects = cleanInput($_POST['defects'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');
    $result_text = cleanInput($_POST['result_text'] ?? '');
    $result = cleanInput($_POST['result'] ?? 'UYGUNDUR');
    $authorized_person_id = cleanInput($_POST['authorized_person_id']);

    try {
        if ($report_id) {
            // Update
            $sql = "UPDATE jenarator_reports SET 
                report_date=?, start_date=?, end_date=?, next_control_date=?, isg_katip_id=?,
                firma_adi_eki=?, control_reason=?, brand_model=?, production_year=?, capacity=?, serial_no=?,
                inspection_results=?, defects=?, notes=?, result_text=?, result=?, authorized_person_id=?
                WHERE id=? AND kurum_id=?";
            $params = [
                $report_date, $start_date, $end_date, $next_control_date, $isg_katip_id,
                $firma_adi_eki, $control_reason, $brand_model, $production_year, $capacity, $serial_no,
                $json_results, $defects, $notes, $result_text, $result, $authorized_person_id,
                $report_id, $kurum_id
            ];
        } else {
            // Insert
            $stmt = $pdo->prepare("SELECT il_kodu, kurum_kodu FROM institutions WHERE id = ?");
            $stmt->execute([$kurum_id]);
            $k_codes = $stmt->fetch();
            $report_no = $k_codes['il_kodu'] . '-' . $k_codes['kurum_kodu'] . '-jen-' . time();

            $sql = "INSERT INTO jenarator_reports 
                (kurum_id, report_no, report_date, start_date, end_date, next_control_date, isg_katip_id, 
                firma_adi_eki, control_reason, brand_model, production_year, capacity, serial_no,
                inspection_results, defects, notes, result_text, result, authorized_person_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $kurum_id, $report_no, $report_date, $start_date, $end_date, $next_control_date, $isg_katip_id,
                $firma_adi_eki, $control_reason, $brand_model, $production_year, $capacity, $serial_no,
                $json_results, $defects, $notes, $result_text, $result, $authorized_person_id
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if (!$report_id) {
            $report_id = $pdo->lastInsertId();
        }

        redirect("../results/jenarator_sonuclar.php?report_id=$report_id&status=success");

    } catch (PDOException $e) {
        $save_error = "Hata: " . $e->getMessage();
    }
}

// Generate Default text values
$default_defects = "Periyodik bakım ve kontrolleri düzenli olarak yapılmalı ve kayıtları muhafaza edilmelidir.";
$default_res_text = "Yukarıda teknik özellikleri verilen ve kontrol tarihinde durumu belirtilen JENARATÖR 'ün kontrolü yapılmış olup bir sonraki kontrol tarihine kadar kullanılması İŞÇİ SAĞLIĞI ve İŞ GÜVENLİĞİ açısından risk taşımamaktadır. İşletmesi UYGUNDUR.";

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $report_id ? 'Rapor Düzenle' : 'Yeni Rapor Oluştur'; ?>: Jeneratör Güvenliği</h1>
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
                            <label class="form-label fw-bold">Bölüm (Firma Eki)</label>
                            <input type="text" class="form-control" name="firma_adi_eki" id="firma_adi_eki"
                                value="<?php echo htmlspecialchars($report['firma_adi_eki'] ?? ''); ?>" placeholder="Örn: Jeneratör Odası">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">İSG-KATİP Sözleşme ID</label>
                            <input type="text" class="form-control" name="isg_katip_id"
                                value="<?php echo htmlspecialchars($report['isg_katip_id'] ?? $kurum['isg_katip_id'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kontrol Nedeni</label>
                            <select class="form-select" name="control_reason">
                                <option value="Periyodik Kontrol" <?php echo (isset($report['control_reason']) && $report['control_reason'] == 'Periyodik Kontrol') ? 'selected' : ''; ?>>Periyodik Kontrol</option>
                                <option value="İlk Kontrol" <?php echo (isset($report['control_reason']) && $report['control_reason'] == 'İlk Kontrol') ? 'selected' : ''; ?>>İlk Kontrol</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Rapor Tarihi</label>
                            <input type="date" class="form-control" name="report_date" required
                                value="<?php echo $report['report_date'] ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bir Sonraki Kontrol Tarihi</label>
                            <input type="date" class="form-control" name="next_control_date" required
                                value="<?php echo $report['next_control_date'] ?? date('Y-m-d', strtotime('+1 year -2 days')); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kontrol Başlangıç Zamanı</label>
                            <input type="datetime-local" class="form-control" name="start_date"
                                value="<?php echo isset($report['start_date']) ? date('Y-m-d\TH:i', strtotime($report['start_date'])) : date('Y-m-d\T09:00'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kontrol Bitiş Zamanı</label>
                            <input type="datetime-local" class="form-control" name="end_date"
                                value="<?php echo isset($report['end_date']) ? date('Y-m-d\TH:i', strtotime($report['end_date'])) : date('Y-m-d\T17:00'); ?>">
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
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Markası ve Modeli</label>
                            <input type="text" class="form-control" name="brand_model" value="<?php echo htmlspecialchars($report['brand_model'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Seri No</label>
                            <input type="text" class="form-control" name="serial_no" value="<?php echo htmlspecialchars($report['serial_no'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">İmal Yılı</label>
                            <input type="text" class="form-control" name="production_year" value="<?php echo htmlspecialchars($report['production_year'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kapasite (kVA)</label>
                            <input type="text" class="form-control" name="capacity" value="<?php echo htmlspecialchars($report['capacity'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Kontroller (Checklist) -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c3">
                    3. Kontroller
                </button>
            </h2>
            <div id="c3" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;" class="text-center">NO</th>
                                    <th style="width: 65%;">Kontrol Kriteri</th>
                                    <th style="width: 30%;" class="text-center">Değerlendirme (Evet / Hayır / Uygulanmaz)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $idx = 1;
                                foreach ($questions as $key => $text): 
                                    $val = $inspection_results[$key] ?? 'Evet';
                                ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo $idx++; ?></td>
                                        <td><?php echo htmlspecialchars($text); ?></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <input type="radio" class="btn-check" name="q[<?php echo $key; ?>]" id="radio_e_<?php echo $key; ?>" value="Evet" <?php echo ($val == 'Evet' || $val == 'UYGUN') ? 'checked' : ''; ?>>
                                                <label class="btn btn-sm btn-outline-success px-3" for="radio_e_<?php echo $key; ?>">EVET</label>

                                                <input type="radio" class="btn-check" name="q[<?php echo $key; ?>]" id="radio_h_<?php echo $key; ?>" value="Hayır" <?php echo ($val == 'Hayır' || $val == 'UYGUN DEĞİL') ? 'checked' : ''; ?>>
                                                <label class="btn btn-sm btn-outline-danger px-3" for="radio_h_<?php echo $key; ?>">HAYIR</label>

                                                <input type="radio" class="btn-check" name="q[<?php echo $key; ?>]" id="radio_un_<?php echo $key; ?>" value="Uygulanmaz" <?php echo ($val == 'Uygulanmaz' || $val == 'UYGULANMAZ') ? 'checked' : ''; ?>>
                                                <label class="btn btn-sm btn-outline-secondary px-2" for="radio_un_<?php echo $key; ?>">UYGULANMAZ</label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Sonuçlar ve Yetkili Kişi -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c4">
                    4. Sonuçlar ve Yetkili Kişi
                </button>
            </h2>
            <div id="c4" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">İkaz ve Öneriler</label>
                            <textarea class="form-control" name="defects" rows="4"><?php echo htmlspecialchars($report['defects'] ?? $default_defects); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Genel Notlar</label>
                            <textarea class="form-control" name="notes" rows="4" placeholder="Ek Notlar..."><?php echo htmlspecialchars($report['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Sonuç Beyan Metni</label>
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
                            <label class="form-label fw-bold">Kontrolü Yapan Yetkili (Elektrik Mühendisi)</label>
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

<?php include '../../includes/footer.php'; ?>
