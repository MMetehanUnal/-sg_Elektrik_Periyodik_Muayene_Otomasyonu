<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

if (!isset($_SESSION['active_institution_id'])) {
    redirect('tesis_secimi.php');
}
$kurum_id = $_SESSION['active_institution_id'];

$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rapor = null;

if ($report_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM general_reports WHERE id = ? AND kurum_id = ?");
    $stmt->execute([$report_id, $kurum_id]);
    $rapor = $stmt->fetch();
    if (!$rapor) {
        die("Rapor bulunamadı veya bu kuruma ait değil.");
    }
}

$pageTitle = $rapor ? 'Raporu Düzenle' : 'Yeni Rapor Oluştur';

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-<?php echo $rapor ? 'edit' : 'plus-circle'; ?> me-2"></i><?php echo $pageTitle; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="genel_rapor.php" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i> Geri Dön
        </a>
        <?php if ($rapor): ?>
            <a href="genel_rapor_yazdir.php?id=<?php echo $report_id; ?>" target="_blank" class="btn btn-sm btn-outline-dark me-2">
                <i class="fas fa-print me-1"></i> Yazdır
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-primary" id="btnSave">
            <i class="fas fa-save me-1"></i> Kaydet
        </button>
    </div>
</div>

<!-- Rapor Başlığı -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-2">
                <label for="raporBaslik" class="form-label fw-bold mb-0">Rapor Başlığı</label>
            </div>
            <div class="col-md-10">
                <input type="text" class="form-control form-control-lg" id="raporBaslik"
                    placeholder="Rapor başlığını yazın..."
                    value="<?php echo htmlspecialchars($rapor['title'] ?? ''); ?>"
                    maxlength="500">
            </div>
        </div>
    </div>
</div>

<!-- Rapor İçerik Editörü (Microservice) -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-pen-fancy me-2"></i>Rapor İçeriği</span>
        <small class="text-muted" id="saveStatus"></small>
    </div>
    <div class="card-body p-0">
        <textarea id="editor"><?php echo htmlspecialchars($rapor['content'] ?? ''); ?></textarea>
    </div>
</div>

<!-- Alt Kaydet Butonu -->
<div class="d-flex justify-content-end mt-3 mb-4">
    <button type="button" class="btn btn-primary btn-lg" id="btnSaveBottom">
        <i class="fas fa-save me-1"></i> Kaydet
    </button>
</div>

