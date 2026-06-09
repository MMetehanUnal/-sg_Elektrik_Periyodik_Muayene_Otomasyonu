<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = cleanInput($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM institutions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    redirect('kurumlar.php');
}

// Handle Add/Edit
$editMode = false;
$institution = [
    'firma_adi' => '',
    'adresi' => '',
    'sgk_sicil_no' => '',
    'il_kodu' => '01',
    'kurum_kodu' => '',
    'isg_katip_id' => '',
    'report_date' => '',
    'start_date' => '',
    'end_date' => '',
    'next_control_date' => ''
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

    if ($editMode) {
        $stmt = $pdo->prepare("UPDATE institutions SET firma_adi=?, adresi=?, sgk_sicil_no=?, il_kodu=?, kurum_kodu=?, isg_katip_id=?, report_date=?, start_date=?, end_date=?, next_control_date=? WHERE id=? AND user_id=?");
        $stmt->execute([$firma_adi, $adresi, $sgk_sicil_no, $il_kodu, $kurum_kodu, $isg_katip_id, $report_date, $start_date, $end_date, $next_control_date, $_POST['id'], $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO institutions (user_id, firma_adi, adresi, sgk_sicil_no, il_kodu, kurum_kodu, isg_katip_id, report_date, start_date, end_date, next_control_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $firma_adi, $adresi, $sgk_sicil_no, $il_kodu, $kurum_kodu, $isg_katip_id, $report_date, $start_date, $end_date, $next_control_date]);
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
                            <label class="form-label">Kurum Kodu</label>
                            <input type="text" class="form-control" name="kurum_kodu"
                                value="<?php echo $institution['kurum_kodu']; ?>" maxlength="3" required>
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
                                        <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning"><i
                                                class="fas fa-edit"></i></a>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Silmek istediğinize emin misiniz?')"><i
                                                class="fas fa-trash"></i></a>
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

<?php include '../includes/footer.php'; ?>