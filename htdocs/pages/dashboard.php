<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Kurumlar</div>
            <div class="card-body">
                <h5 class="card-title">Toplam Kurum</h5>
                <p class="card-text display-4">
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) FROM institutions WHERE user_id = {$_SESSION['user_id']}");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
                <a href="kurumlar.php" class="btn btn-light btn-sm">Yönet</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Aktif Oturum</div>
            <div class="card-body">
                <h5 class="card-title">Seçili Tesis</h5>
                <p class="card-text">
                    <?php echo isset($_SESSION['active_institution_name']) ? $_SESSION['active_institution_name'] : 'Tesis Seçilmedi'; ?>
                </p>
                <a href="tesis_secimi.php" class="btn btn-light btn-sm">Değiştir</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>