<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Auto-add contract_pdf column if not exists
try {
    $pdo->exec("ALTER TABLE institutions ADD COLUMN contract_pdf VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {
    // Column already exists, ignore
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = cleanInput($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM institutions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    redirect('kurumlar.php');
}

// Handle Add/Edit
$editMode = false;

$next_code = 1;
$stmt_max = $pdo->prepare("SELECT MAX(CAST(kurum_kodu AS UNSIGNED)) as max_code FROM institutions WHERE user_id = ?");
$stmt_max->execute([$_SESSION['user_id']]);
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
        $stmt = $pdo->prepare("INSERT INTO institutions (user_id, firma_adi, adresi, sgk_sicil_no, il_kodu, kurum_kodu, isg_katip_id, report_date, start_date, end_date, next_control_date, contract_pdf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $firma_adi, $adresi, $sgk_sicil_no, $il_kodu, $kurum_kodu, $isg_katip_id, $report_date, $start_date, $end_date, $next_control_date, $contract_pdf]);
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
        <div class="card">
            <div class="card-header">Kurum Listesi</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Firma Adı</th>
                                <th>Adres</th>
                                <th>Kurum ID</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM institutions WHERE user_id = ? ORDER BY id DESC");
                            $stmt->execute([$_SESSION['user_id']]);
                            while ($row = $stmt->fetch()):
                                ?>
                                <tr>
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
                                                onclick="return confirm('Silmek istediğinize emin misiniz?')">
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                        document.querySelector('input[name="il_kodu"]').value = data.city_code;
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
});
</script>

<?php include '../includes/footer.php'; ?>