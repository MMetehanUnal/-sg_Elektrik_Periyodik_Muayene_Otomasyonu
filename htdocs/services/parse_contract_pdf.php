<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

requireLogin();

function tr_upper($str) {
    $map = [
        'i' => 'İ', 'ı' => 'I', 'ş' => 'Ş', 'ğ' => 'Ğ', 'ç' => 'Ç', 'ü' => 'Ü', 'ö' => 'Ö'
    ];
    $str = str_replace(array_keys($map), array_values($map), $str);
    return mb_strtoupper($str, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['contract_pdf'])) {
    $file = $_FILES['contract_pdf'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Dosya yüklenirken hata oluştu. Hata kodu: ' . $file['error']]);
        exit;
    }
    
    $upload_dir = '../uploads/sozlesmeler/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = 'contract_' . time() . '_' . rand(1000, 9999) . '.pdf';
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        require_once '../includes/class.pdf2text.php';
        
        $pdf2text = new PDF2Text();
        $pdf2text->setFilename($target_path);
        $pdf2text->decodePDF();
        $text = $pdf2text->output();
        
        if (empty($text)) {
            if (file_exists($target_path)) {
                unlink($target_path);
            }
            echo json_encode(['error' => 'PDF dosyasından metin ayıklanamadı veya dosya boş.']);
            exit;
        }
        
        $text_upper = tr_upper($text);
        
        // Split into lines
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        
        // Clean parser variables
        $start_date = null;
        $city_code = "01";
        $city_name = null;
        $firma_adi = null;
        $adres = null;
        $sgk_no = null;
        $isg_katip_id = null;
        
        // 1. SGK No (Global 20-30 digits search)
        if (preg_match('/\b\d{20,30}\b/', $text, $m_sgk)) {
            $sgk_no = $m_sgk[0];
        }
        
        // 2. ISG-KATIP ID (8 digits near "SÖZLEŞME ID")
        foreach ($lines as $i => $line) {
            $line_upper = tr_upper($line);
            if (strpos($line_upper, "SÖZLEŞME ID") !== false) {
                for ($j = -5; $j <= 10; $j++) {
                    if (isset($lines[$i + $j])) {
                        $val = preg_replace('/\s+/', '', $lines[$i + $j]);
                        if (preg_match('/^\d{8}$/', $val)) {
                            $isg_katip_id = $val;
                            break;
                        }
                    }
                }
            }
        }
        
        // 3. Start Date (Date near "BAŞLANGIÇ")
        foreach ($lines as $i => $line) {
            $line_upper = tr_upper($line);
            if (strpos($line_upper, "BAŞLANGIÇ") !== false || strpos($line_upper, "BAŞLANGIC") !== false) {
                for ($j = -5; $j <= 5; $j++) {
                    if (isset($lines[$i + $j])) {
                        if (preg_match('/(\d{2})[.\/-](\d{2})[.\/-](\d{4})/', $lines[$i + $j], $m_date)) {
                            $start_date = "{$m_date[3]}-{$m_date[2]}-{$m_date[1]}";
                            break;
                        }
                    }
                }
            }
        }
        
        // Fallback for start date
        if (!$start_date) {
            if (preg_match_all('/(\d{2})[.\/-](\d{2})[.\/-](\d{4})/', $text, $m_all)) {
                if (isset($m_all[3][0]) && isset($m_all[2][0]) && isset($m_all[1][0])) {
                    $start_date = "{$m_all[3][0]}-{$m_all[2][0]}-{$m_all[1][0]}";
                }
            }
        }
        
        // 4. Firma Adı (Lines containing company keywords, excluding contract headers)
        $company_keywords = ['ŞİRKETİ', 'LTD', 'ŞTİ', 'A.Ş', 'ORTAKLIĞI', 'KOOP', 'SANAYİ VE TİCARET', 'TİC. LTD'];
        foreach ($lines as $line) {
            $line_upper = tr_upper($line);
            $is_company = false;
            foreach ($company_keywords as $kw) {
                if (strpos($line_upper, $kw) !== false) {
                    $is_company = true;
                    break;
                }
            }
            if ($is_company && strpos($line_upper, "SÖZLEŞMESİ") === false && strlen($line) > 5) {
                $firma_adi = $line;
                break;
            }
        }
        
        // 5. Adres (Lines containing address keywords, excluding the company name itself)
        $address_keywords = ['MAH.', 'MAHALLESİ', 'CAD.', 'CADDESİ', 'SOK.', 'SOKAĞI', 'NO:', 'OSB', 'BULVARI', 'BLV'];
        foreach ($lines as $line) {
            $line_upper = tr_upper($line);
            $is_address = false;
            foreach ($address_keywords as $kw) {
                if (strpos($line_upper, $kw) !== false) {
                    $is_address = true;
                    break;
                }
            }
            if ($is_address && $line !== $firma_adi && strlen($line) > 10) {
                $adres = $line;
                break;
            }
        }
        
        // 6. City Code
        $PLATE_CODES = [
            "ADANA" => "01", "ADIYAMAN" => "02", "AFYONKARAHİSAR" => "03", "AFYON" => "03", "AĞRI" => "04", "AMASYA" => "05", "ANKARA" => "06", 
            "ANTALYA" => "07", "ARTVİN" => "08", "AYDIN" => "09", "BALIKESİR" => "10", "BİLECİK" => "11", "BİNGÖL" => "12", "BİTLİS" => "13", 
            "BOLU" => "14", "BURDUR" => "15", "BURSA" => "16", "ÇANAKKALE" => "17", "ÇANKIRI" => "18", "ÇORUM" => "19", "DENİZLİ" => "20", 
            "DİYARBAKIR" => "21", "EDİRNE" => "22", "ELAZIĞ" => "23", "ERZİNCAN" => "24", "ERZURUM" => "25", "ESKİŞEHİR" => "26", 
            "GAZİANTEP" => "27", "GİRESUN" => "28", "GÜMÜŞHANE" => "29", "HAKKARİ" => "30", "HATAY" => "31", "ISPARTA" => "32", 
            "MERSİN" => "33", "İÇEL" => "33", "İSTANBUL" => "34", "İZMİR" => "35", "KARS" => "36", "KASTAMONU" => "37", "KAYSERİ" => "38", 
            "KIRKLARELİ" => "39", "KIRŞEHİR" => "40", "KOCAELİ" => "41", "KONYA" => "42", "KÜTAHYA" => "43", "MALATYA" => "44", "MANİSA" => "45", 
            "KAHRAMANMARAŞ" => "46", "MARAŞ" => "46", "MARDİN" => "47", "MUĞLA" => "48", "MUŞ" => "49", "NEVŞEHİR" => "50", "NİĞDE" => "51", 
            "ORDU" => "52", "RİZE" => "53", "SAKARYA" => "54", "SAMSUN" => "55", "SİİRT" => "56", "SİNOP" => "57", "SİVAS" => "58", 
            "TEKİRDAĞ" => "59", "TOKAT" => "60", "TRABZON" => "61", "TUNCELİ" => "62", "ŞANLIURFA" => "63", "URFA" => "63", "UŞAK" => "64", 
            "VAN" => "65", "YOZGAT" => "66", "ZONGULDAK" => "67", "AKSARAY" => "68", "BAYBURT" => "69", "KARAMAN" => "70", "KIRIKKALE" => "71", 
            "BATMAN" => "72", "ŞIRNAK" => "73", "BARTIN" => "74", "ARDAHAN" => "75", "IĞDIR" => "76", "YALOVA" => "77", "KARABÜK" => "78", 
            "KİLİS" => "79", "OSMANİYE" => "80", "DÜZCE" => "81"
        ];
        
        foreach ($lines as $line) {
            $line_upper = tr_upper($line);
            if (strpos($line_upper, "İL") !== false && strpos($line, ":") !== false) {
                $parts = explode(":", $line);
                if (count($parts) > 1) {
                    $possible_city = tr_upper(trim($parts[1]));
                    if (isset($PLATE_CODES[$possible_city])) {
                        $city_name = $possible_city;
                        $city_code = $PLATE_CODES[$possible_city];
                        break;
                    }
                }
            }
        }
        
        if (!$city_name) {
            foreach ($PLATE_CODES as $city => $code) {
                if (preg_match('/\b' . preg_quote($city, '/') . '\b/u', $text_upper)) {
                    $city_name = $city;
                    $city_code = $code;
                    break;
                }
            }
        }
        
        // Return JSON
        echo json_encode([
            "start_date" => $start_date,
            "city_name" => $city_name,
            "city_code" => $city_code,
            "firma_adi" => $firma_adi,
            "adres" => $adres,
            "sgk_no" => $sgk_no,
            "isg_katip_id" => $isg_katip_id,
            "contract_pdf" => $filename
        ]);
        
    } else {
        echo json_encode(['error' => 'Dosya geçici olarak kaydedilemedi.']);
    }
} else {
    echo json_encode(['error' => 'Geçersiz istek metodu.']);
}
?>
