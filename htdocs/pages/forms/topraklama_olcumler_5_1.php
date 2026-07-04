<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$report_id = isset($_GET['report_id']) ? cleanInput($_GET['report_id']) : null;
if (!$report_id) {
    die("Rapor ID gerekli.");
}

// Fetch current report to get kurum_id
$stmt = $pdo->prepare("SELECT kurum_id FROM grounding_reports WHERE id = ?");
$stmt->execute([$report_id]);
$rpt_info = $stmt->fetch();
$kurum_id = $rpt_info['kurum_id'] ?? null;

if (!$kurum_id) {
    die("Kurum bilgisi bulunamadı.");
}

// Handle Import from İç Tesisat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_ic_tesisat') {
    $source_report_id = (int)$_POST['source_report_id'];
    
    $stmt = $pdo->prepare("
        SELECT r.*, p.panel_name
        FROM ic_tesisat_section6_1_rows r
        JOIN ic_tesisat_panels p ON r.panel_id = p.id
        WHERE p.report_id = ?
        ORDER BY p.panel_order, r.id
    ");
    $stmt->execute([$source_report_id]);
    $rows_to_import = $stmt->fetchAll();
    
    if ($rows_to_import) {
        $pdo->prepare("DELETE FROM measurements_5_1 WHERE report_id = ?")->execute([$report_id]);
        
        $ins = $pdo->prepare("INSERT INTO measurements_5_1 
            (report_id, point_no, point_name, prot_in, prot_type, prot_ia, limit_zs_ra, rcd_type_limits, rcd_test_ia, rcd_test_ta, result)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
        $no = 1;
        foreach ($rows_to_import as $r) {
            $pName = trim($r['panel_name'] ?? '');
            $lName = trim($r['linye_adi'] ?? '');
            if ($lName === '') {
                $point_name = $pName;
            } elseif ($pName === '' || $pName === $lName) {
                $point_name = $lName;
            } else {
                $point_name = $pName . " - " . $lName;
            }
            $in_a = $r['in_a'] ?: '';
            $ia = '';
            if (is_numeric($in_a)) {
                $ia = (float)$in_a * 10;
            }
            $ins->execute([
                $report_id,
                $no++,
                $point_name,
                $in_a,
                $r['acma_egrisi'] ?: '',
                $ia,
                '250',
                'AC/40/30',
                $r['rcd_ia'] ?: '',
                $r['rcd_ta'] ?: '',
                $r['sonuc'] ?: ''
            ]);
        }
        $success_msg = "İç Tesisat verileri başarıyla miras alındı.";
    } else {
        $error_msg = "Seçilen raporda linye verisi bulunamadı.";
    }
}

// Handle Form Submission (Only if not an import action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $measurements = [];

    // Check if data came as JSON (to bypass max_input_vars)
    if (isset($_POST['measurements_json']) && !empty($_POST['measurements_json'])) {
        $measurements = json_decode($_POST['measurements_json'], true);
    } elseif (isset($_POST['measurements'])) {
        $measurements = $_POST['measurements'];
    }

    // First, clear existing measurements for this report (simple way to handle updates/deletes)
    // In a production app, might want to be smarter to preserve IDs, but this is fine for now.
    $stmt = $pdo->prepare("DELETE FROM measurements_5_1 WHERE report_id = ?");
    $stmt->execute([$report_id]);

    $stmt = $pdo->prepare("INSERT INTO measurements_5_1 
        (report_id, point_no, point_name, prot_in, prot_type, prot_ia, prot_ik1, 
        measured_zx_rx, limit_zs_ra, rcd_type_limits, rcd_test_ia, rcd_test_ta, result)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($measurements as $row) {
        if (empty($row['point_name']))
            continue; // Skip empty rows

        $stmt->execute([
            $report_id,
            $row['point_no'] ?? '',
            $row['point_name'] ?? '',
            $row['prot_in'] ?? '',
            $row['prot_type'] ?? '',
            $row['prot_ia'] ?? '',
            $row['prot_ik1'] ?? '',
            $row['measured_zx_rx'] ?? '',
            $row['limit_zs_ra'] ?? '',
            $row['rcd_type_limits'] ?? '',
            $row['rcd_test_ia'] ?? '',
            $row['rcd_test_ta'] ?? '',
            $row['result'] ?? ''
        ]);
    }

    // Check if "Save & Next" or just "Save"
    if (isset($_POST['next'])) {
        redirect("topraklama_olcumler_5_2.php?report_id=$report_id");
    } else {
        $success_msg = "Kayıt Başarılı";
    }
}

// Fetch available ic tesisat reports
$stmt = $pdo->prepare("SELECT id, report_no, report_date, firma_adi_eki FROM internal_installation_reports WHERE kurum_id = ? ORDER BY report_date DESC");
$stmt->execute([$kurum_id]);
$ic_tesisat_reports = $stmt->fetchAll();

// Fetch existing measurements
$stmt = $pdo->prepare("SELECT * FROM measurements_5_1 WHERE report_id = ? ORDER BY point_no ASC");
$stmt->execute([$report_id]);
$existing_measurements = $stmt->fetchAll();

// Ensure at least a few empty rows if none exist
if (count($existing_measurements) == 0) {
    for ($i = 1; $i <= 3; $i++) {
        $existing_measurements[] = ['point_no' => $i];
    }
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">5.1 Dolaylı Dokunmaya Karşı Koruma</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="topraklama_kontrol.php" class="btn btn-secondary me-2">Geri</a>
        <a href="topraklama_olcumler_5_2.php?report_id=<?php echo $report_id; ?>" class="btn btn-outline-primary">5.2'ye
            Geç</a>
    </div>
</div>

<?php if (isset($success_msg)): ?>
    <div class="alert alert-success">
        <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_GET['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger">
        <?php echo $error_msg; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">İç Tesisat'tan Veri Miras Al</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-2 align-items-end">
                    <input type="hidden" name="action" value="import_ic_tesisat">
                    <div class="col-grow">
                        <label class="form-label small">İç Tesisat Raporu Seçin</label>
                        <select name="source_report_id" class="form-select form-select-sm" required>
                            <option value="">Rapor Seçiniz...</option>
                            <?php foreach ($ic_tesisat_reports as $icr): ?>
                                <option value="<?php echo $icr['id']; ?>">
                                    <?php 
                                    $label = $icr['report_no'];
                                    if (!empty($icr['firma_adi_eki'])) {
                                        $label .= " - " . $icr['firma_adi_eki'];
                                    }
                                    $label .= " (" . date('d.m.Y', strtotime($icr['report_date'])) . ")";
                                    echo htmlspecialchars($label); 
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Mevcut ölçümler silinecek ve seçilen rapordaki linye verileri aktarılacak. Onaylıyor musunuz?')">
                            <i class="fas fa-file-import"></i> Verileri Getir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">CSV ile Veri Yükleme</h5>
                <div>
                    <a href="csv_handler.php?action=download&type=5_1" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-file-download"></i> CSV Şablonu İndir
                    </a>
                    <a href="csv_handler.php?action=download&type=5_1&report_id=<?php echo $report_id; ?>&current=1" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-download"></i> Mevcut Verileri İndir (CSV)
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="csv_handler.php?action=upload&type=5_1&report_id=<?php echo $report_id; ?>" method="POST"
                    enctype="multipart/form-data" class="row g-3">
                    <div class="col-auto">
                        <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-file-upload"></i> CSV'den Yükle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="" id="mainForm">
    <div class="table-responsive">
        <table class="table table-bordered table-sm" id="measurementsTable">
            <thead class="table-light">
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Ölçüm noktası /<br>Etiketi veya kodu</th>
                    <th colspan="3" class="text-center">Koruma Elemanının</th>
                    <th rowspan="2">Toprak<br>kısa devre<br>akımı<br>Ik1 (A)</th>
                    <th colspan="2" class="text-center">Ölçüm</th>
                    <th rowspan="2">RCD tipi, dayanma akımı ve açma akımı<br>In(A) / IΔn(mA)</th>
                    <th colspan="2" class="text-center">RCD Testi</th>
                    <th rowspan="2">Sonuç<br>(Uygunluk notu)</th>
                    <th rowspan="2"></th>
                </tr>
                <tr>
                    <th>In(A)</th>
                    <th>Açma eğrisi tipi</th>
                    <th>Açma akımı Ia(A)</th>
                    <th>Ölçülen değer Zx/Rx(Ω)</th>
                    <th>Sınır değer Zs /RA(Ω)</th>
                    <th>Açma akımı IΔ(mA)</th>
                    <th>Açma zamanı TΔ(ms)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($existing_measurements as $index => $row): ?>
                    <tr>
                        <td><input type="number" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][point_no]"
                                value="<?php echo isset($row['point_no']) ? $row['point_no'] : ($index + 1); ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][point_name]"
                                value="<?php echo $row['point_name'] ?? ''; ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][prot_in]"
                                value="<?php echo $row['prot_in'] ?? ''; ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][prot_type]"
                                value="<?php echo $row['prot_type'] ?? ''; ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][prot_ia]"
                                value="<?php echo $row['prot_ia'] ?? ''; ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][prot_ik1]"
                                value="<?php echo $row['prot_ik1'] ?? ''; ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][measured_zx_rx]"
                                value="<?php echo $row['measured_zx_rx'] ?? ''; ?>"></td>
                        <td>
                            <select class="form-select form-select-sm" name="measurements[<?php echo $index; ?>][limit_zs_ra]">
                                <?php $zval = $row['limit_zs_ra'] ?? '250'; ?>
                                <option value="250" <?php echo ($zval == '250') ? 'selected' : ''; ?>>250</option>
                                <option value="83" <?php echo ($zval == '83') ? 'selected' : ''; ?>>83</option>
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][rcd_type_limits]"
                                value="<?php echo (!isset($row['rcd_type_limits']) || $row['rcd_type_limits'] === '') ? 'AC/40/30' : htmlspecialchars($row['rcd_type_limits']); ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][rcd_test_ia]"
                                value="<?php echo $row['rcd_test_ia'] ?? ''; ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][rcd_test_ta]"
                                value="<?php echo $row['rcd_test_ta'] ?? ''; ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][result]"
                                value="<?php echo $row['result'] ?? ''; ?>"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i
                                    class="fas fa-times"></i></button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button type="button" class="btn btn-secondary mb-3" id="addRow">Satır Ekle</button>
    <button type="button" class="btn btn-danger mb-3" id="clearAllBtn">Tüm Satırları Sil</button>
    <button type="button" class="btn btn-warning mb-3" id="fillRandomBtn"><i class="fas fa-random"></i> Rastgele Test Verisi İle Doldur</button>
    <br>
    <input type="hidden" name="measurements_json" id="measurements_json">
    <button type="button" class="btn btn-primary" id="saveBtn">Kaydet</button>
    <button type="button" class="btn btn-success" id="nextBtn">Kaydet ve İlerle (5.2)</button>
    <input type="hidden" name="save" id="save_trigger" value="">
    <input type="hidden" name="next" id="next_trigger" value="">
</form>

<script>
    function prepareAndSubmit(triggerName) {
        const rows = [];
        const tableRows = document.querySelectorAll('#measurementsTable tbody tr');

        tableRows.forEach((tr, index) => {
            const row = {};
            const inputs = tr.querySelectorAll('input, select');
            inputs.forEach(input => {
                // Get the field name from the name attribute: measurements[index][field_name]
                const match = input.name.match(/\[([^\]]+)\]$/);
                if (match) {
                    const fieldName = match[1];
                    row[fieldName] = input.value;
                }
            });
            rows.push(row);
        });

        document.getElementById('measurements_json').value = JSON.stringify(rows);
        document.getElementById(triggerName + '_trigger').value = '1';

        // Disable individual inputs to prevent them from being sent and hitting the limit
        document.querySelectorAll('#measurementsTable input, #measurementsTable select').forEach(input => {
            input.disabled = true;
        });

        document.getElementById('mainForm').submit();
    }

    document.getElementById('saveBtn').addEventListener('click', () => prepareAndSubmit('save'));
    document.getElementById('nextBtn').addEventListener('click', () => prepareAndSubmit('next'));

    document.getElementById('addRow').addEventListener('click', function () {
        var tableBody = document.querySelector('#measurementsTable tbody');
        var newRow = tableBody.rows[0].cloneNode(true);
        var rowCount = tableBody.rows.length;

        // Update inputs
        var inputs = newRow.querySelectorAll('input, select');
        inputs.forEach(function (input) {
            if (input.tagName === 'INPUT') {
                if (input.name.includes('[rcd_type_limits]')) {
                    input.value = 'AC/40/30';
                } else {
                    input.value = '';
                }
            } else if (input.tagName === 'SELECT' && input.name.includes('[limit_zs_ra]')) {
                input.value = '250';
            }
            input.name = input.name.replace(/\[\d+\]/, '[' + rowCount + ']');
        });

        // Set point No
        inputs[0].value = rowCount + 1;

        tableBody.appendChild(newRow);
    });

    document.addEventListener('click', function (e) {
        if (e.target && e.target.closest('.remove-row')) {
            var row = e.target.closest('tr');
            if (document.querySelectorAll('#measurementsTable tbody tr').length > 1) {
                row.remove();
            }
        }
    });

    document.getElementById('clearAllBtn').addEventListener('click', function () {
        if (confirm('Tüm satırları silmek istediğinize emin misiniz?')) {
            var tableBody = document.querySelector('#measurementsTable tbody');
            while (tableBody.rows.length > 1) {
                tableBody.deleteRow(1);
            }
            var firstRow = tableBody.rows[0];
            var inputs = firstRow.querySelectorAll('input, select');
            inputs.forEach(function (input) {
                if (input.tagName === 'INPUT') {
                    if (input.type === 'number' && input.name.includes('[point_no]')) {
                        input.value = '1';
                    } else if (input.name.includes('[rcd_type_limits]')) {
                        input.value = 'AC/40/30';
                    } else {
                        input.value = '';
                    }
                } else if (input.tagName === 'SELECT') {
                    input.value = '250';
                }
            });
        }
    });

    document.getElementById('fillRandomBtn').addEventListener('click', function () {
        const tableRows = document.querySelectorAll('#measurementsTable tbody tr');
        tableRows.forEach(tr => {
            const ik1Input = tr.querySelector('input[name*="[prot_ik1]"]');
            const zxInput = tr.querySelector('input[name*="[measured_zx_rx]"]');
            
            if (ik1Input) {
                // Random between 0.8 and 2.6, 2 decimal places
                const randomIk1 = (Math.random() * (2.6 - 0.8) + 0.8).toFixed(2);
                ik1Input.value = randomIk1;
            }
            if (zxInput) {
                // Random between 0.00 and 5.00, 2 decimal places
                const randomZx = (Math.random() * 5.0).toFixed(2);
                zxInput.value = randomZx;
            }
        });
    });

    // Auto-calculate Ia = In * 10
    document.getElementById('measurementsTable').addEventListener('input', function (e) {
        if (e.target && e.target.name && e.target.name.includes('[prot_in]')) {
            const tr = e.target.closest('tr');
            const inVal = parseFloat(e.target.value);
            const iaInput = tr.querySelector('input[name*="[prot_ia]"]');
            if (iaInput) {
                if (!isNaN(inVal)) {
                    iaInput.value = inVal * 10;
                } else {
                    iaInput.value = '';
                }
            }
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>