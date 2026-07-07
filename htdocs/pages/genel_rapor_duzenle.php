<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

if (!isset($_SESSION['active_institution_id'])) {
    redirect('tesis_secimi.php');
}
$kurum_id = $_SESSION['active_institution_id'];

$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rapor = null;

if ($report_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM general_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$report_id, $kurum_id]);
    $rapor = $stmt->fetch();
    if (!$rapor) {
        die("Rapor bulunamadı veya bu kuruma ait değil.");
    }
}

// Fetch Authorized Persons
$stmt_ap = $pdo->prepare("SELECT * FROM authorized_persons");
$stmt_ap->execute();
$authorized_persons = $stmt_ap->fetchAll();

// Default values lookup if $rapor is empty
$default_yonetici = $facility_info['mesul_mudur'] ?? '';
$default_yatak_kapasitesi = '';
$default_phone = $kurum['telefon'] ?? '';
$default_ada = '0';
$default_pafta = '6';
$default_parsel = '446';
$default_report_no = '';
$default_report_date = date('Y-m-d');
$default_control_date = date('Y-m-d');
$default_next_control_date = date('Y-m-d', strtotime('+1 year -2 days'));
$default_isg_uzmani = '';
$default_mekanik_id = '';
$default_elektrik_id = '';

// Try fetching default values from other reports
try {
    $stmt_def = $pdo->prepare("SELECT phone, next_control_date, kurum_yoneticisi, kurum_kapasitesi FROM sihhi_tesisat_reports WHERE kurum_id = ? ORDER BY id DESC LIMIT 1");
    $stmt_def->execute([$kurum_id]);
    $def_rep = $stmt_def->fetch();
    if ($def_rep) {
        if (!empty($def_rep['phone'])) $default_phone = $def_rep['phone'];
        if (!empty($def_rep['next_control_date'])) $default_next_control_date = $def_rep['next_control_date'];
        if (!empty($def_rep['kurum_yoneticisi'])) $default_yonetici = $def_rep['kurum_yoneticisi'];
        if (!empty($def_rep['kurum_kapasitesi'])) $default_yatak_kapasitesi = $def_rep['kurum_kapasitesi'];
    }
} catch (Exception $ex) {}

// Fetch all existing reports for this institution to display on the side
$stmt_reports = $pdo->prepare("
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'topraklama' as type FROM grounding_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'ic_tesisat' as type FROM internal_installation_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'yildirim' as type FROM lightning_protection_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'yangin' as type FROM fire_detection_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'sihhi_tesisat' as type FROM sihhi_tesisat_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'gaz_tesisat' as type FROM gaz_tesisat_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'isinma_tesisat' as type FROM isinma_tesisat_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'genlesme_tanki' as type FROM genlesme_tanki_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'engelli_rampasi' as type FROM engelli_rampasi_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'boyler_tanki' as type FROM boyler_tanki_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'jenarator' as type FROM jenarator_reports WHERE kurum_id = ?)
    UNION ALL
    (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, 'kamera_bakim' as type FROM kamera_bakim_reports WHERE kurum_id = ?)
    ORDER BY report_date DESC
");
$stmt_reports->execute([$kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id]);
$existing_reports = $stmt_reports->fetchAll();

$pageTitle = $rapor ? 'Raporu Düzenle' : 'Yeni Rapor Oluştur';

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-<?php echo $rapor ? 'edit' : 'plus-circle'; ?> me-2"></i><?php echo $pageTitle; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="genel_rapor.php" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i> Geri Dön
        </a>
        <?php if ($rapor): ?>
            <a href="genel_rapor_yazdir.php?id=<?php echo $report_id; ?>" target="_blank" class="btn btn-sm btn-outline-dark me-2">
                <i class="fas fa-print me-1"></i> Yazdır
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-primary" id="btnSave">
            <i class="fas fa-save me-1"></i> Kaydet
        </button>
    </div>
</div>

<!-- Rapor Başlığı -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-2">
                <label for="raporBaslik" class="form-label fw-bold mb-0">Rapor Başlığı</label>
            </div>
            <div class="col-md-10">
                <input type="text" class="form-control form-control-lg" id="raporBaslik"
                    placeholder="Rapor başlığını yazın..."
                    value="<?php echo htmlspecialchars($rapor['title'] ?? ''); ?>"
                    maxlength="500">
            </div>
        </div>
    </div>
