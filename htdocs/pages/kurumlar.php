<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// AJAX handler to get next available kurum_kodu for a specific il_kodu
if (isset($_GET['get_next_code'])) {
    header('Content-Type: application/json');
    $il_kodu = cleanInput($_GET['get_next_code']);
    $next_code = 1;
    $stmt_max = $pdo->prepare("SELECT MAX(CAST(kurum_kodu AS UNSIGNED)) as max_code FROM institutions WHERE user_id = ? AND il_kodu = ?");
    $stmt_max->execute([$_SESSION['user_id'], $il_kodu]);
    $max_row = $stmt_max->fetch();
    if ($max_row && $max_row['max_code'] !== null) {
        $next_code = intval($max_row['max_code']) + 1;
    }
    echo json_encode(['next_code' => str_pad($next_code, 3, '0', STR_PAD_LEFT)]);
    exit;
}

// Auto-add contract_pdf column if not exists
try {
    $pdo->exec("ALTER TABLE institutions ADD COLUMN contract_pdf VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {
    // Column already exists, ignore
}

function deleteInstitutionCascade($pdo, $kurum_id, $user_id) {
    // 1. Child tables of reports
    $child_deletes = [
        "DELETE FROM measurements_5_1 WHERE report_id IN (SELECT id FROM grounding_reports WHERE kurum_id = ?)",
        "DELETE FROM measurements_5_2 WHERE report_id IN (SELECT id FROM grounding_reports WHERE kurum_id = ?)",
        
        "DELETE FROM ic_tesisat_panels WHERE report_id IN (SELECT id FROM internal_installation_reports WHERE kurum_id = ?)",
        "DELETE FROM ic_tesisat_photos WHERE report_id IN (SELECT id FROM internal_installation_reports WHERE kurum_id = ?)",
        "DELETE FROM ic_tesisat_section5 WHERE report_id IN (SELECT id FROM internal_installation_reports WHERE kurum_id = ?)",
        "DELETE FROM ic_tesisat_section6_1 WHERE report_id IN (SELECT id FROM internal_installation_reports WHERE kurum_id = ?)",
        "DELETE FROM ic_tesisat_section6_1_rows WHERE report_id IN (SELECT id FROM internal_installation_reports WHERE kurum_id = ?)",
        "DELETE FROM ic_tesisat_section6_2_rows WHERE report_id IN (SELECT id FROM internal_installation_reports WHERE kurum_id = ?)",
        "DELETE FROM ic_tesisat_section6_3_rows WHERE report_id IN (SELECT id FROM internal_installation_reports WHERE kurum_id = ?)",
        "DELETE FROM ic_tesisat_section6_header WHERE report_id IN (SELECT id FROM internal_installation_reports WHERE kurum_id = ?)",
        
        "DELETE FROM fire_detection_photos WHERE report_id IN (SELECT id FROM fire_detection_reports WHERE kurum_id = ?)",
        "DELETE FROM fire_detection_section5_2 WHERE report_id IN (SELECT id FROM fire_detection_reports WHERE kurum_id = ?)",
        
        "DELETE FROM katodik_koruma_measurements WHERE report_id IN (SELECT id FROM katodik_koruma_reports WHERE kurum_id = ?)",
        
        "DELETE FROM lightning_protection_section4 WHERE report_id IN (SELECT id FROM lightning_protection_reports WHERE kurum_id = ?)"
    ];
    
    foreach ($child_deletes as $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$kurum_id]);
    }
    
    // 2. Report tables
    $report_tables = [
        'boyler_tanki_reports',
        'engelli_rampasi_reports',
        'facility_info',
        'fire_detection_reports',
        'gaz_tesisat_reports',
        'general_reports',
        'genlesme_tanki_reports',
        'grounding_reports',
        'internal_installation_reports',
        'isinma_tesisat_reports',
        'jenarator_reports',
        'kamera_bakim_reports',
        'katodik_koruma_reports',
        'lightning_protection_reports',
        'sihhi_tesisat_reports',
        'yangin_tesisat_reports'
    ];
    
    foreach ($report_tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE kurum_id = ?");
        $stmt->execute([$kurum_id]);
    }
    
    // 3. Delete institution
    $stmt = $pdo->prepare("DELETE FROM institutions WHERE id = ? AND user_id = ?");
    $stmt->execute([$kurum_id, $user_id]);
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)cleanInput($_GET['delete']);
    deleteInstitutionCascade($pdo, $id, $_SESSION['user_id']);
    redirect('kurumlar.php');
}

