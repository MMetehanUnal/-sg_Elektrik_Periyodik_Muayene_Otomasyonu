<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$kurum_id = $_SESSION['active_institution_id'] ?? 0;

if (!$type || !$id || !$kurum_id) {
    echo '<p style="color:red; font-weight:bold;">Hata: Geçersiz rapor tipi veya ID.</p>';
    exit;
}

// Map types to display titles and queries
$type_titles = [
    'topraklama' => 'Alçak Gerilim Topraklama Tesisatı Raporu',
    'ic_tesisat' => 'Elektrik İç Tesisatı Raporu',
    'yildirim' => 'Yıldırımdan Korunma Tesisatı Raporu',
    'yangin' => 'Yangın Algılama ve Uyarı Sistemleri Raporu',
    'sihhi_tesisat' => 'Sıhhi Tesisat Periyodik Kontrol Raporu',
    'gaz_tesisat' => 'Gaz Tesisatı Periyodik Kontrol Raporu',
    'isinma_tesisat' => 'Isınma Tesisatı Periyodik Kontrol Raporu',
    'genlesme_tanki' => 'Genleşme Tankı Periyodik Kontrol Raporu',
    'engelli_rampasi' => 'Engelli Rampası Periyodik Kontrol Raporu',
    'boyler_tanki' => 'Boyler Tankı Periyodik Kontrol Raporu',
    'jenarator' => 'Jeneratör Periyodik Kontrol Raporu',
    'kamera_bakim' => 'Kamera Bakım Raporu',
    'yangin_tesisat' => 'Yangın Tesisatı Güvenliği Raporu'
];

if (!isset($type_titles[$type])) {
    echo '<p style="color:red; font-weight:bold;">Hata: Bilinmeyen rapor tipi.</p>';
    exit;
}

$title = $type_titles[$type];

