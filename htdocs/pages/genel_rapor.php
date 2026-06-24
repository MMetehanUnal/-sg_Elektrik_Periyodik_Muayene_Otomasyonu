<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

if (!isset($_SESSION['active_institution_id'])) {
    redirect('tesis_secimi.php');
}
$kurum_id = $_SESSION['active_institution_id'];

// Silme işlemi (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json; charset=utf-8');
    $delete_id = intval($_POST['id'] ?? 0);
    if ($delete_id > 0) {
        try {
            // Önce raporun bu kuruma ait olduğunu doğrula
            $stmt = $pdo->prepare("SELECT id FROM general_reports WHERE id = ? AND kurum_id = ?");
            $stmt->execute([$delete_id, $kurum_id]);
            if ($stmt->fetch()) {
                // İlişkili görselleri diskten sil
                $stmt_img = $pdo->prepare("SELECT filename FROM general_report_images WHERE report_id = ?");
                $stmt_img->execute([$delete_id]);
                while ($img = $stmt_img->fetch()) {
                    $filepath = '../uploads/genel_rapor/' . $img['filename'];
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }
                }
                // Raporu sil (CASCADE ile görseller de silinir)
                $stmt = $pdo->prepare("DELETE FROM general_reports WHERE id = ? AND kurum_id = ?");
                $stmt->execute([$delete_id, $kurum_id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Rapor bulunamadı.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Veritabanı hatası.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Geçersiz ID.']);
    }
    exit;
}

// Raporları çek
$stmt = $pdo->prepare("SELECT id, title, created_at, updated_at FROM general_reports WHERE kurum_id = ? ORDER BY updated_at DESC");
$stmt->execute([$kurum_id]);
$raporlar = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-file-alt me-2"></i>Genel Raporlar</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="genel_rapor_duzenle.php" class="btn btn-sm btn-outline-success">
            <i class="fas fa-plus me-1"></i> Yeni Rapor Oluştur
        </a>
    </div>
</div>

<?php if (empty($raporlar)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Henüz genel rapor oluşturulmamış</h5>
            <p class="text-muted">Yeni bir rapor oluşturmak için yukarıdaki butonu kullanın.</p>
            <a href="genel_rapor_duzenle.php" class="btn btn-primary mt-2">
                <i class="fas fa-plus me-1"></i> İlk Raporunuzu Oluşturun
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 45%;">Rapor Başlığı</th>
                            <th style="width: 18%;">Oluşturulma</th>
                            <th style="width: 18%;">Son Güncelleme</th>
                            <th style="width: 14%;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($raporlar as $i => $rapor): ?>
                            <tr id="rapor-row-<?php echo $rapor['id']; ?>">
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($rapor['title']); ?></strong>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($rapor['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($rapor['updated_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="genel_rapor_yazdir.php?id=<?php echo $rapor['id']; ?>" target="_blank"
                                            class="btn btn-outline-dark" title="Yazdır / PDF">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="genel_rapor_duzenle.php?id=<?php echo $rapor['id']; ?>"
                                            class="btn btn-outline-primary" title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger btn-delete"
                                            data-id="<?php echo $rapor['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($rapor['title']); ?>"
                                            title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Silme Onay Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Raporu Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <p><strong id="deleteRaporTitle"></strong> başlıklı raporu silmek istediğinize emin misiniz?</p>
                <p class="text-danger small">Bu işlem geri alınamaz. Rapora ait tüm içerik ve görseller de silinecektir.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash me-1"></i> Sil
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let deleteId = null;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    // Silme butonları
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            deleteId = this.getAttribute('data-id');
            document.getElementById('deleteRaporTitle').textContent = this.getAttribute('data-title');
            deleteModal.show();
        });
    });

    // Silme onayı
    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (!deleteId) return;

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Siliniyor...';

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', deleteId);

        fetch('genel_rapor.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('rapor-row-' + deleteId);
                if (row) {
                    row.style.transition = 'opacity 0.3s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // Tablo boşsa sayfayı yenile
                        if (document.querySelectorAll('tbody tr').length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
                deleteModal.hide();
            } else {
                alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
            }
        })
        .catch(() => {
            alert('Bağlantı hatası oluştu.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash me-1"></i> Sil';
            deleteId = null;
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
