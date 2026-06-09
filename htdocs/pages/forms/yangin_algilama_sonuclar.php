<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$report_id = isset($_GET['report_id']) ? cleanInput($_GET['report_id']) : null;
if (!$report_id) {
    die("Geçersiz Rapor ID");
}

$stmt = $pdo->prepare("SELECT * FROM fire_detection_reports WHERE id = ?");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    die("Rapor bulunamadı");
}

$results = $report['inspection_results'] ? json_decode($report['inspection_results'], true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_results = $_POST['q'] ?? [];
    $json_results = json_encode($new_results);

    $stmt = $pdo->prepare("UPDATE fire_detection_reports SET inspection_results = ? WHERE id = ?");
    $stmt->execute([$json_results, $report_id]);

    // Handle Loops (Section 5.2)
    $pdo->prepare("DELETE FROM fire_detection_section5_2 WHERE report_id = ?")->execute([$report_id]);
    if (isset($_POST['loops']) && is_array($_POST['loops'])) {
        $l_stmt = $pdo->prepare("INSERT INTO fire_detection_section5_2 (report_id, loop_no, bolum_adi, ekipman_adi, projede_mi, erisim_durumu, montaj_durumu, test, sesli_uyari, isikli_uyari, adresleme) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($_POST['loops'] as $l_no => $items) {
            foreach ($items as $item) {
                if (!empty($item['ekipman_adi'])) {
                    $l_stmt->execute([
                        $report_id,
                        $l_no,
                        $item['bolum_adi'] ?? '',
                        $item['ekipman_adi'] ?? '',
                        $item['p'] ?? '',
                        $item['e'] ?? '',
                        $item['m'] ?? '',
                        $item['t'] ?? '',
                        $item['s'] ?? '',
                        $item['i'] ?? '',
                        $item['a'] ?? ''
                    ]);
                }
            }
        }
    }

    redirect("yangin_algilama_sonuclar.php?report_id=$report_id&status=success");
}

// Fetch existing loops
$loop_stmt = $pdo->prepare("SELECT * FROM fire_detection_section5_2 WHERE report_id = ? ORDER BY loop_no, id");
$loop_stmt->execute([$report_id]);
$existing_loops = [];
$total_existing_rows = 0;
foreach ($loop_stmt->fetchAll() as $row) {
    $existing_loops[$row['loop_no']][] = $row;
    $total_existing_rows++;
}

include '../../includes/header.php';

