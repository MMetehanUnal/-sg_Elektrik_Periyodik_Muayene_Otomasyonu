-- ============================================================
-- İSG Elektrik Periyodik Muayene Otomasyonu - Veritabanı Şeması
-- ============================================================
-- Bu dosya projenin tüm tablo yapılarını içerir.
-- Herhangi bir kullanıcı verisi veya hassas bilgi içermez.
--
-- Kullanım:
--   1. MySQL/MariaDB'de "factory_automation" veritabanını oluşturun
--   2. Bu dosyayı içe aktarın:
--      mysql -u root -p factory_automation < database/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS factory_automation
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE factory_automation;

-- ============================================================
-- 1. TEMEL TABLOLAR
-- ============================================================

-- Kullanıcılar
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kurumlar / Tesisler
CREATE TABLE IF NOT EXISTS institutions (
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tesis Bilgileri
CREATE TABLE IF NOT EXISTS facility_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kurum_id INT NOT NULL,
    enerji_saglayan VARCHAR(255),
    sebeke_tipi VARCHAR(20),
    sebeke_gerilimi VARCHAR(50),
    proje_var_mi TINYINT(1) DEFAULT 0,
    sema_var_mi TINYINT(1) DEFAULT 0,
    yapi_cinsi VARCHAR(50),
    kullanim_amaci VARCHAR(255),
    sozlesme_id VARCHAR(100),
    son_kontrol_tarihi DATE,
    weather_condition VARCHAR(255),
    ground_moisture VARCHAR(255),
    grounding_type VARCHAR(50),
    control_reason VARCHAR(255),
    next_control_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES institutions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Yetkili Kişiler
CREATE TABLE IF NOT EXISTS authorized_persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adi_soyadi VARCHAR(255) NOT NULL,
    meslegi VARCHAR(255),
    kayit_no VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ölçüm Cihazları
CREATE TABLE IF NOT EXISTS measurement_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_name VARCHAR(255) NOT NULL,
    serial_no VARCHAR(100),
    cal_date DATE,
    validity_date DATE,
    cal_no VARCHAR(100),
    is_thermal_camera TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sistem Ayarları
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Yüklenen Logolar
CREATE TABLE IF NOT EXISTS uploaded_logos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. TOPRAKLAMA KONTROL MODÜLÜ
-- ============================================================

-- Topraklama Raporları
CREATE TABLE IF NOT EXISTS grounding_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kurum_id INT NOT NULL,
    report_no VARCHAR(100),
    report_date DATE,
    start_date DATETIME,
    end_date DATETIME,
    next_control_date DATE,
    isg_katip_id VARCHAR(100),
    control_reason VARCHAR(255),
    grounding_type VARCHAR(50),
    weather VARCHAR(255),
    soil_moisture VARCHAR(255),
    sebeke_tipi VARCHAR(20),
    proje_var_mi TINYINT(1) DEFAULT 0,
    sema_var_mi TINYINT(1) DEFAULT 0,
    yapi_cinsi VARCHAR(50),
    protection_measure TEXT,
    changes_exist TINYINT(1) DEFAULT 0,
    prev_label_exists TINYINT(1) DEFAULT 0,
    panel_id VARCHAR(100),
    device1_id INT,
    device2_id INT,
    measurement_method VARCHAR(100),
    project_info TEXT,
    prev_control_date DATE,
    defects TEXT,
    notes TEXT,
    result VARCHAR(50),
    result_notes_selection TEXT,
    authorized_person_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES institutions(id) ON DELETE CASCADE,
    FOREIGN KEY (device1_id) REFERENCES measurement_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (device2_id) REFERENCES measurement_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (authorized_person_id) REFERENCES authorized_persons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Topraklama Ölçümleri 5.1 (Dolaylı Dokunmaya Karşı Koruma)
CREATE TABLE IF NOT EXISTS measurements_5_1 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    point_no INT,
    point_name VARCHAR(255),
    prot_in VARCHAR(50),
    prot_type VARCHAR(50),
    prot_ia VARCHAR(50),
    prot_ik1 VARCHAR(50),
    measured_zx_rx VARCHAR(50),
    limit_zs_ra VARCHAR(50),
    rcd_type_limits VARCHAR(100),
    rcd_test_ia VARCHAR(50),
    rcd_test_ta VARCHAR(50),
    result VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES grounding_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Topraklama Ölçümleri 5.2 (RCD Selektivite Kontrolü)
CREATE TABLE IF NOT EXISTS measurements_5_2 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    row_no INT,
    upstream_panel VARCHAR(255),
    upstream_rcd_type VARCHAR(50),
    upstream_rcd_in VARCHAR(50),
    upstream_rcd_idn VARCHAR(50),
    upstream_rcd_dt VARCHAR(50),
    downstream_panel VARCHAR(255),
    downstream_rcd_type VARCHAR(50),
    downstream_rcd_idn VARCHAR(50),
    downstream_rcd_t VARCHAR(50),
    result VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES grounding_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. İÇ TESİSAT KONTROL MODÜLÜ
-- ============================================================

-- İç Tesisat Raporları
CREATE TABLE IF NOT EXISTS internal_installation_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kurum_id INT NOT NULL,
    report_no VARCHAR(100),
    report_date DATE,
    energy_provider VARCHAR(255),
    sebeke_tipi VARCHAR(20),
    proje_var_mi TINYINT(1) DEFAULT 0,
    sema_var_mi TINYINT(1) DEFAULT 0,
    start_date DATETIME,
    end_date DATETIME,
    next_control_date DATE,
    isg_katip_id VARCHAR(100),
    control_reason VARCHAR(255),
    grounding_type VARCHAR(50),
    building_type VARCHAR(50),
    usage_purpose VARCHAR(255),
    prev_control_date DATE,
    weather_condition VARCHAR(255),
    ground_moisture VARCHAR(255),
    phase_count_type VARCHAR(50),
    conductor_type VARCHAR(50),
    grounding_resistance VARCHAR(50),
    additional_electrode_details TEXT,
    system_grounding_conductor VARCHAR(100),
    main_equipotential_conductor VARCHAR(100),
    nominal_voltage_kV VARCHAR(50),
    nominal_frequency_Hz VARCHAR(50),
    fault_current_kA VARCHAR(50),
    external_loop_impedance VARCHAR(50),
    main_rcd_rating VARCHAR(50),
    main_breaker_type VARCHAR(100),
    main_breaker_rating VARCHAR(50),
    main_rcd_test_mA VARCHAR(50),
    main_rcd_test_ms VARCHAR(50),
    installation_change TINYINT(1) DEFAULT 0,
    has_spd TINYINT(1) DEFAULT 0,
    protection_measures TEXT,
    prev_label_exists TINYINT(1) DEFAULT 0,
    thermal_camera_id INT,
    device1_id INT,
    device2_id INT,
    authorized_person_id INT,
    defects TEXT,
    notes TEXT,
    result VARCHAR(50),
    result_notes_selection TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES institutions(id) ON DELETE CASCADE,
    FOREIGN KEY (thermal_camera_id) REFERENCES measurement_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (device1_id) REFERENCES measurement_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (device2_id) REFERENCES measurement_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (authorized_person_id) REFERENCES authorized_persons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- İç Tesisat Panolar
CREATE TABLE IF NOT EXISTS ic_tesisat_panels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    panel_name VARCHAR(255) NOT NULL,
    panel_order INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES internal_installation_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- İç Tesisat Bölüm 5 (Gözle Muayene - Pano Bazlı)
CREATE TABLE IF NOT EXISTS ic_tesisat_section5 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    panel_id INT NOT NULL,
    question_key VARCHAR(100) NOT NULL,
    answer VARCHAR(50),
    FOREIGN KEY (panel_id) REFERENCES ic_tesisat_panels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- İç Tesisat Bölüm 6 Başlık (Ölçüm Metodu - Rapor Bazlı)
CREATE TABLE IF NOT EXISTS ic_tesisat_section6_header (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL UNIQUE,
    measurement_method VARCHAR(100),
    FOREIGN KEY (report_id) REFERENCES internal_installation_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- İç Tesisat Bölüm 6.1 (Aşırı Akım Cihazı - Pano Başlık)
CREATE TABLE IF NOT EXISTS ic_tesisat_section6_1 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    panel_id INT NOT NULL,
    zx VARCHAR(50),
    zln VARCHAR(50),
    voltage_ff VARCHAR(50),
    voltage_ln VARCHAR(50),
    voltage_npe VARCHAR(50),
    short_circuit_3ph VARCHAR(50),
    dkd_type VARCHAR(100),
    dkd_current VARCHAR(50),
    FOREIGN KEY (panel_id) REFERENCES ic_tesisat_panels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- İç Tesisat Bölüm 6.1 Satırları (Linye Listesi - Pano Bazlı)
CREATE TABLE IF NOT EXISTS ic_tesisat_section6_1_rows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    panel_id INT NOT NULL,
    no_col VARCHAR(10),
    linye_adi VARCHAR(255),
    acma_egrisi VARCHAR(50),
    kutup_sayisi VARCHAR(10),
    in_a VARCHAR(50),
    icu VARCHAR(50),
    faz_kesiti VARCHAR(50),
    npen_kesiti VARCHAR(50),
    pe_kesiti VARCHAR(50),
    ib_tasarim VARCHAR(50),
    iz_kapasite VARCHAR(50),
    rcd_ia VARCHAR(50),
    rcd_ta VARCHAR(50),
    sonuc VARCHAR(100),
    FOREIGN KEY (panel_id) REFERENCES ic_tesisat_panels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- İç Tesisat Bölüm 6.2 Satırları (Potansiyel Dengeleme - Rapor Bazlı)
CREATE TABLE IF NOT EXISTS ic_tesisat_section6_2_rows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    no_col VARCHAR(10),
    bolum VARCHAR(255),
    pd_kesiti VARCHAR(50),
    pd_sureklilik VARCHAR(50),
    tpd_kesiti VARCHAR(50),
    tpd_sureklilik VARCHAR(50),
    sonuc VARCHAR(100),
    FOREIGN KEY (report_id) REFERENCES internal_installation_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- İç Tesisat Bölüm 6.3 Satırları (Halı Direnci - Rapor Bazlı)
CREATE TABLE IF NOT EXISTS ic_tesisat_section6_3_rows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    no_col VARCHAR(10),
    hali_yeri VARCHAR(255),
    eni VARCHAR(50),
    boyu VARCHAR(50),
    direnc VARCHAR(50),
    sonuc VARCHAR(100),
    FOREIGN KEY (report_id) REFERENCES internal_installation_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- İç Tesisat Fotoğraflar (Pano Bazlı)
CREATE TABLE IF NOT EXISTS ic_tesisat_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    panel_id INT NOT NULL,
    photo_type ENUM('normal', 'termal') DEFAULT 'normal',
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (panel_id) REFERENCES ic_tesisat_panels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. YILDIRIMDAN KORUNMA KONTROL MODÜLÜ
-- ============================================================

-- Yıldırımdan Korunma Raporları
CREATE TABLE IF NOT EXISTS lightning_protection_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kurum_id INT NOT NULL,
    report_no VARCHAR(100),
    report_date DATE,
    start_date DATETIME,
    end_date DATETIME,
    next_control_date DATE,
    isg_katip_id VARCHAR(100),
    energy_provider VARCHAR(255),
    sebeke_tipi VARCHAR(20),
    sebeke_voltage VARCHAR(50),
    has_project VARCHAR(10) DEFAULT 'Yok',
    project_details TEXT,
    has_risk_analysis VARCHAR(10) DEFAULT 'Yok',
    control_reason VARCHAR(255),
    grounding_type VARCHAR(50),
    building_type VARCHAR(50),
    usage_purpose_yks_type VARCHAR(255),
    prev_control_date DATE,
    weather_condition VARCHAR(255),
    ground_moisture VARCHAR(255),
    installation_change VARCHAR(10) DEFAULT 'Yok',
    prev_label_exists VARCHAR(10) DEFAULT 'Yok',
    equipment_identification VARCHAR(255),
    protection_system_type VARCHAR(255),
    protection_level_eps VARCHAR(100),
    building_usage_details TEXT,
    thermal_camera_id INT,
    device1_id INT,
    device2_id INT,
    authorized_person_id INT,
    defects TEXT,
    notes TEXT,
    result VARCHAR(50),
    result_notes_selection TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES institutions(id) ON DELETE CASCADE,
    FOREIGN KEY (thermal_camera_id) REFERENCES measurement_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (device1_id) REFERENCES measurement_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (device2_id) REFERENCES measurement_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (authorized_person_id) REFERENCES authorized_persons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Yıldırımdan Korunma Bölüm 4 (Kontrol Kriterleri - ESE/Faraday)
CREATE TABLE IF NOT EXISTS lightning_protection_section4 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    question_key VARCHAR(100) NOT NULL,
    answer VARCHAR(50),
    FOREIGN KEY (report_id) REFERENCES lightning_protection_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. YANGIN ALGILAMA VE UYARI SİSTEMİ KONTROL MODÜLÜ
-- ============================================================

-- Yangın Algılama Raporları
CREATE TABLE IF NOT EXISTS fire_detection_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kurum_id INT NOT NULL,
    report_no VARCHAR(100),
    report_date DATE,
    start_date DATETIME,
    end_date DATETIME,
    next_control_date DATE,
    isg_katip_id VARCHAR(100),
    algilama_sistemi VARCHAR(50),
    uyari_sistemi VARCHAR(50),
    sistem_calisma_tipi VARCHAR(50),
    proje_onay_kurumu VARCHAR(255),
    control_reason VARCHAR(255),
    proje_onay_bilgileri VARCHAR(255),
    panel_marka_model VARCHAR(255),
    ilk_kontrol_tarihi DATE,
    prev_control_date DATE,
    weather_condition VARCHAR(255),
    ground_moisture VARCHAR(255),
    panel_seri_no VARCHAR(100),
    panel_calisma_gerilimi VARCHAR(50),
    algilama_ekipmanlari TEXT,
    panel_yeri VARCHAR(255),
    uyari_ekipmanlari TEXT,
    sondurme_ekipmanlari TEXT,
    installation_change VARCHAR(50),
    prev_label_exists VARCHAR(50),
    bina_kullanma_sinifi VARCHAR(255),
    bina_tehlike_sinifi VARCHAR(100),
    tehlike_kategorisi VARCHAR(10),
    toplam_alan VARCHAR(50),
    kat_sayisi VARCHAR(20),
    bina_yuksekligi VARCHAR(50),
    yapi_kullanma_izin_tarihi DATE,
    bolum_sayisi VARCHAR(20),
    diger_tespitler TEXT,
    device1_id INT,
    device2_id INT,
    authorized_person_id INT,
    defects TEXT,
    notes TEXT,
    result VARCHAR(50),
    inspection_results JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kurum_id) REFERENCES institutions(id) ON DELETE CASCADE,
    FOREIGN KEY (device1_id) REFERENCES measurement_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (device2_id) REFERENCES measurement_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (authorized_person_id) REFERENCES authorized_persons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Yangın Algılama Bölüm 5.2 (Loop Bazlı Ekipman Kontrolleri)
CREATE TABLE IF NOT EXISTS fire_detection_section5_2 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    loop_no VARCHAR(50),
    bolum_adi VARCHAR(255),
    ekipman_adi VARCHAR(255),
    projede_mi VARCHAR(10),
    erisim_durumu VARCHAR(10),
    montaj_durumu VARCHAR(10),
    test VARCHAR(10),
    sesli_uyari VARCHAR(10),
    isikli_uyari VARCHAR(10),
    adresleme VARCHAR(10),
    FOREIGN KEY (report_id) REFERENCES fire_detection_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. VARSAYILAN VERİLER
-- ============================================================

-- Varsayılan Admin Kullanıcısı
-- Kullanıcı adı: admin / Şifre: admin123
-- ⚠️ İlk girişten sonra şifreyi mutlaka değiştirin!
INSERT INTO users (username, password, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Varsayılan Sistem Ayarları
INSERT INTO system_settings (setting_key, setting_value) VALUES
('logo_text', 'LOGO'),
('logo_type', 'text'),
('active_logo', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
