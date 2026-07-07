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
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <button type="button" id="btnTopluYazdir" class="btn btn-sm btn-dark" style="display: none;">
            <i class="fas fa-print me-1"></i> Seçilenleri Yazdır
        </button>
        <a href="indir_yetki_belgeleri.php" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-file-archive me-1"></i> Yetki Belgelerini İndir
        </a>
        <a href="forms/topraklama_kontrol.php" class="btn btn-sm btn-outline-success">
            <i class="fas fa-plus"></i> Yeni Rapor Oluştur
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 3%;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                        <th class="sortable" data-column="type" style="cursor: pointer; user-select: none;">Rapor No / Tür <i class="fas fa-sort text-muted ms-1"></i></th>
                        <th class="sortable" data-column="ext" style="cursor: pointer; user-select: none;">Firma Adı Eki <i class="fas fa-sort text-muted ms-1"></i></th>
                        <th class="sortable" data-column="date" style="cursor: pointer; user-select: none;">Rapor Tarihi <i class="fas fa-sort-up ms-1"></i></th>
                        <th class="sortable" data-column="reason" style="cursor: pointer; user-select: none;">Kontrol Nedeni <i class="fas fa-sort text-muted ms-1"></i></th>
                        <th class="sortable" data-column="result" style="cursor: pointer; user-select: none;">Sonuç <i class="fas fa-sort text-muted ms-1"></i></th>
                        <th class="sortable" data-column="creator" style="cursor: pointer; user-select: none;">Oluşturan (Yetkili) <i class="fas fa-sort text-muted ms-1"></i></th>
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
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'sihhi_tesisat' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM sihhi_tesisat_reports WHERE kurum_id = ?)
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'gaz_tesisat' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM gaz_tesisat_reports WHERE kurum_id = ?)
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'isinma_tesisat' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM isinma_tesisat_reports WHERE kurum_id = ?)
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'genlesme_tanki' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM genlesme_tanki_reports WHERE kurum_id = ?)
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'engelli_rampasi' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM engelli_rampasi_reports WHERE kurum_id = ?)
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'boyler_tanki' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM boyler_tanki_reports WHERE kurum_id = ?)
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'jenarator' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM jenarator_reports WHERE kurum_id = ?)
                        UNION ALL
                        (SELECT id, report_no COLLATE utf8mb4_general_ci as report_no, report_date, control_reason COLLATE utf8mb4_general_ci as control_reason, result COLLATE utf8mb4_general_ci as result, authorized_person_id, 'kamera_bakim' as type, firma_adi_eki COLLATE utf8mb4_general_ci as firma_adi_eki FROM kamera_bakim_reports WHERE kurum_id = ?)
                        ORDER BY report_date DESC
                    ");
                    $stmt->execute([$kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id, $kurum_id]);
                    while ($row = $stmt->fetch()):
                        $stmt_ap = $pdo->prepare("SELECT adi_soyadi FROM authorized_persons WHERE id = ?");
                        $stmt_ap->execute([$row['authorized_person_id']]);
                        $ap = $stmt_ap->fetch();
                        $ap_name = $ap ? $ap['adi_soyadi'] : '-';
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input report-checkbox" value="<?php echo $row['type'] . '_' . $row['id']; ?>">
                            </td>
                            <td data-sort-val="<?php
                                if ($row['type'] == 'topraklama') echo 'Topraklama';
                                elseif ($row['type'] == 'ic_tesisat') echo 'İç Tesisat';
                                elseif ($row['type'] == 'yildirim') echo 'Yıldırımdan Korunma';
                                elseif ($row['type'] == 'sihhi_tesisat') echo 'Sıhhi Tesisat';
                                elseif ($row['type'] == 'gaz_tesisat') echo 'Gaz Tesisatı';
                                elseif ($row['type'] == 'isinma_tesisat') echo 'Isınma Tesisatı';
                                elseif ($row['type'] == 'genlesme_tanki') echo 'Genleşme Tankı';
                                elseif ($row['type'] == 'engelli_rampasi') echo 'Engelli Rampası';
                                elseif ($row['type'] == 'boyler_tanki') echo 'Boyler Tankı';
                                elseif ($row['type'] == 'jenarator') echo 'Jeneratör';
                                elseif ($row['type'] == 'kamera_bakim') echo 'Kamera Bakım';
                                else echo 'Yangın Algılama';
                            ?>">
                                <?php echo htmlspecialchars($row['report_no']); ?>
                                <br><small class="text-muted">
                                    <?php
                                    if ($row['type'] == 'topraklama')
                                        echo 'Topraklama';
                                    elseif ($row['type'] == 'ic_tesisat')
                                        echo 'İç Tesisat';
                                    elseif ($row['type'] == 'yildirim')
                                        echo 'Yıldırımdan Korunma';
                                    elseif ($row['type'] == 'sihhi_tesisat')
                                        echo 'Sıhhi Tesisat';
                                    elseif ($row['type'] == 'gaz_tesisat')
                                        echo 'Gaz Tesisatı';
                                    elseif ($row['type'] == 'isinma_tesisat')
                                        echo 'Isınma Tesisatı';
                                    elseif ($row['type'] == 'genlesme_tanki')
                                        echo 'Genleşme Tankı';
                                    elseif ($row['type'] == 'engelli_rampasi')
                                        echo 'Engelli Rampası';
                                    elseif ($row['type'] == 'boyler_tanki')
                                        echo 'Boyler Tankı';
                                    elseif ($row['type'] == 'jenarator')
                                        echo 'Jeneratör';
                                    elseif ($row['type'] == 'kamera_bakim')
                                        echo 'Kamera Bakım';
                                    else
                                        echo 'Yangın Algılama';
                                    ?>
                                </small>
                            </td>
                            <td data-sort-val="<?php echo htmlspecialchars($row['firma_adi_eki'] ?? ''); ?>">
                                <?php echo !empty($row['firma_adi_eki']) ? htmlspecialchars($row['firma_adi_eki']) : '-'; ?>
                            </td>
                            <td data-sort-val="<?php echo $row['report_date']; ?>">
                                <?php echo date('d.m.Y', strtotime($row['report_date'])); ?>
                            </td>
                            <td data-sort-val="<?php echo htmlspecialchars($row['control_reason']); ?>">
                                <?php echo htmlspecialchars($row['control_reason']); ?>
                            </td>
                            <td data-sort-val="<?php echo ($row['result'] == 'UYGUNDUR' || $row['result'] == 'GÜVENLİDİR') ? 'UYGUNDUR' : 'UYGUN DEĞİL'; ?>">
                                <?php if ($row['result'] == 'UYGUNDUR' || $row['result'] == 'GÜVENLİDİR'): ?>
                                    <span class="badge bg-success">UYGUNDUR</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">UYGUN DEĞİL</span>
                                <?php endif; ?>
                            </td>
                            <td data-sort-val="<?php echo htmlspecialchars($ap_name); ?>">
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
                                    <?php elseif ($row['type'] == 'sihhi_tesisat'): ?>
                                        <a href="sihhi_tesisat_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/sihhi_tesisat_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/sihhi_tesisat_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php elseif ($row['type'] == 'gaz_tesisat'): ?>
                                        <a href="gaz_tesisat_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/gaz_tesisat_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/gaz_tesisat_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php elseif ($row['type'] == 'isinma_tesisat'): ?>
                                        <a href="isinma_tesisat_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/isinma_tesisat_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/isinma_tesisat_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php elseif ($row['type'] == 'genlesme_tanki'): ?>
                                        <a href="genlesme_tanki_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/genlesme_tanki_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/genlesme_tanki_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php elseif ($row['type'] == 'engelli_rampasi'): ?>
                                        <a href="engelli_rampasi_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/engelli_rampasi_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/engelli_rampasi_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php elseif ($row['type'] == 'boyler_tanki'): ?>
                                        <a href="boyler_tanki_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/boyler_tanki_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/boyler_tanki_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php elseif ($row['type'] == 'jenarator'): ?>
                                        <a href="jenarator_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/jenarator_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/jenarator_sonuclar.php?report_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-secondary" title="Sonuçları Düzenle">
                                            <i class="fas fa-table"></i>
                                        </a>
                                    <?php elseif ($row['type'] == 'kamera_bakim'): ?>
                                        <a href="kamera_bakim_yazdir.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="btn btn-sm btn-outline-dark" title="Yazdır/Görüntüle">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="forms/kamera_bakim_kontrol.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Raporu Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="results/kamera_bakim_sonuclar.php?report_id=<?php echo $row['id']; ?>"
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.report-checkbox');
    const btnTopluYazdir = document.getElementById('btnTopluYazdir');

    function updatePrintButtonVisibility() {
        const checkedCount = document.querySelectorAll('.report-checkbox:checked').length;
        if (checkedCount >= 2) {
            btnTopluYazdir.style.display = 'inline-block';
        } else {
            btnTopluYazdir.style.display = 'none';
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updatePrintButtonVisibility();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updatePrintButtonVisibility);
    });

    if (btnTopluYazdir) {
        btnTopluYazdir.addEventListener('click', function() {
            const selected = [];
            document.querySelectorAll('.report-checkbox:checked').forEach(cb => {
                selected.push(cb.value);
            });
            if (selected.length >= 2) {
                const url = 'toplu_yazdir.php?reports=' + selected.join(',');
                window.open(url, '_blank');
            }
        });
    }

    // Client-side Table Column Sorting
    const table = document.querySelector('.table');
    const tbody = table.querySelector('tbody');
    const headers = table.querySelectorAll('th.sortable');
    let currentColumn = 'date'; // Initial database sort is by date desc
    let isAsc = false;

    headers.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-column');
            const columnIndex = Array.from(this.parentNode.children).indexOf(this);
            
            if (currentColumn === column) {
                isAsc = !isAsc;
            } else {
                currentColumn = column;
                isAsc = true;
            }

            // Reset all icons
            headers.forEach(h => {
                const icon = h.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-sort text-muted ms-1';
                }
            });

            // Set current active icon
            const activeIcon = this.querySelector('i');
            if (activeIcon) {
                activeIcon.className = isAsc ? 'fas fa-sort-up ms-1' : 'fas fa-sort-down ms-1';
            }

            // Sort logic
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const cellA = a.children[columnIndex];
                const cellB = b.children[columnIndex];
                const valA = cellA.getAttribute('data-sort-val') || cellA.innerText.trim();
                const valB = cellB.getAttribute('data-sort-val') || cellB.innerText.trim();

                if (column === 'date') {
                    const dateA = new Date(valA);
                    const dateB = new Date(valB);
                    return isAsc ? dateA - dateB : dateB - dateA;
                }

                // String comparison (Locale aware Turkish sorting)
                return isAsc 
                    ? valA.localeCompare(valB, 'tr', { sensitivity: 'base' }) 
                    : valB.localeCompare(valA, 'tr', { sensitivity: 'base' });
            });

            // Re-append rows in sorted order
            rows.forEach(row => tbody.appendChild(row));
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>