$questions = [
    'ÖN KONTROLLER' => [
        'personel_varmi' => 'Yetkili ve eğitimli personel var mı?',
        'sorumlular_belirlenmismi' => 'Yangın güvenliği sorumluları belirlenmiş mi?',
        'panel_durumu' => 'Yangın alarm panelinin durumu (ekran-tuşlar-LED\'ler)',
        'anons_sistemi' => 'Acil durum anons sistemi mevcudiyeti',
        'bakim_kayitlari' => 'Bakım/servis kayıtları tutuluyor mu?',
        'sistem_kutugu' => 'Sistem kütüğü belgesi var mı?'
    ],
    'YANGIN ALGILAMA VE YANGIN UYARI SİSTEMİ VE TESİSATI' => [
        'panel_yerlesim' => 'Kontrol paneli ve varsa tekrarlayıcı panellerin yerleşim durumu',
        'panel_izlenebilirlik' => 'Kontrol paneli sürekli izlenebilir durumda mı?',
        'adresleme_harita' => 'Dedektör ve/veya buton adreslemesi veya yerleşim haritası var mı?',
        'paralel_ihbar' => 'Asma tavan, yükseltilmiş döşeme vb. içinde kalan dedektörlerin uyarılarının görülebilmesi için paralel ihbar lambaları var mı?',
        'koruma_devre' => 'Çevrimlerde kısa devre ve açık devre koruması',
        'devre_ayrilmasi' => 'Güvenlik devre ayrılması (Bant-I, Bant-II\'den ayırma/yalıtım)',
        'kullanma_talimati' => 'Kullanma talimatı var mı?',
        'aku_durumu' => 'Akü kapasitesi, gerilimi ve fiziki durumu',
        'ortam_uyumu' => 'Dedektörlerin çalışma ortamına uyumu ve yeterli olması',
        'uyari_yerlesim' => 'Sesli-siren/ışıklı-flaşör uyarılarının yerleşim durumu ve yeterli olması',
        'kablo_uygunluk' => 'Yangın alarm ve uyarı kablolarının uygunluğu'
    ],
    'ACİL DURUM AYDINLATMA VE ACİL DURUM YÖNLENDİRME SİSTEMİ' => [
        'armatur_uygunluk' => 'Acil durum aydınlatma armatürleri uygunluğu',
        'aydinlatma_varlik' => 'Acil durum aydınlatma sistemi varlığı, yeterliliği-diğer gerekli alanlar',
        'yonlendirme_isaretleri' => 'Kaçış yollarında acil durum yönlendirme işaretleri varlığı, yeterliliği',
        'aydinlatma_seviye' => 'Acil durum aydınlatma ünitelerinin aydınlatma seviyelerinin uygunluğu',
        'panel_onu_lux' => 'Acil durum aydınlatma sistemi varlığı, yeterliliği-panel önü (lux değeri TS EN 12464\'e göre)',
        'cikis_hol_yonlendirme' => 'Acil çıkış hollerinde acil durum yönlendirme işaretleri varlığı, yeterliliği',
        'aydinlatma_sure' => 'Acil durum aydınlatma ünitelerinin aydınlatma sürelerinin uygunluğu',
        'otomatik_devreye_girme' => 'Acil durum aydınlatması ve yönlendirmesi elektrik kesildiğinde otomatik devreye girmesi'
    ],
    'YANGIN ANINDA DİĞER MEKANİK, ELEKTRİK VE ELEKTRONİK SİSTEMLERLE ENTEGRASYON' => [
        'damper_izlenebilirlik' => 'Duman damperleri açık/kapalı konum bilgilerinin doğrudan çevrimlere bağlı kontak izleme cihazlar ile izlenebilirliği',
        'sondurme_entegrasyon' => 'Yangın alarm sisteminin diğer otomatik söndürme sistemleri ile entegre olma durumu',
        'otomasyon_baglanti' => 'Yangın algılama ve uyarı sisteminin bina otomasyon sistemi ile bağlantı ve haberleşme kontrolü',
        'asansor_davranis' => 'Asansörlerin yangın anında davranışları kontrolü',
        'kesici_yedek_enerji' => 'Yangın anında elektrik tesisatında kesicilerin çalışıp çalışmadığı, enerjisi kesilmemesi gereken bölümlerin yedek enerji kaynaklarının bulunup bulunmadığı ve devreye otomatik girip girmediği',
        'iklimlendirme_sinyal' => 'İklimlendirme/havalandırma sistemi ve duman egzoz sistemi sinyal kontrolü',
        'akis_anahtari_izlenebilirlik' => 'Yangın söndürme sistemi akış anahtarları, hat kesme vanaları, yangın pompaları çalışma fonksiyonları konum bilgisi izlenebilirliği',
        'basinclandirma_kontrol' => 'Yangın anında asansör kuyuları ve yangın merdiveni kovaları basınçlandırma sistemi kontrolleri',
        'kapi_tutucu_kontrol' => 'Yangın bölme kapıları elektromanyetik tutucuları kontrolü',
        'gaz_kesme_kontrol' => 'Yangın anında patlayıcı gaz dağıtım sistemlerinin kontrolü'
    ]
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Yangın Algılama: Tespit ve Değerlendirmeler</h1>
    <a href="yangin_algilama_kontrol.php?id=<?php echo $report_id; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Ana Forma Dön
    </a>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="alert alert-success">Sonuçlar başarıyla kaydedildi.</div>
<?php endif; ?>

<form method="POST">
    <?php foreach ($questions as $group => $items): ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <?php echo $group; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60%;">Kontrol Kriteri</th>
                            <th style="width: 40%; text-align: center;">Değerlendirme</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $key => $label): ?>
                            <tr>
                                <td>
                                    <?php echo $label; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                    $current_val = $results[$key] ?? 'U';
                                    foreach (['U' => 'UYGUN (U)', 'UD' => 'UYGUN DEĞİL (UD)', 'UG' => 'UYGULANMAZ (UG)'] as $val => $text):
                                        ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="q[<?php echo $key; ?>]"
                                                value="<?php echo $val; ?>" <?php echo ($current_val == $val) ? 'checked' : ''; ?>
                                                required>
                                            <label class="form-check-label">
                                                <?php echo $val; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="card mb-4 mt-5">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">5.2. YANGIN ALGILAMA VE UYARI CİHAZLARI KONTROLÜ VE TESTLER</h5>
            <button type="button" class="btn btn-light btn-sm" onclick="addLoop()">+ Yeni Loop Ekle</button>
        </div>
        <div class="card-body" id="loops-container">
            <?php if (empty($existing_loops)): ?>
                <div class="alert alert-info text-center" id="no-loops-msg">Henüz loop eklenmemiş. Yukarıdaki butonu
                    kullanarak başlayın.</div>
            <?php else: ?>
                <?php
                $row_idx = 0;
                foreach ($existing_loops as $l_no => $items): ?>
                    <div class="loop-group border p-3 mb-4 rounded bg-light"
                        data-loop-no="<?php echo htmlspecialchars($l_no); ?>">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="fw-bold text-danger">LOOP: <?php echo htmlspecialchars($l_no); ?></h6>
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                onclick="this.closest('.loop-group').remove()">Loop'u Sil</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white">
                                <thead class="table-dark small">
                                    <tr class="text-center">
                                        <th>Bölüm Adı</th>
                                        <th>Ekipman</th>
                                        <th style="width: 100px;">Hızlı</th>
                                        <th>P</th>
                                        <th>E</th>
                                        <th>M</th>
                                        <th>T</th>
                                        <th>S</th>
                                        <th>I</th>
                                        <th>A</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item):
                                        $row_idx++;
                                        ?>
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm"
                                                    name="loops[<?php echo htmlspecialchars($l_no); ?>][<?php echo $row_idx; ?>][bolum_adi]"
                                                    value="<?php echo htmlspecialchars($item['bolum_adi']); ?>"></td>
                                            <td><input type="text" class="form-control form-control-sm"
                                                    name="loops[<?php echo htmlspecialchars($l_no); ?>][<?php echo $row_idx; ?>][ekipman_adi]"
                                                    value="<?php echo htmlspecialchars($item['ekipman_adi']); ?>"></td>
                                            <td>
                                                <select class="form-select form-select-sm" onchange="setAllInRow(this, this.value)">
                                                    <option value="">Seç</option>
                                                    <option value="U">U</option>
                                                    <option value="UG">UG</option>
                                                    <option value="UD">UD</option>
                                                </select>
                                            </td>
                                            <?php foreach (['p' => 'projede_mi', 'e' => 'erisim_durumu', 'm' => 'montaj_durumu', 't' => 'test', 's' => 'sesli_uyari', 'i' => 'isikli_uyari', 'a' => 'adresleme'] as $key => $col): ?>
                                                <td>
                                                    <select class="form-select form-select-sm"
                                                        name="loops[<?php echo htmlspecialchars($l_no); ?>][<?php echo $row_idx; ?>][<?php echo $key; ?>]">
                                                        <?php foreach (['U', 'UD', 'UG'] as $opt): ?>
                                                            <option value="<?php echo $opt; ?>" <?php echo ($item[$col] == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                            <?php endforeach; ?>
                                            <td><button type="button" class="btn btn-danger btn-sm"
                                                    onclick="this.closest('tr').remove()">&times;</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-success btn-sm"
                                onclick="addRow(this, '<?php echo htmlspecialchars($l_no); ?>')">+ Satır Ekle</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4 mb-5 d-grid">
        <button type="submit" class="btn btn-danger btn-lg">Tüm Sonuçları Kaydet</button>
    </div>
</form>

<script>
    let loopCount = <?php echo empty($existing_loops) ? 0 : count($existing_loops); ?>;
    let globalRowIdx = <?php echo $total_existing_rows + 1; ?>;

    function addLoop() {
        const msg = document.getElementById('no-loops-msg');
        if (msg) msg.remove();

        loopCount++;
        const loopNo = 'Loop ' + loopCount;
        const container = document.getElementById('loops-container');

        const div = document.createElement('div');
        div.className = 'loop-group border p-3 mb-4 rounded bg-light';
        div.dataset.loopNo = loopNo;
        div.innerHTML = `
        <div class="d-flex justify-content-between mb-2">
            <h6 class="fw-bold text-danger">LOOP: ${loopNo}</h6>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.loop-group').remove()">Loop'u Sil</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered bg-white">
                <thead class="table-dark small">
                    <tr class="text-center">
                        <th>Bölüm Adı</th><th>Ekipman</th><th style="width: 100px;">Hızlı</th><th>P</th><th>E</th><th>M</th><th>T</th><th>S</th><th>I</th><th>A</th><th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button type="button" class="btn btn-success btn-sm" onclick="addRow(this, '${loopNo}')">+ Satır Ekle</button>
        </div>
    `;
        container.appendChild(div);

        // Add default equipment rows
        const defaultEquip = [
            'Optik duman dedektörü',
            'Isı dedektörü',
            'Yangın Alarm butonu',
            'Siren',
            'Flaşör (ışıklı ve sesli)',
            'Diğer'
        ];

        const tbody = div.querySelector('tbody');
        defaultEquip.forEach(item => {
            tbody.appendChild(createRow(loopNo, item));
        });
    }

    function addRow(btn, loopNo) {
        const tbody = btn.closest('.table-responsive').querySelector('tbody');
        tbody.appendChild(createRow(loopNo, ''));
    }

    function createRow(loopNo, equipName) {
        globalRowIdx++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
        <td><input type="text" class="form-control form-control-sm" name="loops[${loopNo}][${globalRowIdx}][bolum_adi]"></td>
        <td><input type="text" class="form-control form-control-sm" name="loops[${loopNo}][${globalRowIdx}][ekipman_adi]" value="${equipName}"></td>
        <td>
            <select class="form-select form-select-sm" onchange="setAllInRow(this, this.value)">
                <option value="">Seç</option>
                <option value="U">U</option>
                <option value="UG">UG</option>
                <option value="UD">UD</option>
            </select>
        </td>
        <td><select class="form-select form-select-sm" name="loops[${loopNo}][${globalRowIdx}][p]"><option value="U">U</option><option value="UD">UD</option><option value="UG">UG</option></select></td>
        <td><select class="form-select form-select-sm" name="loops[${loopNo}][${globalRowIdx}][e]"><option value="U">U</option><option value="UD">UD</option><option value="UG">UG</option></select></td>
        <td><select class="form-select form-select-sm" name="loops[${loopNo}][${globalRowIdx}][m]"><option value="U">U</option><option value="UD">UD</option><option value="UG">UG</option></select></td>
        <td><select class="form-select form-select-sm" name="loops[${loopNo}][${globalRowIdx}][t]"><option value="U">U</option><option value="UD">UD</option><option value="UG">UG</option></select></td>
        <td><select class="form-select form-select-sm" name="loops[${loopNo}][${globalRowIdx}][s]"><option value="U">U</option><option value="UD">UD</option><option value="UG">UG</option></select></td>
        <td><select class="form-select form-select-sm" name="loops[${loopNo}][${globalRowIdx}][i]"><option value="U">U</option><option value="UD">UD</option><option value="UG">UG</option></select></td>
        <td><select class="form-select form-select-sm" name="loops[${loopNo}][${globalRowIdx}][a]"><option value="U">U</option><option value="UD">UD</option><option value="UG">UG</option></select></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">&times;</button></td>
    `;
        return tr;
    }
    function setAllInRow(el, val) {
        if (!val) return;
        const tr = el.closest('tr');
        const selects = tr.querySelectorAll('select');
        selects.forEach(s => {
            s.value = val;
        });
    }
</script>

<?php include '../../includes/footer.php'; ?>