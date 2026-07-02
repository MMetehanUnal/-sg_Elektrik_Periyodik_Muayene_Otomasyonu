<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

// Filter reports by active institution if selected, otherwise show all user's reports?
// Usually better to enforce institution selection first.
if (!isset($_SESSION['active_institution_id'])) {
    redirect('tesis_secimi.php');
}
$kurum_id = $_SESSION['active_institution_id'];

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Raporlar</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="forms/topraklama_kontrol.php" class="btn btn-sm btn-outline-success">
            <i class="fas fa-plus"></i> Yeni Rapor Oluştur
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Rapor No</th>
                        <th>Firma Adı Eki</th>
                        <th>Rapor Tarihi</th>
                        <th>Kontrol Nedeni</th>
                        <th>Sonuç</th>
                        <th>Oluşturan (Yetkili)</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'topraklama' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM grounding_reports WHERE kurum_id = ?)
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'ic_tesisat' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM internal_installation_reports WHERE kurum_id = ?)
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'yildirim' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM lightning_protection_reports WHERE kurum_id = ?)
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'yangin' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM fire_detection_reports WHERE kurum_id = ?)
                        ORDER BY report_date DESC
                    ");
                    $stmt->execute([$kurum_id, $kurum_id, $kurum_id, $kurum_id]);
                    while ($row = $stmt->fetch()):
                        $stmt_ap = $pdo->prepare("SELECT adi_soyadi FROM authorized_persons WHERE id = ?");
                        $stmt_ap->execute([$row['authorized_person_id']]);
                        $ap = $stmt_ap->fetch();
                        $ap_name = $ap ? $ap['adi_soyadi'] : '-';
                        ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row['report_no']); ?>
                                <br><small class="text-muted">
                                    <?php
                                    if ($row['type'] == 'topraklama')
                                        echo 'Topraklama';
                                    elseif ($row['type'] == 'ic_tesisat')
                                        echo 'İç Tesisat';
                                    elseif ($row['type'] == 'yildirim')
                                        echo 'Yıldırımdan Korunma';
                                    else
                                        echo 'Yangın Algılama';
                                    ?>
                                </small>
                            </td>
                            <td>
                                <?php echo !empty($row['firma_adi_eki']) ? htmlspecialchars($row['firma_adi_eki']) : '-'; ?>
                            </td>
                            <td>
                                <?php echo date('d.m.Y', strtotime($row['report_date'])); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['control_reason']); ?>
                            </td>
                            <td>
                                <?php if ($row['result'] == 'UYGUNDUR'): ?>
                                    <span class="badge bg-success">UYGUNDUR</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">UYGUN DEĞİL</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($ap_name); ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($row['type'] == 'topraklama'): ?>
                                        <a href="rapor_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/topraklama_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/topraklama_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Ölçümleri Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php elseif ($row['type'] == 'ic_tesisat'): ?>
                                        <a href="ic_tesisat_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/ic_tesisat_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/ic_tesisat_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php elseif ($row['type'] == 'yildirim'): ?>
                                        <a href="yildirimdan_korunma_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/yildirimdan_korunma_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/yildirimdan_korunma_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="yangin_algilama_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/yangin_algilama_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/yangin_algilama_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-list-check"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>