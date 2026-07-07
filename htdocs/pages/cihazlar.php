<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Edit Device Logic
$edit_device = null;
if (isset($_GET['edit'])) {
    $id = cleanInput($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM measurement_devices WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $edit_device = $stmt->fetch();
}

// Add or Update Device
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_device'])) {
        $device_name = cleanInput($_POST['device_name']);
        $serial_no = cleanInput($_POST['serial_no']);
        $cal_date = cleanInput($_POST['cal_date']);
        $validity_date = cleanInput($_POST['validity_date']);
        $cal_no = cleanInput($_POST['cal_no']);
        $is_thermal_camera = isset($_POST['is_thermal_camera']) ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO measurement_devices (user_id, device_name, serial_no, cal_date, validity_date, cal_no, is_thermal_camera) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $device_name, $serial_no, $cal_date, $validity_date, $cal_no, $is_thermal_camera]);
        redirect('cihazlar.php');
    } elseif (isset($_POST['update_device'])) {
        $id = cleanInput($_POST['device_id']);
        $device_name = cleanInput($_POST['device_name']);
        $serial_no = cleanInput($_POST['serial_no']);
        $cal_date = cleanInput($_POST['cal_date']);
        $validity_date = cleanInput($_POST['validity_date']);
        $cal_no = cleanInput($_POST['cal_no']);
        $is_thermal_camera = isset($_POST['is_thermal_camera']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE measurement_devices SET device_name = ?, serial_no = ?, cal_date = ?, validity_date = ?, cal_no = ?, is_thermal_camera = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$device_name, $serial_no, $cal_date, $validity_date, $cal_no, $is_thermal_camera, $id, $_SESSION['user_id']]);
        redirect('cihazlar.php');
    }
}

// Delete Device
if (isset($_GET['delete'])) {
    $id = cleanInput($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM measurement_devices WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    redirect('cihazlar.php');
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Ölçüm Cihazları</h1>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><?php echo $edit_device ? 'Cihazı Düzenle' : 'Yeni Cihaz Ekle'; ?></div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if ($edit_device): ?>
                        <input type="hidden" name="device_id" value="<?php echo $edit_device['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Cihaz Adı</label>
                        <input type="text" class="form-control" name="device_name" value="<?php echo $edit_device ? htmlspecialchars($edit_device['device_name']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Seri Numarası</label>
                        <input type="text" class="form-control" name="serial_no" value="<?php echo $edit_device ? htmlspecialchars($edit_device['serial_no']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kalibrasyon Tarihi</label>
                        <input type="date" class="form-control" name="cal_date" value="<?php echo $edit_device ? $edit_device['cal_date'] : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Geçerlilik Tarihi</label>
                        <input type="date" class="form-control" name="validity_date" value="<?php echo $edit_device ? $edit_device['validity_date'] : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kalibrasyon Numarası</label>
                        <input type="text" class="form-control" name="cal_no" value="<?php echo $edit_device ? htmlspecialchars($edit_device['cal_no']) : ''; ?>" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_thermal_camera" name="is_thermal_camera" <?php echo ($edit_device && $edit_device['is_thermal_camera']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_thermal_camera">Termal Kamera mı?</label>
                    </div>
                    <?php if ($edit_device): ?>
                        <button type="submit" name="update_device" class="btn btn-warning">Güncelle</button>
                        <a href="cihazlar.php" class="btn btn-secondary">İptal</a>
                    <?php else: ?>
                        <button type="submit" name="add_device" class="btn btn-primary">Ekle</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Cihaz Listesi</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Cihaz Adı</th>
                            <th>Seri No</th>
                            <th>Kal. Tarihi</th>
                            <th>Geç. Tarihi</th>
                            <th>Kal. No</th>
                            <th>Tür</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM measurement_devices WHERE user_id = ? ORDER BY id DESC");
                        $stmt->execute([$_SESSION['user_id']]);
                        while ($row = $stmt->fetch()):
                            ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row['device_name']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['serial_no']); ?>
                                </td>
                                <td>
                                    <?php echo $row['cal_date']; ?>
                                </td>
                                <td>
                                    <?php echo $row['validity_date']; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['cal_no']); ?>
                                </td>
                                <td>
                                    <?php echo $row['is_thermal_camera'] ? '<span class="badge bg-warning text-dark">Termal</span>' : 'Ölçüm'; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="cihaz_kalibrasyon_belgeleri.php?device_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary text-white" style="background-color: #4361EE; border-color: #4361EE;" title="Kalibrasyon Belgeleri">
                                            <i class="fas fa-file-contract me-1"></i> Kalibrasyon Belgeleri
                                        </a>
                                        <a href="cihazlar.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="cihazlar.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Sil"
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

<?php include '../includes/footer.php'; ?>