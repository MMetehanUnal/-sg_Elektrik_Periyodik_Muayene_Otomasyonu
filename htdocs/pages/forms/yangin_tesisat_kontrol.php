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
    $stmt = $pdo->prepare("SELECT * FROM yangin_tesisat_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$report_id, $kurum_id]);
    $report = $stmt->fetch();
    if (!$report) {
        redirect('yangin_tesisat_kontrol.php'); // Invalid ID
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
    'q1' => 'Yangın söndürme tesisatının projesi mevcut mu?',
    'q2' => 'Yangın söndürme tesisatının periyodik kontrol raporu var mı?',
    'q3' => 'Basınçlandırma ve duman tahliye tesisatının test ve periyodik kontrolü yapılmış mı?',
    'q4' => 'Güvenlik ve kontrol sistemlerinin bulunduğu yerde, kırmızı zemin üzerine fosforlu sarı veya beyaz renkte "YANGIN 112" yazılmış mı?',
    'q5' => 'İtfaiye araçlarının gerektiğinde binaya kolaylıkla ulaşımı ve yaklaşması sağlanabilmekte midir?',
    'q6' => 'Acil durum yönlendirmeleri mevcut mudur?',
    'q7' => 'Yangın dolaplarına erişim uygun mu, çalışır durumda mı?',
    'q8' => 'Yangın dolapları boru bağlantı çapı ve vanası uygun mu? (hidrolik hesaplara göre belirlenir-en az 50 mm.)',
    'q9' => 'Yangın su deposu var mı, dolum ve emme vanaları açık mı?',
    'q10' => 'Yangın pompaları çalışır durumda mı ve elektrik bağlantısı (kofradan önce) uygun mu?',
    'q11' => 'Yangın pompalarından ikisi de elektrikli ise en azından asıl pompa jeneratörle %100 beslenebiliyor mu?',
    'q12' => 'Sprinkler tesisatı varsa, projesine uygun mu?',
    'q13' => 'Taşınabilir söndürme tüplerinin TS standartlarına göre bakımları yapılmış mı?',
    'q14' => 'Merdiven sahanlığından yangın merdivenine erişim uygun mu?',
    'q15' => 'Yangın merdiveni kapıları dışarı açılabiliyor mu?',
    'q16' => 'Yangın algılama ve ihbar sistemi aktif olarak çalışıyor mu?'
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
    
    // Yangın tesisat specific header fields
    $kurum_yoneticisi = cleanInput($_POST['kurum_yoneticisi'] ?? '');
    $kurum_kapasitesi = !empty($_POST['kurum_kapasitesi']) ? (int)$_POST['kurum_kapasitesi'] : null;

    // Checklist answers
    $answers = $_POST['q'] ?? [];
    $json_results = json_encode($answers);

    // Recommendations, outcomes, and authorized person
    $defects = cleanInput($_POST['defects']);
    $notes = cleanInput($_POST['notes']);
    $result = cleanInput($_POST['result'] ?? 'UYGUNDUR');
    $authorized_person_id = cleanInput($_POST['authorized_person_id']);

    try {
        if ($report_id) {
            // Update Report
            $sql = "UPDATE yangin_tesisat_reports SET 
                report_date=?, start_date=?, end_date=?, next_control_date=?, isg_katip_id=?,
                firma_adi_eki=?, control_reason=?, kurum_yoneticisi=?, kurum_kapasitesi=?,
                defects=?, notes=?, result=?, authorized_person_id=?, inspection_results=?
                WHERE id=? AND kurum_id=?";
            $params = [
                $report_date, $start_date, $end_date, $next_control_date, $isg_katip_id,
                $firma_adi_eki, $control_reason, $kurum_yoneticisi, $kurum_kapasitesi,
                $defects, $notes, $result, $authorized_person_id, $json_results,
                $report_id, $kurum_id
            ];
        } else {
            // Insert Report
            $stmt = $pdo->prepare("SELECT il_kodu, kurum_kodu FROM institutions WHERE id = ?");
            $stmt->execute([$kurum_id]);
            $k_codes = $stmt->fetch();
            $report_no = $k_codes['il_kodu'] . '-' . $k_codes['kurum_kodu'] . '-yt-' . time();

            $sql = "INSERT INTO yangin_tesisat_reports 
                (kurum_id, report_no, report_date, start_date, end_date, next_control_date, isg_katip_id, 
                firma_adi_eki, control_reason, kurum_yoneticisi, kurum_kapasitesi,
                defects, notes, result, authorized_person_id, inspection_results)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $kurum_id, $report_no, $report_date, $start_date, $end_date, $next_control_date, $isg_katip_id,
                $firma_adi_eki, $control_reason, $kurum_yoneticisi, $kurum_kapasitesi,
                $defects, $notes, $result, $authorized_person_id, $json_results
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if (!$report_id) {
            $report_id = $pdo->lastInsertId();
        }

        redirect("../results/yangin_tesisat_sonuclar.php?report_id=$report_id&status=success");

    } catch (PDOException $e) {
        $save_error = "Hata: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $report_id ? 'Rapor Düzenle' : 'Yeni Rapor Oluştur'; ?>: Yangın Tesisatı Güvenliği</h1>
    <a href="/pages/raporlar.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Vazgeç ve Raporlara Dön
    </a>
</div>

<?php if (isset($save_error)): ?>
    <div class="alert alert-danger"><?php echo $save_error; ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="accordion shadow-sm mb-4" id="accordionForm">
        
        <!-- 1. Bölüm: Rapor / Tesis Genel Bilgileri -->
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
                            <input type="text" class="form-control" name="firma_adi_eki" id="firma_adi_eki"
                                value="<?php echo htmlspecialchars($report['firma_adi_eki'] ?? ''); ?>" placeholder="Örn: B Blok / İmalathane">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kurum Yöneticisi</label>
                            <input type="text" class="form-control" name="kurum_yoneticisi"
                                value="<?php echo htmlspecialchars($report['kurum_yoneticisi'] ?? ''); ?>" placeholder="Örn: Fatih Ulusoy">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kurum Kapasitesi</label>
                            <input type="number" class="form-control" name="kurum_kapasitesi"
                                value="<?php echo htmlspecialchars($report['kurum_kapasitesi'] ?? ''); ?>" placeholder="Örn: 96">
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

        <!-- 2. Bölüm: Tespit ve Değerlendirme Soruları -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c2">
                    2. Tespit ve Değerlendirme Soruları
                </button>
            </h2>
            <div id="c2" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;" class="text-center">NO</th>
                                    <th style="width: 65%;">SORU</th>
                                    <th style="width: 30%;" class="text-center">DEĞERLENDİRME</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $idx = 1;
                                foreach ($questions as $key => $text): 
                                    $val = $inspection_results[$key] ?? 'UYGUN';
                                ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo $idx++; ?></td>
                                        <td><?php echo htmlspecialchars($text); ?></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <input type="radio" class="btn-check" name="q[<?php echo $key; ?>]" id="radio_u_<?php echo $key; ?>" value="UYGUN" <?php echo ($val == 'UYGUN') ? 'checked' : ''; ?>>
                                                <label class="btn btn-sm btn-outline-success px-3" for="radio_u_<?php echo $key; ?>">UYGUN</label>

                                                <input type="radio" class="btn-check" name="q[<?php echo $key; ?>]" id="radio_ud_<?php echo $key; ?>" value="UYGUN DEĞİL" <?php echo ($val == 'UYGUN DEĞİL') ? 'checked' : ''; ?>>
                                                <label class="btn btn-sm btn-outline-danger px-2" for="radio_ud_<?php echo $key; ?>">UYGUN DEĞİL</label>

                                                <input type="radio" class="btn-check" name="q[<?php echo $key; ?>]" id="radio_un_<?php echo $key; ?>" value="UYGULANMAZ" <?php echo ($val == 'UYGULANMAZ') ? 'checked' : ''; ?>>
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

        <!-- 3. Bölüm: Öneriler, Sonuç ve Yetkili Kişi -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c3">
                    3. Öneriler, Sonuç ve Yetkili Kişi
                </button>
            </h2>
            <div id="c3" class="accordion-collapse collapse" data-bs-parent="#accordionForm">
                <div class="accordion-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Öneriler (Eksikler/Kusurlar)</label>
                            <textarea class="form-control" name="defects" rows="4" placeholder="Raporda belirtmek istediğiniz eksiklik ve öneriler..."><?php echo htmlspecialchars($report['defects'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Genel Notlar</label>
                            <textarea class="form-control" name="notes" rows="4" placeholder="Ek açıklama veya notlar..."><?php echo htmlspecialchars($report['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sonuç Değerlendirmesi</label>
                            <div class="border p-3 rounded bg-light">
                                <?php $resVal = $report['result'] ?? 'UYGUNDUR'; ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="result" id="res_g" value="UYGUNDUR" <?php echo ($resVal == 'UYGUNDUR' || $resVal == 'GÜVENLİDİR') ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-success fw-bold" for="res_g">UYGUNDUR</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="result" id="res_gd" value="UYGUN DEĞİLDİR" <?php echo ($resVal == 'UYGUN DEĞİLDİR' || $resVal == 'GÜVENLİ DEĞİLDİR') ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-danger fw-bold" for="res_gd">UYGUN DEĞİLDİR</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kontrolü Gerçekleştiren (Yetkili Kişi)</label>
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
