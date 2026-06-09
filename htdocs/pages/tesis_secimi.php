<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Handle Logout Institution
if (isset($_GET['action']) && $_GET['action'] == 'logout_institution') {
    unset($_SESSION['active_institution_id']);
    unset($_SESSION['active_institution_name']);
    redirect('tesis_secimi.php');
}

// Handle Selection
if (isset($_GET['select'])) {
    $id = cleanInput($_GET['select']);

    // Verify user owns this institution
    $stmt = $pdo->prepare("SELECT id, firma_adi FROM institutions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $inst = $stmt->fetch();

    if ($inst) {
        $_SESSION['active_institution_id'] = $inst['id'];
        $_SESSION['active_institution_name'] = $inst['firma_adi'];

        // Redirect to tesis bilgileri (facility info) after selection
        redirect('forms/tesis_bilgileri.php');
    }
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tesis Seçimi</h1>
</div>

<div class="row">
    <div class="col-md-12">
        <?php if (isset($_SESSION['active_institution_id'])): ?>
            <div class="alert alert-success">
                <strong>Aktif Seçim:</strong>
                <?php echo htmlspecialchars($_SESSION['active_institution_name']); ?> üzerinde çalışıyorsunuz.
                <a href="?action=logout_institution" class="btn btn-sm btn-danger ms-3">Oturumu Kapat</a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                Lütfen işlem yapmak için listeden bir tesis seçiniz.
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">Kurum Listesi</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Kurum Kodu</th>
                                <th>Firma Adı</th>
                                <th>Adres</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM institutions WHERE user_id = ? ORDER BY firma_adi ASC");
                            $stmt->execute([$_SESSION['user_id']]);
                            while ($row = $stmt->fetch()):
                                $isActive = (isset($_SESSION['active_institution_id']) && $_SESSION['active_institution_id'] == $row['id']);
                                ?>
                                <tr class="<?php echo $isActive ? 'table-success' : ''; ?>">
                                    <td>
                                        <?php echo $row['il_kodu'] . '-' . $row['kurum_kodu']; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['firma_adi']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['adresi']); ?>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge bg-success">Seçili</span>
                                        <?php else: ?>
                                            <a href="?select=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Seç ve
                                                Başla</a>
                                        <?php endif; ?>
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