<!-- Jodit Editor CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@4/es2021/jodit.min.css">
<script src="https://cdn.jsdelivr.net/npm/jodit@4/es2021/jodit.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reportId = <?php echo $report_id ?: 'null'; ?>;
    const SAVE_URL = '/services/rapor_icerik/api_save.php';
    const UPLOAD_URL = '/services/rapor_icerik/api_upload_image.php';

    // Jodit Editor Başlatma
    const editor = Jodit.make('#editor', {
        height: 600,
        language: 'tr',
        theme: 'default',
        toolbarAdaptive: false,
        askBeforePasteHTML: false,
        askBeforePasteFromWord: false,
        defaultActionOnPaste: 'insert_clear_html',
        defaultActionOnPasteFromWord: 'insert_clear_html',
        buttons: [
            'bold', 'italic', 'underline', 'strikethrough', '|',
            'font', 'fontsize', 'paragraph', '|',
            'ul', 'ol', '|',
            'outdent', 'indent', '|',
            'align', '|',
            'brush', '|',
            'image', 'table', 'link', 'hr', '|',
            'superscript', 'subscript', '|',
            'copyformat', 'eraser', '|',
            'undo', 'redo', '|',
            'fullsize', 'source', 'print', 'preview'
        ],
        buttonsMD: [
            'bold', 'italic', 'underline', '|',
            'font', 'fontsize', 'paragraph', '|',
            'ul', 'ol', '|',
            'align', '|',
            'brush', '|',
            'image', 'table', 'link', '|',
            'undo', 'redo', '|',
            'fullsize', 'dots'
        ],
        buttonsSM: [
            'bold', 'italic', '|',
            'paragraph', '|',
            'ul', 'ol', '|',
            'image', 'table', '|',
            'undo', 'redo', '|',
            'dots'
        ],
        uploader: {
            url: UPLOAD_URL,
            format: 'json',
            filesVariableName: function(i) {
                return 'images[' + i + ']';
            },
            withCredentials: false,
            pathVariableName: 'path',
            prepareData: function(formdata) {
                if (reportId) {
                    formdata.append('report_id', reportId);
                }
                return formdata;
            },
            isSuccess: function(resp) {
                return resp.success;
            },
            process: function(resp) {
                return {
                    files: resp.data.files || [],
                    path: '',
                    baseurl: '',
                    error: resp.data.error || 0,
                    msg: resp.data.message || ''
                };
            },
            defaultHandlerSuccess: function(data) {
                if (data.files && data.files.length) {
                    for (let i = 0; i < data.files.length; i++) {
                        this.s.insertImage(data.files[i]);
                    }
                }
            },
            error: function(e) {
                console.error('Upload error:', e);
                alert('Resim yükleme hatası oluştu.');
            }
        },
        // Tablo ayarları
        table: {
            allowCellSelection: true,
            selectionCellStyle: 'border: 1px double #1e88e5 !important;',
            useExtraClassesOptions: false
        },
        // Stil
        style: {
            font: "'Arial', sans-serif",
            'font-size': '12px'
        },
        // Placeholder
        placeholder: 'Rapor içeriğinizi buraya yazın...\n\nMetin, tablo, resim ve daha fazlasını ekleyebilirsiniz.',
        // Enter tuşu davranışı
        enter: 'p',
        // Tab size
        tabIndex: 0,
        // İzin verilen etiketler
        allowResizeX: false,
        allowResizeY: true,
        saveHeightInStorage: true
    });

    // Kaydetme fonksiyonu
    function saveReport() {
        const title = document.getElementById('raporBaslik').value.trim();
        const content = editor.value;

        if (!title) {
            alert('Lütfen rapor başlığını girin.');
            document.getElementById('raporBaslik').focus();
            return;
        }

        const statusEl = document.getElementById('saveStatus');
        statusEl.textContent = 'Kaydediliyor...';
        statusEl.className = 'text-warning';

        // Kaydet butonlarını devre dışı bırak
        document.getElementById('btnSave').disabled = true;
        document.getElementById('btnSaveBottom').disabled = true;

        const payload = {
            title: title,
            content: content
        };

        if (reportId) {
            payload.report_id = reportId;
        }

        fetch(SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusEl.textContent = '✓ Kaydedildi - ' + new Date().toLocaleTimeString('tr-TR');
                statusEl.className = 'text-success';

                // Yeni rapor oluşturulduysa URL'yi güncelle
                if (!reportId && data.report_id) {
                    window.history.replaceState(null, '', 'genel_rapor_duzenle.php?id=' + data.report_id);
                    // Sayfa yenilendiğinde reportId'yi güncelle
                    location.href = 'genel_rapor_duzenle.php?id=' + data.report_id;
                }
            } else {
                statusEl.textContent = '✗ Hata: ' + (data.error || 'Bilinmeyen hata');
                statusEl.className = 'text-danger';
                alert('Kaydetme hatası: ' + (data.error || 'Bilinmeyen hata'));
            }
        })
        .catch(err => {
            console.error('Save error:', err);
            statusEl.textContent = '✗ Bağlantı hatası';
            statusEl.className = 'text-danger';
            alert('Bağlantı hatası oluştu. Lütfen tekrar deneyin.');
        })
        .finally(() => {
            document.getElementById('btnSave').disabled = false;
            document.getElementById('btnSaveBottom').disabled = false;
        });
    }

    // Kaydet butonları
    document.getElementById('btnSave').addEventListener('click', saveReport);
    document.getElementById('btnSaveBottom').addEventListener('click', saveReport);

    // Ctrl+S ile kaydetme
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveReport();
        }
    });
});
</script>

<style>
/* Jodit editör özelleştirmeleri */
.jodit-container {
    border: none !important;
    border-radius: 0 0 15px 15px;
}
.jodit-toolbar__box {
    background: #f8f9fc !important;
    border-bottom: 1px solid rgba(0,0,0,0.08) !important;
}
.jodit-workplace {
    min-height: 400px;
}
/* Editör içi tablo stilleri */
.jodit-wysiwyg table {
    border-collapse: collapse;
    width: 100%;
    margin: 10px 0;
}
.jodit-wysiwyg table td,
.jodit-wysiwyg table th {
    border: 1px solid #333;
    padding: 6px 10px;
    min-width: 40px;
}
.jodit-wysiwyg table th {
    background-color: #f0f0f0;
    font-weight: bold;
}
.jodit-wysiwyg img {
    max-width: 100%;
    height: auto;
}
#raporBaslik:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.15);
}
</style>

<?php include '../includes/footer.php'; ?>
