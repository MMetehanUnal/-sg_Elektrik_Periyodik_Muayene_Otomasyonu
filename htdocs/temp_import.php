<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Veri Aktarım Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .import-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: none;
            margin-top: 50px;
        }
        .brand-gradient {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            color: #ffffff;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }
        .stat-badge {
            font-size: 1.1rem;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card import-card">
                <div class="card-header brand-gradient p-4 text-center">
                    <h3 class="mb-0"><i class="fas fa-file-excel me-2"></i> Excel Veri Aktarım Paneli</h3>
                    <p class="mb-0 mt-2 opacity-75">Sistem veritabanına otomatik eşleştirme ve yükleme aracı</p>
                </div>
                <div class="card-body p-4">
                    <?php
                    error_reporting(E_ALL);
                    ini_set('display_errors', 1);

                    require_once 'includes/db.php';

                    if (isset($_POST['run_import']) && isset($_FILES['excel_file'])) {
                        $uploaded_file = $_FILES['excel_file']['tmp_name'];
                        if (is_uploaded_file($uploaded_file)) {
                            processImport($uploaded_file, $pdo);
                        } else {
                            echo '<div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i> Hata: Dosya yüklenemedi!
                                  </div>';
                        }
                    } else {
                        echo '<div class="text-center py-4">
                                <p class="lead">Lütfen bilgisayarınızdaki Excel dosyasını seçin ve aktarımı başlatın.</p>
                                <div class="alert alert-warning text-start" role="alert">
                                    <i class="fas fa-info-circle me-2"></i> Bu işlem, Excel dosyasındaki tüm verileri okuyarak kurumları ve raporları sistem veritabanına yükleyecektir. Aynı verilerin mükerrer (çift) eklenmesini engellemek için kontrol mekanizması aktiftir.
                                </div>
                                <form method="POST" enctype="multipart/form-data" class="mt-4">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Excel Dosyası Seçin (.xlsx)</label>
                                        <input type="file" name="excel_file" class="form-control form-control-lg mx-auto" style="max-width: 450px;" accept=".xlsx" required>
                                    </div>
                                    <button type="submit" name="run_import" class="btn btn-primary btn-lg px-5 py-3 shadow">
                                        <i class="fas fa-upload me-2"></i> Yükle ve Aktarımı Başlat
                                    </button>
                                </form>
                              </div>';
                    }

                    function processImport($excel_path, $pdo) {
                        function parseDateToYmd($val) {
                            $val = trim($val);
                            if (empty($val)) return null;
                            if (is_numeric($val) && $val > 30000 && $val < 60000) {
                                $utc_days = $val - 25569;
                                $utc_value = $utc_days * 86400;
                                return date('Y-m-d', $utc_value);
                            }
                            if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $val, $m)) {
                                return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                            }
                            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                                return $val;
                            }
                            return null;
                        }

                        function getCityCode($mintika) {
                            $mintika = mb_strtoupper($mintika, 'UTF-8');
                            if (strpos($mintika, 'KARATAY') !== false) return '42';
                            if (strpos($mintika, 'MERAM') !== false) return '42';
                            if (strpos($mintika, 'SEL') !== false) return '42';
                            if (strpos($mintika, 'AK') !== false) return '42';
                            if (strpos($mintika, 'BEY') !== false) return '42';
                            if (strpos($mintika, 'UMRA') !== false) return '42';
                            if (strpos($mintika, 'ERE') !== false) return '42';
                            if (strpos($mintika, 'ILGIN') !== false) return '42';
                            if (strpos($mintika, 'C') !== false && strpos($mintika, 'HAN') !== false) return '42';
                            if (strpos($mintika, 'SEYD') !== false) return '42';
                            if (strpos($mintika, 'KARAMAN') !== false) return '70';
                            if (strpos($mintika, 'ERMENEK') !== false) return '70';
                            if (strpos($mintika, 'AKSARAY') !== false) return '68';
                            if (strpos($mintika, 'N') !== false && (strpos($mintika, 'DE') !== false || strpos($mintika, 'GDE') !== false)) return '51';
                            return '42';
                        }

                        function getPressureVal($press) {
                            $press = trim($press);
                            if (empty($press)) return 10;
                            preg_match('/^\d+/', $press, $m);
                            return !empty($m) ? (int)$m[0] : 10;
                        }

                        function getEquipmentStatus($val) {
                            $val = mb_strtolower(trim($val), 'UTF-8');
                            if ($val === 'var' || $val === 'evet') return 'Var';
                            if ($val === 'yok' || $val === 'hayır') return 'Yok';
                            return 'Yok';
                        }

                        if (!class_exists('ZipArchive')) {
                            echo '<div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i> Sunucuda <code>ZipArchive</code> sınıfı bulunamadı. Lütfen PHP zip uzantısını aktif edin.
                                  </div>';
                            return;
                        }

                        $zip = new ZipArchive();
                        if ($zip->open($excel_path) !== TRUE) {
                            echo '<div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i> Excel dosyası açılamadı. Arşiv bozuk olabilir.
                                  </div>';
                            return;
                        }

                        $shared_strings = [];
                        $ss_xml = $zip->getFromName('xl/sharedStrings.xml');
                        if ($ss_xml) {
                            $xml = simplexml_load_string($ss_xml);
                            foreach ($xml->si as $si) {
                                if (isset($si->r)) {
                                    $text = '';
                                    foreach ($si->r as $r) {
                                        $text .= (string)$r->t;
                                    }
                                    $shared_strings[] = $text;
                                } else {
                                    $shared_strings[] = (string)$si->t;
                                }
                            }
                        }

                        $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
                        if (!$sheet_xml) {
                            echo '<div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i> Excel tablosu okunamadı (sheet1.xml bulunamadı).
                                  </div>';
                            return;
                        }

                        $xml = simplexml_load_string($sheet_xml);
                        $xml->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

                        $rows_imported = 0;
                        $institutions_added = 0;
                        $jenarator_added = 0;
                        $genlesme_added = 0;
                        $boyler_added = 0;
                        $kamera_added = 0;

                        $pdo->beginTransaction();

                        try {
                            // Find or create authorized person dynamically to prevent FK constraints issues
                            $stmt_auth = $pdo->query("SELECT id FROM authorized_persons LIMIT 1");
                            $auth_row = $stmt_auth->fetch();
                            if ($auth_row) {
                                $authorized_person_id = $auth_row['id'];
                            } else {
                                $stmt_ins_auth = $pdo->prepare(
                                    "INSERT INTO authorized_persons (adi_soyadi, meslegi, kayit_no, diploma_no, oda_sicil_no)
                                     VALUES (?, ?, ?, ?, ?)"
                                );
                                $stmt_ins_auth->execute([
                                    'Fatih ÖKSÜZ',
                                    'Makine Mühendisi',
                                    'K12345678',
                                    'D-123456',
                                    'O-9999'
                                ]);
                                $authorized_person_id = $pdo->lastInsertId();
                            }

                            // Also resolve a default user_id from users table
                            $stmt_user = $pdo->query("SELECT id FROM users LIMIT 1");
                            $user_row = $stmt_user->fetch();
                            $db_user_id = $user_row ? $user_row['id'] : 1;

                            foreach ($xml->sheetData->row as $row) {
                                $row_num = (int)$row['r'];
                                if ($row_num < 4) continue; // skip headers
                                
                                $cells = [];
                                foreach ($row->c as $c) {
                                    $cell_ref = (string)$c['r'];
                                    preg_match('/^[A-Z]+/', $cell_ref, $matches);
                                    $col_letter = $matches[0];
                                    
                                    $val = '';
                                    if (isset($c->v)) {
                                        $t = (string)$c['t'];
                                        if ($t === 's') {
                                            $string_idx = (int)$c->v;
                                            $val = isset($shared_strings[$string_idx]) ? $shared_strings[$string_idx] : '';
                                        } else {
                                            $val = (string)$c->v;
                                        }
                                    }
                                    $cells[$col_letter] = trim($val);
                                }
                                
                                if (empty($cells['D'])) continue; // skip empty rows
                                
                                $mintika = $cells['B'] ?? '';
                                $firma_adi = $cells['D'] ?? '';
                                $yurt_muduru = $cells['F'] ?? '';
                                $adresi = $cells['I'] ?? '';
                                $sgk_sicil_no = $cells['K'] ?? '';
                                
                                $rapor_no = $cells['AT'] ?? '';
                                $report_date = parseDateToYmd($cells['AU'] ?? '');
                                $control_date = parseDateToYmd($cells['AV'] ?? '');
                                $next_control_date = parseDateToYmd($cells['AW'] ?? '');
                                
                                // 1. Manage Institution
                                $stmt = $pdo->prepare("SELECT id, kurum_kodu, il_kodu FROM institutions WHERE firma_adi = ?");
                                $stmt->execute([$firma_adi]);
                                $exist = $stmt->fetch();
                                
                                if ($exist) {
                                    $kurum_id = $exist['id'];
                                    $il_kodu = $exist['il_kodu'];
                                    $kurum_kodu = $exist['kurum_kodu'];
                                } else {
                                    $il_kodu = getCityCode($mintika);
                                    
                                    $stmt_max = $pdo->prepare("SELECT MAX(CAST(kurum_kodu AS UNSIGNED)) AS max_kodu FROM institutions WHERE il_kodu = ?");
                                    $stmt_max->execute([$il_kodu]);
                                    $row_max = $stmt_max->fetch();
                                    $next_kodu = ($row_max && $row_max['max_kodu']) ? (int)$row_max['max_kodu'] + 1 : 1;
                                    $kurum_kodu = str_pad($next_kodu, 3, '0', STR_PAD_LEFT);
                                    
                                    $stmt_ins = $pdo->prepare(
                                        "INSERT INTO institutions (user_id, firma_adi, adresi, sgk_sicil_no, il_kodu, kurum_kodu, report_date)
                                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                                    );
                                    $stmt_ins->execute([
                                        $db_user_id,
                                        $firma_adi,
                                        $adresi,
                                        $sgk_sicil_no,
                                        $il_kodu,
                                        $kurum_kodu,
                                        $report_date
                                    ]);
                                    $kurum_id = $pdo->lastInsertId();
                                    $institutions_added++;
                                }
                                
                                $start_date = $control_date ? $control_date . ' 10:00:00' : null;
                                $end_date = $control_date ? $control_date . ' 11:00:00' : null;
                                
                                // 2. Generator
                                $gen_brand_model = $cells['M'] ?? '';
                                if (!empty($gen_brand_model)) {
                                    $gen_rep_no = sprintf("%s-%s-jen-%s", $il_kodu, $kurum_kodu, $rapor_no);
                                    $stmt_dup = $pdo->prepare("SELECT id FROM jenarator_reports WHERE report_no = ? AND kurum_id = ?");
                                    $stmt_dup->execute([$gen_rep_no, $kurum_id]);
                                    if (!$stmt_dup->fetch()) {
                                        $gen_serial = $cells['N'] ?? '';
                                        $gen_power = $cells['O'] ?? '';
                                        $gen_prod_year = $cells['P'] ?? '';
                                        
                                        $inspection_results = '{"q1":"Evet","q2":"Evet","q3":"Evet","q4":"Evet","q5":"Evet","q6":"Evet","q7":"Evet","q8":"Evet","q9":"Evet","q10":"Evet","q11":"Evet","q12":"Evet"}';
                                        $defects = 'Periyodik bakım ve kontrolleri düzenli olarak yapılmalı ve kayıtları muhafaza edilmelidir.';
                                        $result_text = "Yukarıda teknik özellikleri verilen ve kontrol tarihinde durumu belirtilen JENARATÖR 'ün kontrolü yapılmış olup bir sonraki kontrol tarihine kadar kullanılması İŞÇİ SAĞLIĞI ve İŞ GÜVENLİĞİ açısından risk taşımamaktadır. İşletmesi UYGUNDUR.";
                                        
                                        $stmt_gen = $pdo->prepare(
                                            "INSERT INTO jenarator_reports (kurum_id, report_no, report_date, start_date, end_date, next_control_date, control_reason, brand_model, serial_no, capacity, production_year, inspection_results, defects, result_text, result, authorized_person_id)
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                                        );
                                        $stmt_gen->execute([
                                            $kurum_id, $gen_rep_no, $report_date, $start_date, $end_date, $next_control_date,
                                            'Periyodik Kontrol', $gen_brand_model, $gen_serial, $gen_power, $gen_prod_year,
                                            $inspection_results, $defects, $result_text, 'UYGUNDUR', $authorized_person_id
                                        ]);
                                        $jenarator_added++;
                                    }
                                }
                                
                                // 3. Genleşme Tankı
                                $gt_brand = $cells['Q'] ?? '';
                                if (!empty($gt_brand)) {
                                    $gt_rep_no = sprintf("%s-%s-gtank-%s", $il_kodu, $kurum_kodu, $rapor_no);
                                    $stmt_dup = $pdo->prepare("SELECT id FROM genlesme_tanki_reports WHERE report_no = ? AND kurum_id = ?");
                                    $stmt_dup->execute([$gt_rep_no, $kurum_id]);
                                    if (!$stmt_dup->fetch()) {
                                        $gt_model = $cells['R'] ?? '';
                                        $gt_prod_year = $cells['S'] ?? '';
                                        $gt_serial = $cells['T'] ?? '';
                                        $gt_oper_press = $cells['U'] ?? '';
                                        $gt_capacity = $cells['V'] ?? '';
                                        
                                        $d1 = getEquipmentStatus($cells['W'] ?? '');
                                        $d2 = getEquipmentStatus($cells['X'] ?? '');
                                        $d3 = getEquipmentStatus($cells['Y'] ?? '');
                                        $d4 = getEquipmentStatus($cells['Z'] ?? '');
                                        $d5 = getEquipmentStatus($cells['AA'] ?? '');
                                        $d6 = getEquipmentStatus($cells['AB'] ?? '');
                                        $d7 = getEquipmentStatus($cells['AC'] ?? '');
                                        
                                        $tank_donanimlari = json_encode([
                                            "d1" => ["status" => $d1, "amount" => "1"], "d2" => ["status" => $d2, "amount" => "1"],
                                            "d3" => ["status" => $d3, "amount" => "1"], "d4" => ["status" => $d4, "amount" => "1"],
                                            "d5" => ["status" => $d5, "amount" => "1"], "d6" => ["status" => $d6, "amount" => "1"],
                                            "d7" => ["status" => $d7, "amount" => "1"]
                                        ]);
                                        
                                        $op_press_val = getPressureVal($gt_oper_press);
                                        $test_press = $op_press_val * 1.5;
                                        
                                        $inspection_results = '{"q1":"UYGUN","q2":"UYGUN","q3":"-","q4":"UYGUN","q5":"UYGUN","q6":"UYGUN","q7":"-","q8":"UYGUN","q9":"UYGUN","q10":"UYGUN","q11":"UYGUN"}';
                                        $hydrostatic_test = "Genleşme Tankının bütün bağlantıları kapatıldı. Tank 20 °C su ile {$test_press} Bar basınç altında 1/2 saat bekletildi. Genleşme Tankında deformasyon ve sızıntıların olmadığı görüldü.";
                                        $result_text = "Yukarıda teknik özellikleri verilen ve kontrol tarihinde durumu belirtilen Genleşme Tankının testi {$test_press} Bar basınç altında yapılmış olup bir sonraki kontrol tarihine kadar kullanılmasında İŞÇİ SAĞLIĞI ve İŞ GÜVENLİĞİ açısından risk taşımamaktadır. İşletmesi UYGUNDUR.";
                                        
                                        $stmt_gt = $pdo->prepare(
                                            "INSERT INTO genlesme_tanki_reports (kurum_id, report_no, report_date, start_date, end_date, next_control_date, control_reason, mevzuat, brand, serial_no, model, operating_pressure, production_year, test_pressure, capacity, tank_donanimlari, inspection_results, hydrostatic_test, result_text, result, authorized_person_id)
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                                        );
                                        $stmt_gt->execute([
                                            $kurum_id, $gt_rep_no, $report_date, $start_date, $end_date, $next_control_date,
                                            'Periyodik Kontrol', 'İş Ekipmanlarının Kullanımında Sağlık ve Güv. Şartları Yön. Sayısı: 28628 / 25.04.2013, TS EN 13445-5',
                                            $gt_brand, $gt_serial, $gt_model, $gt_oper_press, $gt_prod_year, $test_press, $gt_capacity,
                                            $tank_donanimlari, $inspection_results, $hydrostatic_test, $result_text, 'UYGUNDUR', $authorized_person_id
                                        ]);
                                        $genlesme_added++;
                                    }
                                }
                                
                                // 4. Boyler Tankı
                                $bt_brand = $cells['AD'] ?? '';
                                if (!empty($bt_brand)) {
                                    $bt_rep_no = sprintf("%s-%s-btank-%s", $il_kodu, $kurum_kodu, $rapor_no);
                                    $stmt_dup = $pdo->prepare("SELECT id FROM boyler_tanki_reports WHERE report_no = ? AND kurum_id = ?");
                                    $stmt_dup->execute([$bt_rep_no, $kurum_id]);
                                    if (!$stmt_dup->fetch()) {
                                        $bt_model = $cells['AE'] ?? '';
                                        $bt_prod_year = $cells['AF'] ?? '';
                                        $bt_serial = $cells['AG'] ?? '';
                                        $bt_oper_press = $cells['AH'] ?? '';
                                        $bt_capacity = $cells['AI'] ?? '';
                                        
                                        $d1 = getEquipmentStatus($cells['AJ'] ?? '');
                                        $d2 = getEquipmentStatus($cells['AK'] ?? '');
                                        $d3 = getEquipmentStatus($cells['AL'] ?? '');
                                        $d4 = getEquipmentStatus($cells['AM'] ?? '');
                                        $d5 = getEquipmentStatus($cells['AN'] ?? '');
                                        $d6 = getEquipmentStatus($cells['AO'] ?? '');
                                        $d7 = getEquipmentStatus($cells['AP'] ?? '');
                                        
                                        $tank_donanimlari = json_encode([
                                            "d1" => ["status" => $d1, "amount" => "1"], "d2" => ["status" => $d2, "amount" => "1"],
                                            "d3" => ["status" => $d3, "amount" => "1"], "d4" => ["status" => $d4, "amount" => "1"],
                                            "d5" => ["status" => $d5, "amount" => "1"], "d6" => ["status" => $d6, "amount" => "1"],
                                            "d7" => ["status" => $d7, "amount" => "1"]
                                        ]);
                                        
                                        $op_press_val = getPressureVal($bt_oper_press);
                                        $test_press = $op_press_val * 1.5;
                                        
                                        $inspection_results = '{"q1":"UYGUN","q2":"UYGUN","q3":"-","q4":"UYGUN","q5":"UYGUN","q6":"UYGUN","q7":"-","q8":"UYGUN","q9":"UYGUN","q10":"UYGUN","q11":"UYGUN"}';
                                        $hydrostatic_test = "Boyler Tankının bütün bağlantıları kapatıldı. Tank 20 °C su ile {$test_press} Bar basınç altında 1/2 saat bekletildi. Boyler Tankında deformasyon ve sızıntıların olmadığı görüldü.";
                                        $result_text = "Yukarıda teknik özellikleri verilen ve kontrol tarihinde durumu belirtilen Boyler Tankının testi {$test_press} Bar basınç altında yapılmış olup bir sonraki kontrol tarihine kadar kullanılmasında İŞÇİ SAĞLIĞI ve İŞ GÜVENLİĞİ açısından risk taşımamaktadır. İşletmesi UYGUNDUR.";
                                        
                                        $stmt_bt = $pdo->prepare(
                                            "INSERT INTO boyler_tanki_reports (kurum_id, report_no, report_date, start_date, end_date, next_control_date, control_reason, mevzuat, brand, serial_no, model, operating_pressure, production_year, test_pressure, capacity, tank_donanimlari, inspection_results, hydrostatic_test, result_text, result, authorized_person_id)
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                                        );
                                        $stmt_bt->execute([
                                            $kurum_id, $bt_rep_no, $report_date, $start_date, $end_date, $next_control_date,
                                            'Periyodik Kontrol', 'İş Ekipmanlarının Kullanımında Sağlık ve Güv. Şartları Yön. Sayısı: 28628 / 25.04.2013, TS EN 13445-5',
                                            $bt_brand, $bt_serial, $bt_model, $bt_oper_press, $bt_prod_year, $test_press, $bt_capacity,
                                            $tank_donanimlari, $inspection_results, $hydrostatic_test, $result_text, 'UYGUNDUR', $authorized_person_id
                                        ]);
                                        $boyler_added++;
                                    }
                                }
                                
                                // 5. Kamera Bakım
                                $cam_brand_model = $cells['AQ'] ?? '';
                                if (!empty($cam_brand_model)) {
                                    $cam_rep_no = sprintf("%s-%s-kmr-%s", $il_kodu, $kurum_kodu, $rapor_no);
                                    $stmt_dup = $pdo->prepare("SELECT id FROM kamera_bakim_reports WHERE report_no = ? AND kurum_id = ?");
                                    $stmt_dup->execute([$cam_rep_no, $kurum_id]);
                                    if (!$stmt_dup->fetch()) {
                                        $hdd_cap = $cells['AR'] ?? '';
                                        $rec_dur = $cells['AS'] ?? '';
                                        
                                        $report_text = "Yukarıda adresi verilen kurumun güvenlik sistemleri {$cam_brand_model} analog dvr kayıt cihazına {$hdd_cap} kapasiteli harddisk takılı olup bu cihazın kontrolleri yapılmış ve cihaz {$rec_dur} süreli 7/24 kayıt için aktif durumdadır.";
                                        
                                        $stmt_cam = $pdo->prepare(
                                            "INSERT INTO kamera_bakim_reports (kurum_id, report_no, report_date, start_date, end_date, next_control_date, control_reason, yurt_yoneticisi, report_text, result, authorized_person_id)
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                                        );
                                        $stmt_cam->execute([
                                            $kurum_id, $cam_rep_no, $report_date, $start_date, $end_date, $next_control_date,
                                            'Periyodik Kontrol', $yurt_muduru, $report_text, 'UYGUNDUR', $authorized_person_id
                                        ]);
                                        $kamera_added++;
                                    }
                                }
                                
                                $rows_imported++;
                            }
                            
                            $pdo->commit();
                            
                            echo '<div class="alert alert-success" role="alert">
                                    <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i> Aktarım Başarıyla Tamamlandı!</h4>
                                    <p class="mb-0">Tüm veriler veritabanına sorunsuz bir şekilde yüklendi.</p>
                                  </div>';
                            
                            echo '<div class="mt-4">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Aktarılan Veri Tipi</th>
                                                <th class="text-center">Sayı</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Çözümlenen Toplam Satır</td>
                                                <td class="text-center fw-bold text-primary">' . $rows_imported . '</td>
                                            </tr>
                                            <tr>
                                                <td>Yeni Eklenen Kurumlar</td>
                                                <td class="text-center fw-bold text-success">' . $institutions_added . '</td>
                                            </tr>
                                            <tr>
                                                <td>Jeneratör Raporları</td>
                                                <td class="text-center fw-bold">' . $jenarator_added . '</td>
                                            </tr>
                                            <tr>
                                                <td>Genleşme Tankı Raporları</td>
                                                <td class="text-center fw-bold">' . $genlesme_added . '</td>
                                            </tr>
                                            <tr>
                                                <td>Boyler Tankı Raporları</td>
                                                <td class="text-center fw-bold">' . $boyler_added . '</td>
                                            </tr>
                                            <tr>
                                                <td>Kamera Bakım Raporları</td>
                                                <td class="text-center fw-bold">' . $kamera_added . '</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                  </div>';
                            
                            echo '<div class="alert alert-danger mt-3" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i> <strong>Güvenlik Uyarısı:</strong> Veritabanı aktarımı tamamlanmıştır. Güvenlik açığı oluşmaması için lütfen sunucudaki <code>temp_import.php</code> dosyasını silin!
                                  </div>';
                            
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            echo '<div class="alert alert-danger" role="alert">
                                    <h4 class="alert-heading"><i class="fas fa-times-circle me-2"></i> Hata Oluştu ve İşlem Geri Alındı!</h4>
                                    <p class="mb-0">Ayrıntı: ' . htmlspecialchars($e->getMessage()) . '</p>
                                  </div>';
                        }
                    }
                    ?>
                </div>
                <div class="card-footer bg-light p-3 text-center text-muted border-0">
                    &copy; 2026 Otomasyon Sistemi
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
