<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$report_id = isset($_GET['report_id']) ? cleanInput($_GET['report_id']) : null;
if (!$report_id) {
    die("Rapor ID gerekli.");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Reset / Re-import from 5.1
    if (isset($_POST['action']) && $_POST['action'] === 'reset_from_5_1') {
        $stmt = $pdo->prepare("DELETE FROM measurements_5_2 WHERE report_id = ?");
        $stmt->execute([$report_id]);
        redirect("topraklama_olcumler_5_2.php?report_id=$report_id&success=" . urlencode("5.2 verileri 5.1'den yeniden aktarılarak sıfırlandı."));
    }

    $measurements = [];

    // Check if data came as JSON (to bypass max_input_vars)
    if (isset($_POST['measurements_json']) && !empty($_POST['measurements_json'])) {
        $measurements = json_decode($_POST['measurements_json'], true);
    } elseif (isset($_POST['measurements'])) {
        $measurements = $_POST['measurements'];
    }

    $stmt = $pdo->prepare("DELETE FROM measurements_5_2 WHERE report_id = ?");
    $stmt->execute([$report_id]);

    $stmt = $pdo->prepare("INSERT INTO measurements_5_2 
        (report_id, row_no, upstream_panel, upstream_rcd_type, upstream_rcd_in, upstream_rcd_idn, upstream_rcd_dt, 
        downstream_panel, downstream_rcd_type, downstream_rcd_idn, downstream_rcd_t, result)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($measurements as $row) {
        if (empty($row['upstream_panel']))
            continue;

        $stmt->execute([
            $report_id,
            $row['row_no'] ?? '',
            $row['upstream_panel'] ?? '',
            $row['upstream_rcd_type'] ?? '',
            $row['upstream_rcd_in'] ?? '',
            $row['upstream_rcd_idn'] ?? '',
            $row['upstream_rcd_dt'] ?? '',
            $row['downstream_panel'] ?? '',
            $row['downstream_rcd_type'] ?? '',
            $row['downstream_rcd_idn'] ?? '',
            $row['downstream_rcd_t'] ?? '',
            $row['result'] ?? ''
        ]);
    }

    $success_msg = "Kayıt Başarılı. Kontrol tamamlandı.";
}

// Fetch existing measurements
$stmt = $pdo->prepare("SELECT * FROM measurements_5_2 WHERE report_id = ? ORDER BY row_no ASC");
$stmt->execute([$report_id]);
$existing_measurements = $stmt->fetchAll();

if (count($existing_measurements) == 0) {
    // Fetch from 5.1 to autofill
    $stmt51 = $pdo->prepare("SELECT * FROM measurements_5_1 WHERE report_id = ? ORDER BY point_no ASC");
    $stmt51->execute([$report_id]);
    $data_51 = $stmt51->fetchAll();

    if (count($data_51) > 0) {
        $idx = 1;
        foreach ($data_51 as $row51) {
            $dpName = $row51['point_name'];
            $parts = explode(' - ', $dpName);
            if (count($parts) === 2 && trim($parts[0]) === trim($parts[1])) {
                $dpName = trim($parts[0]);
            }

            $existing_measurements[] = [
                'row_no' => $idx++,
                'upstream_panel' => 'Ana pano',
                'upstream_rcd_type' => 'AC',
                'upstream_rcd_in' => '40',
                'upstream_rcd_idn' => '30',
                'upstream_rcd_dt' => '0',
                'downstream_panel' => $dpName,
                'downstream_rcd_type' => 'AC',
                'downstream_rcd_idn' => $row51['rcd_test_ia'],
                'downstream_rcd_t' => $row51['rcd_test_ta'],
                'result' => '1'
            ];
        }
    } else {
        for ($i = 1; $i <= 2; $i++) {
            $existing_measurements[] = [
                'row_no' => $i,
                'upstream_panel' => 'Ana pano',
                'upstream_rcd_type' => 'AC',
                'upstream_rcd_in' => '40',
                'upstream_rcd_idn' => '30',
                'upstream_rcd_dt' => '0',
                'result' => '1'
            ];
        }
    }
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">5.2 RCD Selektivite Kontrolü</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Mevcut 5.2 verileri silinecek ve 5.1\'den yeniden aktarılacaktır. Emin misiniz?');">
            <input type="hidden" name="action" value="reset_from_5_1">
            <button type="submit" class="btn btn-warning me-2">
                <i class="fas fa-sync-alt me-1"></i> 5.1'den Yeniden Aktar (Sıfırla)
            </button>
        </form>
        <a href="topraklama_olcumler_5_1.php?report_id=<?php echo $report_id; ?>"
            class="btn btn-secondary me-2">Geri</a>
        <a href="../raporlar.php" class="btn btn-primary">Raporlara Git</a>
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

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">CSV ile Veri Yükleme</h5>
        <div>
            <a href="csv_handler.php?action=download&type=5_2" class="btn btn-sm btn-outline-secondary me-2">
                <i class="fas fa-file-download"></i> CSV Şablonu İndir
            </a>
            <a href="csv_handler.php?action=download&type=5_2&report_id=<?php echo $report_id; ?>&current=1" class="btn btn-sm btn-outline-success">
                <i class="fas fa-download"></i> Mevcut Verileri İndir (CSV)
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="csv_handler.php?action=upload&type=5_2&report_id=<?php echo $report_id; ?>" method="POST"
            enctype="multipart/form-data" class="row g-3">
            <div class="col-auto">
                <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fas fa-file-upload"></i> CSV'den Yükle
                </button>
            </div>
            <div class="col-12">
                <small class="text-muted">Not: CSV yüklediğinizde mevcut kayıtlar silinecek ve CSV'deki veriler
                    eklenecektir.</small>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="" id="mainForm">
    <div class="table-responsive">
        <table class="table table-bordered table-sm" id="measurementsTable">
            <thead class="table-light">
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Son tüketim noktasını besleyen panodan N önceki panonun adı</th>
                    <th colspan="4" class="text-center">Kullanılan RCD Etiket Bilgileri</th>
                    <th rowspan="2">Son tüketim noktasını besleyen pano adı</th>
                    <th colspan="3" class="text-center">Kullanılan RCD</th>
                    <th rowspan="2">Sonuç<br>(Uygunluk notu)</th>
                    <th rowspan="2"></th>
                </tr>
                <tr>
                    <th>RCD Tipi</th>
                    <th>Dayanma akımı In(A)</th>
                    <th>Açma akımı IΔn(mA)</th>
                    <th>Açma zamanı gecikmesi(ms)</th>
                    <th>RCD Tipi</th>
                    <th>Açma akımı IΔn(mA)</th>
                    <th>Test açma zamanı TΔ(ms)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($existing_measurements as $index => $row): ?>
                    <tr>
                        <td><input type="number" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][row_no]"
                                value="<?php echo isset($row['row_no']) ? $row['row_no'] : ($index + 1); ?>"></td>
                        <td>
                            <select class="form-select form-select-sm" name="measurements[<?php echo $index; ?>][upstream_panel]">
                                <option value="">Seçiniz</option>
                                <?php foreach (['Ana pano', 'Trafo', 'Sayaç Panosu'] as $opt): ?>
                                    <?php 
                                        $val = (!isset($row['upstream_panel']) || $row['upstream_panel'] === '') ? 'Ana pano' : $row['upstream_panel'];
                                    ?>
                                    <option value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm" name="measurements[<?php echo $index; ?>][upstream_rcd_type]">
                                <?php foreach (['AC', 'S', 'A', 'F', 'B'] as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo (isset($row['upstream_rcd_type']) && $row['upstream_rcd_type'] == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm" name="measurements[<?php echo $index; ?>][upstream_rcd_in]">
                                <option value="">Seçiniz</option>
                                <?php foreach ([16, 25, 32, 40, 63, 80, 100, 125] as $opt): ?>
                                    <?php 
                                        $val = (!isset($row['upstream_rcd_in']) || $row['upstream_rcd_in'] === '') ? '40' : $row['upstream_rcd_in'];
                                    ?>
                                    <option value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm" name="measurements[<?php echo $index; ?>][upstream_rcd_idn]">
                                <option value="">Seçiniz</option>
                                <?php foreach ([30, 300] as $opt): ?>
                                    <?php 
                                        $val = (!isset($row['upstream_rcd_idn']) || $row['upstream_rcd_idn'] === '') ? '30' : $row['upstream_rcd_idn'];
                                    ?>
                                    <option value="<?php echo $opt; ?>" <?php echo ($val == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][upstream_rcd_dt]"
                                value="<?php echo (!isset($row['upstream_rcd_dt']) || $row['upstream_rcd_dt'] === '') ? '0' : htmlspecialchars($row['upstream_rcd_dt']); ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][downstream_panel]"
                                value="<?php echo $row['downstream_panel'] ?? ''; ?>"></td>
                        <td>
                            <select class="form-select form-select-sm" name="measurements[<?php echo $index; ?>][downstream_rcd_type]">
                                <?php foreach (['AC', 'S', 'A', 'F', 'B'] as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo (isset($row['downstream_rcd_type']) && $row['downstream_rcd_type'] == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][downstream_rcd_idn]"
                                value="<?php echo $row['downstream_rcd_idn'] ?? ''; ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][downstream_rcd_t]"
                                value="<?php echo $row['downstream_rcd_t'] ?? ''; ?>"></td>
                        <td><input type="text" class="form-control form-control-sm"
                                name="measurements[<?php echo $index; ?>][result]"
                                value="<?php echo (!isset($row['result']) || $row['result'] === '') ? '1' : htmlspecialchars($row['result']); ?>"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i
                                    class="fas fa-times"></i></button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button type="button" class="btn btn-secondary mb-3" id="addRow">Satır Ekle</button>
    <br>
    <input type="hidden" name="measurements_json" id="measurements_json">
    <button type="button" class="btn btn-primary" id="saveBtn">Kaydet</button>
</form>

<script>
    document.getElementById('saveBtn').addEventListener('click', function () {
        const rows = [];
        const tableRows = document.querySelectorAll('#measurementsTable tbody tr');

        tableRows.forEach((tr, index) => {
            const row = {};
            const inputs = tr.querySelectorAll('input, select');
            inputs.forEach(input => {
                const match = input.name.match(/\[([^\]]+)\]$/);
                if (match) {
                    const fieldName = match[1];
                    row[fieldName] = input.value;
                }
            });
            rows.push(row);
        });

        document.getElementById('measurements_json').value = JSON.stringify(rows);

        // Disable individual inputs to prevent hitting the limit
        document.querySelectorAll('#measurementsTable input, #measurementsTable select').forEach(input => {
            input.disabled = true;
        });

        document.getElementById('mainForm').submit();
    });

    // Reuse similar script from 5.1
    document.getElementById('addRow').addEventListener('click', function () {
        var tableBody = document.querySelector('#measurementsTable tbody');
        var newRow = tableBody.rows[0].cloneNode(true);
        var rowCount = tableBody.rows.length;

        var inputs = newRow.querySelectorAll('input, select');
        inputs.forEach(function (input) {
            if (input.tagName === 'INPUT') {
                if (input.name.includes('[upstream_rcd_dt]')) {
                    input.value = '0';
                } else if (input.name.includes('[result]')) {
                    input.value = '1';
                } else {
                    input.value = '';
                }
            } else if (input.tagName === 'SELECT') {
                if (input.name.includes('[upstream_panel]')) {
                    input.value = 'Ana pano';
                } else if (input.name.includes('[upstream_rcd_in]')) {
                    input.value = '40';
                } else if (input.name.includes('[upstream_rcd_idn]')) {
                    input.value = '30';
                } else if (input.name.includes('[upstream_rcd_type]')) {
                    input.value = 'AC';
                } else if (input.name.includes('[downstream_rcd_type]')) {
                    input.value = 'AC';
                }
            }
            input.name = input.name.replace(/\[\d+\]/, '[' + rowCount + ']');
        });
        const firstInput = newRow.querySelector('input');
        if (firstInput) firstInput.value = rowCount + 1;
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
</script>

<?php include '../../includes/footer.php'; ?>