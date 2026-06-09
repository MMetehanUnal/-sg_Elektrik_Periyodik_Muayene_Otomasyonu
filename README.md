<p align="center">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Bootstrap_5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap 5">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

# ⚡ İSG Elektrik Periyodik Muayene Otomasyonu

> **İş Sağlığı ve Güvenliği (İSG) kapsamında elektrik tesisatlarının periyodik muayene ve kontrol raporlarını dijital ortamda oluşturmak, yönetmek ve yazdırmak için geliştirilmiş web tabanlı otomasyon sistemidir.**

İSG mevzuatı gereği işyerlerinin elektrik tesisatlarına yönelik düzenli aralıklarla yapılması gereken periyodik kontrol ve muayene süreçlerini dijitalleştirerek; zaman kaybını en aza indirmeyi, rapor standartlaştırmasını sağlamayı ve arşivleme kolaylığı sunmayı hedefler.

---

## 📋 İçindekiler

- [Özellikler](#-özellikler)
- [Kontrol Modülleri](#-kontrol-modülleri)
- [Teknolojiler](#-teknolojiler)
- [Gereksinimler](#-gereksinimler)
- [Kurulum](#-kurulum)
- [Veritabanı Kurulumu](#-veritabanı-kurulumu)
- [Proje Yapısı](#-proje-yapısı)
- [Kullanım](#-kullanım)
- [Ekran Görüntüleri](#-ekran-görüntüleri)
- [Katkıda Bulunma](#-katkıda-bulunma)
- [Lisans](#-lisans)

---

## ✨ Özellikler

### Genel
- 🔐 **Kullanıcı Kimlik Doğrulama** — Güvenli oturum yönetimi (giriş/çıkış)
- 🏢 **Çoklu Kurum Yönetimi** — Birden fazla tesis/firma kaydı ve yönetimi
- 🔀 **Tesis Seçimi** — Aktif çalışılacak tesisi hızlıca seçme ve değiştirme
- 👤 **Yetkili Kişi Yönetimi** — Kontrol personeli kayıtlarının tutulması
- 🔧 **Ölçüm Cihazı Yönetimi** — Kalibrasyon bilgileri dahil cihaz kaydı
- 🖼️ **Logo Yönetimi** — Raporlarda kullanılacak metin veya resim logosu desteği
- ⚙️ **Sistem Ayarları** — Merkezi yapılandırma paneli

### Raporlama
- 📊 **Kapsamlı Rapor Modülleri** — 4 farklı kontrol türü için ayrı rapor formları
- 🖨️ **Yazdırılabilir Raporlar** — Tarayıcıdan doğrudan yazdırmaya uygun rapor çıktıları
- 📁 **CSV İçe Aktarma** — Toplu veri girişi için CSV dosyasından veri yükleme
- 📈 **Merkezi Rapor Listesi** — Tüm rapor türlerini tek sayfada görüntüleme ve yönetme

---

## 🔌 Kontrol Modülleri

| Modül | Açıklama | Kapsam |
|-------|----------|--------|
| **⏚ Topraklama Kontrolü** | Topraklama direnci ölçümleri ve değerlendirmeleri | Topraklama ölçüm noktaları, direnç değerleri |
| **🏠 İç Tesisat Kontrolü** | Elektrik iç tesisat denetimi | Panel kontrolleri, devre kontrolleri, termal görüntüleme |
| **⚡ Yıldırımdan Korunma** | Yıldırımdan korunma sistemi kontrolü | Paratoner, iletken, topraklama bağlantıları |
| **🔥 Yangın Algılama** | Yangın algılama ve uyarı sistemi kontrolü | Dedektörler, butonlar, sirenler, santral |

Her modül için:
- ✅ Detaylı kontrol formu
- ✅ Sonuç değerlendirme sayfası
- ✅ Yazdırılabilir rapor çıktısı
- ✅ Düzenleme ve güncelleme imkânı

---

## 🛠️ Teknolojiler

| Katman | Teknoloji |
|--------|-----------|
| **Backend** | PHP 7.4+ (Vanilla PHP) |
| **Veritabanı** | MySQL / MariaDB |
| **Frontend** | HTML5, CSS3, JavaScript |
| **UI Framework** | Bootstrap 5.3 |
| **İkon Seti** | Font Awesome 6.4 |
| **Veri Erişim** | PDO (Prepared Statements) |

---

## 📦 Gereksinimler

- **PHP** 7.4 veya üzeri
- **MySQL** 5.7+ veya **MariaDB** 10.3+
- **Apache** (mod_rewrite aktif) veya benzeri web sunucusu
- PHP GD kütüphanesi (logo resim sıkıştırma için)
- Tarayıcı: Chrome, Firefox, Edge (güncel sürüm)

---

## 🚀 Kurulum

### 1. Projeyi Klonlayın

```bash
git clone https://github.com/KullaniciAdiniz/Isg_Elektrik_Periyodik_Muayene_Otomasyonu.git
```

### 2. Web Sunucusu Dizinine Taşıyın

`htdocs` klasörünün içeriğini web sunucunuzun kök dizinine kopyalayın:

```bash
# XAMPP için:
cp -r htdocs/* C:/xampp/htdocs/

# WAMP için:
cp -r htdocs/* C:/wamp64/www/

# Linux (Apache) için:
sudo cp -r htdocs/* /var/www/html/
```

### 3. Veritabanı Yapılandırması

`htdocs/includes/db.example.php` dosyasını `db.php` olarak kopyalayın ve kendi bilgilerinize göre düzenleyin:

```bash
cp htdocs/includes/db.example.php htdocs/includes/db.php
```

```php
<?php
$host = 'localhost';        // Veritabanı sunucusu
$dbname = 'factory_automation'; // Veritabanı adı
$username = 'root';         // Veritabanı kullanıcı adı
$password = '';             // Veritabanı şifresi
?>
```

### 4. Yazma İzinleri

Yükleme dizinlerine yazma izni verin:

```bash
chmod -R 755 htdocs/uploads/
```

---

## 🗄️ Veritabanı Kurulumu

`factory_automation` adında bir MySQL veritabanı oluşturun ve aşağıdaki tabloları oluşturun:

```sql
CREATE DATABASE IF NOT EXISTS factory_automation
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE factory_automation;

-- Kullanıcılar
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kurumlar / Tesisler
CREATE TABLE institutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    firma_adi VARCHAR(255) NOT NULL,
    adresi TEXT,
    sgk_sicil_no VARCHAR(50),
    il_kodu VARCHAR(2) DEFAULT '01',
    kurum_kodu VARCHAR(3),
    isg_katip_id VARCHAR(100),
    report_date DATE,
    start_date DATETIME,
    end_date DATETIME,
    next_control_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Yetkili Kişiler
CREATE TABLE authorized_persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adi_soyadi VARCHAR(255) NOT NULL,
    meslegi VARCHAR(255),
    kayit_no VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ölçüm Cihazları
CREATE TABLE measurement_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_name VARCHAR(255) NOT NULL,
    serial_no VARCHAR(100),
    cal_date DATE,
    validity_date DATE,
    cal_no VARCHAR(100),
    is_thermal_camera TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sistem Ayarları
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Yüklenen Logolar
CREATE TABLE uploaded_logos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Topraklama Raporları
CREATE TABLE grounding_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kurum_id INT NOT NULL,
    report_no VARCHAR(100),
    report_date DATE,
    control_reason VARCHAR(255),
    result VARCHAR(50),
    authorized_person_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES institutions(id),
    FOREIGN KEY (authorized_person_id) REFERENCES authorized_persons(id)
);

-- İç Tesisat Raporları
CREATE TABLE internal_installation_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kurum_id INT NOT NULL,
    report_no VARCHAR(100),
    report_date DATE,
    control_reason VARCHAR(255),
    result VARCHAR(50),
    authorized_person_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES institutions(id),
    FOREIGN KEY (authorized_person_id) REFERENCES authorized_persons(id)
);

-- Yıldırımdan Korunma Raporları
CREATE TABLE lightning_protection_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kurum_id INT NOT NULL,
    report_no VARCHAR(100),
    report_date DATE,
    control_reason VARCHAR(255),
    result VARCHAR(50),
    authorized_person_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES institutions(id),
    FOREIGN KEY (authorized_person_id) REFERENCES authorized_persons(id)
);

-- Yangın Algılama Raporları
CREATE TABLE fire_detection_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kurum_id INT NOT NULL,
    report_no VARCHAR(100),
    report_date DATE,
    control_reason VARCHAR(255),
    result VARCHAR(50),
    authorized_person_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES institutions(id),
    FOREIGN KEY (authorized_person_id) REFERENCES authorized_persons(id)
);

-- Varsayılan Admin Kullanıcısı (şifre: admin123)
INSERT INTO users (username, password, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Varsayılan Sistem Ayarları
INSERT INTO system_settings (setting_key, setting_value) VALUES
('logo_text', 'LOGO'),
('logo_type', 'text'),
('active_logo', '');
```

> **⚠️ Not:** Varsayılan admin şifresi `admin123` olarak ayarlanmıştır. İlk girişten sonra mutlaka değiştirin. Yukarıdaki SQL şeması temel tabloları içermektedir; rapor detay tabloları (ölçüm sonuçları vb.) ilk rapor oluşturulduğunda otomatik olarak oluşturulabilir veya projedeki migration dosyaları ile eklenebilir.

---

## 📁 Proje Yapısı

```
İsg_Elektrik_Periyodik_Muayene_Otomasyonu/
├── 📄 README.md
├── 📄 .gitignore
├── 📄 .gitattributes
│
└── htdocs/                          # Web sunucu kök dizini
    ├── 📄 index.php                 # Ana giriş noktası (dashboard'a yönlendirir)
    ├── 📄 login.php                 # Kullanıcı giriş sayfası
    ├── 📄 logout.php                # Oturum kapatma
    │
    ├── assets/                      # Statik dosyalar
    │   ├── css/
    │   │   ├── style.css            # Ana stil dosyası
    │   │   └── rapor.css            # Rapor yazdırma stilleri
    │   └── js/
    │       └── main.js              # Sidebar ve UI etkileşimleri
    │
    ├── includes/                    # PHP dahil dosyaları
    │   ├── db.php                   # Veritabanı bağlantısı (git'e eklenmez)
    │   ├── db.example.php           # Veritabanı yapılandırma örneği
    │   ├── auth.php                 # Oturum ve yetkilendirme fonksiyonları
    │   ├── functions.php            # Genel yardımcı fonksiyonlar
    │   ├── header.php               # Sayfa başlığı, navbar ve sidebar
    │   └── footer.php               # Sayfa alt bilgisi ve JS yükleme
    │
    ├── pages/                       # Uygulama sayfaları
    │   ├── dashboard.php            # Ana panel
    │   ├── kurumlar.php             # Kurum/tesis CRUD yönetimi
    │   ├── tesis_secimi.php         # Aktif tesis seçimi
    │   ├── yetkili_kisiler.php      # Yetkili personel yönetimi
    │   ├── cihazlar.php             # Ölçüm cihazları yönetimi
    │   ├── raporlar.php             # Tüm raporların listesi
    │   ├── ayarlar.php              # Sistem ayarları ve logo yönetimi
    │   │
    │   ├── forms/                   # Kontrol/denetim formları
    │   │   ├── tesis_bilgileri.php           # Tesis bilgi formu
    │   │   ├── topraklama_kontrol.php        # Topraklama kontrol formu
    │   │   ├── ic_tesisat_kontrol.php        # İç tesisat kontrol formu
    │   │   ├── yildirimdan_korunma_kontrol.php # Yıldırımdan korunma formu
    │   │   ├── yangin_algilama_kontrol.php   # Yangın algılama formu
    │   │   ├── csv_handler.php              # CSV veri içe aktarma
    │   │   └── ...                          # Ölçüm ve sonuç formları
    │   │
    │   ├── results/                 # Sonuç görüntüleme sayfaları
    │   │   ├── topraklama_sonuclar.php
    │   │   ├── ic_tesisat_sonuclar.php
    │   │   ├── yildirimdan_korunma_sonuclar.php
    │   │   └── yangin_algilama_sonuclar.php
    │   │
    │   ├── rapor_yazdir.php                 # Topraklama raporu yazdırma
    │   ├── ic_tesisat_yazdir.php             # İç tesisat raporu yazdırma
    │   ├── yildirimdan_korunma_yazdir.php    # Yıldırımdan korunma yazdırma
    │   └── yangin_algilama_yazdir.php        # Yangın algılama yazdırma
    │
    └── uploads/                     # Kullanıcı yüklemeleri (git'e eklenmez)
        ├── logos/                   # Rapor logo resimleri
        └── ic_tesisat/              # İç tesisat görselleri
```

---

## 💻 Kullanım

### İlk Giriş

1. Tarayıcınızda `http://localhost/` adresine gidin
2. Varsayılan yönetici bilgileri ile giriş yapın:
   - **Kullanıcı adı:** `admin`
   - **Şifre:** `admin123`

### Temel İş Akışı

```
1. Giriş Yap
       ↓
2. Kurum / Tesis Kaydı Oluştur (Kurumlar sayfası)
       ↓
3. Yetkili Kişileri Ekle (Yetkili Kişiler sayfası)
       ↓
4. Ölçüm Cihazlarını Kaydet (Cihazlar sayfası)
       ↓
5. Tesis Seçimi Yap (Tesis Seçimi sayfası)
       ↓
6. Periyodik Kontrol Formlarını Doldur
   ├── Topraklama Kontrolü
   ├── İç Tesisat Kontrolü
   ├── Yıldırımdan Korunma Kontrolü
   └── Yangın Algılama Kontrolü
       ↓
7. Sonuçları İncele ve Değerlendir
       ↓
8. Raporu Yazdır / PDF Olarak Kaydet
```

### Logo Ayarları

Sistem Ayarları sayfasından raporlarda görünecek logoyu yapılandırabilirsiniz:
- **Metin Logosu**: Şirket adınızı metin olarak yazdırır
- **Resim Logosu**: Yüklediğiniz logo resmini kullanır (PNG, JPG, WEBP desteklenir)

---

## 📸 Ekran Görüntüleri

> 📌 Ekran görüntüleri yakında eklenecektir.

<!--
Ekran görüntülerini `screenshots/` klasörüne ekleyip aşağıdaki gibi gösterebilirsiniz:

![Dashboard](screenshots/dashboard.png)
![Rapor Formu](screenshots/rapor_formu.png)
![Yazdırma Önizleme](screenshots/yazdir.png)
-->

---

## 🤝 Katkıda Bulunma

Katkılarınızı memnuniyetle karşılıyoruz! Lütfen aşağıdaki adımları izleyin:

1. Bu repository'yi **fork** edin
2. Yeni bir **branch** oluşturun (`git checkout -b feature/YeniOzellik`)
3. Değişikliklerinizi **commit** edin (`git commit -m 'feat: Yeni özellik eklendi'`)
4. Branch'inizi **push** edin (`git push origin feature/YeniOzellik`)
5. Bir **Pull Request** açın

### Geliştirme Notları

- Kod PHP 7.4+ uyumluluğu korunmalıdır
- Veritabanı sorguları **PDO prepared statements** ile yazılmalıdır
- Frontend bileşenleri **Bootstrap 5** ile uyumlu olmalıdır
- Türkçe karakter desteği için **UTF-8** encoding kullanılmalıdır

---

## 🔒 Güvenlik

- Kullanıcı şifreleri `password_hash()` / `password_verify()` ile şifrelenir
- SQL Injection koruması PDO prepared statements ile sağlanır
- XSS koruması `htmlspecialchars()` ve `cleanInput()` fonksiyonları ile sağlanır
- Oturum yönetimi PHP native session mekanizması ile yapılır

> ⚠️ **Uyarı:** Bu proje bir geliştirme/prototip aşamasındadır. Üretim ortamında kullanmadan önce kapsamlı bir güvenlik denetimi yapılması önerilir. `db.php` dosyasını asla doğrudan GitHub'a yüklemeyin.

---

## 📄 Lisans

Bu proje [MIT Lisansı](LICENSE) ile lisanslanmıştır.

---

## 📞 İletişim

Sorularınız veya önerileriniz için bir **Issue** açabilirsiniz.

---

<p align="center">
  <sub>⚡ İSG Elektrik Periyodik Muayene Otomasyonu ile geliştiren</sub>
  <br>
  <sub>İş güvenliği dijitalleşiyor 🇹🇷</sub>
</p>
