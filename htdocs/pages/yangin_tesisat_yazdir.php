<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? cleanInput($_GET['id']) : null;
if (!$id) {
    die("Rapor ID gerekli.");
}

// Fetch Yangin Tesisat Report Data
$stmt = $pdo->prepare("
    SELECT r.*, 
           i.firma_adi, i.adresi, i.sgk_sicil_no, i.il_kodu, i.kurum_kodu,
           ap.adi_soyadi, ap.meslegi, ap.kayit_no, ap.diploma_no, ap.oda_sicil_no
    FROM yangin_tesisat_reports r
    JOIN institutions i ON r.kurum_id = i.id
    LEFT JOIN authorized_persons ap ON r.authorized_person_id = ap.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Rapor bulunamadı.");
}

$questions = [
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

$inspection_results = [];
if (!empty($data['inspection_results'])) {
    $inspection_results = json_decode($data['inspection_results'], true);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yangın Tesisatı Güvenliği Raporu: <?php echo htmlspecialchars($data['report_no']); ?></title>
    <link rel="stylesheet" href="../assets/css/rapor.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9.5px;
            line-height: 1.25;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }

        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }

        .no-print button {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            border-radius: 4px;
            font-size: 11px;
        }

        .header-bg {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .section-title-bg {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            padding: 4px 8px;
            border: 1px solid black;
            margin-top: 8px;
            margin-bottom: 4px;
            font-size: 10px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: left;
            vertical-align: middle;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .result-box-compact {
            border: 1px solid #000;
            padding: 6px 8px;
            margin-bottom: 6px;
            font-size: 9.5px;
            background-color: #fff;
        }

        .result-badge {
            font-weight: bold;
            text-decoration: underline;
        }

        .result-badge.safe {
            color: green;
        }

        .result-badge.unsafe {
            color: red;
        }

        .signature-table-wrapper {
            margin-top: 6px;
        }

        .signature-table-wrapper td {
            padding: 0;
        }

        .compact-inner-table {
            width: 100%;
            margin: 0;
            border: none;
        }

        .compact-inner-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 9.5px;
        }

        .compact-inner-table tr:first-child td {
            border-top: none;
        }
        .compact-inner-table tr:last-child td {
            border-bottom: none;
        }
        .compact-inner-table tr td:first-child {
            border-left: none;
        }
        .compact-inner-table tr td:last-child {
            border-right: none;
        }

        /* A4 Page constraints */
        @page {
            size: A4;
            margin: 8mm 10mm 8mm 10mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .section-title-bg, table, .result-box-compact, .signature-table-wrapper {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">
            <i class="fas fa-print"></i> Yazdır / PDF Kaydet
        </button>
    </div>

    <table class="main-report-table" style="border:none; margin:0 auto;">
        <tbody>
            <tr>
                <td style="border:none; padding:0;">
                    <div class="page">
                        
                        <!-- RED BANNER HEADER -->
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 6px;">
                            <tr>
                                <td style="background-color: #ff0000; color: #ffffff; text-align: center; font-weight: bold; font-size: 15px; padding: 12px; border: 1px solid black; text-transform: uppercase;">
                                    YANGIN TESİSAT GÜVENLİĞİ RAPORU
                                </td>
                            </tr>
                        </table>

                        <!-- 1. FİRMA BİLGİLERİ -->
                        <table>
                            <tr>
                                <td style="width: 18%;" class="header-bg fw-bold">Kurum Adı</td>
                                <td style="width: 42%;"><?php echo htmlspecialchars($data['firma_adi'] ?? ''); ?></td>
                                <td style="width: 15%;" class="header-bg fw-bold">Bölümü</td>
                                <td style="width: 25%;"><?php echo htmlspecialchars($data['firma_adi_eki'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Kurum Adresi</td>
                                <td><?php echo htmlspecialchars($data['adresi'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">Kontrol Tarihi</td>
                                <td><?php echo date('d.m.Y', strtotime($data['report_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Kurum Yöneticisi</td>
                                <td><?php echo htmlspecialchars($data['kurum_yoneticisi'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">Bir Sonraki Kontrol</td>
                                <td><?php echo date('d.m.Y', strtotime($data['next_control_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold">Kurum Kapasitesi</td>
                                <td><?php echo htmlspecialchars($data['kurum_kapasitesi'] ?? '-'); ?></td>
                                <td class="header-bg fw-bold">Rapor No</td>
                                <td><?php echo htmlspecialchars($data['report_no'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" style="font-size: 8px; font-style: italic; border-top: 1px solid #000; padding: 2px 5px;">
                                    <strong>İlgili Mevzuat:</strong> Binaların Yangından Korunması Hakkında Yönetmelik & İş Ekipmanlarının Kullanımında Sağlık ve Güvenlik Şartları Yönetmeliği
                                </td>
                            </tr>
                        </table>

                        <!-- 2. TESPİT VE DEĞERLENDİRME SORULARI -->
                        <div class="section-title-bg">TESPİT ve DEĞERLENDİRME SORULARI</div>
                        <table>
                            <thead>
                                <tr class="header-bg">
                                    <th class="text-center" style="width: 5%;">NO</th>
                                    <th style="width: 75%;">SORU</th>
                                    <th class="text-center" style="width: 20%;">DEĞERLENDİRME</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $idx = 1;
                                foreach ($questions as $key => $text): 
                                    $val = $inspection_results[$key] ?? 'UYGUN';
                                ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo $idx++; ?></td>
                                        <td><?php echo htmlspecialchars($text); ?></td>
                                        <td class="text-center fw-bold">
                                            <?php 
                                            if ($val == 'UYGUN') echo 'UYGUN';
                                            else if ($val == 'UYGUN DEĞİL') echo 'UYGUN DEĞİL';
                                            else echo htmlspecialchars($val);
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- 3. ÖNERİLER -->
                        <div class="fw-bold" style="margin-top: 4px; margin-bottom: 2px;">ÖNERİLER :</div>
                        <div class="result-box-compact" style="min-height: 50px;">
                            <?php echo !empty($data['defects']) ? nl2br(htmlspecialchars($data['defects'])) : 'Yapılan periyodik kontrolde herhangi bir eksiklik veya öneri tespit edilmemiştir.'; ?>
                        </div>

                        <!-- 4. GENEL NOTLAR -->
                        <div class="fw-bold" style="margin-top: 4px; margin-bottom: 2px;">NOTLAR :</div>
                        <div class="result-box-compact" style="min-height: 30px;">
                            <?php echo !empty($data['notes']) ? nl2br(htmlspecialchars($data['notes'])) : '-'; ?>
                        </div>

                        <!-- 5. SONUÇ -->
                        <div class="fw-bold" style="margin-top: 4px; margin-bottom: 2px;">SONUÇ :</div>
                        <div class="result-box-compact" style="padding: 6px 8px;">
                            Yukarıda tespit ve değerlendirme sorularına göre adı geçen Yurt Yangın Tesisatı yönünden 
                            <?php if ($data['result'] == 'GÜVENLİDİR' || $data['result'] == 'UYGUNDUR'): ?>
                                <span class="result-badge safe">UYGUNDUR.</span>
                            <?php else: ?>
                                <span class="result-badge unsafe">UYGUN DEĞİLDİR.</span>
                            <?php endif; ?>
                        </div>

                        <!-- 6. KONTROLÜ GERÇEKLEŞTİREN YETKİLİ KİŞİ -->
                        <div class="fw-bold" style="margin-top: 8px; margin-bottom: 2px;">6. KONTROLÜ GERÇEKLEŞTİREN YETKİLİ KİŞİ BİLGİLERİ VE ONAY :</div>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 4px;">
                            <tr>
                                <td class="header-bg fw-bold" style="width: 25%; border: 1px solid #000; padding: 4px 6px;">Adı Soyadı / Unvanı</td>
                                <td style="width: 45%; border: 1px solid #000; padding: 4px 6px;"><?php echo htmlspecialchars($data['adi_soyadi'] ?? '-'); ?> / <?php echo htmlspecialchars($data['meslegi'] ?? '-'); ?></td>
                                <td rowspan="4" style="width: 30%; vertical-align: middle; text-align: center; font-weight: bold; border: 1px solid #000; background-color: #fafafa; padding: 4px 6px;">İMZA / ONAY</td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold" style="border: 1px solid #000; padding: 4px 6px;">Diploma No / Tarih</td>
                                <td style="border: 1px solid #000; padding: 4px 6px;"><?php echo htmlspecialchars($data['diploma_no'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold" style="border: 1px solid #000; padding: 4px 6px;">Oda Sicil No</td>
                                <td style="border: 1px solid #000; padding: 4px 6px;"><?php echo htmlspecialchars($data['oda_sicil_no'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <td class="header-bg fw-bold" style="border: 1px solid #000; padding: 4px 6px;">Bakanlık Sicil No (Kayıt No)</td>
                                <td style="border: 1px solid #000; padding: 4px 6px;"><?php echo htmlspecialchars($data['kayit_no'] ?? '-'); ?></td>
                            </tr>
                        </table>

                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