// Handle Bulk Delete
if (isset($_POST['bulk_delete']) && isset($_POST['selected_institutions'])) {
    $selected_ids = $_POST['selected_institutions'];
    if (is_array($selected_ids)) {
        foreach ($selected_ids as $id) {
            $id = (int)$id;
            deleteInstitutionCascade($pdo, $id, $_SESSION['user_id']);
        }
    }
    if (isset($_SESSION['active_institution_id']) && in_array($_SESSION['active_institution_id'], $selected_ids)) {
        unset($_SESSION['active_institution_id']);
        unset($_SESSION['active_institution_name']);
    }
    redirect('kurumlar.php');
}

// Handle Selection
if (isset($_GET['select'])) {
    $id = cleanInput($_GET['select']);
    $stmt = $pdo->prepare("SELECT id, firma_adi FROM institutions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $inst = $stmt->fetch();
    if ($inst) {
        $_SESSION['active_institution_id'] = $inst['id'];
        $_SESSION['active_institution_name'] = $inst['firma_adi'];
        redirect('forms/tesis_bilgileri.php');
    }
}

// Handle Add/Edit
$editMode = false;

$next_code = 1;
$stmt_max = $pdo->prepare("SELECT MAX(CAST(kurum_kodu AS UNSIGNED)) as max_code FROM institutions WHERE user_id = ? AND il_kodu = ?");
$stmt_max->execute([$_SESSION['user_id'], '01']);
$max_row = $stmt_max->fetch();
if ($max_row && $max_row['max_code'] !== null) {
    $next_code = intval($max_row['max_code']) + 1;
}
$auto_kurum_kodu = str_pad($next_code, 3, '0', STR_PAD_LEFT);

$institution = [
    'firma_adi' => '',
    'adresi' => '',
    'sgk_sicil_no' => '',
    'il_kodu' => '01',
    'kurum_kodu' => $auto_kurum_kodu,
    'isg_katip_id' => '',
    'report_date' => '',
    'start_date' => '',
    'end_date' => '',
    'next_control_date' => '',
    'contract_pdf' => ''
];

if (isset($_GET['edit'])) {
    $editMode = true;
    $id = cleanInput($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM institutions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $institution = $stmt->fetch();
    if (!$institution)
        redirect('kurumlar.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firma_adi = cleanInput($_POST['firma_adi']);
    $adresi = cleanInput($_POST['adresi']);
    $sgk_sicil_no = cleanInput($_POST['sgk_sicil_no']);
    $il_kodu = cleanInput($_POST['il_kodu']);
    $kurum_kodu = cleanInput($_POST['kurum_kodu']);
    $isg_katip_id = cleanInput($_POST['isg_katip_id']);
    $report_date = !empty($_POST['report_date']) ? cleanInput($_POST['report_date']) : null;
    $start_date = !empty($_POST['start_date']) ? cleanInput($_POST['start_date']) : null;
    $end_date = !empty($_POST['end_date']) ? cleanInput($_POST['end_date']) : null;
    $next_control_date = !empty($_POST['next_control_date']) ? cleanInput($_POST['next_control_date']) : null;
    $contract_pdf = !empty($_POST['contract_pdf']) ? cleanInput($_POST['contract_pdf']) : null;

    if ($editMode) {
        $stmt = $pdo->prepare("UPDATE institutions SET firma_adi=?, adresi=?, sgk_sicil_no=?, il_kodu=?, kurum_kodu=?, isg_katip_id=?, report_date=?, start_date=?, end_date=?, next_control_date=?, contract_pdf=? WHERE id=? AND user_id=?");
        $stmt->execute([$firma_adi, $adresi, $sgk_sicil_no, $il_kodu, $kurum_kodu, $isg_katip_id, $report_date, $start_date, $end_date, $next_control_date, $contract_pdf, $_POST['id'], $_SESSION['user_id']]);
    } else {
        // Dynamic auto-increment check per province
        $next_code = 1;
        $stmt_max = $pdo->prepare("SELECT MAX(CAST(kurum_kodu AS UNSIGNED)) as max_code FROM institutions WHERE user_id = ? AND il_kodu = ?");
        $stmt_max->execute([$_SESSION['user_id'], $il_kodu]);
        $max_row = $stmt_max->fetch();
        if ($max_row && $max_row['max_code'] !== null) {
            $next_code = intval($max_row['max_code']) + 1;
        }
        $calculated_kurum_kodu = str_pad($next_code, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO institutions (user_id, firma_adi, adresi, sgk_sicil_no, il_kodu, kurum_kodu, isg_katip_id, report_date, start_date, end_date, next_control_date, contract_pdf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $firma_adi, $adresi, $sgk_sicil_no, $il_kodu, $calculated_kurum_kodu, $isg_katip_id, $report_date, $start_date, $end_date, $next_control_date, $contract_pdf]);
    }
    redirect('kurumlar.php');
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kurumlar</h1>
</div>

<div class="row">
    <!-- Form Column -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <?php echo $editMode ? 'Kurum Düzenle' : 'Yeni Kurum Ekle'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="id" value="<?php echo $institution['id']; ?>">
                    <?php endif; ?>
                    <input type="hidden" name="contract_pdf" id="contract_pdf_val" value="<?php echo htmlspecialchars($institution['contract_pdf'] ?? ''); ?>">

                    <div class="mb-3 border p-3 rounded bg-light shadow-sm">
                        <label class="form-label fw-bold text-primary mb-1">
                            <i class="fas fa-file-pdf me-1"></i>
                            <?php echo $editMode ? 'Sözleşme PDF\'ini Güncelle' : 'Sözleşme PDF\'inden Doldur'; ?>
                        </label>
                        <input type="file" class="form-control form-control-sm" id="contract_pdf_loader" accept=".pdf">
                        <div class="form-text small">Bilgileri otomatik olarak doldurmak için PDF seçin.</div>
                        <div id="pdf_status_message" class="small mt-2 font-weight-bold" style="display:none;"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Firma Adı</label>
                        <input type="text" class="form-control" name="firma_adi"
                            value="<?php echo $institution['firma_adi']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adres</label>
                        <textarea class="form-control" name="adresi"
                            required><?php echo $institution['adresi']; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SGK Sicil No</label>
                        <input type="text" class="form-control" name="sgk_sicil_no"
                            value="<?php echo $institution['sgk_sicil_no']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İSG-KATİP Sözleşme ID</label>
                        <input type="text" class="form-control" name="isg_katip_id"
                            value="<?php echo $institution['isg_katip_id']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Varsayılan Rapor Tarihi</label>
                        <input type="date" class="form-control" name="report_date"
                            value="<?php echo $institution['report_date']; ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Başlangıç Tarih/Saat</label>
                            <input type="datetime-local" class="form-control" name="start_date"
                                value="<?php echo $institution['start_date'] ? date('Y-m-d\TH:i', strtotime($institution['start_date'])) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bitiş Tarih/Saat</label>
                            <input type="datetime-local" class="form-control" name="end_date"
                                value="<?php echo $institution['end_date'] ? date('Y-m-d\TH:i', strtotime($institution['end_date'])) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bir Sonraki Periyodik Kontrol Tarihi</label>
                        <input type="date" class="form-control" name="next_control_date"
                            value="<?php echo $institution['next_control_date']; ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">İl Kodu</label>
                            <input type="text" class="form-control" name="il_kodu"
                                value="<?php echo $institution['il_kodu']; ?>" maxlength="2" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kurum Kodu (Otomatik)</label>
                            <input type="text" class="form-control" name="kurum_kodu"
                                value="<?php echo $institution['kurum_kodu']; ?>" maxlength="3" readonly required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <?php echo $editMode ? 'Güncelle' : 'Kaydet'; ?>
                    </button>
                    <?php if ($editMode): ?>
                        <a href="kurumlar.php" class="btn btn-secondary w-100 mt-2">İptal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- List Column -->
    <div class="col-md-8">
        <form id="bulkDeleteForm" method="POST" action="">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Kurum Listesi</span>
                    <button type="submit" name="bulk_delete" class="btn btn-sm btn-danger shadow-sm" id="btnBulkDelete" style="display: none;" onclick="return confirm('Seçilen kurumları ve onlara ait tüm periyodik kontrol raporlarını kalıcı olarak silmek istediğinize emin misiniz?')">
                        <i class="fas fa-trash-alt me-1"></i> Seçilenleri Sil (<span id="selectedCount">0</span>)
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Firma adı, adres veya Kurum ID'ye göre ara...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 3%;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                                    <th class="sortable" data-column="id" style="cursor: pointer; user-select: none;">ID <i class="fas fa-sort text-muted ms-1"></i></th>
                                    <th class="sortable" data-column="firma_adi" style="cursor: pointer; user-select: none;">Firma Adı <i class="fas fa-sort text-muted ms-1"></i></th>
                                    <th class="sortable" data-column="adresi" style="cursor: pointer; user-select: none;">Adres <i class="fas fa-sort text-muted ms-1"></i></th>
                                    <th class="sortable" data-column="kurum_id" style="cursor: pointer; user-select: none;">Kurum ID <i class="fas fa-sort text-muted ms-1"></i></th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM institutions WHERE user_id = ? ORDER BY id DESC");
                                $stmt->execute([$_SESSION['user_id']]);
                                while ($row = $stmt->fetch()):
                                    $isActive = (isset($_SESSION['active_institution_id']) && $_SESSION['active_institution_id'] == $row['id']);
                                    ?>
                                    <tr class="<?php echo $isActive ? 'table-success' : ''; ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input institution-checkbox" name="selected_institutions[]" value="<?php echo $row['id']; ?>">
                                        </td>
                                        <td>
                                            <?php echo $row['id']; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['firma_adi']); ?>
                                        </td>
                                        <td title="<?php echo htmlspecialchars($row['adresi']); ?>">
                                            <?php echo htmlspecialchars(substr($row['adresi'], 0, 30)) . '...'; ?>
                                        </td>
                                        <td><span class="badge bg-info text-dark">
                                                <?php echo $row['il_kodu'] . '-' . $row['kurum_kodu']; ?>
                                            </span></td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if ($isActive): ?>
                                                    <button type="button" class="btn btn-sm btn-success" disabled title="Seçili Kurum">
                                                        <i class="fas fa-check-circle"></i> Seçili
                                                    </button>
                                                <?php else: ?>
                                                    <a href="?select=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Kurumu Seç ve Başla">
                                                        <i class="fas fa-play"></i> Seç
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($row['contract_pdf'])): ?>
                                                    <a href="../uploads/sozlesmeler/<?php echo htmlspecialchars($row['contract_pdf']); ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="İSG Katip Sözleşmesi">
                                                        <i class="fas fa-file-pdf text-danger me-1"></i> Sözleşme
                                                    </a>
                                                <?php else: ?>
                                                    <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Sözleşme Yükle">
                                                        <i class="fas fa-file-pdf text-muted me-1"></i> Sözleşme Yok
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Düzenle">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Sil"
                                                    onclick="return confirm('Bu kurumu ve buna ait tüm periyodik raporları silmek istediğinize emin misiniz?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Diagnostic console and screen overlay logger for any Javascript errors (registered immediately)
window.addEventListener('error', function(e) {
    console.error("Global JS Error Captured:", e);
    const errDiv = document.createElement('div');
    errDiv.style.position = 'fixed';
    errDiv.style.bottom = '10px';
    errDiv.style.left = '10px';
    errDiv.style.backgroundColor = '#dc3545';
    errDiv.style.color = 'white';
    errDiv.style.padding = '8px 12px';
    errDiv.style.zIndex = '9999';
    errDiv.style.borderRadius = '4px';
    errDiv.style.fontFamily = 'monospace';
    errDiv.style.fontSize = '11px';
    errDiv.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
    errDiv.textContent = 'JS Hata: ' + e.message + ' (' + e.filename.split('/').pop() + ':' + e.lineno + ')';
    document.body.appendChild(errDiv);
});

function initKurumlarPage() {
    const ilKoduInput = document.querySelector('input[name="il_kodu"]');
    const kurumKoduInput = document.querySelector('input[name="kurum_kodu"]');
    
    if (ilKoduInput && kurumKoduInput) {
        ilKoduInput.addEventListener('input', function() {
            const val = this.value.trim();
            if (val.length === 2 && !isNaN(val)) {
                fetch(`?get_next_code=${val}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.next_code) {
                            kurumKoduInput.value = data.next_code;
                        }
                    })
                    .catch(err => console.error('Error fetching next code:', err));
            }
        });
    }

    const pdfLoader = document.getElementById('contract_pdf_loader');
    const statusMsg = document.getElementById('pdf_status_message');
    
    if (pdfLoader) {
        pdfLoader.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            statusMsg.style.display = 'block';
            statusMsg.className = 'small text-muted';
            statusMsg.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> PDF okunuyor ve veriler çözümleniyor, lütfen bekleyin...';
            
            const formData = new FormData();
            formData.append('contract_pdf', file);
            
            fetch('../services/parse_contract_pdf.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    statusMsg.className = 'small text-danger';
                    statusMsg.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Hata: ' + data.error;
                } else {
                    statusMsg.className = 'small text-success';
                    statusMsg.innerHTML = '<i class="fas fa-check-circle me-1"></i> PDF verileri başarıyla yüklendi ve forma aktarıldı!';
                    
                    if (data.contract_pdf) {
                        document.getElementById('contract_pdf_val').value = data.contract_pdf;
                    }
                    
                    if (data.start_date) {
                        const dateParts = data.start_date.split('-');
                        if (dateParts.length === 3) {
                            const year = parseInt(dateParts[0]);
                            const month = dateParts[1];
                            const day = dateParts[2];
                            
                            const reportDate = `${year}-${month}-${day}`;
                            const startDateLocal = `${year}-${month}-${day}T08:00`;
                            const endDateLocal = `${year}-${month}-${day}T17:00`;
                            const nextControlDate = `${year + 1}-${month}-${day}`;
                            
                            document.querySelector('input[name="report_date"]').value = reportDate;
                            document.querySelector('input[name="start_date"]').value = startDateLocal;
                            document.querySelector('input[name="end_date"]').value = endDateLocal;
                            document.querySelector('input[name="next_control_date"]').value = nextControlDate;
                        }
                    }
                    
                    if (data.city_code) {
                        const ilInput = document.querySelector('input[name="il_kodu"]');
                        if (ilInput) {
                            ilInput.value = data.city_code;
                            ilInput.dispatchEvent(new Event('input'));
                        }
                    }
                    
                    if (data.firma_adi) {
                        document.querySelector('input[name="firma_adi"]').value = data.firma_adi;
                    }
                    if (data.adres) {
                        document.querySelector('textarea[name="adresi"]').value = data.adres;
                    }
                    if (data.sgk_no) {
                        document.querySelector('input[name="sgk_sicil_no"]').value = data.sgk_no;
                    }
                    if (data.isg_katip_id) {
                        document.querySelector('input[name="isg_katip_id"]').value = data.isg_katip_id;
                    }
                }
            })
            .catch(err => {
                statusMsg.className = 'small text-danger';
                statusMsg.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Sunucu ile bağlantı kurulamadı.';
                console.error(err);
            });
        });
    }

    // Turkish-friendly lowercase converter helper
    function safeLowerCase(str) {
        if (!str) return '';
        try {
            return str.toLocaleLowerCase('tr');
        } catch (err) {
            return str.toLowerCase();
        }
    }

    // Client-side Search/Filter logic
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            try {
                const query = safeLowerCase(this.value).trim();
                const rows = document.querySelectorAll('.col-md-8 .table tbody tr');
                
                rows.forEach(row => {
                    const c1 = row.children[1];
                    const c2 = row.children[2];
                    const c3 = row.children[3];
                    const c4 = row.children[4];
                    
                    const idCell = c1 ? safeLowerCase(c1.textContent || c1.innerText) : '';
                    const nameCell = c2 ? safeLowerCase(c2.textContent || c2.innerText) : '';
                    const addressCell = c3 ? safeLowerCase(c3.textContent || c3.innerText) : '';
                    const kurumIdCell = c4 ? safeLowerCase(c4.textContent || c4.innerText) : '';
                    
                    const matches = idCell.includes(query) || 
                                    nameCell.includes(query) || 
                                    addressCell.includes(query) || 
                                    kurumIdCell.includes(query);
                                    
                    row.style.display = matches ? '' : 'none';
                });
            } catch (err) {
                console.error("Search failed:", err);
            }
        });
    }

    // Bulk Delete UI handlers
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.institution-checkbox');
    const btnBulkDelete = document.getElementById('btnBulkDelete');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectAll && btnBulkDelete) {
        function updateBulkDeleteButton() {
            const checkedCount = document.querySelectorAll('.institution-checkbox:checked').length;
            if (checkedCount > 0) {
                btnBulkDelete.style.display = '';
                selectedCount.textContent = checkedCount;
            } else {
                btnBulkDelete.style.display = 'none';
            }
        }
        
        selectAll.addEventListener('change', function() {
            const isChecked = this.checked;
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') {
                    cb.checked = isChecked;
                }
            });
            updateBulkDeleteButton();
        });
        
        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteButton);
        });
    }

    // Client-side Column Sorting logic
    const table = document.querySelector('.col-md-8 .table');
    const tbody = table ? table.querySelector('tbody') : null;
    const headers = table ? table.querySelectorAll('th.sortable') : [];
    let currentColumn = '';
    let isAsc = true;
    
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            try {
                const column = this.getAttribute('data-column');
                const columnIndex = Array.from(this.parentNode.children).indexOf(this);
                
                if (currentColumn === column) {
                    isAsc = !isAsc;
                } else {
                    currentColumn = column;
                    isAsc = true;
                }
                
                // Reset all icons
                headers.forEach(h => {
                    const icon = h.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-sort text-muted ms-1';
                    }
                });
                
                // Set current active icon
                const activeIcon = this.querySelector('i');
                if (activeIcon) {
                    activeIcon.className = isAsc ? 'fas fa-sort-up ms-1' : 'fas fa-sort-down ms-1';
                }
                
                if (!tbody) return;
                
                // Sort rows
                const rows = Array.from(tbody.querySelectorAll('tr'));
                rows.sort((a, b) => {
                    const cellA = a.children[columnIndex];
                    const cellB = b.children[columnIndex];
                    
                    let valA = cellA ? (cellA.textContent || cellA.innerText || '').trim() : '';
                    let valB = cellB ? (cellB.textContent || cellB.innerText || '').trim() : '';
                    
                    if (column === 'id') {
                        return isAsc ? (parseInt(valA) - parseInt(valB)) : (parseInt(valB) - parseInt(valA));
                    }
                    
                    return isAsc ? valA.localeCompare(valB, 'tr') : valB.localeCompare(valA, 'tr');
                });
                
                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
            } catch (err) {
                console.error("Sorting failed:", err);
            }
        });
    });
}

// Immediate execution or fallback to DOMContentLoaded
if (document.readyState !== 'loading') {
    initKurumlarPage();
} else {
    document.addEventListener('DOMContentLoaded', initKurumlarPage);
}
</script>

<?php include '../includes/footer.php'; ?>