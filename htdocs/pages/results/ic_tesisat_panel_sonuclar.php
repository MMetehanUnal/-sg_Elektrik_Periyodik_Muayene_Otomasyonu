<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

if (!isset($_SESSION['active_institution_id'])) {
    redirect('/pages/tesis_secimi.php');
}
$kurum_id = $_SESSION['active_institution_id'];

$report_id = isset($_GET['report_id']) ? (int) $_GET['report_id'] : null;
if (!$report_id)
    redirect('/pages/results/ic_tesisat_sonuclar.php');

// Validate report belongs to institution
$stmt = $pdo->prepare("SELECT * FROM internal_installation_reports WHERE id = ? AND kurum_id = ?");
$stmt->execute([$report_id, $kurum_id]);
$rpt = $stmt->fetch();
if (!$rpt)
    redirect('/pages/results/ic_tesisat_sonuclar.php');

$section = $_GET['section'] ?? '5';
$panel_id_param = isset($_GET['panel_id']) ? (int) $_GET['panel_id'] : null;
$msg = '';
$msg_type = 'success';

// Section5 questions definition
$s5_questions = [
    'PANO VE DİĞER DONANIMLARA GİRİŞİN UYGUNLUĞU' => [
        'kablo_sebeke' => 'Kablo şebeke tarafı',
        'kablo_donanim' => 'Kablo donanım tarafı',
        'pano_sabitleme' => 'Pano sabitlenmesi (Depreme dayanıklılık)',
        'dis_darbe' => 'Dış darbelere karşı koruma önlemi',
        'yabanci_malzeme' => 'Elektrik panosu etrafında yabancı malzemeler',
        'zemin_izolasyon' => 'Zemin izolasyonu',
    ],
    'TOPRAKLANMIŞ POTANSİYEL DENGELEME VE BESLEMENİN OTOMATİK KESİLMESİ, ELEKTRİK ÇARPMASINA KARŞI KORUMA' => [
        'topraklama_iletken' => 'Topraklama iletkeni',
        'ana_pot_iletken' => 'Ana potansiyel dengeleme iletkeni',
        'ek_pot_iletken' => 'Ek Potansiyel dengeleme İletkeni (Tamamlayıcı pot.den)',
        'kapak_6mm' => 'Pano kapak bağlantısı kontrolü 6 mm²',
    ],
    'KARŞILIKLI ZARARLI ETKİLERİN ÖNLENMESİ' => [
        'elektriksel_olmayan' => 'Elektriksel olmayan tesislere yaklaşma ve diğer etkilerin kontrolü',
        'bant_ayirma' => 'Bant I ve Bant II ayrılması, Bant II yalıtımı',
        'guvenlik_devre' => 'Güvenlik devre ayrılması',
        'pano_kapak_erisim' => 'Pano iç kapak, faza erişim engeli veya pleksi koruma',
    ],
    'TANIMLAMA' => [
        'semalar' => 'Şemalar, talimatlar, devre çizimleri ve kısa bilgiler',
        'koruma_etiket' => 'Koruma cihaz ve terminal etiket',
        'tehlike_isaretleri' => 'Tehlike işaretleri ve diğer uyarı işaretleri',
    ],
    'KABLO ve İLETKENLER' => [
        'kablo_yolu' => 'Kablo yollarının uygunluğu ve mekanik koruma',
        'kablo_renk' => 'Kablo renk kodları Nötr: Mavi Toprak: Sarı/Yeşil',
        'tesisat_yontemi' => 'Tesisat yöntemi',
        'yangin_engeli' => 'Yangın engeli, uygun kilitleme ve sıcaklık etkisine karşı koruma',
    ],
    'TERMAL KAMERA' => [
        'fotograf_tarihi' => 'Fotoğraf tarihi',
        'kontak_gevsekligi' => 'Kontak gevşekliği ısınması',
        'fotograf_no' => 'Fotoğraf no.',
        'asiri_yuk_isinma' => 'Aşırı yük ısınması PVC kablolar için >70 derece',
    ],
    'GENEL DEĞERLENDİRMELER' => [
        'yangin_sondurme' => 'Ekipman yakınında elektriksel ekipman yangın söndürme tertibatı',
        'ekipman_temizlik' => 'Ekipman temizlik/bakım durumu',
        'korozyon' => 'Pano içi ve bağlantılarının korozyon kontrolü',
        'acil_aydinlatma' => 'Ekipman içi veya yakınında acil durum aydınlatma tertibatı',
    ],
];

