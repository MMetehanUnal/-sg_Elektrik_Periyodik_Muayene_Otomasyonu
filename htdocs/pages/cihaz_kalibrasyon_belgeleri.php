<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Auto-create database table if not exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS device_calibration_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES measurement_devices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

$device_id = isset($_GET['device_id']) ? intval(cleanInput($_GET['device_id'])) : 0;
if (!$device_id) {
    redirect('cihazlar.php');
}

// Fetch device details
$stmt_device = $pdo->prepare("SELECT * FROM measurement_devices WHERE id = ?");
$stmt_device->execute([$device_id]);
$device = $stmt_device->fetch();
if (!$device) {
    die("Cihaz bulunamadı.");
}

// Handle document delete
if (isset($_GET['delete'])) {
    $doc_id = intval(cleanInput($_GET['delete']));
    
    // Fetch doc filename to delete from disk
    $stmt_doc = $pdo->prepare("SELECT filename FROM device_calibration_documents WHERE id = ? AND device_id = ?");
    $stmt_doc->execute([$doc_id, $device_id]);
    $doc = $stmt_doc->fetch();
    
    if ($doc) {
        $filepath = '../uploads/cihaz_kalibrasyon_belgeler/' . $doc['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        $stmt_del = $pdo->prepare("DELETE FROM device_calibration_documents WHERE id = ?");
        $stmt_del->execute([$doc_id]);
        
        $_SESSION['success_msg'] = "Kalibrasyon belgesi başarıyla silindi.";
    } else {
        $_SESSION['error_msg'] = "Belge bulunamadı.";
    }
    redirect("cihaz_kalibrasyon_belgeleri.php?device_id=" . $device_id);
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    $title = cleanInput($_POST['title']);
    
    if (empty($title)) {
        $_SESSION['error_msg'] = "Lütfen belge başlığı giriniz.";
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_msg'] = "Lütfen geçerli bir dosya seçin.";
    } else {
        $file = $_FILES['document'];
        $original_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        
        $allowed_extensions = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        
        $max_file_size = 20 * 1024 * 1024; // 20 MB
        
        if (!in_array($file_ext, $allowed_extensions)) {
            $_SESSION['error_msg'] = "İzin verilmeyen dosya türü. İzin verilen uzantılar: " . implode(', ', $allowed_extensions);
        } elseif ($file_size > $max_file_size) {
            $_SESSION['error_msg'] = "Dosya boyutu çok büyük. Maksimum 20 MB yükleyebilirsiniz.";
        } else {
            $new_filename = 'cal_doc_' . $device_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_dir = '../uploads/cihaz_kalibrasyon_belgeler/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                $stmt_ins = $pdo->prepare("INSERT INTO device_calibration_documents (device_id, title, filename, file_size) VALUES (?, ?, ?, ?)");
                $stmt_ins->execute([$device_id, $title, $new_filename, $file_size]);
                $_SESSION['success_msg'] = "Kalibrasyon belgesi başarıyla yüklendi.";
            } else {
                $_SESSION['error_msg'] = "Dosya yüklenirken hata oluştu.";
            }
        }
    }
    redirect("cihaz_kalibrasyon_belgeleri.php?device_id=" . $device_id);
}

// Fetch all documents for this device
$stmt_docs = $pdo->prepare("SELECT * FROM device_calibration_documents WHERE device_id = ? ORDER BY uploaded_at DESC");
$stmt_docs->execute([$device_id]);
$documents = $stmt_docs->fetchAll();

// Formatting size function
function formatSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' Bytes';
}

$pageTitle = htmlspecialchars($device['device_name']) . " - Kalibrasyon Belgeleri";
include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-file-contract me-2"></i><?php echo $pageTitle; ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="cihazlar.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Geri Dön
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-1"></i> <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-1"></i> <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Left Column: Upload Document Form -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="card-title mb-0 fw-bold"><i class="fas fa-upload me-2"></i>Yeni Kalibrasyon Belgesi Yükle</h6>
            </div>
            <div class="card-body py-4">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="upload_doc" value="1">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Belge Başlığı</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Örn: 2026 Kalibrasyon Sertifikası" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="document" class="form-label fw-bold">Dosya Seçin</label>
                        <input type="file" class="form-control" id="document" name="document" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" required>
                        <div class="form-text text-muted small mt-2">
                            İzin verilen formatlar: <strong>PDF, DOC, DOCX, PNG, JPG</strong>.<br>
                            Maksimum boyut: <strong>20 MB</strong>.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="fas fa-cloud-upload-alt me-1"></i> Yükle
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Device Summary Info -->
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3 text-muted">Cihaz Bilgileri</h6>
                <p class="mb-2"><strong>Cihaz Adı:</strong> <?php echo htmlspecialchars($device['device_name']); ?></p>
                <p class="mb-2"><strong>Seri No:</strong> <?php echo htmlspecialchars($device['serial_no']); ?></p>
                <p class="mb-2"><strong>Kalibrasyon No:</strong> <?php echo htmlspecialchars($device['cal_no']); ?></p>
                <p class="mb-2"><strong>Son Kalibrasyon Tarihi:</strong> <?php echo date('d.m.Y', strtotime($device['cal_date'])); ?></p>
                <p class="mb-0"><strong>Geçerlilik Tarihi:</strong> <?php echo date('d.m.Y', strtotime($device['validity_date'])); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Document List -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="fas fa-folder me-2 text-primary"></i>Yüklenmiş Belgeler</span>
                <span class="badge bg-secondary"><?php echo count($documents); ?> Belge</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($documents)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-invoice fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">Henüz kalibrasyon belgesi yüklenmemiş.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead>
                                <tr class="table-light">
                                    <th style="padding-left: 20px;">Belge Başlığı</th>
                                    <th>Dosya Boyutu</th>
                                    <th>Yüklenme Tarihi</th>
                                    <th class="text-end" style="padding-right: 20px;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td style="padding-left: 20px;">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-file-pdf text-danger fa-lg me-2"></i>
                                                <span class="fw-bold"><?php echo htmlspecialchars($doc['title']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo formatSize($doc['file_size']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($doc['uploaded_at'])); ?></td>
                                        <td class="text-end" style="padding-right: 20px;">
                                            <div class="btn-group">
                                                <a href="../uploads/cihaz_kalibrasyon_belgeler/<?php echo htmlspecialchars($doc['filename']); ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Görüntüle">
                                                    <i class="fas fa-eye"></i> Görüntüle
                                                </a>
                                                <a href="?device_id=<?php echo $device_id; ?>&delete=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-danger" title="Sil" onclick="return confirm('Bu belgeyi silmek istediğinize emin misiniz?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
