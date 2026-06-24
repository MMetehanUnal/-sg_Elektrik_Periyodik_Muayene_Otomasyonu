<?php
// ============================================================
// API Yapılandırma Dosyası (ÖRNEK)
// ============================================================
// Bu dosyayı "config.php" olarak kopyalayın ve değerlerinizi girin.
// config.php dosyası .gitignore'a eklenmiştir.
//
//   cp api/config.example.php api/config.php
// ============================================================

// JWT Ayarları
define('JWT_SECRET', 'BURAYA-GUCLU-BIR-ANAHTAR-GIRIN-EN-AZ-32-KARAKTER');
define('JWT_EXPIRY', 86400); // Token geçerlilik süresi (saniye) — 86400 = 24 saat
define('JWT_ISSUER', 'isg-otomasyon-api');

// API Ayarları
define('API_VERSION', 'v1');
define('API_DEBUG', false); // Üretimde false olmalı

// Sayfalama Varsayılanları
define('DEFAULT_PAGE', 1);
define('DEFAULT_PER_PAGE', 20);
define('MAX_PER_PAGE', 100);