</div>

<!-- Kapak Sayfası Bilgileri -->
<div class="card mb-3 shadow-sm">
    <div class="card-header bg-light">
        <span class="fw-bold"><i class="fas fa-file-invoice me-2"></i>Kapak Sayfası Genel Bilgileri</span>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="yurt_yoneticisi" class="form-label fw-bold">Yurt Yöneticisi</label>
                <input type="text" class="form-control" id="yurt_yoneticisi" value="<?php echo htmlspecialchars($rapor['yurt_yoneticisi'] ?? $default_yonetici); ?>">
            </div>
            <div class="col-md-4">
                <label for="yatak_kapasitesi" class="form-label fw-bold">Yurt Yatak Kapasitesi</label>
                <input type="text" class="form-control" id="yatak_kapasitesi" value="<?php echo htmlspecialchars($rapor['yatak_kapasitesi'] ?? $default_yatak_kapasitesi); ?>">
            </div>
            <div class="col-md-4">
                <label for="is_guvenligi_uzmani" class="form-label fw-bold">İş Güvenliği Uzmanı</label>
                <input type="text" class="form-control" id="is_guvenligi_uzmani" value="<?php echo htmlspecialchars($rapor['is_guvenligi_uzmani'] ?? $default_isg_uzmani); ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="ada" class="form-label fw-bold">Ada</label>
                <input type="text" class="form-control" id="ada" value="<?php echo htmlspecialchars($rapor['ada'] ?? $default_ada); ?>">
            </div>
            <div class="col-md-4">
                <label for="pafta" class="form-label fw-bold">Pafta</label>
                <input type="text" class="form-control" id="pafta" value="<?php echo htmlspecialchars($rapor['pafta'] ?? $default_pafta); ?>">
            </div>
            <div class="col-md-4">
                <label for="parsel" class="form-label fw-bold">Parsel</label>
                <input type="text" class="form-control" id="parsel" value="<?php echo htmlspecialchars($rapor['parsel'] ?? $default_parsel); ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="phone" class="form-label fw-bold">Telefon</label>
                <input type="text" class="form-control" id="phone" value="<?php echo htmlspecialchars($rapor['phone'] ?? $default_phone); ?>">
            </div>
            <div class="col-md-4">
                <label for="report_no" class="form-label fw-bold">Rapor No</label>
                <input type="text" class="form-control" id="report_no" value="<?php echo htmlspecialchars($rapor['report_no'] ?? $default_report_no); ?>" placeholder="Örn: 2025 / 049">
            </div>
            <div class="col-md-4">
                <label for="report_date" class="form-label fw-bold">Rapor Tarihi</label>
                <input type="date" class="form-control" id="report_date" value="<?php echo htmlspecialchars($rapor['report_date'] ?? $default_report_date); ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="control_date" class="form-label fw-bold">Kontrol Tarihi</label>
                <input type="date" class="form-control" id="control_date" value="<?php echo htmlspecialchars($rapor['control_date'] ?? $default_control_date); ?>">
            </div>
            <div class="col-md-6">
                <label for="next_control_date" class="form-label fw-bold text-danger">Bir Sonraki Kontrol Tarihi</label>
                <input type="date" class="form-control" id="next_control_date" value="<?php echo htmlspecialchars($rapor['next_control_date'] ?? $default_next_control_date); ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="mekanik_uzman_id" class="form-label fw-bold">Mekanik Tesisat Kontrol Uzmanı</label>
                <select class="form-select" id="mekanik_uzman_id">
                    <option value="">Seçiniz...</option>
                    <?php foreach ($authorized_persons as $ap): ?>
                        <option value="<?php echo $ap['id']; ?>" <?php echo (isset($rapor['mekanik_uzman_id']) && $rapor['mekanik_uzman_id'] == $ap['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ap['adi_soyadi'] . ' (' . $ap['meslegi'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="elektrik_uzman_id" class="form-label fw-bold">Elektrik Tesisat Kontrol Uzmanı</label>
                <select class="form-select" id="elektrik_uzman_id">
                    <option value="">Seçiniz...</option>
                    <?php foreach ($authorized_persons as $ap): ?>
                        <option value="<?php echo $ap['id']; ?>" <?php echo (isset($rapor['elektrik_uzman_id']) && $rapor['elektrik_uzman_id'] == $ap['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ap['adi_soyadi'] . ' (' . $ap['meslegi'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Split Editör ve Var Olan Raporlar Grubu -->
<div class="row">
    <!-- Sol Sütun: Editör -->
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-pen-fancy me-2"></i>Rapor İçeriği</span>
                <small class="text-muted" id="saveStatus"></small>
            </div>
            <div class="card-body p-0">
                <textarea id="editor"><?php echo htmlspecialchars($rapor['content'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>
    
    <!-- Sağ Sütun: Var Olan Raporlar Listesi -->
    <div class="col-lg-4">
        <div class="card mb-3 shadow-sm" style="max-height: 700px; display: flex; flex-direction: column;">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="fas fa-file-import me-2"></i>Var Olan Raporları Ekle</span>
                <span class="badge bg-secondary"><?php echo count($existing_reports); ?> Rapor</span>
            </div>
            <div class="card-body" style="overflow-y: auto;">
                <p class="text-muted small mb-3">Aşağıdaki raporlardan birini seçip <strong>"Ekle"</strong> butonuna basarak, rapor içeriğini imlecin bulunduğu yere doğrudan ekleyebilirsiniz.</p>
                <?php if (empty($existing_reports)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-folder-open fa-2x text-muted mb-2"></i>
                        <p class="text-muted small mb-0">Bu kuruma ait başka bir rapor bulunamadı.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($existing_reports as $rep): 
                            $date_str = date('d.m.Y', strtotime($rep['report_date']));
                            $type_label = '';
                            if ($rep['type'] === 'topraklama') $type_label = 'Topraklama';
                            elseif ($rep['type'] === 'ic_tesisat') $type_label = 'İç Tesisat';
                            elseif ($rep['type'] === 'yildirim') $type_label = 'Yıldırımdan Korunma';
                            elseif ($rep['type'] === 'yangin') $type_label = 'Yangın Algılama';
                            elseif ($rep['type'] === 'sihhi_tesisat') $type_label = 'Sıhhi Tesisat';
                            elseif ($rep['type'] === 'gaz_tesisat') $type_label = 'Gaz Tesisatı';
                            elseif ($rep['type'] === 'isinma_tesisat') $type_label = 'Isınma Tesisatı';
                            elseif ($rep['type'] === 'genlesme_tanki') $type_label = 'Genleşme Tankı';
                            elseif ($rep['type'] === 'engelli_rampasi') $type_label = 'Engelli Rampası';
                            elseif ($rep['type'] === 'boyler_tanki') $type_label = 'Boyler Tankı';
                            elseif ($rep['type'] === 'jenarator') $type_label = 'Jeneratör';
                            elseif ($rep['type'] === 'kamera_bakim') $type_label = 'Kamera Bakım';
                            ?>
                            <div class="list-group-item px-0 py-2 border-bottom">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div style="max-width: 70%;">
                                        <h6 class="mb-0 fw-bold small text-primary"><?php echo $type_label; ?></h6>
                                        <small class="text-muted d-block text-truncate">No: <?php echo htmlspecialchars($rep['report_no']); ?></small>
                                        <small class="text-muted d-block"><?php echo $date_str; ?></small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-import-report" 
                                            data-type="<?php echo $rep['type']; ?>" 
                                            data-id="<?php echo $rep['id']; ?>">
                                        <i class="fas fa-plus"></i> Ekle
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Alt Kaydet Butonu -->
<div class="d-flex justify-content-end mt-3 mb-4">
    <button type="button" class="btn btn-primary btn-lg" id="btnSaveBottom">
        <i class="fas fa-save me-1"></i> Kaydet
    </button>
</div>

<!-- Jodit Editor CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@4/es2021/jodit.min.css">
<script src="https://cdn.jsdelivr.net/npm/jodit@4/es2021/jodit.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reportId = <?php echo $report_id ?: 'null'; ?>;
    const SAVE_URL = '/services/rapor_icerik/api_save.php';
    const UPLOAD_URL = '/services/rapor_icerik/api_upload_image.php';

    // Jodit Editor Başlatma
    const editor = Jodit.make('#editor', {
        height: 600,
        language: 'tr',
        theme: 'default',
        toolbarAdaptive: false,
        askBeforePasteHTML: false,
        askBeforePasteFromWord: false,
        defaultActionOnPaste: 'insert_clear_html',
        defaultActionOnPasteFromWord: 'insert_clear_html',
        buttons: [
            'bold', 'italic', 'underline', 'strikethrough', '|',
            'font', 'fontsize', 'paragraph', '|',
            'ul', 'ol', '|',
            'outdent', 'indent', '|',
            'align', '|',
            'brush', '|',
            'image', 'table', 'link', 'hr', '|',
            'superscript', 'subscript', '|',
            'copyformat', 'eraser', '|',
            'undo', 'redo', '|',
            'fullsize', 'source', 'print', 'preview'
        ],
        buttonsMD: [
            'bold', 'italic', 'underline', '|',
            'font', 'fontsize', 'paragraph', '|',
            'ul', 'ol', '|',
            'align', '|',
            'brush', '|',
            'image', 'table', 'link', '|',
            'undo', 'redo', '|',
            'fullsize', 'dots'
        ],
        buttonsSM: [
            'bold', 'italic', '|',
            'paragraph', '|',
            'ul', 'ol', '|',
            'image', 'table', '|',
            'undo', 'redo', '|',
            'dots'
        ],
        uploader: {
            url: UPLOAD_URL,
            format: 'json',
            filesVariableName: function(i) {
                return 'images[' + i + ']';
            },
            withCredentials: false,
            pathVariableName: 'path',
            prepareData: function(formdata) {
                if (reportId) {
                    formdata.append('report_id', reportId);
                }
                return formdata;
            },
            isSuccess: function(resp) {
                return resp.success;
            },
            process: function(resp) {
                return {
                    files: resp.data.files || [],
                    path: '',
                    baseurl: '',
                    error: resp.data.error || 0,
                    msg: resp.data.message || ''
                };
            },
            defaultHandlerSuccess: function(data) {
                if (data.files && data.files.length) {
                    for (let i = 0; i < data.files.length; i++) {
                        this.s.insertImage(data.files[i]);
                    }
                }
            },
            error: function(e) {
                console.error('Upload error:', e);
                alert('Resim yükleme hatası oluştu.');
            }
        },
        // Tablo ayarları
        table: {
            allowCellSelection: true,
            selectionCellStyle: 'border: 1px double #1e88e5 !important;',
            useExtraClassesOptions: false
        },
        // Stil
        style: {
            font: "'Arial', sans-serif",
            'font-size': '12px'
        },
        // Placeholder
        placeholder: 'Rapor içeriğinizi buraya yazın...\n\nMetin, tablo, resim ve daha fazlasını ekleyebilirsiniz.',
        // Enter tuşu davranışı
        enter: 'p',
        // Tab size
        tabIndex: 0,
        // İzin verilen etiketler
        allowResizeX: false,
        allowResizeY: true,
        saveHeightInStorage: true
    });

    // Kaydetme fonksiyonu
    function saveReport() {
        const title = document.getElementById('raporBaslik').value.trim();
        const content = editor.value;

        if (!title) {
            alert('Lütfen rapor başlığını girin.');
            document.getElementById('raporBaslik').focus();
            return;
        }

        const statusEl = document.getElementById('saveStatus');
        statusEl.textContent = 'Kaydediliyor...';
        statusEl.className = 'text-warning';

        // Kaydet butonlarını devre dışı bırak
        document.getElementById('btnSave').disabled = true;
        document.getElementById('btnSaveBottom').disabled = true;

        const payload = {
            title: title,
            content: content,
            yurt_yoneticisi: document.getElementById('yurt_yoneticisi').value.trim(),
            yatak_kapasitesi: document.getElementById('yatak_kapasitesi').value.trim(),
            is_guvenligi_uzmani: document.getElementById('is_guvenligi_uzmani').value.trim(),
            ada: document.getElementById('ada').value.trim(),
            pafta: document.getElementById('pafta').value.trim(),
            parsel: document.getElementById('parsel').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            report_no: document.getElementById('report_no').value.trim(),
            report_date: document.getElementById('report_date').value,
            control_date: document.getElementById('control_date').value,
            next_control_date: document.getElementById('next_control_date').value,
            mekanik_uzman_id: document.getElementById('mekanik_uzman_id').value,
            elektrik_uzman_id: document.getElementById('elektrik_uzman_id').value
        };

        if (reportId) {
            payload.report_id = reportId;
        }

        fetch(SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusEl.textContent = '✓ Kaydedildi - ' + new Date().toLocaleTimeString('tr-TR');
                statusEl.className = 'text-success';

                // Yeni rapor oluşturulduysa URL'yi güncelle
                if (!reportId && data.report_id) {
                    window.history.replaceState(null, '', 'genel_rapor_duzenle.php?id=' + data.report_id);
                    // Sayfa yenilendiğinde reportId'yi güncelle
                    location.href = 'genel_rapor_duzenle.php?id=' + data.report_id;
                }
            } else {
                statusEl.textContent = '✗ Hata: ' + (data.error || 'Bilinmeyen hata');
                statusEl.className = 'text-danger';
                alert('Kaydetme hatası: ' + (data.error || 'Bilinmeyen hata'));
            }
        })
        .catch(err => {
            console.error('Save error:', err);
            statusEl.textContent = '✗ Bağlantı hatası';
            statusEl.className = 'text-danger';
            alert('Bağlantı hatası oluştu. Lütfen tekrar deneyin.');
        })
        .finally(() => {
            document.getElementById('btnSave').disabled = false;
            document.getElementById('btnSaveBottom').disabled = false;
        });
    }

    // Kaydet butonları
    document.getElementById('btnSave').addEventListener('click', saveReport);
    document.getElementById('btnSaveBottom').addEventListener('click', saveReport);

    // Ctrl+S ile kaydetme
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveReport();
        }
    });

    // Var olan raporları kontrol etme ve butonları güncelleme
    function checkImportedReports() {
        const html = editor.value;
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        const importedBlocks = tempDiv.querySelectorAll('.imported-report-block');
        const importedKeys = new Set();
        importedBlocks.forEach(function(block) {
            const type = block.getAttribute('data-imported-type');
            const id = block.getAttribute('data-imported-id');
            if (type && id) {
                importedKeys.add(type + '-' + id);
            }
        });
        
        document.querySelectorAll('.btn-import-report').forEach(function(btn) {
            const type = btn.getAttribute('data-type');
            const id = btn.getAttribute('data-id');
            const key = type + '-' + id;
            
            if (importedKeys.has(key)) {
                btn.disabled = true;
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-secondary');
                btn.innerHTML = '<i class="fas fa-check"></i> Eklendi';
            } else {
                btn.disabled = false;
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-outline-primary');
                btn.innerHTML = '<i class="fas fa-plus"></i> Ekle';
            }
        });
    }

    // Var olan raporları içe aktarma
    document.querySelectorAll('.btn-import-report').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const type = this.getAttribute('data-type');
            const id = this.getAttribute('data-id');
            const btn = this;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            fetch('../../services/rapor_icerik/get_report_html.php?type=' + type + '&id=' + id)
                .then(function(response) { return response.text(); })
                .then(function(html) {
                    editor.s.insertHTML(html);
                    // İnceleme durumunu hemen güncelle
                    checkImportedReports();
                })
                .catch(function(err) {
                    console.error(err);
                    alert('Rapor içeriği yüklenirken hata oluştu.');
                    checkImportedReports();
                });
        });
    });

    // Editör değişikliklerini dinle
    editor.events.on('change', function() {
        checkImportedReports();
    });

    // Sayfa yüklendiğinde mevcut içeriği kontrol et
    setTimeout(checkImportedReports, 500);
});
</script>

<style>
/* Jodit editör özelleştirmeleri */
.jodit-container {
    border: none !important;
    border-radius: 0 0 15px 15px;
}
.jodit-toolbar__box {
    background: #f8f9fc !important;
    border-bottom: 1px solid rgba(0,0,0,0.08) !important;
}
.jodit-workplace {
    min-height: 400px;
}
/* Editör içi tablo stilleri */
.jodit-wysiwyg table {
    border-collapse: collapse;
    width: 100%;
    margin: 10px 0;
}
.jodit-wysiwyg table td,
.jodit-wysiwyg table th {
    border: 1px solid #333;
    padding: 6px 10px;
    min-width: 40px;
}
.jodit-wysiwyg table th {
    background-color: #f0f0f0;
    font-weight: bold;
}
.jodit-wysiwyg img {
    max-width: 100%;
    height: auto;
}
.jodit-wysiwyg .imported-report-block {
    position: relative;
    border-top: 3px dashed #1f4e79 !important;
    margin-top: 25px !important;
    padding-top: 15px !important;
}
#raporBaslik:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.15);
}
</style>

<?php include '../includes/footer.php'; ?>
