<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Hata/Başarı Mesajları için Session Başlatma (auth.php içinde session_start() çağrılmaktadır)

// Döküman Silme İşlemi (Sadece Admin Yetkilidir)
if (isset($_GET['delete']) && isAdmin()) {
    $id = intval(cleanInput($_GET['delete']));
    
    // Önce dosya adını veritabanından alalım ki diskten de silelim
    $stmt = $pdo->prepare("SELECT filename FROM general_documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    
    if ($doc) {
        $filepath = '../uploads/documents/' . $doc['filename'];
        if (file_exists($filepath)) {
            unlink($filepath); // Diskten sil
        }
        
        // Veritabanından sil
        $deleteStmt = $pdo->prepare("DELETE FROM general_documents WHERE id = ?");
        $deleteStmt->execute([$id]);
        
        $_SESSION['success_msg'] = "Döküman başarıyla silindi.";
    } else {
        $_SESSION['error_msg'] = "Döküman bulunamadı.";
    }
    
    redirect('dokumanlar.php');
}

// Döküman Yükleme İşlemi (Sadece Admin Yetkilidir)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc']) && isAdmin()) {
    $title = cleanInput($_POST['title']);
    $description = cleanInput($_POST['description']);
    
    if (empty($title)) {
        $_SESSION['error_msg'] = "Lütfen döküman başlığı giriniz.";
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $upload_error = $_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE;
        $_SESSION['error_msg'] = "Dosya yüklenirken bir hata oluştu (Hata Kodu: $upload_error).";
    } else {
        $file = $_FILES['document'];
        $original_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        
        // İzin verilen dosya uzantıları
        $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'png', 'jpg', 'jpeg', 'dwg'];
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        
        // Dosya boyutu sınırı (Örn: 20 MB)
        $max_file_size = 20 * 1024 * 1024;
        
        if (!in_array($file_ext, $allowed_extensions)) {
            $_SESSION['error_msg'] = "Bu dosya türünün yüklenmesine izin verilmiyor. İzin verilen uzantılar: " . implode(', ', $allowed_extensions);
        } elseif ($file_size > $max_file_size) {
            $_SESSION['error_msg'] = "Dosya boyutu çok büyük. Maksimum 20 MB yükleyebilirsiniz.";
        } else {
            // Güvenli dosya adı üretimi
            $new_filename = 'doc_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_dir = '../uploads/documents/';
            
            // Klasör yoksa oluştur
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $target_path)) {
                // Veritabanına kaydet
                $insertStmt = $pdo->prepare("INSERT INTO general_documents (filename, original_name, title, description, file_size, user_id) VALUES (?, ?, ?, ?, ?, ?)");
                $insertStmt->execute([
                    $new_filename,
                    $original_name,
                    $title,
                    $description,
                    $file_size,
                    $_SESSION['user_id']
                ]);
                
                $_SESSION['success_msg'] = "Döküman başarıyla yüklendi.";
            } else {
                $_SESSION['error_msg'] = "Dosya sunucuya taşınırken bir hata oluştu.";
            }
        }
    }
    
    redirect('dokumanlar.php');
}

// Tüm dökümanları getir
$stmt = $pdo->query("SELECT d.*, u.username FROM general_documents d LEFT JOIN users u ON d.user_id = u.id ORDER BY d.uploaded_at DESC");
$documents = $stmt->fetchAll();

// Dosya boyutu formatlama fonksiyonu
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' B';
    } elseif ($bytes == 1) {
        return '1 B';
    } else {
        return '0 B';
    }
}

// Dosya türüne göre Font Awesome ikonu döndüren fonksiyon
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf': return '<i class="fas fa-file-pdf text-danger fa-lg me-2"></i>';
        case 'doc':
        case 'docx': return '<i class="fas fa-file-word text-primary fa-lg me-2"></i>';
        case 'xls':
        case 'xlsx': return '<i class="fas fa-file-excel text-success fa-lg me-2"></i>';
        case 'ppt':
        case 'pptx': return '<i class="fas fa-file-powerpoint text-warning fa-lg me-2"></i>';
        case 'zip':
        case 'rar': return '<i class="fas fa-file-archive text-secondary fa-lg me-2"></i>';
        case 'png':
        case 'jpg':
        case 'jpeg': return '<i class="fas fa-file-image text-info fa-lg me-2"></i>';
        case 'dwg': return '<i class="fas fa-pencil-ruler text-dark fa-lg me-2"></i>';
        default: return '<i class="fas fa-file text-muted fa-lg me-2"></i>';
    }
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-file-download me-2 text-primary"></i> Dökümanlar</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/pages/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dökümanlar</li>
        </ol>
    </nav>
</div>

<!-- Bildirim Mesajları -->
<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
<?php endif; ?>