try {
    // 1. Fetch main report row based on type
    $row = null;
    $ap_name = '-';
    
    switch ($type) {
        case 'topraklama':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM grounding_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'ic_tesisat':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM internal_installation_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'yildirim':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM lightning_protection_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'yangin':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM fire_detection_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'sihhi_tesisat':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM sihhi_tesisat_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'gaz_tesisat':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM gaz_tesisat_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'isinma_tesisat':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM isinma_tesisat_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'genlesme_tanki':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM genlesme_tanki_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'engelli_rampasi':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM engelli_rampasi_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'boyler_tanki':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM boyler_tanki_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'jenarator':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM jenarator_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'kamera_bakim':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM kamera_bakim_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
        case 'yangin_tesisat':
            $stmt = $pdo->prepare("SELECT r.*, ap.adi_soyadi FROM yangin_tesisat_reports r LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id WHERE r.id = ? AND r.kurum_id = ?");
            break;
    }
    
    $stmt->execute([$id, $kurum_id]);
    $row = $stmt->fetch();
    
    if (!$row) {
        echo '<p style="color:red; font-weight:bold;">Hata: Rapor bulunamadı veya yetkiniz yok.</p>';
        exit;
    }
    
    $ap_name = $row['adi_soyadi'] ?? '-';
    $report_no = $row['report_no'] ?? '';
    $report_date = !empty($row['report_date']) ? date('d.m.Y', strtotime($row['report_date'])) : '';
    $control_reason = $row['control_reason'] ?? 'Periyodik Kontrol';
    $result = $row['result'] ?? 'UYGUNDUR';
    $defects = $row['defects'] ?? '';
    $notes = $row['notes'] ?? '';

    // Output clean HTML block
    ?>
    <div class="imported-report-block" data-imported-type="<?php echo htmlspecialchars($type); ?>" data-imported-id="<?php echo htmlspecialchars($id); ?>" style="page-break-before: always; border: 1px solid #c0c0c0; padding: 12px; margin: 15px 0; background-color: #fafafa; font-family: Arial, sans-serif; font-size: 11px; line-height: 1.4;">
        <h3 style="margin-top: 0; margin-bottom: 8px; color: #1f4e79; border-bottom: 2px solid #1f4e79; padding-bottom: 4px; font-size: 13px; text-transform: uppercase;">
            <?php echo htmlspecialchars($title); ?> (Rapor No: <?php echo htmlspecialchars($report_no); ?>)
        </h3>
        
        <!-- Genel Bilgiler Tablosu -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td style="width: 20%; border: 1px solid #ccc; padding: 4px 6px; background-color: #f0f0f0; font-weight: bold;">Rapor Tarihi</td>
                <td style="width: 30%; border: 1px solid #ccc; padding: 4px 6px;"><?php echo htmlspecialchars($report_date); ?></td>
                <td style="width: 20%; border: 1px solid #ccc; padding: 4px 6px; background-color: #f0f0f0; font-weight: bold;">Kontrol Nedeni</td>
                <td style="width: 30%; border: 1px solid #ccc; padding: 4px 6px;"><?php echo htmlspecialchars($control_reason); ?></td>
            </tr>
            <tr>
                <td style="border: 1px solid #ccc; padding: 4px 6px; background-color: #f0f0f0; font-weight: bold;">Kontrolör</td>
                <td style="border: 1px solid #ccc; padding: 4px 6px;"><?php echo htmlspecialchars($ap_name); ?></td>
                <td style="border: 1px solid #ccc; padding: 4px 6px; background-color: #f0f0f0; font-weight: bold;">Sonuç</td>
                <td style="border: 1px solid #ccc; padding: 4px 6px; font-weight: bold; color: <?php echo (in_array($result, ['UYGUNDUR', 'GÜVENLİDİR', 'GÜVENLİ', 'GUVENLIDIR'])) ? 'green' : 'red'; ?>;">
                    <?php 
                     $display_result = $result;
                     if ($result == 'GÜVENLİDİR' || $result == 'GÜVENLİ' || $result == 'GUVENLIDIR') $display_result = 'UYGUNDUR';
                     elseif ($result == 'GÜVENLİ DEĞİLDİR' || $result == 'GÜVENLİ DEĞİL' || $result == 'GUVENLI DEGILDIR') $display_result = 'UYGUN DEĞİLDİR';
                     echo htmlspecialchars($display_result); 
                     ?>
                </td>
            </tr>
        </table>

        <!-- Specific Report Content -->
        <?php if ($type === 'engelli_rampasi' || $type === 'kamera_bakim'): ?>
            <!-- Narrative Paragraph reports -->
            <div style="background-color: #fff; border: 1px solid #ccc; padding: 8px 12px; margin-bottom: 8px; font-style: italic; font-weight: bold; text-align: center;">
                <?php echo nl2br(htmlspecialchars($row['report_text'] ?? '')); ?>
            </div>
        <?php else: ?>
            <!-- Checklist and specifics -->
            
            <?php
            // Define questions mapping for checklist types
            $checklist_questions = [];
            if ($type === 'sihhi_tesisat') {
                $checklist_questions = [
                    'q1' => 'Sıhhi tesisat projesi mevcut mu ?',
                    'q2' => 'Hidrofor bulunması durumunda periyodik kontrol raporu var mı?',
                    'q3' => 'Boyler vb. basınçlı kaplar bulunması durumunda periyodik Kontrol Raporu var mı?',
                    'q4' => 'Su deposunun temizliği yapılıyor mu?',
                    'q5' => 'Yüksek katlı olan binalar için farklı basınç zonları yapılarak sistemin yüksek basınca karşı korunması mevcut mu?',
                    'q6' => 'Temiz su ve pis su tesisatının donma riski var ise önlem alınmış mı?',
                    'q7' => 'Boruların kiriş, kolon gibi taşıyıcı elemanların içerisinden geçmemesi göz önüne alınmış mı?',
                    'q8' => 'Havalandırma bacalarının tıkanmasına karşı yeterli önlem alınmış mı?',
                    'q9' => 'Bacaların, su tesisatının havalandırılmasında kullanılması engellenmiş mi?',
                    'q10' => 'Foseptik / rögar kapakları eksiksiz mi?',
                    'q11' => 'Pis kokuya karşı banyo, duş ve mutfaklarda yer süzgeci var mı?',
                    'q12' => 'Merkezi Sıcak Su tesisatında Lejonella riskine karşı tedbir alınmış mı?',
                    'q13' => 'Mutfak giderlerinde yağ tutucu var mı?',
                    'q14' => 'Sıhhi Tesisat Borularında deformasyon var mı?',
                    'q15' => 'İçme ve kullanım suyunun analizi raporu yapılmış mı?'
                ];
            } elseif ($type === 'gaz_tesisat') {
                $checklist_questions = [
                    'q1' => 'Baca ve gaz tesisatının yıllık kontrol raporu var mı?',
                    'q2' => 'Depreme karşı mekanik veya elektriksel solenoid gaz kesme vanası var mı?',
                    'q3' => 'Boru tesisatının sabitleme kelepçeleri var mı?',
                    'q4' => 'Duvar/döşeme geçişleri uygun mu?',
                    'q5' => 'Elektrik Tesisatına olan emniyet mesafeleri uygun mu?',
                    'q6' => 'Korozyona karşı koruma var mı?',
                    'q7' => 'Kolonda topraklama var mı?',
                    'q8' => 'Exproof Gaz alarm cihazı var mı? Selenoid vana ile irtibatlı mı?',
                    'q9' => 'Havalandırma uygun mu?',
                    'q10' => 'Cihazlara ait gaz kesme vanası var mı?',
                    'q11' => 'Cihazların baca bağlantıları var mı?',
                    'q12' => 'Bacalı ve hermetik cihazların atık gaz sensörü var mı?',
                    'q13' => 'Aydınlatma (Kapalı etanj) uygun mu?',
                    'q14' => 'Mutfak cihazlarında gazı otomatik kesen düzenek var mı?'
                ];
            } elseif ($type === 'isinma_tesisat') {
                $checklist_questions = [
                    'q1' => 'Isınma sistemi projesi mevcut mu?',
                    'q2' => 'Isınma cihazına ait periyodik kontrol raporu var mı?',
                    'q3' => 'Kazancı / Ateşçi belgesi var mı?',
                    'q4' => 'Yakıtların depolama koşulları uygun mu?',
                    'q5' => 'Kazan dairesinin alt / üst havalandırması mevcut mu?',
                    'q6' => 'Isı merkezi alanı uygun mu?',
                    'q7' => 'Isı merkezine yetkisiz kişilerin erişimi engellenmiş mi?',
                    'q8' => 'Isınma cihazı çalışma basıncı bina statik yüksekliği ile uyumlu mu?',
                    'q9' => 'Kapalı genleşme tankı uygun mu?',
                    'q10' => 'Yakıt türüne uygun baca uygulaması mevcut mu?',
                    'q11' => 'Çelik bacalar için topraklama bağlantısı var mı?',
                    'q12' => 'Gerekli noktalarda hava atıcıları mevcut mu?',
                    'q13' => 'Elektrik bağlantısı uygun mu?',
                    'q14' => 'Aydınlatma (Kapalı etanj) uygun mu?',
                    'q15' => 'Gaz alarm cihazı (Exproof) var mı?'
                ];
            } elseif ($type === 'genlesme_tanki' || $type === 'boyler_tanki') {
                $checklist_questions = [
                    'q1' => '1. Manometre çalışıyor ve tüzüğe uygun mu ?',
                    'q2' => '2. Güvenlik ventili çalışıyor ve tüzüğe uygun mu ?',
                    'q3' => '3. Basınç ayar otomatiği (presostat) çalışıyor ve tüzüğe uygun mu ?',
                    'q4' => '4. Blöf vanası çalışıyor ve tüzüğe uygun mu ?',
                    'q5' => '1. Tağdiye cihazı bağlantısı tekniğe uygun mu ?',
                    'q6' => '2. Yapılan bakım ve onarımlar sicil defterine işleniyor mu ?',
                    'q7' => '3. Tank üretim tekniği;',
                    'q8' => '3.1 Kaynak dikişleri uygun mu ?',
                    'q9' => '3.2 Tank malzemesi uygun mu ?',
                    'q10' => '3.3 Tankta kalıcı deformasyon var mı ?',
                    'q11' => '4. Tankın beslenme suyu üzerinde çek valfi var mı ?'
                ];
            } elseif ($type === 'jenarator') {
                $checklist_questions = [
                    'q1' => 'Jeneratörün konulduğu yer ve alan uygun mu?',
                    'q2' => 'Jeneratörün koruyucu kabini var mı?',
                    'q3' => 'Motor suyu seviyesi uygun mu?',
                    'q4' => 'Yağ seviyesi uygun mu?',
                    'q5' => 'Su kaçağı var mı?',
                    'q6' => 'Yağ kaçağı var mı?',
                    'q7' => 'Yakıt kaçağı var mı?',
                    'q8' => 'Yakıt seviyesi gösterme paneli uygun mu?',
                    'q9' => 'Akü şarj ünitesi uygun mu?',
                    'q10' => 'Aküde şişme ve sızıntı var mı?',
                    'q11' => 'Kablolar uygun mu?',
                    'q12' => 'Yetkili servis bakım kayıtları düzenli olarak tutuluyor mu?'
                ];
            } elseif ($type === 'yangin_tesisat') {
                $checklist_questions = [
                    'q1' => 'Yangın söndürme tesisatının projesi mevcut mu?',
                    'q2' => 'Yangın söndürme tesisatının periyodik kontrol raporu var mı?',
                    'q3' => 'Basınçlandırma ve duman tahliye tesisatının test ve periyodik kontrolü yapılmış mı?',
                    'q4' => 'Güvenlik ve kontrol sistemlerinin bulunduğu yerde, kırmızı zemin üzerine fosforlu sarı veya beyaz renkte "YANGIN 112" yazılmış mı?',
                    'q5' => 'İtfaiye araçlarının gerektiğinde binaya kolaylıkla ulaşımı ve yaklaşması sağlanabilmekte midir?',
                    'q6' => 'Acil durum yönlendirmeleri mevcut mudur?',
                    'q7' => 'Yangın dolaplarına erişim uygun mu, çalışır durumda mı?',
                    'q8' => 'Yangın dolapları boru bağlantı çapı ve vanası uygun mu? (hidrolik hesaplara göre belirlenir-en az 50 mm.)',
                    'q9' => 'Yangın su deposu var mı, dolum ve emme vanaları açık mı?',
                    'q10' => 'Yangın pompaları çalışır durumda mı ve elektrik bağlantısı (kofradan önce) uygun mu?',
                    'q11' => 'Yangın pompalarından ikisi de elektrikli ise en azından asıl pompa jeneratörle %100 beslenebiliyor mu?',
                    'q12' => 'Sprinkler tesisatı varsa, projesine uygun mu?',
                    'q13' => 'Taşınabilir söndürme tüplerinin TS standartlarına göre bakımları yapılmış mı?',
                    'q14' => 'Merdiven sahanlığından yangın merdivenine erişim uygun mu?',
                    'q15' => 'Yangın merdiveni kapıları dışarı açılabiliyor mu?',
                    'q16' => 'Yangın algılama ve ihbar sistemi aktif olarak çalışıyor mu?'
                ];
            }

            if (!empty($checklist_questions)):
                $inspection_results = !empty($row['inspection_results']) ? json_decode($row['inspection_results'], true) : [];
                ?>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                    <thead>
                        <tr style="background-color: #f0f0f0;">
                            <th style="border: 1px solid #ccc; padding: 4px; text-align: left; font-size: 10px;">Kontrol Kriteri</th>
                            <th style="border: 1px solid #ccc; padding: 4px; text-align: center; width: 120px; font-size: 10px;">Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checklist_questions as $key => $text): 
                            $val = $inspection_results[$key] ?? '-';
                            ?>
                            <tr>
                                <td style="border: 1px solid #ccc; padding: 4px; font-size: 10px;"><?php echo htmlspecialchars($text); ?></td>
                                <td style="border: 1px solid #ccc; padding: 4px; text-align: center; font-weight: bold; font-size: 10px;">
                                    <?php echo htmlspecialchars(strtoupper($val)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <?php if ($type === 'topraklama'): 
                // Fetch grounding measurements count
                $stmt_m1 = $pdo->prepare("SELECT COUNT(*) FROM measurements_5_1 WHERE report_id = ?");
                $stmt_m1->execute([$id]);
                $count51 = $stmt_m1->fetchColumn();

                $stmt_m2 = $pdo->prepare("SELECT COUNT(*) FROM measurements_5_2 WHERE report_id = ?");
                $stmt_m2->execute([$id]);
                $count52 = $stmt_m2->fetchColumn();
                ?>
                <div style="background-color: #fdfdfd; border: 1px solid #ccc; padding: 8px; margin-bottom: 8px;">
                    <strong>Ölçüm Sonuçları Özeti:</strong>
                    <ul style="margin: 4px 0; padding-left: 18px;">
                        <li>Çevrim Empedansı / Topraklama Direnci Ölçüm Noktası Sayısı: <strong><?php echo $count51; ?> adet</strong></li>
                        <li>Hata Akımı Koruma Düzenekleri (RCD) Test Sayısı: <strong><?php echo $count52; ?> adet</strong></li>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($type === 'ic_tesisat'): 
                // Fetch panel info
                $stmt_panels = $pdo->prepare("SELECT COUNT(*) FROM ic_tesisat_panels WHERE report_id = ?");
                $stmt_panels->execute([$id]);
                $countPanels = $stmt_panels->fetchColumn();
                ?>
                <div style="background-color: #fdfdfd; border: 1px solid #ccc; padding: 8px; margin-bottom: 8px;">
                    <strong>İç Tesisat Dağıtım Panoları Özeti:</strong>
                    <ul style="margin: 4px 0; padding-left: 18px;">
                        <li>Kontrol Edilen Dağıtım/Tali Pano Sayısı: <strong><?php echo $countPanels; ?> adet</strong></li>
                    </ul>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>

        <!-- Hata ve Kusurlar / Öneriler -->
        <?php if (!empty($defects) || !empty($notes) || !empty($row['result_text'])): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <?php if (!empty($row['result_text'])): ?>
                    <tr>
                        <td style="width: 20%; border: 1px solid #ccc; padding: 4px; background-color: #f2f2f2; font-weight: bold; font-size: 10px;">Sonuç Değerlendirmesi</td>
                        <td style="border: 1px solid #ccc; padding: 4px; font-style: italic; font-size: 10px;"><?php echo nl2br(htmlspecialchars($row['result_text'])); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if (!empty($defects)): ?>
                    <tr>
                        <td style="width: 20%; border: 1px solid #ccc; padding: 4px; background-color: #f2f2f2; font-weight: bold; color: red; font-size: 10px;">Tespit Edilen Eksiklikler</td>
                        <td style="border: 1px solid #ccc; padding: 4px; color: red; font-weight: bold; font-size: 10px;"><?php echo nl2br(htmlspecialchars($defects)); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if (!empty($notes)): ?>
                    <tr>
                        <td style="width: 20%; border: 1px solid #ccc; padding: 4px; background-color: #f2f2f2; font-weight: bold; font-size: 10px;">Notlar ve Öneriler</td>
                        <td style="border: 1px solid #ccc; padding: 4px; font-size: 10px;"><?php echo nl2br(htmlspecialchars($notes)); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        <?php endif; ?>
    </div>
    <p>&nbsp;</p>
    <?php

} catch (PDOException $e) {
    echo '<p style="color:red; font-weight:bold;">Veritabanı Hatası: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
