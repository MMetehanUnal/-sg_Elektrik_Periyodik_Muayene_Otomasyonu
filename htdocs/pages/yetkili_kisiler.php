<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = cleanInput($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM authorized_persons WHERE id = ?");
    $stmt->execute([$id]);
    redirect('yetkili_kisiler.php');
}

// Handle Add/Edit
$editMode = false;
$person = ['adi_soyadi' => '', 'meslegi' => '', 'kayit_no' => ''];

if (isset($_GET['edit'])) {
    $editMode = true;
    $id = cleanInput($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM authorized_persons WHERE id = ?");
    $stmt->execute([$id]);
    $person = $stmt->fetch();
    if (!$person)
        redirect('yetkili_kisiler.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adi_soyadi = cleanInput($_POST['adi_soyadi']);
    $meslegi = cleanInput($_POST['meslegi']);
    $kayit_no = cleanInput($_POST['kayit_no']);

    if ($editMode) {
        $stmt = $pdo->prepare("UPDATE authorized_persons SET adi_soyadi=?, meslegi=?, kayit_no=? WHERE id=?");
        $stmt->execute([$adi_soyadi, $meslegi, $kayit_no, $_POST['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO authorized_persons (adi_soyadi, meslegi, kayit_no) VALUES (?, ?, ?)");
        $stmt->execute([$adi_soyadi, $meslegi, $kayit_no]);
    }
    redirect('yetkili_kisiler.php');
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Yetkili Kişiler</h1>
</div>

<div class="row">
    <!-- Form Column -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <?php echo $editMode ? 'Kişi Düzenle' : 'Yeni Yetkili Ekle'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="id" value="<?php echo $person['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Adı Soyadı</label>
                        <input type="text" class="form-control" name="adi_soyadi"
                            value="<?php echo $person['adi_soyadi']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mesleği</label>
                        <input type="text" class="form-control" name="meslegi" value="<?php echo $person['meslegi']; ?>"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kayıt Numarası</label>
                        <input type="text" class="form-control" name="kayit_no"
                            value="<?php echo $person['kayit_no']; ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <?php echo $editMode ? 'Güncelle' : 'Kaydet'; ?>
                    </button>
                    <?php if ($editMode): ?>
                        <a href="yetkili_kisiler.php" class="btn btn-secondary w-100 mt-2">İptal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- List Column -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Yetkili Kişi Listesi</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Adı Soyadı</th>
                                <th>Mesleği</th>
                                <th>Kayıt No</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM authorized_persons ORDER BY adi_soyadi ASC");
                            while ($row = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($row['adi_soyadi']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['meslegi']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['kayit_no']); ?>
                                    </td>
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