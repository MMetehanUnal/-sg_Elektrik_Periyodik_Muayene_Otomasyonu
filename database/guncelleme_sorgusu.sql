-- ========================================================
-- ISG Elektrik Periyodik Muayene Otomasyonu - Veritabanı Güncelleme Scripti
-- Hedef: Sunucu üzerindeki eski veritabanını yeni şemaya güncellemek
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. YENİ TABLOLARIN OLUŞTURULMASI
-- --------------------------------------------------------

-- Yeni tablo: api_tokens
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_expires (user_id, expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. MEVCUT TABLOLARIN GÜNCELLENMESİ (ALTER TABLE)
-- --------------------------------------------------------

-- Tablo güncelleniyor: users
ALTER TABLE `users`
    MODIFY COLUMN `username` VARCHAR(50) NOT NULL,
    MODIFY COLUMN `role` ENUM('admin', 'user') DEFAULT 'user',
    MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Tablo güncelleniyor: institutions
ALTER TABLE `institutions`
    MODIFY COLUMN `adresi` TEXT,
    MODIFY COLUMN `sgk_sicil_no` VARCHAR(50),
    MODIFY COLUMN `il_kodu` VARCHAR(2) DEFAULT '01',
    MODIFY COLUMN `kurum_kodu` VARCHAR(3),
    MODIFY COLUMN `isg_katip_id` VARCHAR(100),
    MODIFY COLUMN `report_date` DATE,
    MODIFY COLUMN `start_date` DATETIME,
    MODIFY COLUMN `end_date` DATETIME,
    MODIFY COLUMN `next_control_date` DATE,
    MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Tablo güncelleniyor: facility_info
ALTER TABLE `facility_info`
    ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    MODIFY COLUMN `enerji_saglayan` VARCHAR(255),
    MODIFY COLUMN `sebeke_tipi` VARCHAR(20),
    MODIFY COLUMN `sebeke_gerilimi` VARCHAR(50),
    MODIFY COLUMN `yapi_cinsi` VARCHAR(50),
    MODIFY COLUMN `kullanim_amaci` VARCHAR(255),
    MODIFY COLUMN `sozlesme_id` VARCHAR(100),
    MODIFY COLUMN `son_kontrol_tarihi` DATE,
    MODIFY COLUMN `weather_condition` VARCHAR(255),
    MODIFY COLUMN `ground_moisture` VARCHAR(255),
    MODIFY COLUMN `grounding_type` VARCHAR(50),
    MODIFY COLUMN `control_reason` VARCHAR(255),
    MODIFY COLUMN `next_control_date` DATE;
-- Not: Aşağıdaki sütunlar local şemada bulunmuyor. Gerekirse verilerinizi yedekleyip çalıştırın:
-- ALTER TABLE `facility_info` DROP COLUMN `updated_at`;

-- Tablo güncelleniyor: authorized_persons
ALTER TABLE `authorized_persons`
    MODIFY COLUMN `adi_soyadi` VARCHAR(255) NOT NULL,
    MODIFY COLUMN `meslegi` VARCHAR(255),
    MODIFY COLUMN `kayit_no` VARCHAR(100),
    MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Tablo güncelleniyor: measurement_devices
ALTER TABLE `measurement_devices`
    MODIFY COLUMN `user_id` INT NOT NULL,
    MODIFY COLUMN `device_name` VARCHAR(255) NOT NULL,
    MODIFY COLUMN `serial_no` VARCHAR(100),
    MODIFY COLUMN `cal_date` DATE,
    MODIFY COLUMN `validity_date` DATE,
    MODIFY COLUMN `cal_no` VARCHAR(100),
    MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Tablo güncelleniyor: system_settings (Özel dönüşüm)
ALTER TABLE `system_settings` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `system_settings` DROP PRIMARY KEY;
ALTER TABLE `system_settings` ADD COLUMN `id` INT AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `system_settings` ADD UNIQUE KEY `setting_key` (`setting_key`);
ALTER TABLE `system_settings` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Tablo güncelleniyor: uploaded_logos
ALTER TABLE `uploaded_logos`
    MODIFY COLUMN `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Tablo güncelleniyor: grounding_reports
ALTER TABLE `grounding_reports`
    ADD COLUMN `firma_adi_eki` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `sebeke_tipi` VARCHAR(20),
    ADD COLUMN `proje_var_mi` TINYINT(1) DEFAULT 0,
    ADD COLUMN `sema_var_mi` TINYINT(1) DEFAULT 0,
    ADD COLUMN `yapi_cinsi` VARCHAR(50),
    MODIFY COLUMN `report_no` VARCHAR(100),
    MODIFY COLUMN `report_date` DATE,
    MODIFY COLUMN `start_date` DATETIME,
    MODIFY COLUMN `end_date` DATETIME,
    MODIFY COLUMN `next_control_date` DATE,
    MODIFY COLUMN `isg_katip_id` VARCHAR(100),
    MODIFY COLUMN `control_reason` VARCHAR(255),
    MODIFY COLUMN `grounding_type` VARCHAR(50),
    MODIFY COLUMN `weather` VARCHAR(255),
    MODIFY COLUMN `soil_moisture` VARCHAR(255),
    MODIFY COLUMN `protection_measure` TEXT,
    MODIFY COLUMN `changes_exist` TINYINT(1) DEFAULT 0,
    MODIFY COLUMN `prev_label_exists` TINYINT(1) DEFAULT 0,
    MODIFY COLUMN `panel_id` VARCHAR(100),
    MODIFY COLUMN `device1_id` INT,
    MODIFY COLUMN `device2_id` INT,
    MODIFY COLUMN `measurement_method` VARCHAR(100),
    MODIFY COLUMN `project_info` TEXT,
    MODIFY COLUMN `prev_control_date` DATE,
    MODIFY COLUMN `defects` TEXT,
    MODIFY COLUMN `notes` TEXT,
    MODIFY COLUMN `result` VARCHAR(50),
    MODIFY COLUMN `result_notes_selection` TEXT,
    MODIFY COLUMN `authorized_person_id` INT,
    MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
-- Not: Aşağıdaki sütunlar local şemada bulunmuyor. Gerekirse verilerinizi yedekleyip çalıştırın:
-- ALTER TABLE `grounding_reports` DROP COLUMN `status`;

-- Tablo güncelleniyor: measurements_5_1
ALTER TABLE `measurements_5_1`
    ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    MODIFY COLUMN `point_no` INT,
    MODIFY COLUMN `point_name` VARCHAR(255),
    MODIFY COLUMN `prot_in` VARCHAR(50),
    MODIFY COLUMN `prot_type` VARCHAR(50),
    MODIFY COLUMN `prot_ia` VARCHAR(50),
    MODIFY COLUMN `prot_ik1` VARCHAR(50),
    MODIFY COLUMN `measured_zx_rx` VARCHAR(50),
    MODIFY COLUMN `limit_zs_ra` VARCHAR(50),
    MODIFY COLUMN `rcd_type_limits` VARCHAR(100),
    MODIFY COLUMN `rcd_test_ia` VARCHAR(50),
    MODIFY COLUMN `rcd_test_ta` VARCHAR(50),
    MODIFY COLUMN `result` VARCHAR(100);

-- Tablo güncelleniyor: measurements_5_2
ALTER TABLE `measurements_5_2`
    ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    MODIFY COLUMN `row_no` INT,
    MODIFY COLUMN `upstream_panel` VARCHAR(255),
    MODIFY COLUMN `upstream_rcd_type` VARCHAR(50),
    MODIFY COLUMN `upstream_rcd_in` VARCHAR(50),
    MODIFY COLUMN `upstream_rcd_idn` VARCHAR(50),
    MODIFY COLUMN `upstream_rcd_dt` VARCHAR(50),
    MODIFY COLUMN `downstream_panel` VARCHAR(255),
    MODIFY COLUMN `downstream_rcd_type` VARCHAR(50),
    MODIFY COLUMN `downstream_rcd_idn` VARCHAR(50),
    MODIFY COLUMN `downstream_rcd_t` VARCHAR(50),
    MODIFY COLUMN `result` VARCHAR(100);

-- Tablo güncelleniyor: internal_installation_reports
ALTER TABLE `internal_installation_reports`
    ADD COLUMN `firma_adi_eki` VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN `report_no` VARCHAR(100),
    MODIFY COLUMN `report_date` DATE,
    MODIFY COLUMN `energy_provider` VARCHAR(255),
    MODIFY COLUMN `sebeke_tipi` VARCHAR(20),
    MODIFY COLUMN `start_date` DATETIME,
    MODIFY COLUMN `end_date` DATETIME,
    MODIFY COLUMN `next_control_date` DATE,
    MODIFY COLUMN `isg_katip_id` VARCHAR(100),
    MODIFY COLUMN `control_reason` VARCHAR(255),
    MODIFY COLUMN `grounding_type` VARCHAR(50),
    MODIFY COLUMN `building_type` VARCHAR(50),
    MODIFY COLUMN `usage_purpose` VARCHAR(255),
    MODIFY COLUMN `prev_control_date` DATE,
    MODIFY COLUMN `weather_condition` VARCHAR(255),
    MODIFY COLUMN `ground_moisture` VARCHAR(255),
    MODIFY COLUMN `phase_count_type` VARCHAR(50),
    MODIFY COLUMN `conductor_type` VARCHAR(50),
    MODIFY COLUMN `grounding_resistance` VARCHAR(50),
    MODIFY COLUMN `additional_electrode_details` TEXT,
    MODIFY COLUMN `system_grounding_conductor` VARCHAR(100),
    MODIFY COLUMN `main_equipotential_conductor` VARCHAR(100),
    MODIFY COLUMN `nominal_voltage_kV` VARCHAR(50),
    MODIFY COLUMN `nominal_frequency_Hz` VARCHAR(50),
    MODIFY COLUMN `fault_current_kA` VARCHAR(50),
    MODIFY COLUMN `external_loop_impedance` VARCHAR(50),
    MODIFY COLUMN `main_rcd_rating` VARCHAR(50),
    MODIFY COLUMN `main_breaker_type` VARCHAR(100),
    MODIFY COLUMN `main_breaker_rating` VARCHAR(50),
    MODIFY COLUMN `main_rcd_test_mA` VARCHAR(50),
    MODIFY COLUMN `main_rcd_test_ms` VARCHAR(50),
    MODIFY COLUMN `protection_measures` TEXT,
    MODIFY COLUMN `thermal_camera_id` INT,
    MODIFY COLUMN `device1_id` INT,
    MODIFY COLUMN `device2_id` INT,
    MODIFY COLUMN `authorized_person_id` INT,
    MODIFY COLUMN `defects` TEXT,
    MODIFY COLUMN `notes` TEXT,
    MODIFY COLUMN `result` VARCHAR(50),
    MODIFY COLUMN `result_notes_selection` TEXT,
    MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Tablo güncelleniyor: ic_tesisat_panels
ALTER TABLE `ic_tesisat_panels`
    ADD COLUMN `thermal_numbers` VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN `notes` TEXT,
    MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Tablo güncelleniyor: ic_tesisat_section5
ALTER TABLE `ic_tesisat_section5`
    MODIFY COLUMN `answer` VARCHAR(50);

-- Tablo güncelleniyor: ic_tesisat_section6_header
ALTER TABLE `ic_tesisat_section6_header`
    MODIFY COLUMN `report_id` INT NOT NULL,
    MODIFY COLUMN `measurement_method` VARCHAR(100);

-- Tablo güncelleniyor: ic_tesisat_section6_1
ALTER TABLE `ic_tesisat_section6_1`
    MODIFY COLUMN `zx` VARCHAR(50),
    MODIFY COLUMN `zln` VARCHAR(50),
    MODIFY COLUMN `voltage_ff` VARCHAR(50),
    MODIFY COLUMN `voltage_ln` VARCHAR(50),
    MODIFY COLUMN `voltage_npe` VARCHAR(50),
    MODIFY COLUMN `short_circuit_3ph` VARCHAR(50),
    MODIFY COLUMN `dkd_type` VARCHAR(100),
    MODIFY COLUMN `dkd_current` VARCHAR(50);

-- Tablo güncelleniyor: ic_tesisat_section6_1_rows
ALTER TABLE `ic_tesisat_section6_1_rows`
    MODIFY COLUMN `no_col` VARCHAR(10),
    MODIFY COLUMN `linye_adi` VARCHAR(255),
    MODIFY COLUMN `acma_egrisi` VARCHAR(50),
    MODIFY COLUMN `kutup_sayisi` VARCHAR(10),
    MODIFY COLUMN `in_a` VARCHAR(50),
    MODIFY COLUMN `icu` VARCHAR(50),
    MODIFY COLUMN `faz_kesiti` VARCHAR(50),
    MODIFY COLUMN `npen_kesiti` VARCHAR(50),
    MODIFY COLUMN `pe_kesiti` VARCHAR(50),
    MODIFY COLUMN `ib_tasarim` VARCHAR(50),
    MODIFY COLUMN `iz_kapasite` VARCHAR(50),
    MODIFY COLUMN `rcd_ia` VARCHAR(50),
    MODIFY COLUMN `rcd_ta` VARCHAR(50),
    MODIFY COLUMN `sonuc` VARCHAR(100);

-- Tablo güncelleniyor: ic_tesisat_section6_2_rows
ALTER TABLE `ic_tesisat_section6_2_rows`
    MODIFY COLUMN `no_col` VARCHAR(10),
    MODIFY COLUMN `bolum` VARCHAR(255),
    MODIFY COLUMN `pd_kesiti` VARCHAR(50),
    MODIFY COLUMN `pd_sureklilik` VARCHAR(50),
    MODIFY COLUMN `tpd_kesiti` VARCHAR(50),
    MODIFY COLUMN `tpd_sureklilik` VARCHAR(50),
    MODIFY COLUMN `sonuc` VARCHAR(100);

-- Tablo güncelleniyor: ic_tesisat_section6_3_rows
ALTER TABLE `ic_tesisat_section6_3_rows`
    MODIFY COLUMN `no_col` VARCHAR(10),
    MODIFY COLUMN `hali_yeri` VARCHAR(255),
    MODIFY COLUMN `eni` VARCHAR(50),
    MODIFY COLUMN `boyu` VARCHAR(50),
    MODIFY COLUMN `direnc` VARCHAR(50),
    MODIFY COLUMN `sonuc` VARCHAR(100);

-- Tablo güncelleniyor: ic_tesisat_photos
ALTER TABLE `ic_tesisat_photos`
    ADD COLUMN `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    MODIFY COLUMN `photo_type` ENUM('normal', 'termal') DEFAULT 'normal';
-- Not: Aşağıdaki sütunlar local şemada bulunmuyor. Gerekirse verilerinizi yedekleyip çalıştırın:
-- ALTER TABLE `ic_tesisat_photos` DROP COLUMN `created_at`;

-- Tablo güncelleniyor: lightning_protection_reports
ALTER TABLE `lightning_protection_reports`
    ADD COLUMN `firma_adi_eki` VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN `report_no` VARCHAR(100),
    MODIFY COLUMN `report_date` DATE,
    MODIFY COLUMN `start_date` DATETIME,
    MODIFY COLUMN `end_date` DATETIME,
    MODIFY COLUMN `next_control_date` DATE,
    MODIFY COLUMN `isg_katip_id` VARCHAR(100),
    MODIFY COLUMN `energy_provider` VARCHAR(255),
    MODIFY COLUMN `sebeke_tipi` VARCHAR(20),
    MODIFY COLUMN `sebeke_voltage` VARCHAR(50),
    MODIFY COLUMN `has_project` VARCHAR(10) DEFAULT 'Yok',
    MODIFY COLUMN `project_details` TEXT,
    MODIFY COLUMN `has_risk_analysis` VARCHAR(10) DEFAULT 'Yok',
    MODIFY COLUMN `control_reason` VARCHAR(255),
    MODIFY COLUMN `grounding_type` VARCHAR(50),
    MODIFY COLUMN `building_type` VARCHAR(50),
    MODIFY COLUMN `usage_purpose_yks_type` VARCHAR(255),
    MODIFY COLUMN `prev_control_date` DATE,
    MODIFY COLUMN `weather_condition` VARCHAR(255),
    MODIFY COLUMN `ground_moisture` VARCHAR(255),
    MODIFY COLUMN `installation_change` VARCHAR(10) DEFAULT 'Yok',
    MODIFY COLUMN `prev_label_exists` VARCHAR(10) DEFAULT 'Yok',
    MODIFY COLUMN `equipment_identification` VARCHAR(255),
    MODIFY COLUMN `protection_system_type` VARCHAR(255),
    MODIFY COLUMN `protection_level_eps` VARCHAR(100),
    MODIFY COLUMN `building_usage_details` TEXT,
    MODIFY COLUMN `thermal_camera_id` INT,
    MODIFY COLUMN `device1_id` INT,
    MODIFY COLUMN `device2_id` INT,
    MODIFY COLUMN `authorized_person_id` INT,
    MODIFY COLUMN `defects` TEXT,
    MODIFY COLUMN `notes` TEXT,
    MODIFY COLUMN `result` VARCHAR(50),
    MODIFY COLUMN `result_notes_selection` TEXT,
    MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Tablo güncelleniyor: lightning_protection_section4
ALTER TABLE `lightning_protection_section4`
    MODIFY COLUMN `report_id` INT NOT NULL,
    MODIFY COLUMN `question_key` VARCHAR(100) NOT NULL,
    MODIFY COLUMN `answer` VARCHAR(50);

-- Tablo güncelleniyor: fire_detection_reports
ALTER TABLE `fire_detection_reports`
    ADD COLUMN `firma_adi_eki` VARCHAR(255) DEFAULT NULL,
    MODIFY COLUMN `report_no` VARCHAR(100),
    MODIFY COLUMN `report_date` DATE,
    MODIFY COLUMN `start_date` DATETIME,
    MODIFY COLUMN `end_date` DATETIME,
    MODIFY COLUMN `next_control_date` DATE,
    MODIFY COLUMN `isg_katip_id` VARCHAR(100),
    MODIFY COLUMN `algilama_sistemi` VARCHAR(50),
    MODIFY COLUMN `uyari_sistemi` VARCHAR(50),
    MODIFY COLUMN `sistem_calisma_tipi` VARCHAR(50),
    MODIFY COLUMN `proje_onay_kurumu` VARCHAR(255),
    MODIFY COLUMN `control_reason` VARCHAR(255),
    MODIFY COLUMN `proje_onay_bilgileri` VARCHAR(255),
    MODIFY COLUMN `panel_marka_model` VARCHAR(255),
    MODIFY COLUMN `ilk_kontrol_tarihi` DATE,
    MODIFY COLUMN `prev_control_date` DATE,
    MODIFY COLUMN `weather_condition` VARCHAR(255),
    MODIFY COLUMN `ground_moisture` VARCHAR(255),
    MODIFY COLUMN `panel_seri_no` VARCHAR(100),
    MODIFY COLUMN `panel_calisma_gerilimi` VARCHAR(50),
    MODIFY COLUMN `algilama_ekipmanlari` TEXT,
    MODIFY COLUMN `panel_yeri` VARCHAR(255),
    MODIFY COLUMN `uyari_ekipmanlari` TEXT,
    MODIFY COLUMN `sondurme_ekipmanlari` TEXT,
    MODIFY COLUMN `installation_change` VARCHAR(50),
    MODIFY COLUMN `prev_label_exists` VARCHAR(50),
    MODIFY COLUMN `bina_kullanma_sinifi` VARCHAR(255),
    MODIFY COLUMN `bina_tehlike_sinifi` VARCHAR(100),
    MODIFY COLUMN `tehlike_kategorisi` VARCHAR(10),
    MODIFY COLUMN `toplam_alan` VARCHAR(50),
    MODIFY COLUMN `kat_sayisi` VARCHAR(20),
    MODIFY COLUMN `bina_yuksekligi` VARCHAR(50),
    MODIFY COLUMN `yapi_kullanma_izin_tarihi` DATE,
    MODIFY COLUMN `bolum_sayisi` VARCHAR(20),
    MODIFY COLUMN `diger_tespitler` TEXT,
    MODIFY COLUMN `device1_id` INT,
    MODIFY COLUMN `device2_id` INT,
    MODIFY COLUMN `authorized_person_id` INT,
    MODIFY COLUMN `defects` TEXT,
    MODIFY COLUMN `notes` TEXT,
    MODIFY COLUMN `result` VARCHAR(50),
    MODIFY COLUMN `inspection_results` JSON,
    MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
-- Not: Aşağıdaki sütunlar local şemada bulunmuyor. Gerekirse verilerinizi yedekleyip çalıştırın:
-- ALTER TABLE `fire_detection_reports` DROP COLUMN `result_notes_selection`;

-- Tablo güncelleniyor: fire_detection_section5_2
ALTER TABLE `fire_detection_section5_2`
    MODIFY COLUMN `report_id` INT NOT NULL,
    MODIFY COLUMN `loop_no` VARCHAR(50),
    MODIFY COLUMN `bolum_adi` VARCHAR(255),
    MODIFY COLUMN `ekipman_adi` VARCHAR(255),
    MODIFY COLUMN `projede_mi` VARCHAR(10),
    MODIFY COLUMN `erisim_durumu` VARCHAR(10),
    MODIFY COLUMN `montaj_durumu` VARCHAR(10),
    MODIFY COLUMN `test` VARCHAR(10),
    MODIFY COLUMN `sesli_uyari` VARCHAR(10),
    MODIFY COLUMN `isikli_uyari` VARCHAR(10),
    MODIFY COLUMN `adresleme` VARCHAR(10);

-- Tablo güncelleniyor: general_reports
ALTER TABLE `general_reports`
    MODIFY COLUMN `content` LONGTEXT,
    MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    MODIFY COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Tablo güncelleniyor: general_report_images
ALTER TABLE `general_report_images`
    MODIFY COLUMN `original_name` VARCHAR(255),
    MODIFY COLUMN `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- --------------------------------------------------------
-- 3. ESKİ/ARTIK TABLOLAR (Yeni şemada bulunmayan eski tablolar)
-- --------------------------------------------------------

-- DROP TABLE IF EXISTS `report_devices`; -- Eski veritabanından kalan artık tablo

SET FOREIGN_KEY_CHECKS = 1;