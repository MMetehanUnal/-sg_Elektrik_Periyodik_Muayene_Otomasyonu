<?php
//giriş verilerini temizlemek için kullanılır(güvenlik + temizlik)
function cleanInput($data)
{
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
//yönlendirme yapmak için kullanılır
function redirect($url)
{
    header("Location: $url");
    exit;
}
//kurum oturumu için kullanılır
function getActiveInstitution()
{
    return isset($_SESSION['active_institution_id']) ? $_SESSION['active_institution_id'] : null;
}

function getActiveInstitutionName($pdo)
{
    $id = getActiveInstitution();
    if (!$id)
        return null;

    $stmt = $pdo->prepare("SELECT firma_adi FROM institutions WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}

function compressImage($source_path, $destination_path, $quality = 70, $max_dim = 800) {
    if (!file_exists($source_path)) return false;
    $info = @getimagesize($source_path);
    if ($info == false) return false;
    
    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $image = @imagecreatefromgif($source_path);
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    if (!$image) return false;
    
    $width = $info[0];
    $height = $info[1];
    
    if ($width > $max_dim || $height > $max_dim) {
        if ($width > $height) {
            $new_width = $max_dim;
            $new_height = intval($height * ($max_dim / $width));
        } else {
            $new_height = $max_dim;
            $new_width = intval($width * ($max_dim / $height));
        }
    } else {
        $new_width = $width;
        $new_height = $height;
    }
    
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    if ($mime == 'image/png' || $mime == 'image/gif' || $mime == 'image/webp') {
        imagecolortransparent($new_image, imagecolorallocatealpha($new_image, 0, 0, 0, 127));
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }
    
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    $result = false;
    switch ($mime) {
        case 'image/jpeg':
            $result = imagejpeg($new_image, $destination_path, $quality);
            break;
        case 'image/png':
            $result = imagepng($new_image, $destination_path, 8); // PNG size 0-9
            break;
        case 'image/gif':
            $result = imagegif($new_image, $destination_path);
            break;
        case 'image/webp':
            $result = imagewebp($new_image, $destination_path, $quality);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($new_image);
    
    return $result;
}

function getSetting($pdo, $key, $default = '')
{
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? $val : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function setSetting($pdo, $key, $value)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        return $stmt->execute([$key, $value, $value]);
    } catch (PDOException $e) {
        return false;
    }
}

function getFacilityDefaults($pdo, $kurum_id)
{
    static $defaults = null;
    if ($defaults === null) {
        try {
            $stmt = $pdo->prepare("SELECT default_authorized_person_id, default_device_id, default_thermal_device_id FROM facility_info WHERE kurum_id = ?");
            $stmt->execute([$kurum_id]);
            $defaults = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $defaults = null;
        }
        if (!$defaults) {
            $defaults = [
                'default_authorized_person_id' => null,
                'default_device_id' => null,
                'default_thermal_device_id' => null
            ];
        }
    }
    return $defaults;
}
?>