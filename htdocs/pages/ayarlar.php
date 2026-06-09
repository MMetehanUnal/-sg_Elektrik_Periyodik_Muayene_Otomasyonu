<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'save_general') {
        $logo_text = cleanInput($_POST['logo_text'] ?? '');
        $logo_type = cleanInput($_POST['logo_type'] ?? 'text');
        
        if (empty($logo_text)) {
            $error_msg = 'Şirket adı / Logo metni boş bırakılamaz.';
        } else {
            if (setSetting($pdo, 'logo_text', $logo_text) && setSetting($pdo, 'logo_type', $logo_type)) {
                $success_msg = 'Genel ayarlar başarıyla güncellendi.';
            } else {
                $error_msg = 'Ayarlar kaydedilirken bir hata oluştu.';
            }
        }
    } 
    elseif ($action === 'upload_logo') {
        if (!empty($_FILES['logo_image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $dir = "../uploads/logos/";
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                $filename = 'logo_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $dest_path = $dir . $filename;
                $original_name = cleanInput($_FILES['logo_image']['name']);
                
                // Compress logo to a reasonable width (max 400px is perfect for report logos)
                $uploaded = compressImage($_FILES['logo_image']['tmp_name'], $dest_path, 80, 400);
                if (!$uploaded) {
                    $uploaded = move_uploaded_file($_FILES['logo_image']['tmp_name'], $dest_path);
                }
                
                if ($uploaded) {
                    $stmt = $pdo->prepare("INSERT INTO uploaded_logos (filename, original_name) VALUES (?, ?)");
                    if ($stmt->execute([$filename, $original_name])) {
                        $success_msg = 'Logo resmi başarıyla yüklendi.';
                    } else {
                        $error_msg = 'Veritabanı kaydı oluşturulamadı.';
                    }
                } else {
                    $error_msg = 'Dosya sunucuya kaydedilemedi.';
                }
            } else {
                $error_msg = 'Lütfen geçerli bir resim dosyası seçin (JPG, PNG, WEBP, GIF).';
            }
        } else {
            $error_msg = 'Lütfen yüklenecek bir dosya seçin.';
        }
    } 
    elseif ($action === 'set_active_logo') {
        $logo_id = (int)($_POST['logo_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT filename FROM uploaded_logos WHERE id = ?");
        $stmt->execute([$logo_id]);
        $filename = $stmt->fetchColumn();
        
        if ($filename) {
            if (setSetting($pdo, 'active_logo', $filename)) {
                $success_msg = 'Aktif logo resmi başarıyla değiştirildi.';
            } else {
                $error_msg = 'Aktif logo ayarı güncellenemedi.';
            }
        } else {
            $error_msg = 'Geçersiz logo seçimi.';
        }
    } 
    elseif ($action === 'delete_logo') {
        $logo_id = (int)($_POST['logo_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT filename FROM uploaded_logos WHERE id = ?");
        $stmt->execute([$logo_id]);
        $filename = $stmt->fetchColumn();
        
        if ($filename) {
            // Delete file from disk
            $file_path = "../uploads/logos/" . $filename;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete record
            $stmt_del = $pdo->prepare("DELETE FROM uploaded_logos WHERE id = ?");
            if ($stmt_del->execute([$logo_id])) {
                // If it was the active logo, reset the setting
                $current_active = getSetting($pdo, 'active_logo', '');
                if ($current_active === $filename) {
                    setSetting($pdo, 'active_logo', '');
                }
                $success_msg = 'Logo resmi kalıcı olarak silindi.';
            } else {
                $error_msg = 'Logo kaydı veritabanından silinemedi.';
            }
        } else {
            $error_msg = 'Silinecek logo bulunamadı.';
        }
    }
}

// Fetch settings
$current_logo_text = getSetting($pdo, 'logo_text', 'LOGO');
$current_logo_type = getSetting($pdo, 'logo_type', 'text');
$current_active_logo = getSetting($pdo, 'active_logo', '');

// Fetch uploaded logos
$stmt_logos = $pdo->query("SELECT * FROM uploaded_logos ORDER BY id DESC");
$uploaded_logos = $stmt_logos->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-cog text-primary me-2"></i> Sistem Ayarları</h1>
</div>

<?php if ($success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2 fs-5"></i>
        <div><?php echo htmlspecialchars($success_msg); ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2 fs-5"></i>
        <div><?php echo htmlspecialchars($error_msg); ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- LEFT: General Settings Card -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0 rounded-3 h-100">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="card-title mb-0 fw-semibold"><i class="fas fa-sliders-h me-2"></i> Genel Rapor Logosu Ayarları</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="save_general">

                    <!-- Logo Type Toggle -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary d-block">Görüntülenecek Logo Türü</label>
                        <div class="btn-group w-100 mt-1" role="group" aria-label="Logo Tipi Seçimi">
                            <input type="radio" class="btn-check" name="logo_type" id="logo_type_text" value="text" 
                                   <?php echo ($current_logo_type === 'text') ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary py-2.5 fw-medium" for="logo_type_text">
                                <i class="fas fa-font me-2"></i> Metin Logosu Kullan
                            </label>

                            <input type="radio" class="btn-check" name="logo_type" id="logo_type_image" value="image" 
                                   <?php echo ($current_logo_type === 'image') ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary py-2.5 fw-medium" for="logo_type_image">
                                <i class="fas fa-image me-2"></i> Resim Logosu Kullan
                            </label>
                        </div>
                    </div>

                    <!-- Logo Text Input -->
                    <div class="mb-4">
                        <label for="logo_text" class="form-label fw-bold text-secondary">Rapor Logo Metni (Şirket Adı)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-heading text-muted"></i></span>
                            <input type="text" class="form-control" id="logo_text" name="logo_text" 
                                   value="<?php echo htmlspecialchars($current_logo_text); ?>" required>
                        </div>
                        <div class="form-text mt-2 text-muted small">
                            Metin logosu modu seçildiğinde veya resim logosu modu seçili olup herhangi bir resim yüklenmediğinde raporlarda bu isim yazdırılacaktır.
                        </div>
                    </div>

                    <div class="d-grid mt-4 pt-2">
                        <button type="submit" class="btn btn-primary py-2.5 rounded-3">
                            <i class="fas fa-save me-2"></i> Genel Ayarları Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT: Logo Image Manager Card -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0 rounded-3 h-100">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="card-title mb-0 fw-semibold"><i class="fas fa-images me-2"></i> Resim Logosu Kütüphanesi</h5>
            </div>
            <div class="card-body p-4">
                <!-- Upload Form -->
                <form method="POST" action="" enctype="multipart/form-data" class="border-bottom pb-4 mb-4">
                    <input type="hidden" name="action" value="upload_logo">
                    
                    <label class="form-label fw-bold text-secondary mb-2">Yeni Logo Resmi Yükle</label>
                    <div class="d-flex gap-2">
                        <input class="form-control" type="file" name="logo_image" accept="image/*" required>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-upload me-2"></i> Yükle</button>
                    </div>
                    <div class="form-text mt-2 text-muted small">
                        Tavsiye edilen: Saydam arka planlı PNG formatı. Maksimum boyut: 5MB.
                    </div>
                </form>

                <!-- Library Grid -->
                <label class="form-label fw-bold text-secondary mb-3">Yüklenmiş Logolar</label>
                
                <?php if (empty($uploaded_logos)): ?>
                    <div class="text-center py-4 text-muted bg-light rounded-3">
                        <i class="far fa-image fa-3x mb-3 text-muted opacity-50"></i>
                        <p class="mb-0 small fw-medium">Kütüphanede henüz yüklü bir logo resmi yok.</p>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-sm-2 g-3 overflow-auto" style="max-height: 380px;">
                        <?php foreach ($uploaded_logos as $logo): 
                            $is_active = ($logo['filename'] === $current_active_logo);
                            ?>
                            <div class="col">
                                <div class="card h-100 border rounded-3 position-relative <?php echo $is_active ? 'border-primary shadow-sm bg-light bg-opacity-25' : ''; ?>">
                                    
                                    <!-- Active Badge -->
                                    <?php if ($is_active): ?>
                                        <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-primary px-3 shadow-sm">
                                            Aktif Logo
                                        </span>
                                    <?php endif; ?>

                                    <!-- Thumbnail Wrapper -->
                                    <div class="d-flex align-items-center justify-content-center p-3" style="height: 120px; background-color: #f8f9fa;">
                                        <img src="../uploads/logos/<?php echo htmlspecialchars($logo['filename']); ?>" 
                                             alt="<?php echo htmlspecialchars($logo['original_name']); ?>" 
                                             class="img-fluid" style="max-height: 90px; object-fit: contain;">
                                    </div>
                                    
                                    <!-- Actions Footer -->
                                    <div class="card-footer bg-white border-top-0 d-flex gap-2 p-2">
                                        <?php if (!$is_active): ?>
                                            <form method="POST" action="" class="w-100">
                                                <input type="hidden" name="action" value="set_active_logo">
                                                <input type="hidden" name="logo_id" value="<?php echo $logo['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                                    <i class="fas fa-check me-1"></i> Aktif Yap
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success w-100 disabled" disabled>
                                                <i class="fas fa-check-circle me-1"></i> Seçili
                                            </button>
                                        <?php endif; ?>
                                        
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bu logo resmini kalıcı olarak silmek istediğinizden emin misiniz?')">
                                            <input type="hidden" name="action" value="delete_logo">
                                            <input type="hidden" name="logo_id" value="<?php echo $logo['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Kalıcı Olarak Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
