<?php
// Hataları aktif et (Beyaz ekran sorununu teşhis etmek için)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

// Hedef Rapor Kimliği (Varsayılan olarak 12)
$report_id = 12;
if (isset($_GET['report_id'])) {
    $report_id = (int)$_GET['report_id'];
}

// Raporu ve kurumunu sorgula
$stmt = $pdo->prepare("SELECT * FROM internal_installation_reports WHERE id = ?");
$stmt->execute([$report_id]);
$rpt = $stmt->fetch();

if ($rpt) {
    // Otomatik olarak kurum oturumunu ayarla (Seçim yapılmamışsa bile hata vermemesi için)
    $_SESSION['active_institution_id'] = $rpt['kurum_id'];
    
    $stmt_inst = $pdo->prepare("SELECT firma_adi FROM institutions WHERE id=?");
    $stmt_inst->execute([$rpt['kurum_id']]);
    $_SESSION['active_institution_name'] = $stmt_inst->fetchColumn();
}

$kurum_id = $_SESSION['active_institution_id'] ?? null;

// Rapor veritabanında yoksa veya kurum oturumu alınamadıysa rapor listesi göster
if (!$rpt || !$kurum_id) {
    // Mevcut tüm raporları listele
    $stmt_all = $pdo->query("SELECT r.id, r.report_no, i.firma_adi as kurum_name FROM internal_installation_reports r JOIN institutions i ON r.kurum_id = i.id ORDER BY r.id DESC");
    $all_reports = $stmt_all->fetchAll();
    
    include '../../includes/header.php';
    ?>
    <div class="container-fluid pt-5">
        <div class="card shadow border-0 rounded-4">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <h3 class="fw-bold text-dark">Rapor Bulunamadı</h3>
                    <p class="text-muted">Sistemde <strong>Rapor #<?php echo $report_id; ?></strong> bulunamadı. Lütfen işlem yapmak istediğiniz iç tesisat raporunu aşağıdan seçin:</p>
                </div>
                
                <form method="GET" class="mx-auto" style="max-width: 500px;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rapor Seçin</label>
                        <select name="report_id" class="form-select form-select-lg shadow-sm" required>
                            <option value="">-- Rapor Seçin --</option>
                            <?php foreach ($all_reports as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo ($r['id'] == 12) ? 'selected' : ''; ?>>
                                    Rapor #<?php echo $r['id']; ?> - <?php echo htmlspecialchars($r['report_no']); ?> (<?php echo htmlspecialchars($r['kurum_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                        Seçilen Raporla Devam Et <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
    include '../../includes/footer.php';
    exit;
}

$inst_name = $_SESSION['active_institution_name'] ?? 'Bilinmeyen Kurum';

// --- AJAX EYLEMLERİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'initialize_panels') {
        header('Content-Type: application/json');
        $count = isset($_POST['count']) ? (int)$_POST['count'] : 0;
        if ($count <= 0) {
            echo json_encode(['success' => false, 'error' => 'Geçersiz dosya sayısı.']);
            exit;
        }

        // Mevcut en yüksek panel sırasını al (yeni panolar eskilerden sonra eklenecek)
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(panel_order),0) FROM ic_tesisat_panels WHERE report_id=?");
        $stmt->execute([$report_id]);
        $max_order = (int)$stmt->fetchColumn();

        // Bölüm 5 soruları (ic_tesisat_panel_sonuclar.php ile birebir uyumlu)
        $s5_questions = [
            'kablo_sebeke', 'kablo_donanim', 'pano_sabitleme', 'dis_darbe', 'yabanci_malzeme', 'zemin_izolasyon',
            'topraklama_iletken', 'ana_pot_iletken', 'ek_pot_iletken', 'kapak_6mm',
            'elektriksel_olmayan', 'bant_ayirma', 'guvenlik_devre', 'pano_kapak_erisim',
            'semalar', 'koruma_etiket', 'tehlike_isaretleri',
            'kablo_yolu', 'kablo_renk', 'tesisat_yontemi', 'yangin_engeli',
            'fotograf_tarihi', 'kontak_gevsekligi', 'fotograf_no', 'asiri_yuk_isinma',
            'yangin_sondurme', 'ekipman_temizlik', 'korozyon', 'acil_aydinlatma'
        ];

        // Rapor başlangıç tarihi
        $default_start_date = '';
        if (!empty($rpt['start_date'])) {
            $default_start_date = date('d.m.Y', strtotime($rpt['start_date']));
        } else {
            $default_start_date = date('d.m.Y');
        }

        $created_panels = [];
        $pdo->beginTransaction();
        try {
            $ins_panel = $pdo->prepare("INSERT INTO ic_tesisat_panels (report_id, panel_name, panel_order) VALUES (?,?,?)");
            $ins_s5 = $pdo->prepare("INSERT INTO ic_tesisat_section5 (panel_id, question_key, answer) VALUES (?,?,?)");

            for ($i = 1; $i <= $count; $i++) {
                $pname = "kombinasyon" . $i;
                $order = $max_order + $i;

                $ins_panel->execute([$report_id, $pname, $order]);
                $pid = $pdo->lastInsertId();

                $created_panels[] = [
                    'id' => $pid,
                    'name' => $pname,
                    'order' => $order
                ];

                // Bölüm 5'i otomatik "U" (Uygun) olarak doldur
                foreach ($s5_questions as $qkey) {
                    if ($qkey === 'fotograf_tarihi') {
                        $ins_s5->execute([$pid, $qkey, $default_start_date]);
                    } elseif ($qkey === 'fotograf_no') {
                        $ins_s5->execute([$pid, $qkey, $order]);
                    } else {
                        $ins_s5->execute([$pid, $qkey, 'U']);
                    }
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'panels' => $created_panels]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'upload_single_photo') {
        header('Content-Type: application/json');
        $pid = isset($_POST['panel_id']) ? (int)$_POST['panel_id'] : 0;

        // Panel doğrulaması
        $stmt = $pdo->prepare("SELECT id FROM ic_tesisat_panels WHERE id = ? AND report_id = ?");
        $stmt->execute([$pid, $report_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Panel bu rapora ait değil.']);
            exit;
        }

        if (!empty($_FILES['photo']['name'])) {
            $file = $_FILES['photo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $dir = "../../uploads/ic_tesisat/$report_id/$pid/";
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $filename = 'normal_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $dest_path = $dir . $filename;

                // Görseli sıkıştır
                $uploaded = compressImage($file['tmp_name'], $dest_path, 75, 800);
                if (!$uploaded) {
                    $uploaded = move_uploaded_file($file['tmp_name'], $dest_path);
                }

                if ($uploaded) {
                    $fpath = "/uploads/ic_tesisat/$report_id/$pid/" . $filename;
                    $pdo->prepare("INSERT INTO ic_tesisat_photos (panel_id, photo_type, file_path) VALUES (?, 'normal', ?)")
                        ->execute([$pid, $fpath]);

                    echo json_encode(['success' => true, 'path' => $fpath]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Dosya sunucuya yazılamadı.']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Desteklenmeyen dosya uzantısı.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Dosya yüklenemedi.']);
        }
        exit;
    }
}

// Görsel başlığı dahil et
include '../../includes/header.php';
?>

<style>
    .glass-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    .drop-zone {
        border: 2px dashed #0d6efd;
        background-color: rgba(13, 110, 253, 0.01);
        border-radius: 12px;
        padding: 50px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.25s ease-in-out;
    }
    .drop-zone:hover, .drop-zone.dragover {
        background-color: rgba(13, 110, 253, 0.06);
        border-color: #0b5ed7;
        box-shadow: inset 0 0 15px rgba(13, 110, 253, 0.1);
    }
    .drop-zone i {
        font-size: 3.5rem;
        color: #0d6efd;
        margin-bottom: 1rem;
        transition: transform 0.25s ease-in-out;
    }
    .drop-zone:hover i {
        transform: translateY(-8px);
    }
    .file-queue-container {
        max-height: 450px;
        overflow-y: auto;
        border: 1px solid #e9ecef;
        border-radius: 8px;
    }
    .file-row {
        transition: background-color 0.2s ease;
    }
    .progress {
        height: 1.5rem;
        border-radius: 8px;
        overflow: hidden;
    }
    .pulse-animation {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
    }
</style>

<div class="container-fluid pt-3 pb-2 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/pages/dashboard.php">Anasayfa</a></li>
            <li class="breadcrumb-item"><a href="/pages/results/ic_tesisat_sonuclar.php">İç Tesisat</a></li>
            <li class="breadcrumb-item"><a href="/pages/results/ic_tesisat_panel_sonuclar.php?report_id=<?php echo $report_id; ?>">Pano Sonuçları</a></li>
            <li class="breadcrumb-item active" aria-current="page">Geçici Toplu Pano Yükleme</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
        <h1 class="h2 text-dark">
            <i class="fas fa-images text-primary me-2"></i> Toplu Pano & Fotoğraf Ekleme
        </h1>
        <a href="/pages/results/ic_tesisat_panel_sonuclar.php?report_id=<?php echo $report_id; ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Pano Sonuçlarına Dön
        </a>
    </div>

    <div class="row">
        <!-- Bilgilendirme ve Ayarlar -->
        <div class="col-lg-4">
            <div class="glass-card bg-light">
                <h5 class="fw-bold text-dark mb-3"><i class="fas fa-info-circle text-info me-2"></i> Çalışma Mantığı</h5>
                <p class="small text-muted">
                    Bu sayfa yardımıyla toplu olarak seçtiğiniz fotoğraflar için veritabanında otomatik panolar oluşturulacaktır.
                </p>
                <hr>
                <div class="mb-3 small">
                    <strong>Hedef Rapor:</strong> <span class="badge bg-dark">#<?php echo $report_id; ?> (<?php echo htmlspecialchars($rpt['report_no'] ?? ''); ?>)</span><br>
                    <strong>Kurum:</strong> <span class="text-dark fw-medium"><?php echo htmlspecialchars($inst_name); ?></span>
                </div>
                <div class="alert alert-warning small py-2">
                    <i class="fas fa-exclamation-circle me-1"></i> Panolar, raporda kayıtlı olan <strong>en son panodan sonra</strong> sırayla eklenecektir.
                </div>
                <div class="alert alert-info small py-2 mb-0">
                    <i class="fas fa-check-double me-1"></i> Her pano için <strong>Bölüm 5 (Gözle Muayene)</strong> verileri otomatik olarak <strong>"UYGUN (U)"</strong> şeklinde başlatılacaktır. Yüklenen fotoğraflar <strong>normal fotoğraf</strong> olarak kaydedilecek, termal fotoğraflar boş bırakılacaktır.
                </div>
            </div>

            <!-- Özet Sonuç Kartı -->
            <div id="summaryCard" class="card shadow-sm border-success bg-success bg-opacity-10 d-none mb-4">
                <div class="card-body">
                    <h5 class="card-title text-success fw-bold"><i class="fas fa-check-circle me-1"></i> İşlem Başarıyla Tamamlandı!</h5>
                    <p class="card-text small text-dark mb-3">Tüm fotoğraflar başarıyla yüklendi ve ilgili panolar veritabanına işlendi.</p>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>Oluşturulan Pano Sayısı:</span>
                        <strong class="text-dark" id="panelsCreatedCount">0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 small">
                        <span>Yüklenen Fotoğraf Sayısı:</span>
                        <strong class="text-dark" id="photosUploadedCount">0</strong>
                    </div>
                    <a href="/pages/results/ic_tesisat_panel_sonuclar.php?report_id=<?php echo $report_id; ?>" class="btn btn-success w-100 fw-bold">
                        Pano Sonuçlarını Görüntüle <i class="fas fa-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Dosya Seçimi ve Yükleme Kuyruğu -->
        <div class="col-lg-8">
            <div class="glass-card">
                <!-- Drop Zone -->
                <div id="dropZone" class="drop-zone mb-4">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4 class="fw-bold text-dark">Fotoğrafları Sürükleyip Bırakın</h4>
                    <p class="text-muted mb-3">veya bilgisayarınızdan seçmek için tıklayın (Yaklaşık 100 fotoğraf yüklenebilir)</p>
                    <input type="file" id="bulk_photos" multiple accept="image/*" class="d-none">
                    <span id="fileInfo" class="badge bg-secondary p-2 fs-6">Henüz dosya seçilmedi</span>
                </div>

                <!-- Progress Section (Gizli) -->
                <div id="progressSection" class="mb-4 d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-primary small" id="activeUploadText">Hazırlanıyor...</span>
                        <span class="fw-bold text-primary" id="progressText">0%</span>
                    </div>
                    <div class="progress">
                        <div id="overallProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Kontrol Butonları -->
                <div class="d-flex gap-2 justify-content-end mb-4">
                    <button id="clearListBtn" type="button" class="btn btn-outline-danger" disabled>
                        <i class="fas fa-trash-alt me-1"></i> Listeyi Temizle
                    </button>
                    <button id="startUploadBtn" type="button" class="btn btn-primary px-4 fw-bold" disabled>
                        <i class="fas fa-play me-1"></i> Panoları Oluştur ve Yüklemeyi Başlat
                    </button>
                </div>

                <!-- Kuyruk Listesi -->
                <div id="queueSection" class="d-none">
                    <h5 class="fw-bold text-dark mb-3"><i class="fas fa-list text-secondary me-2"></i> Yüklenecek Dosya Kuyruğu</h5>
                    <div class="file-queue-container">
                        <table class="table table-hover table-bordered table-sm mb-0 align-middle">
                            <thead class="table-light text-center small">
                                <tr>
                                    <th style="width: 5%">Sıra</th>
                                    <th style="width: 35%">Dosya Adı</th>
                                    <th style="width: 15%">Dosya Boyutu</th>
                                    <th style="width: 20%">Atanacak Pano Adı</th>
                                    <th style="width: 15%">Durum</th>
                                    <th style="width: 10%">Detay</th>
                                </tr>
                            </thead>
                            <tbody id="fileListBody" class="small">
                                <!-- JS ile doldurulacak -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('bulk_photos');
    const fileInfo = document.getElementById('fileInfo');
    const startBtn = document.getElementById('startUploadBtn');
    const clearBtn = document.getElementById('clearListBtn');
    const queueSection = document.getElementById('queueSection');
    const progressSection = document.getElementById('progressSection');
    const overallProgressBar = document.getElementById('overallProgressBar');
    const progressText = document.getElementById('progressText');
    const activeUploadText = document.getElementById('activeUploadText');
    const fileListBody = document.getElementById('fileListBody');
    const summaryCard = document.getElementById('summaryCard');
    const panelsCreatedCount = document.getElementById('panelsCreatedCount');
    const photosUploadedCount = document.getElementById('photosUploadedCount');

    let selectedFiles = [];

    // Tıklama ile dosya seçimi tetikleme
    dropZone.addEventListener('click', () => {
        if (!fileInput.disabled) {
            fileInput.click();
        }
    });

    // Sürükle bırak olayları
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            if (!fileInput.disabled) {
                dropZone.classList.add('dragover');
            }
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        }, false);
    });

    dropZone.addEventListener('drop', (e) => {
        if (fileInput.disabled) return;
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        // Yalnızca resim dosyalarını filtreleyelim
        const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'));
        if (imageFiles.length === 0) {
            alert("Lütfen yalnızca resim dosyaları (jpg, jpeg, png, gif, webp) seçin.");
            return;
        }

        selectedFiles = [...selectedFiles, ...imageFiles];
        updateQueueUI();
    }

    function updateQueueUI() {
        if (selectedFiles.length > 0) {
            fileInfo.innerHTML = `<strong>${selectedFiles.length}</strong> adet resim dosyası seçildi.`;
            fileInfo.className = 'badge bg-primary p-2 fs-6';
            startBtn.disabled = false;
            clearBtn.disabled = false;
            queueSection.classList.remove('d-none');

            // Kuyruk tablosunu oluşturalım
            fileListBody.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const tr = document.createElement('tr');
                tr.id = `row-${index}`;
                tr.className = 'file-row';

                const sizeKB = (file.size / 1024).toFixed(1);

                tr.innerHTML = `
                    <td class="text-center fw-bold">${index + 1}</td>
                    <td class="text-truncate" style="max-width: 250px;" title="${file.name}">${file.name}</td>
                    <td class="text-center text-muted small">${sizeKB} KB</td>
                    <td class="text-center font-monospace fw-bold text-primary">kombinasyon${index + 1}</td>
                    <td class="text-center" id="status-${index}"><span class="badge bg-secondary">Bekliyor</span></td>
                    <td id="detail-${index}" class="small text-muted text-center">-</td>
                `;
                fileListBody.appendChild(tr);
            });
        } else {
            fileInfo.innerHTML = 'Henüz dosya seçilmedi';
            fileInfo.className = 'badge bg-secondary p-2 fs-6';
            startBtn.disabled = true;
            clearBtn.disabled = true;
            queueSection.classList.add('d-none');
            fileListBody.innerHTML = '';
        }
    }

    clearBtn.addEventListener('click', () => {
        selectedFiles = [];
        fileInput.value = '';
        updateQueueUI();
        progressSection.classList.add('d-none');
        summaryCard.classList.add('d-none');
    });

    startBtn.addEventListener('click', async () => {
        if (selectedFiles.length === 0) return;

        // Kontrolleri kilitleyelim
        startBtn.disabled = true;
        clearBtn.disabled = true;
        fileInput.disabled = true;
        dropZone.style.pointerEvents = 'none';

        progressSection.classList.remove('d-none');
        overallProgressBar.style.width = '0%';
        overallProgressBar.setAttribute('aria-valuenow', 0);
        progressText.innerText = `0%`;

        const totalFiles = selectedFiles.length;

        // Adım 1: Panoları Veritabanında Tanımla (AJAX)
        activeUploadText.innerText = 'Panolar oluşturuluyor ve Gözle Muayene (Bölüm 5) kriterleri veritabanında hazırlanıyor...';
        
        const initData = new FormData();
        initData.append('action', 'initialize_panels');
        initData.append('count', totalFiles);

        let panels = [];
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: initData
            });
            const result = await response.json();

            if (!result.success) {
                alert('Panolar oluşturulurken sunucu tarafında hata oluştu: ' + result.error);
                resetUI();
                return;
            }
            panels = result.panels;
            panelsCreatedCount.innerText = panels.length;
        } catch (error) {
            console.error(error);
            alert('Ağ hatası oluştu, panolar başlatılamadı.');
            resetUI();
            return;
        }

        // Adım 2: Resimleri Sıralı Şekilde Yükle
        let successCount = 0;

        for (let i = 0; i < totalFiles; i++) {
            const file = selectedFiles[i];
            const panel = panels[i];
            const rowId = `row-${i}`;
            const statusEl = document.getElementById(`status-${i}`);
            const detailEl = document.getElementById(`detail-${i}`);
            const tr = document.getElementById(rowId);

            // Satır durumunu güncelleyelim
            tr.className = 'file-row table-warning';
            statusEl.innerHTML = '<span class="badge bg-warning text-dark"><i class="fas fa-spinner fa-spin me-1"></i> Yükleniyor</span>';
            detailEl.innerText = 'Fotoğraf sıkıştırılıyor ve gönderiliyor...';
            activeUploadText.innerText = `Fotoğraf yükleniyor: ${panel.name} (${file.name}) - [${i + 1}/${totalFiles}]`;

            // Görseli göndermek için form oluşturalım
            const uploadData = new FormData();
            uploadData.append('action', 'upload_single_photo');
            uploadData.append('panel_id', panel.id);
            uploadData.append('photo', file);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: uploadData
                });
                const result = await response.json();

                if (result.success) {
                    successCount++;
                    tr.className = 'file-row table-success';
                    statusEl.innerHTML = '<span class="badge bg-success"><i class="fas fa-check me-1"></i> Tamamlandı</span>';
                    detailEl.innerHTML = `<span class="text-success"><i class="fas fa-check"></i> Kaydedildi</span>`;
                } else {
                    tr.className = 'file-row table-danger';
                    statusEl.innerHTML = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i> Hata</span>';
                    detailEl.innerText = result.error || 'Yükleme başarısız';
                }
            } catch (error) {
                console.error(error);
                tr.className = 'file-row table-danger';
                statusEl.innerHTML = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i> Hata</span>';
                detailEl.innerText = 'Bağlantı hatası oluştu.';
            }

            // Genel ilerleme barını güncelleyelim
            const progressPercent = Math.round(((i + 1) / totalFiles) * 100);
            overallProgressBar.style.width = `${progressPercent}%`;
            overallProgressBar.setAttribute('aria-valuenow', progressPercent);
            progressText.innerText = `${progressPercent}%`;
        }

        // Tamamlandı durumuna getirelim
        activeUploadText.innerHTML = `<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Yükleme işlemi başarıyla tamamlandı!</span>`;
        photosUploadedCount.innerText = successCount;
        summaryCard.classList.remove('d-none');
        
        // UI'ı serbest bırakalım
        resetUI(true);
    });

    function resetUI(keepData = false) {
        startBtn.disabled = selectedFiles.length === 0;
        clearBtn.disabled = selectedFiles.length === 0;
        fileInput.disabled = false;
        dropZone.style.pointerEvents = 'auto';
        if (!keepData) {
            selectedFiles = [];
            fileInput.value = '';
            updateQueueUI();
            progressSection.classList.add('d-none');
            summaryCard.classList.add('d-none');
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
