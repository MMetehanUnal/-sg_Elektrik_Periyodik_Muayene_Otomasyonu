<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

function toExcelDecimal($val) {
    if ($val === null || $val === '') return '';
    return is_numeric($val) ? str_replace('.', ',', $val) : $val;
}

function cleanImportValue($val) {
    if ($val === null || $val === '') return '';
    $val = trim($val);
    $replaced = str_replace(',', '.', $val);
    if (is_numeric($replaced)) {
        return $replaced;
    }
    return $val;
}

$action = $_GET['action'] ?? '';
$type = $_GET['type'] ?? '';
$report_id = $_GET['report_id'] ?? null;

if ($action === 'download') {
    $current = isset($_GET['current']) && $_GET['current'] == '1';

    if ($type === '5_1') {
        $filename = $current ? "topraklama_5_1_mevcut_veriler.csv" : "topraklama_5_1_sablon.csv";
        $headers = ['No', 'Olcum Noktasi', 'In(A)', 'Acma Egrisi Tipi', 'Acma Akimi Ia(A)', 'Ik1(A)', 'Zx/Rx(Ohm)', 'Zs/RA(Ohm)', 'RCD Tipi Limitler', 'RCD Test Ia(mA)', 'RCD Test Ta(ms)', 'Sonuc'];
    } elseif ($type === '5_2') {
        $filename = $current ? "topraklama_5_2_mevcut_veriler.csv" : "topraklama_5_2_sablon.csv";
        $headers = ['No', 'Ust Pano Adi', 'Ust RCD Tipi', 'Ust RCD In(A)', 'Ust RCD Idn(mA)', 'Ust RCD Gecikme(ms)', 'Alt Pano Adi', 'Alt RCD Tipi', 'Alt RCD Idn(mA)', 'Alt RCD T(ms)', 'Sonuc'];
    } else {
        die("Geçersiz tip.");
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, $headers, ';');

    if ($current && $report_id) {
        if ($type === '5_1') {
            $stmt = $pdo->prepare("SELECT * FROM measurements_5_1 WHERE report_id = ? ORDER BY point_no ASC");
            $stmt->execute([$report_id]);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                fputcsv($output, array_map('toExcelDecimal', [
                    $row['point_no'],
                    $row['point_name'],
                    $row['prot_in'],
                    $row['prot_type'],
                    $row['prot_ia'],
                    $row['prot_ik1'],
                    $row['measured_zx_rx'],
                    $row['limit_zs_ra'],
                    $row['rcd_type_limits'],
                    $row['rcd_test_ia'],
                    $row['rcd_test_ta'],
                    $row['result']
                ]), ';');
            }
        } elseif ($type === '5_2') {
            $stmt = $pdo->prepare("SELECT * FROM measurements_5_2 WHERE report_id = ? ORDER BY row_no ASC");
            $stmt->execute([$report_id]);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                fputcsv($output, array_map('toExcelDecimal', [
                    $row['row_no'],
                    $row['upstream_panel'],
                    $row['upstream_rcd_type'],
                    $row['upstream_rcd_in'],
                    $row['upstream_rcd_idn'],
                    $row['upstream_rcd_dt'],
                    $row['downstream_panel'],
                    $row['downstream_rcd_type'],
                    $row['downstream_rcd_idn'],
                    $row['downstream_rcd_t'],
                    $row['result']
                ]), ';');
            }
        }
    }

    fclose($output);
    exit;
}

if ($action === 'upload') {
    if (!$report_id) {
        die("Rapor ID gerekli.");
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        header("Location: topraklama_olcumler_$type.php?report_id=$report_id&error=Dosya yükleme hatası.");
        exit;
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");

    // Skip BOM if present
    $bom = fread($handle, 3);
    if ($bom != chr(0xEF) . chr(0xBB) . chr(0xBF)) {
        rewind($handle);
    }

    // Try to detect separator
    $firstLine = fgets($handle);
    rewind($handle);
    // If BOM was skipped, we need to skip it again or rewind to right position
    $bom = fread($handle, 3);
    if ($bom != chr(0xEF) . chr(0xBB) . chr(0xBF)) {
        rewind($handle);
    }

    $separator = (strpos($firstLine, ';') !== false) ? ';' : ',';

    // Skip header
    fgetcsv($handle, 1000, $separator);

    try {
        $pdo->beginTransaction();

        if ($type === '5_1') {
            $stmt_delete = $pdo->prepare("DELETE FROM measurements_5_1 WHERE report_id = ?");
            $stmt_delete->execute([$report_id]);

            $stmt_insert = $pdo->prepare("INSERT INTO measurements_5_1 
                (report_id, point_no, point_name, prot_in, prot_type, prot_ia, prot_ik1, 
                measured_zx_rx, limit_zs_ra, rcd_type_limits, rcd_test_ia, rcd_test_ta, result)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            while (($data = fgetcsv($handle, 1000, $separator)) !== FALSE) {
                if (empty($data[1]))
                    continue; // Skip if point_name is empty
                $data = array_map('cleanImportValue', $data);
                $stmt_insert->execute([
                    $report_id,
                    $data[0], // point_no
                    $data[1], // point_name
                    $data[2], // prot_in
                    $data[3], // prot_type
                    $data[4], // prot_ia
                    $data[5] ?? '', // prot_ik1
                    $data[6] ?? '', // measured_zx_rx
                    $data[7] ?? '', // limit_zs_ra
                    $data[8] ?? '', // rcd_type_limits
                    $data[9] ?? '', // rcd_test_ia
                    $data[10] ?? '', // rcd_test_ta
                    $data[11] ?? ''  // result
                ]);
            }
        } elseif ($type === '5_2') {
            $stmt_delete = $pdo->prepare("DELETE FROM measurements_5_2 WHERE report_id = ?");
            $stmt_delete->execute([$report_id]);
 
            $stmt_insert = $pdo->prepare("INSERT INTO measurements_5_2 
                (report_id, row_no, upstream_panel, upstream_rcd_type, upstream_rcd_in, upstream_rcd_idn, upstream_rcd_dt, 
                downstream_panel, downstream_rcd_type, downstream_rcd_idn, downstream_rcd_t, result)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
 
            while (($data = fgetcsv($handle, 1000, $separator)) !== FALSE) {
                if (empty($data[1]))
                    continue; // Skip if upstream_panel is empty
                $data = array_map('cleanImportValue', $data);
                $stmt_insert->execute([
                    $report_id,
                    $data[0], // row_no
                    $data[1], // upstream_panel
                    $data[2], // upstream_rcd_type
                    $data[3], // upstream_rcd_in
                    $data[4], // upstream_rcd_idn
                    $data[5] ?? '', // upstream_rcd_dt
                    $data[6] ?? '', // downstream_panel
                    $data[7] ?? '', // downstream_rcd_type
                    $data[8] ?? '', // downstream_rcd_idn
                    $data[9] ?? '', // downstream_rcd_t
                    $data[10] ?? '' // result
                ]);
            }
        }

        $pdo->commit();
        fclose($handle);
        header("Location: topraklama_olcumler_$type.php?report_id=$report_id&success=CSV verileri başarıyla yüklendi.");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        fclose($handle);
        header("Location: topraklama_olcumler_$type.php?report_id=$report_id&error=Hata: " . urlencode($e->getMessage()));
        exit;
    }
}
