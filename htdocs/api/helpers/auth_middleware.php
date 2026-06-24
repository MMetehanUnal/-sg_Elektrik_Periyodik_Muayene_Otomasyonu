<?php
// ============================================================
// API Kimlik Doğrulama Middleware
// ============================================================
// Her korunan endpoint'in başında çağrılır.
// Authorization header'dan Bearer token'ı okur ve doğrular.
// ============================================================

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/response.php';

/**
 * İsteği doğrula — Token kontrol et
 * Başarılıysa $GLOBALS['api_user'] set edilir.
 * Başarısızsa 401 döndürüp exit eder.
 */
function authenticateRequest() {
    $authHeader = null;

    // Authorization header'ı al
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        // Apache mod_rewrite durumunda
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        }
    }

    if (!$authHeader) {
        jsonError('AUTH_REQUIRED', 'Authorization header gereklidir.', 401);
    }

    // Bearer token formatı kontrol
    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        jsonError('AUTH_INVALID', 'Geçersiz Authorization formatı. "Bearer <token>" bekleniyor.', 401);
    }

    $token = $matches[1];

    // JWT doğrula
    $result = jwtValidate($token);
    if (!$result['valid']) {
        $message = $result['error'] === 'AUTH_EXPIRED'
            ? 'Token süresi dolmuş. Lütfen tekrar giriş yapın.'
            : 'Geçersiz token.';
        jsonError($result['error'], $message, 401);
    }

    // Kullanıcı bilgilerini global'e yaz
    $GLOBALS['api_user'] = [
        'user_id' => $result['payload']['user_id'],
        'username' => $result['payload']['username'],
        'role' => $result['payload']['role']
    ];
}

/**
 * Mevcut oturumdaki kullanıcı bilgisini al
 *
 * @return array ['user_id' => int, 'username' => string, 'role' => string]
 */
function getApiUser() {
    return $GLOBALS['api_user'] ?? null;
}

/**
 * Kullanıcının admin olduğunu doğrula
 */
function requireApiAdmin() {
    $user = getApiUser();
    if (!$user || $user['role'] !== 'admin') {
        jsonError('FORBIDDEN', 'Bu işlem için yönetici yetkisi gereklidir.', 403);
    }
}

/**
 * X-Institution-Id header'ından kurum ID'sini oku ve sahipliğini doğrula
 *
 * @param PDO $pdo Veritabanı bağlantısı
 * @return int Doğrulanmış kurum ID
 */
function requireInstitution($pdo) {
    $institutionId = null;

    // Header'dan oku
    if (isset($_SERVER['HTTP_X_INSTITUTION_ID'])) {
        $institutionId = intval($_SERVER['HTTP_X_INSTITUTION_ID']);
    }

    // Query param'dan da kabul et (alternatif)
    if (!$institutionId && isset($_GET['kurum_id'])) {
        $institutionId = intval($_GET['kurum_id']);
    }

    // JSON body'den de kabul et
    if (!$institutionId) {
        $body = getJsonBody();
        if (isset($body['kurum_id'])) {
            $institutionId = intval($body['kurum_id']);
        }
    }

    if (!$institutionId) {
        jsonError('NO_ACTIVE_INSTITUTION', 'Kurum ID gereklidir. X-Institution-Id header veya kurum_id parametresi gönderin.', 400);
    }

    // Sahiplik doğrula
    $user = getApiUser();
    $stmt = $pdo->prepare("SELECT id, firma_adi FROM institutions WHERE id = ? AND user_id = ?");
    $stmt->execute([$institutionId, $user['user_id']]);
    $institution = $stmt->fetch();

    if (!$institution) {
        jsonError('INSTITUTION_ACCESS_DENIED', 'Bu kuruma erişim yetkiniz yok veya kurum bulunamadı.', 403);
    }

    $GLOBALS['api_institution'] = $institution;
    return $institutionId;
}

/**
 * Aktif kurum bilgisini al (requireInstitution çağrıldıktan sonra)
 *
 * @return array|null ['id' => int, 'firma_adi' => string]
 */
function getApiInstitution() {
    return $GLOBALS['api_institution'] ?? null;
}
