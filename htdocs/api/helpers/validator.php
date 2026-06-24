<?php
// ============================================================
// Input Validation Helper
// ============================================================
// API endpoint'lerinde giriş verisi doğrulaması.
// ============================================================

/**
 * Zorunlu alanları kontrol et
 *
 * @param array  $data   Kontrol edilecek veri
 * @param array  $fields Zorunlu alan adları
 * @return array Eksik alan adları (boşsa tüm alanlar mevcut)
 */
function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Zorunlu alanları kontrol et — eksik varsa hata döndür
 *
 * @param array $data   Kontrol edilecek veri
 * @param array $fields Zorunlu alan adları
 */
function requireFields($data, $fields) {
    $missing = validateRequired($data, $fields);
    if (!empty($missing)) {
        jsonError(
            'VALIDATION_ERROR',
            'Zorunlu alanlar eksik: ' . implode(', ', $missing),
            400,
            ['missing_fields' => $missing]
        );
    }
}

/**
 * Tarih formatı doğrula (Y-m-d)
 *
 * @param string $value Tarih string
 * @return bool
 */
function isValidDate($value) {
    if (empty($value)) return true; // Boş kabul (opsiyonel alanlar için)
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
}

/**
 * DateTime formatı doğrula (Y-m-d H:i veya Y-m-d\TH:i)
 *
 * @param string $value Tarih-saat string
 * @return bool
 */
function isValidDateTime($value) {
    if (empty($value)) return true;
    // Y-m-d H:i:s formatı
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    if ($d && $d->format('Y-m-d H:i:s') === $value) return true;
    // Y-m-d H:i formatı
    $d = DateTime::createFromFormat('Y-m-d H:i', $value);
    if ($d && $d->format('Y-m-d H:i') === $value) return true;
    // Y-m-dTH:i formatı (HTML5 datetime-local)
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    if ($d && $d->format('Y-m-d\TH:i') === $value) return true;
    return false;
}

/**
 * Integer doğrula
 *
 * @param mixed $value
 * @return bool
 */
function isValidInteger($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

/**
 * Boolean doğrula (0, 1, true, false, "0", "1")
 *
 * @param mixed $value
 * @return bool
 */
function isValidBoolean($value) {
    return in_array($value, [0, 1, true, false, '0', '1', 'true', 'false'], true);
}

/**
 * Değeri boolean'a çevir
 *
 * @param mixed $value
 * @return int 0 veya 1
 */
function toBool($value) {
    if (is_string($value)) {
        return in_array(strtolower($value), ['1', 'true', 'yes', 'evet']) ? 1 : 0;
    }
    return $value ? 1 : 0;
}

/**
 * String'i temizle (XSS koruması)
 *
 * @param mixed $data
 * @return mixed Temizlenmiş veri
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    if (is_string($data)) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

/**
 * Opsiyonel alanları null olarak ayarla (boşsa)
 *
 * @param array $data   Veri
 * @param array $fields Opsiyonel alan adları
 * @return array İşlenmiş veri
 */
function nullifyEmpty($data, $fields) {
    foreach ($fields as $field) {
        if (isset($data[$field]) && (is_string($data[$field]) && trim($data[$field]) === '')) {
            $data[$field] = null;
        }
    }
    return $data;
}

/**
 * ID parametresini query string'den al ve doğrula
 *
 * @param string $param Parametre adı (varsayılan: 'id')
 * @return int|null ID değeri veya null
 */
function getIdParam($param = 'id') {
    if (isset($_GET[$param])) {
        $value = intval($_GET[$param]);
        return $value > 0 ? $value : null;
    }
    return null;
}