// ------- ACTION HANDLERS -------
// Add Panel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_panel') {
        $pname = trim($_POST['panel_name'] ?? '');
        if ($pname) {
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(panel_order),0)+1 as next FROM ic_tesisat_panels WHERE report_id=?");
            $stmt->execute([$report_id]);
            $next = $stmt->fetchColumn();
            $pdo->prepare("INSERT INTO ic_tesisat_panels (report_id, panel_name, panel_order) VALUES (?,?,?)")->execute([$report_id, $pname, $next]);
            $msg = "Pano eklendi.";
        }
    } elseif ($action === 'delete_panel') {
        $pid = (int) $_POST['panel_id'];
        $pdo->prepare("DELETE FROM ic_tesisat_panels WHERE id = ? AND report_id = ?")->execute([$pid, $report_id]);
        $msg = "Pano silindi.";
    } elseif ($action === 'save_all_standard_section5' || $action === 'save_all_not_suitable_section5') {
        $defaultValue = ($action === 'save_all_standard_section5') ? 'U' : 'UD';
        
        // Fetch all panels for this report
        $stmt = $pdo->prepare("SELECT * FROM ic_tesisat_panels WHERE report_id=? ORDER BY panel_order");
        $stmt->execute([$report_id]);
        $panels_list = $stmt->fetchAll();
        
        // Load report start date for default date
        $default_start_date = '';
        if (!empty($rpt['start_date'])) {
            $default_start_date = date('d.m.Y', strtotime($rpt['start_date']));
        } else {
            // fallback to institution
            $stmt_inst = $pdo->prepare("SELECT start_date FROM institutions WHERE id=?");
            $stmt_inst->execute([$kurum_id]);
            $inst_info = $stmt_inst->fetch();
            $default_start_date = $inst_info['start_date'] ? date('d.m.Y', strtotime($inst_info['start_date'])) : date('d.m.Y');
        }
        
        $ins = $pdo->prepare("INSERT INTO ic_tesisat_section5 (panel_id, question_key, answer) VALUES (?,?,?)");
        
        foreach ($panels_list as $idx => $pnl) {
            $pid = $pnl['id'];
            $panel_idx = $idx + 1;
            
            // Delete existing section 5 answers for this panel
            $pdo->prepare("DELETE FROM ic_tesisat_section5 WHERE panel_id = ?")->execute([$pid]);
            
            // Loop through all questions and insert $defaultValue or default text
            foreach ($s5_questions as $group => $items) {
                foreach ($items as $qkey => $label) {
                    if ($qkey === 'fotograf_tarihi') {
                        $ins->execute([$pid, $qkey, $default_start_date]);
                    } elseif ($qkey === 'fotograf_no') {
                        $ins->execute([$pid, $qkey, $panel_idx]);
                    } else {
                        $ins->execute([$pid, $qkey, $defaultValue]);
                    }
                }
            }
        }
        $msgText = ($defaultValue === 'U') ? "uygun ('U') olarak" : "uygun değil ('UD') olarak";
        $msg = "Bütün panolar için 5. Gözle Muayene bölümü {$msgText} veritabanına kaydedildi.";
    } elseif ($action === 'fill_all_test_data') {
        // Fetch all panels for this report
        $stmt = $pdo->prepare("SELECT * FROM ic_tesisat_panels WHERE report_id=? ORDER BY panel_order");
        $stmt->execute([$report_id]);
        $panels_list = $stmt->fetchAll();
        
        $filled_count = 0;
        foreach ($panels_list as $pnl) {
            $pid = $pnl['id'];
            
            // Check if 6.1 header exists and is empty
            $s61 = $pdo->prepare("SELECT * FROM ic_tesisat_section6_1 WHERE panel_id=?");
            $s61->execute([$pid]);
            $s61data = $s61->fetch();
            
            $header_is_empty = !$s61data || (empty($s61data['zx']) && empty($s61data['zln']));
            
            if ($header_is_empty) {
                $randFloat = function($base, $variance, $decimals) {
                    $random_val = $base + (mt_rand() / mt_getrandmax() * $variance * 2 - $variance);
                    return number_format($random_val, $decimals, '.', '');
                };
                
                $zx = $randFloat(2.0, 0.5, 2);
                $zln = $randFloat(2.0, 0.5, 2);
                $voltage_ff = $randFloat(230, 5, 1);
                $voltage_ln = $randFloat(230, 5, 1);
                $voltage_npe = $randFloat(1.5, 0.5, 2);
                $short_circuit_3ph = $randFloat(0.9, 0.25, 2);
                $dkd_type = '-';
                $dkd_current = '-';
                
                $pdo->prepare("DELETE FROM ic_tesisat_section6_1 WHERE panel_id=?")->execute([$pid]);
                $pdo->prepare("INSERT INTO ic_tesisat_section6_1 (panel_id,zx,zln,voltage_ff,voltage_ln,voltage_npe,short_circuit_3ph,dkd_type,dkd_current) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$pid, $zx, $zln, $voltage_ff, $voltage_ln, $voltage_npe, $short_circuit_3ph, $dkd_type, $dkd_current]);
            }
            
            // Check if 6.1 rows exist and are empty
            $s61rows = $pdo->prepare("SELECT * FROM ic_tesisat_section6_1_rows WHERE panel_id=?");
            $s61rows->execute([$pid]);
            $rows_data = $s61rows->fetchAll();
            
            $rows_are_empty = empty($rows_data) || (count($rows_data) === 1 && empty($rows_data[0]['linye_adi']));
            
            if ($rows_are_empty) {
                $rcd_ia = ['24', '27', '30'][array_rand(['24', '27', '30'])];
                $rcd_ta = number_format(27 + (mt_rand() / mt_getrandmax() * 3 * 2 - 3), 2, '.', '');
                
                $pdo->prepare("DELETE FROM ic_tesisat_section6_1_rows WHERE panel_id=?")->execute([$pid]);
                $ins = $pdo->prepare("INSERT INTO ic_tesisat_section6_1_rows (panel_id,no_col,linye_adi,acma_egrisi,kutup_sayisi,in_a,icu,faz_kesiti,npen_kesiti,pe_kesiti,ib_tasarim,iz_kapasite,rcd_ia,rcd_ta,sonuc) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $ins->execute([
                    $pid, 
                    '1', 
                    $pnl['panel_name'], 
                    'C', 
                    '4', 
                    '630', 
                    '6kA', 
                    '1*10', 
                    '1*10', 
                    '1*10', 
                    '50', 
                    '48', 
                    $rcd_ia, 
                    $rcd_ta, 
                    'Uygun'
                ]);
                $filled_count++;
            }
        }
        $msg = "Bütün boş panolar için test verileri başarıyla oluşturuldu ve veritabanına kaydedildi. (Doldurulan pano sayısı: $filled_count)";
    } elseif ($action === 'save_section5') {
        $pid = (int) $_POST['panel_id'];
        $answers = $_POST['q'] ?? [];
        $pdo->prepare("DELETE FROM ic_tesisat_section5 WHERE panel_id = ?")->execute([$pid]);
        $ins = $pdo->prepare("INSERT INTO ic_tesisat_section5 (panel_id, question_key, answer) VALUES (?,?,?)");
        foreach ($answers as $key => $val) {
            if ($val !== '')
                $ins->execute([$pid, $key, $val]);
        }
        $msg = "5. Bölüm kaydedildi.";
    } elseif ($action === 'save_section6_header') {
        $m = trim($_POST['measurement_method'] ?? '');
        $pdo->prepare("INSERT INTO ic_tesisat_section6_header (report_id, measurement_method) VALUES (?,?) ON DUPLICATE KEY UPDATE measurement_method=?")->execute([$report_id, $m, $m]);
        $msg = "Ölçüm metodu kaydedildi.";
    } elseif ($action === 'save_section6_1') {
        $pid = (int) $_POST['panel_id'];
        // Header row
        $pdo->prepare("DELETE FROM ic_tesisat_section6_1 WHERE panel_id=?")->execute([$pid]);
        $pdo->prepare("INSERT INTO ic_tesisat_section6_1 (panel_id,zx,zln,voltage_ff,voltage_ln,voltage_npe,short_circuit_3ph,dkd_type,dkd_current) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$pid, $_POST['zx'] ?? '', $_POST['zln'] ?? '', $_POST['voltage_ff'] ?? '', $_POST['voltage_ln'] ?? '', $_POST['voltage_npe'] ?? '', $_POST['short_circuit_3ph'] ?? '', $_POST['dkd_type'] ?? '', $_POST['dkd_current'] ?? '']);
        // Table rows
        $pdo->prepare("DELETE FROM ic_tesisat_section6_1_rows WHERE panel_id=?")->execute([$pid]);
        $ins = $pdo->prepare("INSERT INTO ic_tesisat_section6_1_rows (panel_id,no_col,linye_adi,acma_egrisi,kutup_sayisi,in_a,icu,faz_kesiti,npen_kesiti,pe_kesiti,ib_tasarim,iz_kapasite,rcd_ia,rcd_ta,sonuc) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $rows = $_POST['rows'] ?? [];
        foreach ($rows as $r)
            $ins->execute([$pid, $r['no'] ?? '', $r['linye'] ?? '', $r['acma'] ?? '', $r['kutup'] ?? '', $r['in_a'] ?? '', $r['icu'] ?? '', $r['faz'] ?? '', $r['npen'] ?? '', $r['pe'] ?? '', $r['ib'] ?? '', $r['iz'] ?? '', $r['rcd_ia'] ?? '', $r['rcd_ta'] ?? '', $r['sonuc'] ?? '']);
        $msg = "6.1 kaydedildi.";
    } elseif ($action === 'save_section6_2') {
        $pdo->prepare("DELETE FROM ic_tesisat_section6_2_rows WHERE report_id=?")->execute([$report_id]);
        $ins = $pdo->prepare("INSERT INTO ic_tesisat_section6_2_rows (report_id,no_col,bolum,pd_kesiti,pd_sureklilik,tpd_kesiti,tpd_sureklilik,sonuc) VALUES (?,?,?,?,?,?,?,?)");
        $rows = $_POST['rows'] ?? [];
        foreach ($rows as $r)
            $ins->execute([$report_id, $r['no'] ?? '', $r['bolum'] ?? '', $r['pd_kesiti'] ?? '', $r['pd_sur'] ?? '', $r['tpd_kesiti'] ?? '', $r['tpd_sur'] ?? '', $r['sonuc'] ?? '']);
        $msg = "6.2 kaydedildi.";
    } elseif ($action === 'save_section6_3') {
        $pdo->prepare("DELETE FROM ic_tesisat_section6_3_rows WHERE report_id=?")->execute([$report_id]);
        $ins = $pdo->prepare("INSERT INTO ic_tesisat_section6_3_rows (report_id,no_col,hali_yeri,eni,boyu,direnc,sonuc) VALUES (?,?,?,?,?,?,?)");
        $rows = $_POST['rows'] ?? [];
        foreach ($rows as $r)
            $ins->execute([$report_id, $r['no'] ?? '', $r['hali'] ?? '', $r['eni'] ?? '', $r['boyu'] ?? '', $r['direnc'] ?? '', $r['sonuc'] ?? '']);
        $msg = "6.3 kaydedildi.";
    } elseif ($action === 'upload_photo') {
        $pid = (int) $_POST['panel_id'];
        $ptype = in_array($_POST['photo_type'], ['normal', 'termal']) ? $_POST['photo_type'] : 'normal';
        if (!empty($_FILES['photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $dir = "../../uploads/ic_tesisat/$report_id/$pid/";
                if (!is_dir($dir))
                    mkdir($dir, 0755, true);
                $filename = $ptype . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $dest_path = $dir . $filename;
                
                // Attempt to compress first; if that fails, try moving the original as fallback
                $uploaded = compressImage($_FILES['photo']['tmp_name'], $dest_path, 75, 800);
                if (!$uploaded) {
                    $uploaded = move_uploaded_file($_FILES['photo']['tmp_name'], $dest_path);
                }
                
                if ($uploaded) {
                    $fpath = "/uploads/ic_tesisat/$report_id/$pid/" . $filename;
                    $pdo->prepare("INSERT INTO ic_tesisat_photos (panel_id, photo_type, file_path) VALUES (?,?,?)")->execute([$pid, $ptype, $fpath]);
                    echo json_encode(['success' => true, 'path' => $fpath, 'photo_id' => $pdo->lastInsertId()]);
                    exit;
                }
            }
        }
        echo json_encode(['success' => false]);
        exit;
    } elseif ($action === 'bulk_upload_photo') {
        if (!empty($_FILES['photo']['name'])) {
            $file = $_FILES['photo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $type_mode = $_POST['type_mode'] ?? 'auto';
                $ptype = 'normal';
                $filename = pathinfo($file['name'], PATHINFO_FILENAME);
                
                if ($type_mode === 'termal') {
                    $ptype = 'termal';
                } elseif ($type_mode === 'normal') {
                    $ptype = 'normal';
                } else { // auto
                    $lower_name = mb_strtolower($filename, 'UTF-8');
                    $thermal_keywords = ['termal', 'thermal', 'isi', 'temp', 'sicak', 'heat', 'inf', 'ir'];
                    $is_thermal = false;
                    foreach ($thermal_keywords as $keyword) {
                        if (strpos($lower_name, $keyword) !== false) {
                            $is_thermal = true;
                            break;
                        }
                    }
                    $ptype = $is_thermal ? 'termal' : 'normal';
                }

                // Extract number from filename (e.g. TR000450 -> 450)
                $extracted_number = '';
                if (preg_match('/(?:^|[^0-9])(0*[1-9][0-9]*)(?:[^0-9]|$)/', $filename, $matches)) {
                    $extracted_number = (string)(int)$matches[1];
                } elseif (preg_match('/([0-9]+)/', $filename, $matches)) {
                    $extracted_number = (string)(int)$matches[1];
                }

                // Retrieve all panels for this report
                $stmt = $pdo->prepare("SELECT * FROM ic_tesisat_panels WHERE report_id=? ORDER BY panel_order");
                $stmt->execute([$report_id]);
                $all_panels = $stmt->fetchAll();
                
                $matched_panel = null;
                $match_reason = '';

                if ($extracted_number !== '') {
                    foreach ($all_panels as $pnl) {
                        if (!empty($pnl['thermal_numbers'])) {
                            $nums = explode(',', $pnl['thermal_numbers']);
                            foreach ($nums as $n) {
                                if (trim($n) === $extracted_number) {
                                    $matched_panel = $pnl;
                                    $match_reason = "Fotoğraf No ($extracted_number)";
                                    break 2;
                                }
                            }
                        }
                    }
                }

                // Fallback 1: Match by name
                if (!$matched_panel) {
                    $normalizeFn = function($str) {
                        $str = mb_strtolower($str, 'UTF-8');
                        $turk = ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'];
                        $eng  = ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'];
                        $str = str_replace($turk, $eng, $str);
                        $str = preg_replace('/[^a-z0-9]/', '', $str);
                        return $str;
                    };
                    
                    $normalized_filename = $normalizeFn($filename);
                    
                    $panels_by_length = $all_panels;
                    usort($panels_by_length, function($a, $b) use ($normalizeFn) {
                        return strlen($normalizeFn($b['panel_name'])) <=> strlen($normalizeFn($a['panel_name']));
                    });
                    
                    foreach ($panels_by_length as $pnl) {
                        $norm_pname = $normalizeFn($pnl['panel_name']);
                        if (!empty($norm_pname) && strpos($normalized_filename, $norm_pname) !== false) {
                            $matched_panel = $pnl;
                            $match_reason = "Pano Adı ('" . $pnl['panel_name'] . "')";
                            break;
                        }
                    }
                }

                // Fallback 2: Match by order
                if (!$matched_panel && $extracted_number !== '') {
                    $order_num = (int)$extracted_number;
                    foreach ($all_panels as $pnl) {
                        if ((int)$pnl['panel_order'] === $order_num) {
                            $matched_panel = $pnl;
                            $match_reason = "Sıra No Eşleşmesi (#$order_num)";
                            break;
                        }
                    }
                }

                if ($matched_panel) {
                    $pid = $matched_panel['id'];
                    $dir = "../../uploads/ic_tesisat/$report_id/$pid/";
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $new_filename = $ptype . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $dest_path = $dir . $new_filename;
                    
                    $uploaded = compressImage($file['tmp_name'], $dest_path, 75, 800);
                    if (!$uploaded) {
                        $uploaded = move_uploaded_file($file['tmp_name'], $dest_path);
                    }
                    
                    if ($uploaded) {
                        $fpath = "/uploads/ic_tesisat/$report_id/$pid/" . $new_filename;
                        $pdo->prepare("INSERT INTO ic_tesisat_photos (panel_id, photo_type, file_path) VALUES (?,?,?)")->execute([$pid, $ptype, $fpath]);
                        
                        echo json_encode([
                            'success' => true,
                            'path' => $fpath,
                            'photo_id' => $pdo->lastInsertId(),
                            'panel_name' => $matched_panel['panel_name'],
                            'photo_type' => ($ptype === 'termal' ? 'Termal Kamera' : 'Normal Kamera'),
                            'reason' => $match_reason,
                            'extracted_no' => ($extracted_number !== '' ? $extracted_number : '-')
                        ]);
                        exit;
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Dosya sunucuya yazılamadı.', 'extracted_no' => ($extracted_number !== '' ? $extracted_number : '-')]);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Herhangi bir pano ile eşleştirilemedi.', 'extracted_no' => ($extracted_number !== '' ? $extracted_number : '-')]);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Desteklenmeyen dosya türü.', 'extracted_no' => '-']);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Dosya yüklenemedi.', 'extracted_no' => '-']);
        exit;
    } elseif ($action === 'delete_photo') {
        $photo_id = (int) $_POST['photo_id'];
        $stmt = $pdo->prepare("SELECT p.file_path FROM ic_tesisat_photos p JOIN ic_tesisat_panels pan ON p.panel_id=pan.id WHERE p.id=? AND pan.report_id=?");
        $stmt->execute([$photo_id, $report_id]);
        $ph = $stmt->fetch();
        if ($ph) {
            $fpath = "../../" . ltrim($ph['file_path'], '/');
            if (file_exists($fpath))
                unlink($fpath);
            $pdo->prepare("DELETE FROM ic_tesisat_photos WHERE id=?")->execute([$photo_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($action === 'save_panel_notes') {
        $pid = (int) $_POST['panel_id'];
        $notes = trim($_POST['notes'] ?? '');
        $thermal_numbers = trim($_POST['thermal_numbers'] ?? '');
        $thermal_numbers = preg_replace('/\s+/', '', $thermal_numbers); // remove spaces
        $pdo->prepare("UPDATE ic_tesisat_panels SET notes = ?, thermal_numbers = ? WHERE id = ? AND report_id = ?")->execute([$notes, $thermal_numbers, $pid, $report_id]);
        $msg = "Pano bilgileri kaydedildi.";
    }
}

// ------- DATA FETCH -------
$stmt = $pdo->prepare("SELECT * FROM ic_tesisat_panels WHERE report_id=? ORDER BY panel_order");
$stmt->execute([$report_id]);
$panels = $stmt->fetchAll();

$s6header = $pdo->prepare("SELECT * FROM ic_tesisat_section6_header WHERE report_id=?");
$s6header->execute([$report_id]);
$s6hdr = $s6header->fetch();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">İç Tesisat – Pano Sonuçları
        <small class="text-muted fs-6">(Rapor:
            <?php echo htmlspecialchars($rpt['report_no']); ?>
            <?php if (!empty($rpt['firma_adi_eki'])): ?>
                - <?php echo htmlspecialchars($rpt['firma_adi_eki']); ?>
            <?php endif; ?>)
        </small>
    </h1>
    <div class="d-flex gap-2">
        <a href="/pages/results/ic_tesisat_sonuclar.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Geri
        </a>
        <a href="/pages/ic_tesisat_yazdir.php?id=<?php echo $report_id; ?>" target="_blank" class="btn btn-dark btn-sm">
            <i class="fas fa-print"></i> Raporu Yazdır
        </a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- LEFT: panel list -->
    <div class="col-md-3">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="fas fa-th-list me-1"></i> Panolar
            </div>
            <div class="card-body p-2">
                <?php foreach ($panels as $p): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                        <span class="text-truncate" style="max-width:130px;"
                            title="<?php echo htmlspecialchars($p['panel_name']); ?>">
                            <?php echo htmlspecialchars($p['panel_name']); ?>
                        </span>
                        <div class="d-flex gap-1">
                            <a href="?report_id=<?php echo $report_id; ?>&section=<?php echo $section; ?>&panel_id=<?php echo $p['id']; ?>"
                                class="btn btn-xs btn-outline-primary btn-sm py-0 px-1" title="Seç">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Panoyu sil?')">
                                <input type="hidden" name="action" value="delete_panel">
                                <input type="hidden" name="panel_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn btn-xs btn-outline-danger btn-sm py-0 px-1" title="Sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($panels)): ?>
                    <p class="text-muted small text-center p-2">Henüz pano eklenmedi.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer p-2">
                <form method="POST">
                    <input type="hidden" name="action" value="add_panel">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" name="panel_name" placeholder="Pano adı..." required>
                        <button class="btn btn-success" type="submit"><i class="fas fa-plus"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section navigation -->
        <div class="list-group">
            <a href="?report_id=<?php echo $report_id; ?>&section=5<?php echo ($panel_id_param ? '&panel_id=' . $panel_id_param : ''); ?>"
                class="list-group-item list-group-item-action <?php echo ($section == '5') ? 'active' : ''; ?>">
                <i class="fas fa-eye me-2"></i> 5. Gözle Muayene
            </a>
            <a href="?report_id=<?php echo $report_id; ?>&section=6<?php echo ($panel_id_param ? '&panel_id=' . $panel_id_param : ''); ?>"
                class="list-group-item list-group-item-action <?php echo ($section == '6') ? 'active' : ''; ?>">
                <i class="fas fa-vials me-2"></i> 6. Fonksiyon Testleri
            </a>
            <a href="?report_id=<?php echo $report_id; ?>&section=photos<?php echo ($panel_id_param ? '&panel_id=' . $panel_id_param : ''); ?>"
                class="list-group-item list-group-item-action <?php echo ($section == 'photos') ? 'active' : ''; ?>">
                <i class="fas fa-camera me-2"></i> 8. Fotoğraflar
            </a>
        </div>
    </div>

    <!-- RIGHT: content area -->
    <div class="col-md-9">

        <!-- Top bulk actions bar -->
        <div class="mb-3 d-flex gap-2 justify-content-end">
            <?php if ($section === '5'): ?>
                <form method="POST" onsubmit="return confirm('Tüm panolar için Gözle Muayene bölümünü UYGUN (U) olarak doldurmak istiyor musunuz?')" style="display:inline;">
                    <input type="hidden" name="action" value="save_all_standard_section5">
                    <button type="submit" class="btn btn-success btn-sm fw-bold shadow-sm">
                        <i class="fas fa-check-circle me-1"></i> 5. TÜMÜNÜ UYGUN OLARAK KAYDET
                    </button>
                </form>
                <form method="POST" onsubmit="return confirm('Tüm panolar için Gözle Muayene bölümünü UYGUN DEĞİL (UD) olarak doldurmak istiyor musunuz?')" style="display:inline;">
                    <input type="hidden" name="action" value="save_all_not_suitable_section5">
                    <button type="submit" class="btn btn-danger btn-sm fw-bold shadow-sm">
                        <i class="fas fa-times-circle me-1"></i> 5. TÜMÜNÜ UYGUN DEĞİL OLARAK KAYDET
                    </button>
                </form>
            <?php elseif ($section === '6'): ?>
                <form method="POST" onsubmit="return confirm('Tüm boş panoları rastgele test verileriyle doldurmak istiyor musunuz?')">
                    <input type="hidden" name="action" value="fill_all_test_data">
                    <button type="submit" class="btn btn-info text-white btn-sm fw-bold shadow-sm">
                        <i class="fas fa-magic me-1"></i> 6. TEST VERİSİ EKLE
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!$panel_id_param && $section !== '6'): ?>
            <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i> Soldaki listeden bir pano seçin veya yeni
                pano ekleyin.</div>
        <?php endif; ?>

        <?php
        // Load selected panel
        $current_panel = null;
        if ($panel_id_param) {
            foreach ($panels as $p) {
                if ($p['id'] == $panel_id_param) {
                    $current_panel = $p;
                    break;
                }
            }
        }
        ?>

        <?php if ($section === '5' && $current_panel): ?>
            <!-- SECTION 5: Gözle Muayene -->
            <div class="card">
                <div class="card-header bg-warning fw-bold">
                    5. KONTROL KRİTERLERİ VE TESTLER –
                    <strong>
                        <?php echo htmlspecialchars($current_panel['panel_name']); ?>
                    </strong>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Load report start date for default date
                    $default_start_date = '';
                    if (!empty($rpt['start_date'])) {
                        $default_start_date = date('d.m.Y', strtotime($rpt['start_date']));
                    } else {
                        // fallback to institution
                        $stmt_inst = $pdo->prepare("SELECT start_date FROM institutions WHERE id=?");
                        $stmt_inst->execute([$kurum_id]);
                        $inst_info = $stmt_inst->fetch();
                        $default_start_date = $inst_info['start_date'] ? date('d.m.Y', strtotime($inst_info['start_date'])) : '';
                    }

                    // Load existing answers
                    $stmt = $pdo->prepare("SELECT question_key, answer FROM ic_tesisat_section5 WHERE panel_id=?");
                    $stmt->execute([$panel_id_param]);
                    $existing = [];
                    $any_existing = false;
                    foreach ($stmt->fetchAll() as $row) {
                        $existing[$row['question_key']] = $row['answer'];
                        $any_existing = true;
                    }

                    // Calculate panel index for fotograf_no default
                    $panel_idx = 0;
                    foreach ($panels as $idx => $p) {
                        if ($p['id'] == $panel_id_param) {
                            $panel_idx = $idx + 1;
                            break;
                        }
                    }
                    ?>
                    <form method="POST"
                        action="?report_id=<?php echo $report_id; ?>&section=5&panel_id=<?php echo $panel_id_param; ?>">
                        <input type="hidden" name="action" value="save_section5">
                        <input type="hidden" name="panel_id" value="<?php echo $panel_id_param; ?>">
                        <table class="table table-bordered table-sm mb-0 align-middle">
                            <thead class="table-secondary text-center">
                                <tr>
                                    <th style="width:35%">Kontrol Kriteri</th>
                                    <th style="width:30%">Değerlendirme</th>
                                    <th style="width:35%">Kontrol Kriteri</th>
                                    <th style="width:30%">Değerlendirme</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($s5_questions as $group => $items): ?>
                                    <tr class="table-light">
                                        <td colspan="4" class="fw-bold small text-center py-1">
                                            <?php echo $group; ?>
                                        </td>
                                    </tr>
                                    <?php
                                    $keys = array_keys($items);
                                    $labels = array_values($items);
                                    $half = ceil(count($keys) / 2);
                                    for ($i = 0; $i < $half; $i++):
                                        $k1 = $keys[$i];
                                        $l1 = $labels[$i];
                                        $k2 = $keys[$i + $half] ?? null;
                                        $l2 = $labels[$i + $half] ?? null;

                                        // Default values logic
                                        $v1 = $existing[$k1] ?? '';
                                        if (!$any_existing && $v1 === '') {
                                            if ($k1 === 'fotograf_tarihi')
                                                $v1 = $default_start_date;
                                            elseif ($k1 === 'fotograf_no')
                                                $v1 = $panel_idx;
                                            else
                                                $v1 = 'U';
                                        }

                                        $v2 = '';
                                        if ($k2) {
                                            $v2 = $existing[$k2] ?? '';
                                            if (!$any_existing && $v2 === '') {
                                                if ($k2 === 'fotograf_tarihi')
                                                    $v2 = $default_start_date;
                                                elseif ($k2 === 'fotograf_no')
                                                    $v2 = $panel_idx;
                                                else
                                                    $v2 = 'U';
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td class="small"><?php echo $l1; ?></td>
                                            <td class="text-center">
                                                <?php if ($k1 === 'fotograf_tarihi' || $k1 === 'fotograf_no'): ?>
                                                    <input type="text" name="q[<?php echo $k1; ?>]" class="form-control form-control-sm"
                                                        value="<?php echo htmlspecialchars($v1); ?>">
                                                <?php else: ?>
                                                    <?php foreach (['U' => 'U', 'UD' => 'UD', 'UG' => 'UG'] as $val => $lbl): ?>
                                                        <div class="form-check form-check-inline mb-0">
                                                            <input class="form-check-input" type="radio" name="q[<?php echo $k1; ?>]"
                                                                value="<?php echo $val; ?>" <?php echo ($v1 === $val) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label small"><?php echo $lbl; ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($k2): ?>
                                                <td class="small"><?php echo $l2; ?></td>
                                                <td class="text-center">
                                                    <?php if ($k2 === 'fotograf_tarihi' || $k2 === 'fotograf_no'): ?>
                                                        <input type="text" name="q[<?php echo $k2; ?>]" class="form-control form-control-sm"
                                                            value="<?php echo htmlspecialchars($v2); ?>">
                                                    <?php else: ?>
                                                        <?php foreach (['U' => 'U', 'UD' => 'UD', 'UG' => 'UG'] as $val => $lbl): ?>
                                                            <div class="form-check form-check-inline mb-0">
                                                                <input class="form-check-input" type="radio" name="q[<?php echo $k2; ?>]"
                                                                    value="<?php echo $val; ?>" <?php echo ($v2 === $val) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label small"><?php echo $lbl; ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php else: ?>
                                                <td></td>
                                                <td></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endfor; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="p-3">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($section === '6'): ?>
            <!-- SECTION 6: Fonksiyon Testleri -->
            <!-- 6. Header: Ölçüm Metodu (1 kere) -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white fw-bold">6. FONKSİYON KONTROL KRİTERLERİ VE TESTLER</div>
                <div class="card-body">
                    <form method="POST" action="?report_id=<?php echo $report_id; ?>&section=6">
                        <input type="hidden" name="action" value="save_section6_header">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Ölçüm ve doğrulama metodu</label>
                            <?php $mval = $s6hdr['measurement_method'] ?? ''; ?>
                            <div class="d-flex gap-3 flex-wrap">
                                <?php foreach (['Üç Uçlu Karşılaştırma', 'Çevrim Empedansı', 'Klamp Yöntemi'] as $opt): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="measurement_method"
                                            value="<?php echo $opt; ?>" <?php echo ($mval === $opt) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">
                                            <?php echo $opt; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-info text-white"><i class="fas fa-save me-1"></i> Metodu
                            Kaydet</button>
                    </form>
                </div>
            </div>

            <!-- 6.1: Per Panel -->
            <?php foreach ($panels as $pnl): ?>
                <?php
                $s61 = $pdo->prepare("SELECT * FROM ic_tesisat_section6_1 WHERE panel_id=?");
                $s61->execute([$pnl['id']]);
                $s61data = $s61->fetch() ?: [];

                $s61rows = $pdo->prepare("SELECT * FROM ic_tesisat_section6_1_rows WHERE panel_id=? ORDER BY id");
                $s61rows->execute([$pnl['id']]);
                $s61rowsdata = $s61rows->fetchAll();
                if (empty($s61rowsdata))
                    $s61rowsdata = [['sonuc' => 'Uygun']]; // default to 'Uygun' if no rows
                ?>
                <div class="card mb-3">
                    <div class="card-header bg-warning fw-bold">
                        6.1 AŞIRI AKIM CİHAZI –
                        <?php echo htmlspecialchars($pnl['panel_name']); ?>
                    </div>
                    <div class="card-body p-0">
                        <form method="POST" action="?report_id=<?php echo $report_id; ?>&section=6">
                            <input type="hidden" name="action" value="save_section6_1">
                            <input type="hidden" name="panel_id" value="<?php echo $pnl['id']; ?>">
                            <table class="table table-bordered table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="fw-bold small bg-light">Pano (Ekipman) Adı-Etiketi veya Kodu:
                                            <?php echo htmlspecialchars($pnl['panel_name']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="small align-middle">Panodan ölçülen faz-toprak çevrim empedansı (Zx) (Ω)</td>
                                        <td><input type="text" class="form-control form-control-sm" name="zx"
                                                value="<?php echo htmlspecialchars($s61data['zx'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td class="small align-middle">Panodan ölçülen faz-nötr çevrim empedansı (ZLN) (Ω)</td>
                                        <td><input type="text" class="form-control form-control-sm" name="zln"
                                                value="<?php echo htmlspecialchars($s61data['zln'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td class="small align-middle">Gerilimler L-PE (V)</td>
                                        <td><input type="text" class="form-control form-control-sm" name="voltage_ff"
                                                value="<?php echo htmlspecialchars($s61data['voltage_ff'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td class="small align-middle">Gerilimler L-N (V)</td>
                                        <td><input type="text" class="form-control form-control-sm" name="voltage_ln"
                                                value="<?php echo htmlspecialchars($s61data['voltage_ln'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td class="small align-middle">Gerilimler N-PE (V)</td>
                                        <td><input type="text" class="form-control form-control-sm" name="voltage_npe"
                                                value="<?php echo htmlspecialchars($s61data['voltage_npe'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td class="small align-middle">Hesaplanan 3 fazlı kısa devre akımı Ik3 (kA)</td>
                                        <td><input type="text" class="form-control form-control-sm" name="short_circuit_3ph"
                                                value="<?php echo htmlspecialchars($s61data['short_circuit_3ph'] ?? ''); ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="small align-middle">Aşırı gerilim koruma (DKD) tipi</td>
                                        <td><input type="text" class="form-control form-control-sm" name="dkd_type"
                                                value="<?php echo htmlspecialchars($s61data['dkd_type'] ?? ''); ?>"></td>
                                    </tr>
                                    <tr>
                                        <td class="small align-middle">Aşırı gerilim koruma (DKD) dayanma akımı (kA)</td>
                                        <td><input type="text" class="form-control form-control-sm" name="dkd_current"
                                                value="<?php echo htmlspecialchars($s61data['dkd_current'] ?? ''); ?>"></td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- 6.1 detail rows table -->
                            <div class="p-2">
                                <p class="small fw-bold mb-1"><i class="fas fa-list-ol me-1"></i> Linye / Bağlantı Listesi</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm mb-1 align-middle text-center"
                                        id="t61_<?php echo $pnl['id']; ?>" style="font-size: 11px; min-width: 1000px;">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th rowspan="2" style="width:30px">No</th>
                                                <th rowspan="2" style="min-width:120px">Linye / Pano Adı</th>
                                                <th colspan="4">Aşırı Akım Koruma</th>
                                                <th colspan="3">İletken Kesitleri (mm²)</th>
                                                <th colspan="2">Akım Koord.</th>
                                                <th colspan="2">RCD Testi</th>
                                                <th rowspan="2" style="width:70px">Sonuç</th>
                                                <th rowspan="2" style="width:30px">✕</th>
                                            </tr>
                                            <tr>
                                                <th style="width:50px" title="Açma Eğrisi">Eğri</th>
                                                <th style="width:40px" title="Kutup Sayısı">Ktp</th>
                                                <th style="width:50px" title="Nominal Akım (A)">In(A)</th>
                                                <th style="width:50px" title="Kesme Kapasitesi (kA)">Icu</th>
                                                <th style="width:55px">Faz</th>
                                                <th style="width:55px">N/PEN</th>
                                                <th style="width:55px">PE</th>
                                                <th style="width:55px" title="Tasarım Akımı (A)">Ib</th>
                                                <th style="width:55px" title="Akım Taşıma Kapasitesi (A)">Iz</th>
                                                <th style="width:55px" title="Açma Akımı (mA)">IΔ(mA)</th>
                                                <th style="width:55px" title="Açma Süresi (ms)">tΔ(ms)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($s61rowsdata as $ri => $rrow): ?>
                                                <tr>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][no]"
                                                            value="<?php echo htmlspecialchars($rrow['no_col'] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control form-control-sm p-1"
                                                            name="rows[<?php echo $ri; ?>][linye]"
                                                            value="<?php echo htmlspecialchars($rrow['linye_adi'] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][acma]"
                                                            value="<?php echo htmlspecialchars($rrow['acma_egrisi'] ?? ''); ?>">
                                                    </td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][kutup]"
                                                            value="<?php echo htmlspecialchars($rrow['kutup_sayisi'] ?? ''); ?>">
                                                    </td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][in_a]"
                                                            value="<?php echo htmlspecialchars($rrow['in_a'] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][icu]"
                                                            value="<?php echo htmlspecialchars($rrow['icu'] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][faz]"
                                                            value="<?php echo htmlspecialchars($rrow['faz_kesiti'] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][npen]"
                                                            value="<?php echo htmlspecialchars($rrow['npen_kesiti'] ?? ''); ?>">
                                                    </td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][pe]"
                                                            value="<?php echo htmlspecialchars($rrow['pe_kesiti'] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][ib]"
                                                            value="<?php echo htmlspecialchars($rrow['ib_tasarim'] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][iz]"
                                                            value="<?php echo htmlspecialchars($rrow['iz_kapasite'] ?? ''); ?>">
                                                    </td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][rcd_ia]"
                                                            value="<?php echo htmlspecialchars($rrow['rcd_ia'] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][rcd_ta]"
                                                            value="<?php echo htmlspecialchars($rrow['rcd_ta'] ?? ''); ?>"></td>
                                                    <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                            name="rows[<?php echo $ri; ?>][sonuc]"
                                                            value="<?php echo htmlspecialchars($rrow['sonuc'] ?? ''); ?>"></td>
                                                    <td>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger py-0 px-1 remove-row"><i
                                                                class="fas fa-times"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-success add-row-btn"
                                    data-table="t61_<?php echo $pnl['id']; ?>">
                                    <i class="fas fa-plus"></i> Satır Ekle
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info ms-2"
                                    onclick="fillRandom61(<?php echo $pnl['id']; ?>, '<?php echo htmlspecialchars(addslashes($pnl['panel_name'])); ?>')">
                                    <i class="fas fa-magic"></i> Rastgele (Test Verisi)
                                </button>
                            </div>
                            <div class="p-2">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>
                                    Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- 6.2: Potansiyel Dengeleme (1 kere, tüm panolar birlikte) -->
            <?php
            $s62stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_2_rows WHERE report_id=? ORDER BY id");
            $s62stmt->execute([$report_id]);
            $s62rows = $s62stmt->fetchAll();
            if (empty($s62rows)) {
                $s62rows = [];
                $no = 1;
                foreach ($panels as $pnl) {
                    $s62rows[] = ['no_col' => $no++, 'bolum' => $pnl['panel_name'], 'sonuc' => 'Uygun'];
                }
                if (empty($s62rows)) {
                    $s62rows = [['sonuc' => 'Uygun']];
                }
            }
            ?>
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white fw-bold">6.2. POTANSİYEL DENGELEME İLETKENLERİ KONTROLÜ
                </div>
                <div class="card-body p-0">
                    <form method="POST" action="?report_id=<?php echo $report_id; ?>&section=6">
                        <input type="hidden" name="action" value="save_section6_2">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0 align-middle text-center" id="t62"
                                style="font-size: 11px;">
                                <thead class="table-secondary">
                                    <tr>
                                        <th rowspan="2" style="width:40px">No</th>
                                        <th rowspan="2">Bağlantı Yapılan Bölüm / Ekipman ADI</th>
                                        <th colspan="2">Ana Pot. Den. (PD)</th>
                                        <th colspan="2">Tamamlayıcı Pot. Den. (TPD)</th>
                                        <th rowspan="2" style="width:80px">Sonuç</th>
                                        <th rowspan="2" style="width:40px">✕</th>
                                    </tr>
                                    <tr>
                                        <th style="width:90px">Kesit(mm²)</th>
                                        <th style="width:80px">Süreklilik(Ω)</th>
                                        <th style="width:90px">Kesit(mm²)</th>
                                        <th style="width:80px">Süreklilik(Ω)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($s62rows as $ri => $rrow): ?>
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][no]"
                                                    value="<?php echo htmlspecialchars($rrow['no_col'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm p-1"
                                                    name="rows[<?php echo $ri; ?>][bolum]"
                                                    value="<?php echo htmlspecialchars($rrow['bolum'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][pd_kesiti]"
                                                    value="<?php echo htmlspecialchars($rrow['pd_kesiti'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][pd_sur]"
                                                    value="<?php echo htmlspecialchars($rrow['pd_sureklilik'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][tpd_kesiti]"
                                                    value="<?php echo htmlspecialchars($rrow['tpd_kesiti'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][tpd_sur]"
                                                    value="<?php echo htmlspecialchars($rrow['tpd_sureklilik'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][sonuc]"
                                                    value="<?php echo htmlspecialchars($rrow['sonuc'] ?? ''); ?>"></td>
                                            <td><button type="button"
                                                    class="btn btn-sm btn-outline-danger py-0 px-1 remove-row"><i
                                                        class="fas fa-times"></i></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-2 d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success add-row-btn" data-table="t62"><i
                                    class="fas fa-plus"></i> Satır</button>
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i> 6.2
                                Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 6.3: Zemin İzolasyonu (1 kere) -->
            <?php
            $s63stmt = $pdo->prepare("SELECT * FROM ic_tesisat_section6_3_rows WHERE report_id=? ORDER BY id");
            $s63stmt->execute([$report_id]);
            $s63rows = $s63stmt->fetchAll();
            if (empty($s63rows)) {
                $s63rows = [];
                $no = 1;
                foreach ($panels as $pnl) {
                    $s63rows[] = ['no_col' => $no++, 'hali_yeri' => $pnl['panel_name'], 'sonuc' => 'Uygun'];
                }
                if (empty($s63rows)) {
                    $s63rows = [['sonuc' => 'Uygun']];
                }
            }
            ?>
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white fw-bold">6.3. ZEMİN İZOLASYONUNUN KONTROLÜ</div>
                <div class="card-body p-0">
                    <form method="POST" action="?report_id=<?php echo $report_id; ?>&section=6">
                        <input type="hidden" name="action" value="save_section6_3">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0 align-middle text-center" id="t63"
                                style="font-size: 11px;">
                                <thead class="table-secondary">
                                    <tr>
                                        <th style="width:40px">No</th>
                                        <th>İzolasyon Halısının / Zemin Yalıtımının Yeri</th>
                                        <th style="width:80px">Eni (m)</th>
                                        <th style="width:80px">Boyu (m)</th>
                                        <th style="width:110px">Direnç (kΩ)</th>
                                        <th style="width:150px">Uygunluk Notu</th>
                                        <th style="width:40px">✕</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($s63rows as $ri => $rrow): ?>
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][no]"
                                                    value="<?php echo htmlspecialchars($rrow['no_col'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm p-1"
                                                    name="rows[<?php echo $ri; ?>][hali]"
                                                    value="<?php echo htmlspecialchars($rrow['hali_yeri'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][eni]"
                                                    value="<?php echo htmlspecialchars($rrow['eni'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][boyu]"
                                                    value="<?php echo htmlspecialchars($rrow['boyu'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][direnc]"
                                                    value="<?php echo htmlspecialchars($rrow['direnc'] ?? ''); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm text-center p-0"
                                                    name="rows[<?php echo $ri; ?>][sonuc]"
                                                    value="<?php echo htmlspecialchars($rrow['sonuc'] ?? ''); ?>"></td>
                                            <td><button type="button"
                                                    class="btn btn-sm btn-outline-danger py-0 px-1 remove-row"><i
                                                        class="fas fa-times"></i></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-2 d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success add-row-btn" data-table="t63"><i
                                    class="fas fa-plus"></i> Satır</button>
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i> 6.3
                                Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($section === 'photos' && $current_panel): ?>
            <!-- PHOTOS -->
            <div class="card">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="fas fa-camera me-2"></i>Fotoğraflar –
                    <?php echo htmlspecialchars($current_panel['panel_name']); ?>
                </div>
                <div class="card-body">
                    <?php
                    $phptos = $pdo->prepare("SELECT * FROM ic_tesisat_photos WHERE panel_id=? ORDER BY id");
                    $phptos->execute([$panel_id_param]);
                    $photos = $phptos->fetchAll();
                    $normal_photos = array_filter($photos, fn($ph) => $ph['photo_type'] === 'normal');
                    $termal_photos = array_filter($photos, fn($ph) => $ph['photo_type'] === 'termal');
                    ?>
                    <div class="row">
                        <!-- Normal camera -->
                        <div class="col-md-6">
                            <h6 class="fw-bold"><i class="fas fa-camera me-1"></i> Normal Kamera</h6>
                            <div class="photo-grid" id="normal-grid">
                                <?php foreach ($normal_photos as $ph): ?>
                                    <div class="photo-item border rounded p-1 mb-2 d-inline-block"
                                        data-photo-id="<?php echo $ph['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($ph['file_path']); ?>" class="img-thumbnail"
                                            style="width:120px;height:90px;object-fit:cover;">
                                        <br>
                                        <button class="btn btn-xs btn-danger btn-sm py-0 mt-1 delete-photo"
                                            data-photo-id="<?php echo $ph['id']; ?>">✕ Sil</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2">
                                <label class="form-label small fw-bold">Normal Fotoğraf Yükle</label>
                                <input type="file" class="form-control form-control-sm photo-upload" accept="image/*"
                                    multiple data-panel-id="<?php echo $panel_id_param; ?>" data-photo-type="normal"
                                    data-grid="normal-grid">
                            </div>
                        </div>
                        <!-- Thermal camera -->
                        <div class="col-md-6">
                            <h6 class="fw-bold"><i class="fas fa-temperature-high me-1"></i> Termal Kamera</h6>
                            <div class="photo-grid" id="termal-grid">
                                <?php foreach ($termal_photos as $ph): ?>
                                    <div class="photo-item border rounded p-1 mb-2 d-inline-block"
                                        data-photo-id="<?php echo $ph['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($ph['file_path']); ?>" class="img-thumbnail"
                                            style="width:120px;height:90px;object-fit:cover;">
                                        <br>
                                        <button class="btn btn-xs btn-danger btn-sm py-0 mt-1 delete-photo"
                                            data-photo-id="<?php echo $ph['id']; ?>">✕ Sil</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2">
                                <label class="form-label small fw-bold">Termal Fotoğraf Yükle</label>
                                <input type="file" class="form-control form-control-sm photo-upload" accept="image/*"
                                    multiple data-panel-id="<?php echo $panel_id_param; ?>" data-photo-type="termal"
                                    data-grid="termal-grid">
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="mt-3">
                        <form method="POST" action="?report_id=<?php echo $report_id; ?>&section=photos&panel_id=<?php echo $panel_id_param; ?>">
                            <input type="hidden" name="action" value="save_panel_notes">
                            <input type="hidden" name="panel_id" value="<?php echo $panel_id_param; ?>">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">
                                    <i class="fas fa-temperature-high me-1"></i> Termal Kamera Fotoğraf Numaraları
                                </label>
                                <div class="alert alert-info py-2 small mb-2">
                                    <i class="fas fa-info-circle me-1"></i> Bu panoya ait termal fotoğraf numaralarını virgülle ayırarak giriniz (Örn: <code>450,1411</code>). Bu sayede toplu yükleme ekranında <code>TR000450.JPG</code> veya <code>TR001411.JPG</code> dosyaları bu panoya otomatik dağıtılacaktır.
                                </div>
                                <input type="text" class="form-control border-primary" name="thermal_numbers" 
                                    value="<?php echo htmlspecialchars($current_panel['thermal_numbers'] ?? ''); ?>" 
                                    placeholder="Örn: 450, 1411">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-primary">
                                    <i class="fas fa-sticky-note me-1"></i> Pano Notları
                                </label>
                                <div class="alert alert-secondary py-2 small mb-2">
                                    <i class="fas fa-info-circle me-1"></i> Bu kısma yazılan notlar sadece web panelinde görüntülenir, rapor çıktısında yer almaz.
                                </div>
                                <textarea class="form-control border-primary" name="notes" rows="4" 
                                    placeholder="Bu pano için özel notlarınızı buraya girebilirsiniz..."><?php echo htmlspecialchars($current_panel['notes'] ?? ''); ?></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Bilgileri Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php elseif ($section === 'photos' && !$current_panel): ?>
            <!-- TOPLU FOTOĞRAF YÜKLEME VE DAĞITIM -->
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white fw-bold d-flex align-items-center justify-content-between">
                    <span><i class="fas fa-images me-2"></i> Toplu Fotoğraf Yükleme & Otomatik Dağıtım</span>
                    <span class="badge bg-light text-primary">Yeni</span>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Yükleyeceğiniz fotoğraflar (örn: <code>TR000450.JPG</code>, <code>TR001411.JPG</code>), dosya adlarındaki numaralar panolara tanımlanmış olan <strong>Termal Kamera Fotoğraf Numaraları</strong> ile eşleştirilerek ilgili panolara otomatik olarak dağıtılır.
                    </p>
                    
                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Fotoğraf Türü Belirleme</label>
                            <select id="bulk-type-mode" class="form-select form-select-sm">
                                <option value="auto">Dosya Adından Otomatik Tespit Et (Adında termal, isi, temp vb. geçiyorsa termal)</option>
                                <option value="termal" selected>Tümünü Termal Kamera Fotoğrafı Olarak Yükle</option>
                                <option value="normal">Tümünü Normal Fotoğraf Olarak Yükle</option>
                            </select>
                        </div>
                        <div class="col-md-6 text-md-end pt-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('info-panel-nums').classList.toggle('d-none')">
                                <i class="fas fa-list me-1"></i> Rapor Pano Numaralarını Göster/Gizle
                            </button>
                        </div>
                    </div>

                    <div id="info-panel-nums" class="alert alert-secondary py-2 small d-none">
                        <h6 class="fw-bold mb-1"><i class="fas fa-info-circle me-1"></i> Rapor Kapsamındaki Panolar ve Tanımlı Numaralar:</h6>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($panels as $pnl): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($pnl['panel_name']); ?>:</strong> 
                                    <?php echo !empty($pnl['thermal_numbers']) ? '<span class="badge bg-dark">' . htmlspecialchars($pnl['thermal_numbers']) . '</span>' : '<span class="text-danger">Numara tanımlanmamış</span>'; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Dropzone upload area -->
                    <div id="bulk-upload-dropzone" class="border border-2 border-dashed border-primary rounded p-4 text-center bg-light cursor-pointer position-relative" style="transition: all 0.3s ease; border-style: dashed !important;">
                        <input type="file" id="bulk-upload-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" multiple accept="image/*" style="cursor: pointer;">
                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-2"></i>
                        <h5>Fotoğrafları Sürükleyip Bırakın veya Seçmek İçin Tıklayın</h5>
                        <p class="text-muted small mb-0">Desteklenen formatlar: JPG, JPEG, PNG, WEBP (Çoklu seçim yapabilirsiniz)</p>
                    </div>

                    <!-- Upload Log & Preview Results -->
                    <div id="bulk-upload-results" class="mt-4 d-none">
                        <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-clipboard-list me-1"></i> Yükleme ve Dağıtım Sonuçları</span>
                            <span class="badge bg-primary" id="bulk-total-badge">0 / 0</span>
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-hover align-middle" id="results-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 80px;">Önizleme</th>
                                        <th>Dosya Adı</th>
                                        <th>Ayıklanan No</th>
                                        <th>Durum</th>
                                        <th>Eşleşen Pano</th>
                                        <th>Yöntem</th>
                                        <th>Tür</th>
                                    </tr>
                                </thead>
                                <tbody id="results-tbody">
                                    <!-- Dynamic rows will go here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h5 mb-3 fw-bold"><i class="fas fa-th me-2"></i>Mevcut Pano Fotoğrafları</div>

            <!-- Show all panels thumbnails -->
            <div class="row g-3">
                <?php foreach ($panels as $pnl):
                    $allph = $pdo->prepare("SELECT * FROM ic_tesisat_photos WHERE panel_id=? ORDER BY photo_type, id");
                    $allph->execute([$pnl['id']]);
                    $pnlphotos = $allph->fetchAll();
                    ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-secondary text-white fw-bold small">
                                <?php echo htmlspecialchars($pnl['panel_name']); ?>
                                <a href="?report_id=<?php echo $report_id; ?>&section=photos&panel_id=<?php echo $pnl['id']; ?>"
                                    class="btn btn-xs btn-light btn-sm float-end py-0">Düzenle</a>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($pnlphotos as $ph): ?>
                                        <img src="<?php echo htmlspecialchars($ph['file_path']); ?>"
                                            style="width:60px;height:50px;object-fit:cover;" class="rounded border"
                                            title="<?php echo $ph['photo_type']; ?>">
                                    <?php endforeach; ?>
                                    <?php if (empty($pnlphotos)): ?>
                                        <span class="text-muted small">Fotoğraf yok</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div><!-- /col-md-9 -->
</div><!-- /row -->

<script>
    // Dynamic row add for tables
    document.querySelectorAll('.add-row-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tableId = this.dataset.table;
            var table = document.getElementById(tableId);
            var tbody = table.querySelector('tbody');
            var existingRows = tbody.querySelectorAll('tr');
            var newRowIndex = existingRows.length;
            var firstRow = existingRows[existingRows.length - 1];
            if (!firstRow) return;
            var newRow = firstRow.cloneNode(true);
            // reset input values & update name indices
            newRow.querySelectorAll('input').forEach(function (inp) {
                if (inp.name.indexOf('[sonuc]') !== -1) {
                    inp.value = 'Uygun';
                } else {
                    inp.value = '';
                }
                inp.name = inp.name.replace(/\[\d+\]/, '[' + newRowIndex + ']');
            });
            // Ensure delete button is present in the new row
            if (!newRow.querySelector('.remove-row')) {
                var delTd = newRow.querySelector('td:last-child');
                delTd.innerHTML = '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 remove-row"><i class="fas fa-times"></i></button>';
            }
            tbody.appendChild(newRow);
        });
    });

    // Remove row
    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row')) {
            var row = e.target.closest('tr');
            var tbody = row.parentElement;
            if (tbody.querySelectorAll('tr').length > 1) {
                row.remove();
            } else {
                alert('En az bir satır kalmalıdır.');
            }
        }
    });

    // Return exact float format
    function fillRandom61(panelId, panelName) {
        var table = document.getElementById('t61_' + panelId);
        if (!table) return;
        var form = table.closest('form');
        
        var randFloat = function(base, variance, decimals) {
            return (base + (Math.random() * variance * 2 - variance)).toFixed(decimals);
        };
        var randChoice = function(arr) {
            return arr[Math.floor(Math.random() * arr.length)];
        };
        
        // Header fields
        var inputs = {
            'zx': randFloat(2.0, 0.5, 2),
            'zln': randFloat(2.0, 0.5, 2),
            'voltage_ff': randFloat(230, 5, 1),
            'voltage_ln': randFloat(230, 5, 1),
            'voltage_npe': randFloat(1.5, 0.5, 2),
            'short_circuit_3ph': randFloat(0.9, 0.25, 2),
            'dkd_type': '-',
            'dkd_current': '-'
        };
        
        for (var name in inputs) {
            var el = form.querySelector('input[name="' + name + '"]');
            if (el) el.value = inputs[name];
        }
        
        // Table rows
        var tbody = table.querySelector('tbody');
        var rows = tbody.querySelectorAll('tr');
        rows.forEach(function(tr, idx) {
            var f = function(suffix, val) {
                var el = tr.querySelector('input[name$="[' + suffix + ']"]');
                if (el) el.value = val;
            };
            
            var noEl = tr.querySelector('input[name$="[no]"]');
            if (noEl && noEl.value === '') noEl.value = idx + 1;
            
            f('linye', panelName);
            f('acma', 'C');
            f('kutup', '4');
            f('in_a', '630');
            f('icu', '6kA');
            f('faz', '1*10');
            f('npen', '1*10');
            f('pe', '1*10');
            f('ib', '50');
            f('iz', '48');
            f('rcd_ia', randChoice(['24', '27', '30']));
            f('rcd_ta', randFloat(27, 3, 2));
            f('sonuc', 'Uygun');
        });
    }

    // Photo upload (AJAX)
    document.querySelectorAll('.photo-upload').forEach(function (input) {
        input.addEventListener('change', function () {
            var files = this.files;
            var panelId = this.dataset.panelId;
            var ptype = this.dataset.photoType;
            var gridId = this.dataset.grid;
            var grid = document.getElementById(gridId);

            Array.from(files).forEach(function (file) {
                var fd = new FormData();
                fd.append('action', 'upload_photo');
                fd.append('panel_id', panelId);
                fd.append('photo_type', ptype);
                fd.append('photo', file);

                fetch(window.location.href, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            var div = document.createElement('div');
                            div.className = 'photo-item border rounded p-1 mb-2 d-inline-block';
                            div.dataset.photoId = data.photo_id;
                            div.innerHTML = '<img src="' + data.path + '" class="img-thumbnail" style="width:120px;height:90px;object-fit:cover;">' +
                                '<br><button class="btn btn-xs btn-danger btn-sm py-0 mt-1 delete-photo" data-photo-id="' + data.photo_id + '">✕ Sil</button>';
                            grid.appendChild(div);
                        } else {
                            alert('Fotoğraf yüklenemedi.');
                        }
                    });
            });
            this.value = '';
        });
    });

    // Photo delete (AJAX)
    document.addEventListener('click', function (e) {
        if (e.target.closest('.delete-photo')) {
            var btn = e.target.closest('.delete-photo');
            var photoId = btn.dataset.photoId;
            if (!confirm('Fotoğrafı silmek istiyor musunuz?')) return;

            var fd = new FormData();
            fd.append('action', 'delete_photo');
            fd.append('photo_id', photoId);

            fetch(window.location.href, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        var item = document.querySelector('[data-photo-id="' + photoId + '"].photo-item');
                        if (item) item.remove();
                    }
                });
        }
    });

    // Bulk Photo Upload
    var bulkInput = document.getElementById('bulk-upload-input');
    var bulkDropzone = document.getElementById('bulk-upload-dropzone');
    var bulkResults = document.getElementById('bulk-upload-results');
    var resultsTbody = document.getElementById('results-tbody');
    var bulkTotalBadge = document.getElementById('bulk-total-badge');

    if (bulkInput && bulkDropzone) {
        // Drag over states
        bulkDropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('bg-secondary', 'text-white');
            this.style.borderColor = '#0d6efd';
        });

        bulkDropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('bg-secondary', 'text-white');
            this.style.borderColor = '';
        });

        bulkDropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('bg-secondary', 'text-white');
            this.style.borderColor = '';
            var files = e.dataTransfer.files;
            if (files.length > 0) {
                handleBulkFiles(files);
            }
        });

        bulkInput.addEventListener('change', function() {
            var files = this.files;
            if (files.length > 0) {
                handleBulkFiles(files);
            }
        });

        function handleBulkFiles(files) {
            bulkResults.classList.remove('d-none');
            var totalFiles = files.length;
            var completedCount = 0;
            bulkTotalBadge.textContent = '0 / ' + totalFiles;
            bulkTotalBadge.className = 'badge bg-warning text-dark';

            Array.from(files).forEach(function(file, index) {
                var rowId = 'bulk-row-' + Date.now() + '-' + index;
                
                var tr = document.createElement('tr');
                tr.id = rowId;
                tr.innerHTML = `
                    <td class="text-center"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></td>
                    <td class="text-truncate fw-bold" style="max-width: 150px;" title="${file.name}">${file.name}</td>
                    <td class="text-center">-</td>
                    <td><span class="badge bg-secondary">Bekliyor...</span></td>
                    <td class="fw-bold">-</td>
                    <td>-</td>
                    <td>-</td>
                `;
                resultsTbody.insertBefore(tr, resultsTbody.firstChild);

                var fd = new FormData();
                fd.append('action', 'bulk_upload_photo');
                fd.append('type_mode', document.getElementById('bulk-type-mode').value);
                fd.append('photo', file);

                fetch(window.location.href, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        completedCount++;
                        bulkTotalBadge.textContent = completedCount + ' / ' + totalFiles;
                        
                        var targetRow = document.getElementById(rowId);
                        if (!targetRow) return;

                        if (data.success) {
                            targetRow.classList.add('table-success');
                            targetRow.innerHTML = `
                                <td><img src="${data.path}" class="rounded border" style="width: 50px; height: 40px; object-fit: cover;"></td>
                                <td class="text-truncate fw-bold" style="max-width: 150px;" title="${file.name}">${file.name}</td>
                                <td class="text-center"><span class="badge bg-dark text-white">${data.extracted_no}</span></td>
                                <td><span class="badge bg-success"><i class="fas fa-check me-1"></i> Eşleşti</span></td>
                                <td class="fw-bold text-success">${data.panel_name}</td>
                                <td class="small text-muted">${data.reason}</td>
                                <td><span class="badge bg-info text-dark">${data.photo_type}</span></td>
                            `;
                        } else {
                            targetRow.classList.add('table-danger');
                            targetRow.innerHTML = `
                                <td class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i></td>
                                <td class="text-truncate text-muted" style="max-width: 150px;" title="${file.name}">${file.name}</td>
                                <td class="text-center"><span class="badge bg-secondary">${data.extracted_no}</span></td>
                                <td><span class="badge bg-danger"><i class="fas fa-times me-1"></i> Başarısız</span></td>
                                <td class="text-danger fw-bold">Eşleşmedi</td>
                                <td class="small text-danger">${data.error}</td>
                                <td>-</td>
                            `;
                        }

                        if (completedCount === totalFiles) {
                            bulkTotalBadge.className = 'badge bg-success text-white';
                            setTimeout(function() {
                                if (!document.getElementById('refresh-btn')) {
                                    var header = document.querySelector('#bulk-upload-results h6');
                                    var refreshBtn = document.createElement('button');
                                    refreshBtn.id = 'refresh-btn';
                                    refreshBtn.className = 'btn btn-xs btn-success btn-sm ms-2 py-0 px-2';
                                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Sayfayı Yenile';
                                    refreshBtn.onclick = function() { location.reload(); };
                                    header.appendChild(refreshBtn);
                                }
                            }, 500);
                        }
                    })
                    .catch(err => {
                        completedCount++;
                        bulkTotalBadge.textContent = completedCount + ' / ' + totalFiles;
                        var targetRow = document.getElementById(rowId);
                        if (targetRow) {
                            targetRow.classList.add('table-danger');
                            targetRow.innerHTML = `
                                <td class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i></td>
                                <td class="text-truncate text-muted" style="max-width: 150px;">${file.name}</td>
                                <td class="text-center">-</td>
                                <td><span class="badge bg-danger">Ağ Hatası</span></td>
                                <td class="text-danger fw-bold">Hata</td>
                                <td class="small text-danger">Sunucu ile iletişim kurulamadı.</td>
                                <td>-</td>
                            `;
                        }
                        if (completedCount === totalFiles) {
                            bulkTotalBadge.className = 'badge bg-success text-white';
                        }
                    });
            });
            bulkInput.value = '';
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>