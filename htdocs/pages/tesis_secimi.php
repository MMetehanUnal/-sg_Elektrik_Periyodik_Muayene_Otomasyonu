<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Handle Logout Institution
if (isset($_GET['action']) && $_GET['action'] == 'logout_institution') {
    unset($_SESSION['active_institution_id']);
    unset($_SESSION['active_institution_name']);
    redirect('tesis_secimi.php');
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

// Handle Selection
if (isset($_GET['select'])) {
    $id = cleanInput($_GET['select']);

    // Verify user owns this institution
    $stmt = $pdo->prepare("SELECT id, firma_adi FROM institutions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $inst = $stmt->fetch();

    if ($inst) {
        $_SESSION['active_institution_id'] = $inst['id'];
        $_SESSION['active_institution_name'] = $inst['firma_adi'];

        // Redirect to tesis bilgileri (facility info) after selection
        redirect('forms/tesis_bilgileri.php');
    }
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
    redirect('tesis_secimi.php');
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kurum Seçimi</h1>
</div>

<div class="row">
    <div class="col-md-12">
        <?php if (isset($_SESSION['active_institution_id'])): ?>
            <div class="alert alert-success">
                <strong>Aktif Seçim:</strong>
                <?php echo htmlspecialchars($_SESSION['active_institution_name']); ?> üzerinde çalışıyorsunuz.
                <a href="?action=logout_institution" class="btn btn-sm btn-danger ms-3">Oturumu Kapat</a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                Lütfen işlem yapmak için listeden bir tesis seçiniz.
            </div>
        <?php endif; ?>

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
                            <input type="text" id="searchInput" class="form-control" placeholder="Kurum kodu, firma adı veya adrese göre ara...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 3%;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                                    <th class="sortable" data-column="kurum_kodu" style="cursor: pointer; user-select: none;">Kurum Kodu <i class="fas fa-sort text-muted ms-1"></i></th>
                                    <th class="sortable" data-column="firma_adi" style="cursor: pointer; user-select: none;">Firma Adı <i class="fas fa-sort text-muted ms-1"></i></th>
                                    <th class="sortable" data-column="adresi" style="cursor: pointer; user-select: none;">Adres <i class="fas fa-sort text-muted ms-1"></i></th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $active_id = $_SESSION['active_institution_id'] ?? 0;
                                $stmt = $pdo->prepare("SELECT * FROM institutions WHERE user_id = ? ORDER BY (id = ?) DESC, firma_adi ASC");
                                $stmt->execute([$_SESSION['user_id'], $active_id]);
                                while ($row = $stmt->fetch()):
                                    $isActive = (isset($_SESSION['active_institution_id']) && $_SESSION['active_institution_id'] == $row['id']);
                                    ?>
                                    <tr class="<?php echo $isActive ? 'table-success' : ''; ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input institution-checkbox" name="selected_institutions[]" value="<?php echo $row['id']; ?>">
                                        </td>
                                        <td>
                                            <?php echo $row['il_kodu'] . '-' . $row['kurum_kodu']; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['firma_adi']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['adresi']); ?>
                                        </td>
                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-success">Seçili</span>
                                            <?php else: ?>
                                                <a href="?select=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Seç ve
                                                    Başla</a>
                                            <?php endif; ?>
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

function initTesisSecimiPage() {
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
                const rows = document.querySelectorAll('.card-body .table tbody tr');
                
                rows.forEach(row => {
                    const isActive = row.classList.contains('table-success');
                    if (isActive) {
                        row.style.display = ''; // Keep selected row always visible at the top
                        return;
                    }
                    
                    const c1 = row.children[1];
                    const c2 = row.children[2];
                    const c3 = row.children[3];
                    
                    const codeCell = c1 ? safeLowerCase(c1.textContent || c1.innerText) : '';
                    const nameCell = c2 ? safeLowerCase(c2.textContent || c2.innerText) : '';
                    const addressCell = c3 ? safeLowerCase(c3.textContent || c3.innerText) : '';
                    
                    const matches = codeCell.includes(query) || 
                                    nameCell.includes(query) || 
                                    addressCell.includes(query);
                                    
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
    const table = document.querySelector('.card-body .table');
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
                    const isActiveA = a.classList.contains('table-success');
                    const isActiveB = b.classList.contains('table-success');
                    
                    if (isActiveA) return -1;
                    if (isActiveB) return 1;
                    
                    const cellA = a.children[columnIndex];
                    const cellB = b.children[columnIndex];
                    
                    let valA = cellA ? (cellA.textContent || cellA.innerText || '').trim() : '';
                    let valB = cellB ? (cellB.textContent || cellB.innerText || '').trim() : '';
                    
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
    initTesisSecimiPage();
} else {
    document.addEventListener('DOMContentLoaded', initTesisSecimiPage);
}
</script>

<?php include '../includes/footer.php'; ?>