<div class="row">
    <?php if (isAdmin()): ?>
        <!-- Admin İçin Dosya Yükleme Paneli -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="card-title mb-0 fw-semibold"><i class="fas fa-upload me-2"></i> Yeni Döküman Yükle</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="dokumanlar.php" enctype="multipart/form-data">
                        <input type="hidden" name="upload_doc" value="1">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label fw-medium">Döküman Başlığı <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="title" name="title" required placeholder="Örn: Topraklama Ölçüm Yönetmeliği">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label fw-medium">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Döküman hakkında kısa bir bilgi veya sürüm notu yazabilirsiniz..."></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="document" class="form-label fw-medium">Dosya Seçin <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="document" name="document" required>
                            <div class="form-text text-muted small mt-2">
                                <i class="fas fa-info-circle me-1"></i> İzin verilen uzantılar: pdf, doc, docx, xls, xlsx, ppt, pptx, txt, zip, rar, png, jpg, jpeg, dwg (Maks: 20MB)
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                            <i class="fas fa-cloud-upload-alt me-2"></i> Dökümanı Yükle
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Liste Kısmı (Admin için 8 Sütun) -->
        <div class="col-lg-8">
    <?php else: ?>
        <!-- Liste Kısmı (Kullanıcı için 12 Sütun) -->
        <div class="col-lg-12">
    <?php endif; ?>

        <!-- Sistem Standart Dökümanları Kartı -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light py-3">
                <h5 class="card-title mb-0 fw-semibold text-secondary"><i class="fas fa-file-invoice me-2"></i> Sistem Standart Dökümanları</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Döküman Adı</th>
                                <th style="width: 40%;">Açıklama</th>
                                <th style="width: 20%;" class="text-end px-4">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-invoice text-warning fa-lg me-2"></i>
                                        <div>
                                            <div class="fw-semibold text-dark">Jeneratör Yıllık Bakım Formu</div>
                                            <small class="text-muted">Standart Bakım Formu (HTML)</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small">Jeneratörlerin 3 aylık dönemler halinde yıllık periyodik kontrollerini ve rutin işlemlerini içeren standart form.</span>
                                </td>
                                <td class="text-end px-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="jenerator_bakim_formu.php" target="_blank" class="btn btn-sm btn-primary px-3" title="Görüntüle ve Yazdır">
                                            <i class="fas fa-external-link-alt me-1"></i> Görüntüle / Yazdır
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-contract text-info fa-lg me-2"></i>
                                        <div>
                                            <div class="fw-semibold text-dark">Jeneratör Yıllık Bakım Raporu</div>
                                            <small class="text-muted">Yıllık Bakım Şablonu (HTML)</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small">Cihaz bilgileri, bakım adımları, kullanılan malzemeler ve imza alanlarını içeren resmi yıllık bakım rapor şablonu.</span>
                                </td>
                                <td class="text-end px-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="jenerator_yillik_bakim_raporu.php" target="_blank" class="btn btn-sm btn-primary px-3" title="Görüntüle ve Yazdır">
                                            <i class="fas fa-external-link-alt me-1"></i> Görüntüle / Yazdır
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-invoice text-success fa-lg me-2"></i>
                                        <div>
                                            <div class="fw-semibold text-dark">Jeneratör 3 Aylık Kontrol Formu</div>
                                            <small class="text-muted">3 Aylık Kontrol Şablonu (HTML)</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small">Kontrol bilgileri, cihaz bilgileri, rutin kontroller, tespit edilen aksaklıklar ve imza alanlarını içeren 3 aylık periyodik kontrol form şablonu.</span>
                                </td>
                                <td class="text-end px-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="jenerator_uc_aylik_kontrol_formu.php" target="_blank" class="btn btn-sm btn-primary px-3" title="Görüntüle ve Yazdır">
                                            <i class="fas fa-external-link-alt me-1"></i> Görüntüle / Yazdır
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-invoice text-danger fa-lg me-2"></i>
                                        <div>
                                            <div class="fw-semibold text-dark">GSB Jeneratör Yıllık Bakım Formu</div>
                                            <small class="text-muted">GSB Yıllık Bakım Şablonu (HTML)</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small">İstanbul Gelişim Üniversitesi formatında döküman no, revizyon, cihaz listesi ve yıllık kontrol noktalarını içeren resmi bakım formu şablonu.</span>
                                </td>
                                <td class="text-end px-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="gsb_jenerator_yillik_bakim_formu.php" target="_blank" class="btn btn-sm btn-primary px-3" title="Görüntüle ve Yazdır">
                                            <i class="fas fa-external-link-alt me-1"></i> Görüntüle / Yazdır
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Döküman Listesi Kartı -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-secondary"><i class="fas fa-list me-2"></i> Mevcut Dökümanlar</h5>
                <span class="badge bg-secondary text-white rounded-pill px-3 py-2 fw-normal"><?php echo count($documents); ?> Döküman</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($documents)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fa-3x mb-3 text-opacity-50"></i>
                        <p class="mb-0">Henüz hiç döküman yüklenmemiş.</p>
                        <?php if (isAdmin()): ?>
                            <small class="text-muted">Sol taraftaki yükleme panelini kullanarak ilk dökümanı ekleyebilirsiniz.</small>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;">Döküman Adı</th>
                                    <th style="width: 25%;">Açıklama</th>
                                    <th style="width: 10%;" class="text-center">Dosya Boyutu</th>
                                    <th style="width: 15%;" class="text-center">Yükleme Tarihi</th>
                                    <th style="width: 10%;" class="text-end px-4">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php echo getFileIcon($doc['filename']); ?>
                                                <div>
                                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($doc['title']); ?></div>
                                                    <small class="text-muted text-break" style="font-size: 0.75rem;"><?php echo htmlspecialchars($doc['original_name']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted small"><?php echo !empty($doc['description']) ? htmlspecialchars($doc['description']) : '-'; ?></span>
                                        </td>
                                        <td class="text-center font-monospace small">
                                            <?php echo formatSize($doc['file_size']); ?>
                                        </td>
                                        <td class="text-center small text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($doc['uploaded_at'])); ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="/uploads/documents/<?php echo htmlspecialchars($doc['filename']); ?>" 
                                                   download="<?php echo htmlspecialchars($doc['original_name']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="İndir">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <?php if (isAdmin()): ?>
                                                    <a href="dokumanlar.php?delete=<?php echo $doc['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       onclick="return confirm('Bu dökümanı silmek istediğinize emin misiniz? Dosya sistemden tamamen kaldırılacaktır.');" 
                                                       title="Sil">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                <?php endif; ?>
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

<?php
include '../includes/footer.php';
?>
