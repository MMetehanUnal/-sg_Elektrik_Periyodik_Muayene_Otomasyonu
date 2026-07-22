<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

if (!isset($_SESSION['active_institution_id'])) {
    redirect('/pages/tesis_secimi.php');
}
$kurum_id = $_SESSION['active_institution_id'];
$highlight_id = isset($_GET['report_id']) ? (int) $_GET['report_id'] : null;


// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM internal_installation_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$delete_id, $kurum_id]);
    redirect('/pages/results/ic_tesisat_sonuclar.php');
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">İç Tesisat: Tespit ve Değerlendirmeler</h1>
    <a href="/pages/raporlar.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Raporlara Dön
    </a>
</div>

<?php if ($highlight_id): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Lütfen seçtiğiniz rapor (<strong>ID: <?php echo $highlight_id; ?></strong>)
        için sonuç işlemlerini yapın.
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span class="fw-bold">Rapor Listesi</span>
        <a href="/pages/forms/ic_tesisat_kontrol.php" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Yeni Rapor Ekle
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Rapor No</th>
                        <th>Firma Adı Eki</th>
                        <th>Rapor Tarihi</th>
                        <th class="text-center">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT id, report_no, report_date, firma_adi_eki FROM internal_installation_reports WHERE kurum_id = ? ORDER BY report_date DESC");
                    $stmt->execute([$kurum_id]);
                    while ($row = $stmt->fetch()):
                        $is_highlighted = ($highlight_id == $row['id']);
                        ?>
                        <tr class="<?php echo $is_highlighted ? 'table-warning' : ''; ?>">
                            <td><strong>
                                    <?php echo htmlspecialchars($row['report_no']); ?>
                                </strong></td>
                            <td>
                                <?php echo htmlspecialchars($row['firma_adi_eki'] ?? '-'); ?>
                            </td>
                            <td>
                                <?php echo date('d.m.Y', strtotime($row['report_date'])); ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/pages/forms/ic_tesisat_kontrol.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit me-1"></i> Düzenle
                                    </a>
                                    <a href="/pages/results/ic_tesisat_panel_sonuclar.php?report_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-layer-group me-1"></i> Pano Sonuçları
                                    </a>
                                    <a href="/pages/ic_tesisat_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-dark">
                                        <i class="fas fa-print me-1"></i> Yazdır
                                    </a>
                                    <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu raporu silmek istediğinize emin misiniz?')">
                                        <i class="fas fa-trash-alt me-1"></i> Kaldır
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($stmt->rowCount() == 0): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Henüz iç tesisat raporu oluşturulmamış.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>