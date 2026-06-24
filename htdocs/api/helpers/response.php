<?php
// ============================================================
// Standart JSON Response Helper
// ============================================================
// Tüm API endpoint'leri bu fonksiyonları kullanarak
// tutarlı formatta JSON yanıt döndürür.
// ============================================================

/**
 * Başarılı yanıt döndür
 *
 * @param mixed  $data    Yanıt verisi
 * @param string $message Mesaj
 * @param int    $code    HTTP durum kodu (200, 201 vb.)
 */
function jsonSuccess($data = null, $message = 'İşlem başarılı', $code = 200) {
    http_response_code($code);
    $response = [
        'success' => true,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Hata yanıtı döndür
 *
 * @param string     $errorCode Hata kodu (AUTH_REQUIRED, VALIDATION_ERROR vb.)
 * @param string     $message   Kullanıcıya gösterilecek mesaj
 * @param int        $httpCode  HTTP durum kodu
 * @param array|null $details   Ek hata detayları
 */
function jsonError($errorCode, $message, $httpCode = 400, $details = null) {
    http_response_code($httpCode);
    $response = [
        'success' => false,
        'error' => [
            'code' => $errorCode,
            'message' => $message
        ]
    ];
    if ($details !== null) {
        $response['error']['details'] = $details;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Sayfalı yanıt döndür
 *
 * @param array $data     Veri listesi
 * @param int   $total    Toplam kayıt sayısı
 * @param int   $page     Mevcut sayfa
 * @param int   $perPage  Sayfa başına kayıt
 */
function jsonPaginated($data, $total, $page, $perPage) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => (int)$page,
            'per_page' => (int)$perPage,
            'total_items' => (int)$total,
            'total_pages' => (int)ceil($total / max($perPage, 1))
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * HTTP Method kontrolü
 *
 * @param array $allowedMethods İzin verilen HTTP metodları ['GET', 'POST', ...]
 */
function requireMethod($allowedMethods) {
    if (!is_array($allowedMethods)) {
        $allowedMethods = [$allowedMethods];
    }

    // OPTIONS preflight her zaman kabul
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
        jsonError(
            'METHOD_NOT_ALLOWED',
            'Bu endpoint yalnızca ' . implode(', ', $allowedMethods) . ' isteklerini kabul eder.',
            405
        );
    }
}

/**
 * JSON body oku (POST/PUT istekleri için)
 *
 * @return array Parsed JSON data
 */
function getJsonBody() {
    $rawBody = file_get_contents('php://input');
    if (empty($rawBody)) {
        return [];
    }
    $data = json_decode($rawBody, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        jsonError('INVALID_JSON', 'Geçersiz JSON formatı.', 400);
    }
    return $data ?: [];
}

/**
 * Sayfalama parametrelerini oku
 *
 * @return array ['page' => int, 'per_page' => int, 'offset' => int]
 */
function getPaginationParams() {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : DEFAULT_PAGE;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : DEFAULT_PER_PAGE;
    $perPage = max(1, min($perPage, MAX_PER_PAGE));

    return [
        'page' => $page,
        'per_page' => $perPage,
        'offset' => ($page - 1) * $perPage
    ];
}
