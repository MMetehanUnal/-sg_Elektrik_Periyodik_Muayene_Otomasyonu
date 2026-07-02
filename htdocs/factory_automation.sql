-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 25 Haz 2026, 02:46:38
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `factory_automation`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `authorized_persons`
--

CREATE TABLE `authorized_persons` (
  `id` int(11) NOT NULL,
  `adi_soyadi` varchar(100) NOT NULL,
  `meslegi` varchar(100) DEFAULT NULL,
  `kayit_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `authorized_persons`
--

INSERT INTO `authorized_persons` (`id`, `adi_soyadi`, `meslegi`, `kayit_no`, `created_at`) VALUES
(1, 'Ahmet Tuna ARIKAN', 'ELK.ELEKTRONİK MÜH.', 'K2025408825', '2026-02-15 12:44:11');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `facility_info`
--

CREATE TABLE `facility_info` (
  `id` int(11) NOT NULL,
  `kurum_id` int(11) NOT NULL,
  `enerji_saglayan` varchar(255) DEFAULT NULL,
  `sebeke_tipi` varchar(50) DEFAULT NULL,
  `sebeke_gerilimi` varchar(50) DEFAULT NULL,
  `proje_var_mi` tinyint(1) DEFAULT 0,
  `sema_var_mi` tinyint(1) DEFAULT 0,
  `yapi_cinsi` varchar(50) DEFAULT NULL,
  `kullanim_amaci` varchar(255) DEFAULT NULL,
  `sozlesme_id` varchar(50) DEFAULT NULL,
  `son_kontrol_tarihi` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `weather_condition` varchar(255) DEFAULT NULL,
  `ground_moisture` varchar(255) DEFAULT NULL,
  `grounding_type` varchar(100) DEFAULT NULL,
  `control_reason` varchar(100) DEFAULT NULL,
  `next_control_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `facility_info`
--

INSERT INTO `facility_info` (`id`, `kurum_id`, `enerji_saglayan`, `sebeke_tipi`, `sebeke_gerilimi`, `proje_var_mi`, `sema_var_mi`, `yapi_cinsi`, `kullanim_amaci`, `sozlesme_id`, `son_kontrol_tarihi`, `updated_at`, `weather_condition`, `ground_moisture`, `grounding_type`, `control_reason`, `next_control_date`) VALUES
(1, 1, 'MEDAŞ', 'TN-S', '400V', 1, 1, 'Endüstri', 'Fabrika', '11', '2026-02-18', '2026-02-15 18:22:19', NULL, NULL, NULL, NULL, NULL),
(2, 4, 'Fırat EDAŞ', 'TN-S', '400V', 1, 1, 'Ticari', 'Perakende Mağaza', '24360984', '2024-12-03', '2026-04-06 01:14:05', 'yağmurlu 5C', 'nemli', 'Temel', 'Periyodik Kontrol', '2027-02-13'),
(3, 8, 'MEDAŞ', 'TN-S', '220V', 1, 1, 'Ticari', 'Eğitim Hizmetleri', '25126107', '2024-12-24', '2026-04-05 15:15:38', NULL, NULL, NULL, NULL, NULL),
(4, 2, 'Fırat EDAŞ', 'TN-S', '400V', 1, 1, 'Ticari', 'Perakende Mağaza', '24361622', '2024-12-03', '2026-04-06 00:20:46', 'yağmurlu 5C', 'nemli', 'Temel', 'Periyodik Kontrol', '2027-02-12'),
(5, 3, 'Fırat EDAŞ', 'TN-S', '400V', 1, 1, 'Ticari', 'Perakende Mağaza', '24361403', '2024-12-03', '2026-04-06 00:51:46', 'yağmurlu 5C', 'nemli', 'Temel', 'Periyodik Kontrol', '2027-02-13'),
(6, 5, 'Fırat EDAŞ', 'TN-S', '400V', 1, 1, 'Ticari', 'Perakende Mağaza', '24361154', '2024-12-03', '2026-04-06 01:22:51', 'yağmurlu 5C', 'nemli', 'Temel', 'Periyodik Kontrol', '2027-02-13');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `fire_detection_reports`
--

CREATE TABLE `fire_detection_reports` (
  `id` int(11) NOT NULL,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `algilama_sistemi` varchar(50) DEFAULT NULL,
  `uyari_sistemi` varchar(50) DEFAULT NULL,
  `sistem_calisma_tipi` varchar(50) DEFAULT NULL,
  `proje_onay_kurumu` varchar(255) DEFAULT NULL,
  `control_reason` varchar(100) DEFAULT NULL,
  `proje_onay_bilgileri` varchar(255) DEFAULT NULL,
  `panel_marka_model` varchar(255) DEFAULT NULL,
  `ilk_kontrol_tarihi` date DEFAULT NULL,
  `prev_control_date` date DEFAULT NULL,
  `panel_seri_no` varchar(100) DEFAULT NULL,
  `panel_calisma_gerilimi` varchar(50) DEFAULT NULL,
  `algilama_ekipmanlari` text DEFAULT NULL,
  `panel_yeri` varchar(255) DEFAULT NULL,
  `uyari_ekipmanlari` text DEFAULT NULL,
  `sondurme_ekipmanlari` text DEFAULT NULL,
  `installation_change` varchar(20) DEFAULT NULL,
  `prev_label_exists` varchar(10) DEFAULT NULL,
  `bina_kullanma_sinifi` varchar(100) DEFAULT NULL,
  `bina_tehlike_sinifi` varchar(100) DEFAULT NULL,
  `tehlike_kategorisi` varchar(10) DEFAULT NULL,
  `toplam_alan` varchar(50) DEFAULT NULL,
  `kat_sayisi` varchar(50) DEFAULT NULL,
  `bina_yuksekligi` varchar(50) DEFAULT NULL,
  `yapi_kullanma_izin_tarihi` date DEFAULT NULL,
  `bolum_sayisi` varchar(50) DEFAULT NULL,
  `diger_tespitler` text DEFAULT NULL,
  `device1_id` int(11) DEFAULT NULL,
  `device2_id` int(11) DEFAULT NULL,
  `authorized_person_id` int(11) DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'UYGUNDUR',
  `result_notes_selection` text DEFAULT NULL,
  `inspection_results` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `weather_condition` varchar(255) DEFAULT NULL,
  `ground_moisture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `fire_detection_reports`
--

INSERT INTO `fire_detection_reports` (`id`, `kurum_id`, `report_no`, `report_date`, `start_date`, `end_date`, `next_control_date`, `isg_katip_id`, `algilama_sistemi`, `uyari_sistemi`, `sistem_calisma_tipi`, `proje_onay_kurumu`, `control_reason`, `proje_onay_bilgileri`, `panel_marka_model`, `ilk_kontrol_tarihi`, `prev_control_date`, `panel_seri_no`, `panel_calisma_gerilimi`, `algilama_ekipmanlari`, `panel_yeri`, `uyari_ekipmanlari`, `sondurme_ekipmanlari`, `installation_change`, `prev_label_exists`, `bina_kullanma_sinifi`, `bina_tehlike_sinifi`, `tehlike_kategorisi`, `toplam_alan`, `kat_sayisi`, `bina_yuksekligi`, `yapi_kullanma_izin_tarihi`, `bolum_sayisi`, `diger_tespitler`, `device1_id`, `device2_id`, `authorized_person_id`, `defects`, `notes`, `result`, `result_notes_selection`, `inspection_results`, `created_at`, `weather_condition`, `ground_moisture`) VALUES
(1, 1, '42-001-ya-1771971144', '2026-02-24', '2026-02-25 00:37:00', '2026-02-26 00:37:00', '2027-02-24', 'test', 'Manuel', 'Sesli', 'Konvansiyonel', 'asd', 'Periyodik Kontrol', '2025', 'epson', '2026-02-18', '2026-02-25', '111', '12v', 'Duman (optik) dedektörü,Isı dedektörü,İhbar butonu', 'giriş', 'Siren', 'Otomatik söndürme,KKT Özellikli yangın tüpleri,Hidrantlar-Yangın dolapları', 'Yok', 'Var', 'Endüstriyel yapı', 'Orta tehlike', '1', '236', '2', '2', '2026-02-12', '23', 'yok', 1, NULL, 1, 'yok', '', 'UYGUNDUR', NULL, '{\"personel_varmi\":\"U\",\"sorumlular_belirlenmismi\":\"U\",\"panel_durumu\":\"U\",\"anons_sistemi\":\"U\",\"bakim_kayitlari\":\"U\",\"sistem_kutugu\":\"U\",\"panel_yerlesim\":\"U\",\"panel_izlenebilirlik\":\"U\",\"adresleme_harita\":\"U\",\"paralel_ihbar\":\"U\",\"koruma_devre\":\"U\",\"devre_ayrilmasi\":\"U\",\"kullanma_talimati\":\"U\",\"aku_durumu\":\"U\",\"ortam_uyumu\":\"U\",\"uyari_yerlesim\":\"U\",\"kablo_uygunluk\":\"UD\",\"armatur_uygunluk\":\"UG\",\"aydinlatma_varlik\":\"UG\",\"yonlendirme_isaretleri\":\"UG\",\"aydinlatma_seviye\":\"UG\",\"panel_onu_lux\":\"UD\",\"cikis_hol_yonlendirme\":\"UD\",\"aydinlatma_sure\":\"UD\",\"otomatik_devreye_girme\":\"UD\",\"damper_izlenebilirlik\":\"UD\",\"sondurme_entegrasyon\":\"UD\",\"otomasyon_baglanti\":\"UD\",\"asansor_davranis\":\"UD\",\"kesici_yedek_enerji\":\"UD\",\"iklimlendirme_sinyal\":\"UD\",\"akis_anahtari_izlenebilirlik\":\"UD\",\"basinclandirma_kontrol\":\"UD\",\"kapi_tutucu_kontrol\":\"UD\",\"gaz_kesme_kontrol\":\"UD\"}', '2026-02-24 22:12:24', NULL, NULL),
(2, 1, '42-001-ya-1771974088', '2026-02-25', '2026-02-17 02:00:00', '2026-02-18 02:00:00', '2027-02-25', '234', 'Manuel', 'Işıklı', 'Konvansiyonel', '546', 'Periyodik Kontrol', 'a', 'a', '2026-02-03', '2026-02-20', 'a', 'a', '', 'a', 'Siren,Flaşör', 'Otomatik söndürme,KKT Özellikli yangın tüpleri', 'Yok', 'Var', 'Endüstriyel yapı', 'Düşük tehlike', '1', 'a', 'a', 'a', '2026-02-13', 'a', 'a', 1, NULL, 1, 'asx', 'as', 'UYGUNDUR', NULL, '{\"personel_varmi\":\"U\",\"sorumlular_belirlenmismi\":\"U\",\"panel_durumu\":\"U\",\"anons_sistemi\":\"U\",\"bakim_kayitlari\":\"U\",\"sistem_kutugu\":\"U\",\"panel_yerlesim\":\"U\",\"panel_izlenebilirlik\":\"U\",\"adresleme_harita\":\"U\",\"paralel_ihbar\":\"U\",\"koruma_devre\":\"U\",\"devre_ayrilmasi\":\"U\",\"kullanma_talimati\":\"U\",\"aku_durumu\":\"U\",\"ortam_uyumu\":\"U\",\"uyari_yerlesim\":\"U\",\"kablo_uygunluk\":\"U\",\"armatur_uygunluk\":\"U\",\"aydinlatma_varlik\":\"U\",\"yonlendirme_isaretleri\":\"U\",\"aydinlatma_seviye\":\"U\",\"panel_onu_lux\":\"U\",\"cikis_hol_yonlendirme\":\"U\",\"aydinlatma_sure\":\"U\",\"otomatik_devreye_girme\":\"U\",\"damper_izlenebilirlik\":\"U\",\"sondurme_entegrasyon\":\"U\",\"otomasyon_baglanti\":\"U\",\"asansor_davranis\":\"U\",\"kesici_yedek_enerji\":\"U\",\"iklimlendirme_sinyal\":\"U\",\"akis_anahtari_izlenebilirlik\":\"U\",\"basinclandirma_kontrol\":\"U\",\"kapi_tutucu_kontrol\":\"U\",\"gaz_kesme_kontrol\":\"U\"}', '2026-02-24 23:01:28', NULL, NULL),
(3, 1, '42-001-ya-1772546195', '2026-03-01', '2026-02-11 12:25:00', '2026-06-09 12:25:00', '2026-06-09', '24303028', 'Otomatik', 'Işık+Ses', 'Konvansiyonel', 'Bilinmiyor', 'Periyodik Kontrol', 'Bilinmiyor', 'Bilinmeyen', '2025-09-01', '2025-09-01', 'Bilinmiyor', '12V', 'Duman (optik) dedektörü,İhbar butonu', 'İdare', 'Siren,Flaşör', 'Otomatik söndürme,KKT Özellikli yangın tüpleri,CO2 Özellikli yangın tüpleri,Hidrantlar-Yangın dolapları', 'Yok', 'Var', 'Endüstriyel yapı', 'Orta tehlike', '1', 'Bilinmeyen', '2', 'Bilinmeyen', '2025-09-01', 'Bilinmeyen', 'İskan bilgilerine ulaşılamadı', 1, NULL, 1, '', '', 'UYGUNDUR', NULL, '{\"personel_varmi\":\"U\",\"sorumlular_belirlenmismi\":\"U\",\"panel_durumu\":\"U\",\"anons_sistemi\":\"U\",\"bakim_kayitlari\":\"U\",\"sistem_kutugu\":\"U\",\"panel_yerlesim\":\"U\",\"panel_izlenebilirlik\":\"U\",\"adresleme_harita\":\"U\",\"paralel_ihbar\":\"U\",\"koruma_devre\":\"U\",\"devre_ayrilmasi\":\"U\",\"kullanma_talimati\":\"U\",\"aku_durumu\":\"U\",\"ortam_uyumu\":\"U\",\"uyari_yerlesim\":\"U\",\"kablo_uygunluk\":\"U\",\"armatur_uygunluk\":\"U\",\"aydinlatma_varlik\":\"U\",\"yonlendirme_isaretleri\":\"U\",\"aydinlatma_seviye\":\"U\",\"panel_onu_lux\":\"U\",\"cikis_hol_yonlendirme\":\"U\",\"aydinlatma_sure\":\"U\",\"otomatik_devreye_girme\":\"U\",\"damper_izlenebilirlik\":\"U\",\"sondurme_entegrasyon\":\"U\",\"otomasyon_baglanti\":\"U\",\"asansor_davranis\":\"U\",\"kesici_yedek_enerji\":\"U\",\"iklimlendirme_sinyal\":\"U\",\"akis_anahtari_izlenebilirlik\":\"U\",\"basinclandirma_kontrol\":\"U\",\"kapi_tutucu_kontrol\":\"U\",\"gaz_kesme_kontrol\":\"U\"}', '2026-03-03 13:56:35', NULL, NULL),
(4, 2, '44-001-ya-1775435101', '2026-03-05', '2026-02-12 08:00:00', '2027-02-12 18:00:00', '2027-02-12', '24361622', 'Otomatik', 'Işık+Ses', 'Konvansiyonel', 'Malatya İtfaiyesi', 'Periyodik Kontrol', '-', 'Bilinmeyen', '2024-12-03', '2024-12-03', 'Bilinmiyor', '12V', 'Duman (optik) dedektörü,İhbar butonu', 'Yazıhane', 'Siren,Flaşör', 'KKT Özellikli yangın tüpleri,CO2 Özellikli yangın tüpleri,Hidrantlar-Yangın dolapları', 'Yok', 'Var', 'Ticari', 'Düşük tehlike', '4', 'Bilinmeyen', '3', 'Bilinmeyen', '2001-01-01', 'Bilinmeyen', '', 1, NULL, 1, '', '', 'UYGUNDUR', NULL, NULL, '2026-04-06 00:25:01', 'yağmurlu 5C', 'nemli'),
(5, 3, '44-002-ya-1775437877', '2026-03-05', '2026-02-13 08:00:00', '2026-02-13 18:00:00', '2027-02-13', '24361403', 'Otomatik', 'Işık+Ses', 'Konvansiyonel', 'Bilinmiyor', 'Periyodik Kontrol', 'Bilinmiyor', 'Bilinmeyen', NULL, '2024-12-03', 'Bilinmiyor', '12V', 'Duman (optik) dedektörü,İhbar butonu', 'Depo önü', 'Siren,Flaşör', 'KKT Özellikli yangın tüpleri', 'Yok', 'Yok', 'Ticari', 'Düşük tehlike', '4', 'Bilinmeyen', '1', 'Bilinmeyen', '2001-01-01', 'Bilinmeyen', '', 1, NULL, 1, '', 'Kullanımı uygundur.', 'UYGUNDUR', NULL, NULL, '2026-04-06 01:11:17', 'yağmurlu 5C', 'nemli'),
(6, 9, '99-99-ya-1776672871', '2001-01-01', '2001-01-01 00:00:00', '2001-01-01 00:00:00', '2001-01-01', 'test', '', '', '', '', '', '', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, '', '', 1, NULL, 1, '', '', 'UYGUNDUR', NULL, NULL, '2026-04-20 08:14:31', '', ''),
(7, 9, '99-99-ya-1776678943', '2001-01-01', '2001-01-01 00:00:00', '2001-01-01 00:00:00', '2001-01-01', 'test', '', '', '', '', '', '', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, '', '', 2, NULL, 1, '', '', 'UYGUNDUR', NULL, NULL, '2026-04-20 09:55:43', '', ''),
(8, 9, '99-99-ya-1776678974', '2001-10-10', '2001-01-01 00:00:00', '2001-01-01 00:00:00', '2001-01-01', 'test', '', '', '', '', '', '', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, '', '', 2, NULL, 1, '', '', 'UYGUNDUR', NULL, NULL, '2026-04-20 09:56:14', '', '');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `fire_detection_section5_2`
--

CREATE TABLE `fire_detection_section5_2` (
  `id` int(11) NOT NULL,
  `report_id` int(11) DEFAULT NULL,
  `loop_no` varchar(50) DEFAULT NULL,
  `bolum_adi` varchar(255) DEFAULT NULL,
  `ekipman_adi` varchar(255) DEFAULT NULL,
  `projede_mi` varchar(10) DEFAULT NULL,
  `erisim_durumu` varchar(10) DEFAULT NULL,
  `montaj_durumu` varchar(10) DEFAULT NULL,
  `test` varchar(10) DEFAULT NULL,
  `sesli_uyari` varchar(10) DEFAULT NULL,
  `isikli_uyari` varchar(10) DEFAULT NULL,
  `adresleme` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `fire_detection_section5_2`
--

INSERT INTO `fire_detection_section5_2` (`id`, `report_id`, `loop_no`, `bolum_adi`, `ekipman_adi`, `projede_mi`, `erisim_durumu`, `montaj_durumu`, `test`, `sesli_uyari`, `isikli_uyari`, `adresleme`) VALUES
(1, 2, 'Loop 1', '', 'Optik duman dedektörü', '', '', '', '', '', '', ''),
(2, 2, 'Loop 1', '', 'Isı dedektörü', '', '', '', '', '', '', ''),
(3, 2, 'Loop 1', '', 'Yangın Alarm butonu', '', '', '', '', '', '', ''),
(4, 2, 'Loop 1', '', 'Siren', '', '', '', '', '', '', ''),
(5, 2, 'Loop 1', '', 'Flaşör (ışıklı ve sesli)', '', '', '', '', '', '', ''),
(6, 2, 'Loop 1', '', 'Diğer', '', '', '', '', '', '', ''),
(7, 2, 'Loop 2', '', 'Optik duman dedektörü', '', '', '', '', '', '', ''),
(8, 2, 'Loop 2', '', 'Isı dedektörü', '', '', '', '', '', '', ''),
(9, 2, 'Loop 2', '', 'Yangın Alarm butonu', '', '', '', '', '', '', ''),
(10, 2, 'Loop 2', '', 'Siren', '', '', '', '', '', '', ''),
(11, 2, 'Loop 2', '', 'Flaşör (ışıklı ve sesli)', '', '', '', '', '', '', ''),
(12, 2, 'Loop 2', '', 'Diğer', '', '', '', '', '', '', ''),
(61, 3, 'Loop 1', 'kat zemin', 'Optik duman dedektörü', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(62, 3, 'Loop 1', 'kat zemin', 'Isı dedektörü', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(63, 3, 'Loop 1', 'kat zemin', 'Yangın Alarm butonu', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(64, 3, 'Loop 1', 'kat zemin', 'Siren', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(65, 3, 'Loop 1', 'kat zemin', 'Flaşör (ışıklı ve sesli)', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(66, 3, 'Loop 1', 'kat zemin', 'Diğer', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(67, 3, 'Loop 2', 'kat 1', 'Optik duman dedektörü', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(68, 3, 'Loop 2', 'kat 1', 'Isı dedektörü', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(69, 3, 'Loop 2', 'kat 1', 'Yangın Alarm butonu', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(70, 3, 'Loop 2', 'kat 1', 'Siren', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(71, 3, 'Loop 2', 'kat 1', 'Flaşör (ışıklı ve sesli)', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
(72, 3, 'Loop 2', 'kat 1', 'Diğer', 'U', 'U', 'U', 'U', 'U', 'U', 'U');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `general_reports`
--

CREATE TABLE `general_reports` (
  `id` int(11) NOT NULL,
  `kurum_id` int(11) NOT NULL,
  `title` varchar(500) NOT NULL,
  `content` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `general_reports`
--

INSERT INTO `general_reports` (`id`, `kurum_id`, `title`, `content`, `created_at`, `updated_at`) VALUES
(1, 7, 'Beyhekim Sağlık Kampüsü B Blok Biyomedikal Laboratuvarı Elektrik Kontrol Tespitleri', '\n<p><span style=\"font-size: 12px;\">Talebiniz üzerine Elektrik-Elektronik Mühendisimiz tarafından <strong>17.06.2026</strong> tarihinde ilgili <strong>Beyhekim Sağlık Kampüsü</strong>&nbsp;<strong>B Blok Biyomedikal Laboratuvarı</strong> bölümünde kontroller gerçekleştirilmiştir.&nbsp;Tespit edilen hususlar hakkında çalışma yapılması gerekmektedir.</span></p><p><span style=\"font-size: 12px;\"><br><strong>Tespitlerimiz:</strong><br><strong>1- </strong>Mevcutta <span>laboratuvarda bulunan</span>, sonradan birbirine eklenmiş olarak çalıştırılan  münferit tıbbi cihazların (hormon cihazları gibi) topraklama dirençlerinde yükselme olduğu tespit edilmiş olup beslenmelerinin ayrılarak her bir tıbbi cihaza ayrı ayrı linye(besleme hattı) çekilmesi, topraklama direncinin mevcut besleme hattı boyunca yükselmesini engelleyerek can ve mal güvenliğini sağlaması açısından önem arz etmektedir. Ayrıca hata akımı olması durumunda diğer tıbbi cihazların çalışmasını etkilememesi adına her cihaz için ayrı ayrı kaçak akım koruma rölesi tesis edilmesi tavsiye edilir.</span></p><p><span style=\"font-size: 12px;\"><br></span></p><p><span style=\"font-size: 12px;\"><strong>2-</strong> Hormon bölümündeki UPS cihazına bağlı uzatma kablosunda topraklama olmadığı tespit edilmiş olup <u style=\"color: rgb(255, 0, 0);\">acilen</u> önlem alınması gerekmektedir.</span></p><p><span style=\"font-size: 12px;\"><br></span></p><p><strong>3-</strong> Hemogram ve idrar bölümündeki hatta, ölçme esnasında kaçak akım koruma rölesi kesime gittiği için ölçüm alınamamıştır. Bu durum hatta kaçak veya hatta bağlı cihazlarda arıza olabileceğini göstermektedir. İlgili hatta <u style=\"color: rgb(255, 0, 0);\">acilen</u> bakım yapılması gerekmektedir.</p><p><strong><br></strong></p><hr><p><strong>Topraklama Ölçüm Sonuçları</strong></p><table style=\"border-collapse: collapse; width: 55.2561%; height: 91px;\"><tbody>\n<tr>\n	<td style=\"width: 33.3333%;\"><strong>Ölçüm Noktası</strong></td>\n	<td style=\"width: 33.3333%;\"><strong>Ölçülen Değer</strong></td>\n	<td style=\"width: 33.3333%;\"><strong>Uygunluk Değerlendirmesi</strong></td></tr>\n<tr>\n	<td style=\"width: 33.3333%;\">Ana Pano</td>\n	<td style=\"width: 33.3333%;\">0.52</td>\n	<td style=\"width: 33.3333%;\">Uygun</td></tr>\n<tr>\n	<td style=\"width: 33.3333%;\">Şebeke Priz</td>\n	<td style=\"width: 33.3333%;\">1.44</td>\n	<td style=\"width: 33.3333%;\">Uygun</td></tr><tr>\n	<td style=\"width: 33.3333%;\">Hormon Bölümü UPS</td>\n	<td style=\"width: 33.3333%;\">topraklama yok</td>\n	<td style=\"width: 33.3333%;\">Uygun Değil</td></tr><tr>\n	<td style=\"width: 33.3333%;\">Hormon Bölümü UPS2<br></td>\n	<td style=\"width: 33.3333%;\">4.5</td>\n	<td style=\"width: 33.3333%;\">Kabul Edilebilir</td></tr><tr>\n	<td style=\"width: 33.3333%;\">Hormon Bölümü UPS3<br></td>\n	<td style=\"width: 33.3333%;\">1.82</td>\n	<td style=\"width: 33.3333%;\">Uygun</td></tr><tr>\n	<td style=\"width: 33.3333%;\">Hormon Cihazı Arkası UPS</td>\n	<td style=\"width: 33.3333%;\">19.80</td>\n	<td style=\"width: 33.3333%;\">Uygun Değil</td></tr><tr>\n	<td style=\"width: 33.3333%;\">Hormon Arkası Şebeke Priz</td>\n	<td style=\"width: 33.3333%;\">2.4</td>\n	<td style=\"width: 33.3333%;\">Uygun</td></tr><tr>\n	<td style=\"width: 33.3333%;\">Köşe Hormon Arkası Şebeke</td>\n	<td style=\"width: 33.3333%;\">1.04</td>\n	<td style=\"width: 33.3333%;\">Uygun</td></tr><tr>\n	<td style=\"width: 33.3333%;\">Köşe Hormon Arkası UPS<br></td>\n	<td style=\"width: 33.3333%;\">0.68</td>\n	<td style=\"width: 33.3333%;\">Uygun</td></tr><tr>\n	<td style=\"width: 33.3333%;\">Bilgisayar Altı</td>\n	<td style=\"width: 33.3333%;\">4.02</td>\n	<td style=\"width: 33.3333%;\">Kabul Edilebilir</td></tr><tr>\n	<td style=\"width: 33.3333%;\">UPS Bilgisayar</td>\n	<td style=\"width: 33.3333%;\">1.75</td>\n	<td style=\"width: 33.3333%;\">Uygun</td></tr><tr>\n	<td style=\"width: 33.3333%;\">Hemogram Cihazları</td>\n	<td style=\"width: 33.3333%;\">Ölçülemedi</td>\n	<td style=\"width: 33.3333%;\">Uygun Değil</td></tr><tr>\n	<td style=\"width: 33.3333%;\">Hemogram Bilgisayar</td>\n	<td style=\"width: 33.3333%;\">0.87</td>\n	<td style=\"width: 33.3333%;\">Uygun</td></tr><tr>\n	<td style=\"width: 33.3333%;\">İdrar Cihazları</td>\n	<td style=\"width: 33.3333%;\">Ölçülemedi</td>\n	<td style=\"width: 33.3333%;\">ygun Değil<br></td></tr></tbody></table>', '2026-06-18 17:51:26', '2026-06-18 19:23:23');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `general_report_images`
--

CREATE TABLE `general_report_images` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `grounding_reports`
--

CREATE TABLE `grounding_reports` (
  `id` int(11) NOT NULL,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(50) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `control_reason` enum('Periyodik Kontrol','İlk Kontrol') DEFAULT NULL,
  `grounding_type` varchar(50) DEFAULT NULL,
  `weather` varchar(100) DEFAULT NULL,
  `soil_moisture` varchar(100) DEFAULT NULL,
  `changes_exist` tinyint(1) DEFAULT NULL,
  `prev_label_exists` tinyint(1) DEFAULT NULL,
  `panel_id` varchar(100) DEFAULT NULL,
  `measurement_method` enum('Cevrim empedansi','3 Uclu topraklama','Klamp metodu') DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result` enum('UYGUNDUR','UYGUN DEGILDIR') DEFAULT NULL,
  `authorized_person_id` int(11) DEFAULT NULL,
  `status` enum('draft','completed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `device1_id` int(11) DEFAULT NULL,
  `device2_id` int(11) DEFAULT NULL,
  `result_notes_selection` text DEFAULT NULL,
  `protection_measure` varchar(100) DEFAULT NULL,
  `project_info` text DEFAULT NULL,
  `prev_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `grounding_reports`
--

INSERT INTO `grounding_reports` (`id`, `kurum_id`, `report_no`, `report_date`, `start_date`, `end_date`, `next_control_date`, `control_reason`, `grounding_type`, `weather`, `soil_moisture`, `changes_exist`, `prev_label_exists`, `panel_id`, `measurement_method`, `defects`, `notes`, `result`, `authorized_person_id`, `status`, `created_at`, `device1_id`, `device2_id`, `result_notes_selection`, `protection_measure`, `project_info`, `prev_control_date`, `isg_katip_id`) VALUES
(1, 1, '42-001-t', '2026-02-14', '2026-02-14 15:45:00', '2026-02-14 15:45:00', '2026-02-13', 'Periyodik Kontrol', 'Temel', 'bulutlu', 'nemli', 0, 1, '1', 'Cevrim empedansi', 'yok', 'güzel', 'UYGUNDUR', 1, 'draft', '2026-02-15 12:46:26', NULL, NULL, '', '', NULL, NULL, NULL),
(2, 1, '42-001-t', '2026-02-15', '2026-02-05 16:04:00', '2026-02-11 16:04:00', '2027-02-15', 'İlk Kontrol', 'Yüzeysel', 'bulutlu', 'nemli', 0, 0, '', 'Cevrim empedansi', 'a', 'b', 'UYGUNDUR', 1, 'draft', '2026-02-15 13:05:22', 1, NULL, '1,8', 'Koruyucu yalıtma (Sınıf II veya zemin yalıtımı)', NULL, NULL, NULL),
(3, 1, '42-001-t', '2026-02-25', '2026-02-11 21:22:00', '2026-06-09 21:22:00', '2027-06-09', 'Periyodik Kontrol', 'Temel', 'bulutlu 25C', 'nemli', 0, 1, 'Yeni', 'Cevrim empedansi', '', 'Ölçüm sonuçları o günün koşulları için geçerlidir', 'UYGUNDUR', 1, 'draft', '2026-02-15 18:23:58', 1, NULL, '1', 'Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN, IT)', 'Proje mevcut ancak çizen bilgileri bulunamadı.', '2025-09-01', '24303028'),
(4, 1, '42-001-t-1772359481', '2026-03-01', '2026-02-11 12:25:00', '2026-06-09 12:25:00', '2026-06-09', 'Periyodik Kontrol', 'Temel', 'bulutlu 25C', 'nemli', 0, 0, 'Eski', 'Cevrim empedansi', '', 'Ölçüm sonuçları o günün koşulları için geçerlidir', 'UYGUNDUR', 1, 'draft', '2026-03-01 10:04:41', 1, NULL, '1', 'Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN, IT)', 'Proje mevcut ancak çizen bilgileri bulunamadı.', '2025-09-01', '24303028'),
(5, 4, '44-003-t-1772709997', '2026-03-05', '2026-02-13 08:00:00', '2026-02-13 18:00:00', '2027-02-13', 'Periyodik Kontrol', 'Temel', 'Kapalı Hava 6C', 'nemli', 0, 0, 'Mağaza Panosu', '3 Uclu topraklama', '', 'Mağaza AVM içinde faaliyet göstermektedir.', 'UYGUNDUR', 1, 'draft', '2026-03-05 11:26:37', 1, NULL, '1', 'Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN, IT)', 'Proje Mağazada değil. AVM yönetiminde mevcuttur.', '2025-09-01', '24360984'),
(6, 8, '42-002-t-1775219033', '2026-04-03', '2026-04-03 08:00:00', '2026-04-03 18:00:00', '2027-04-03', 'Periyodik Kontrol', 'Temel', 'Yağmuru 6C', 'nemli', 0, 1, '1', 'Cevrim empedansi', '', 'Tesis Kullanıma uygundur.', 'UYGUNDUR', 1, 'draft', '2026-04-03 12:23:53', 1, NULL, '1', 'Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN, IT)', 'Proje mevcut ancak çizen bilgileri bulunamadı.', '2027-04-03', '25126107'),
(7, 2, '44-001-t-1775432568', '2026-03-05', '2026-02-12 08:00:00', '2026-02-12 18:00:00', '2027-02-12', 'Periyodik Kontrol', 'Temel', 'Yağmurlu 5C', 'nemli', 0, 0, 'Ev Konsept', 'Cevrim empedansi', '', 'Tesis kullanıma uygundur.', 'UYGUNDUR', 1, 'draft', '2026-04-05 23:42:48', 1, NULL, '1', 'Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN, IT)', 'Proje mevcut ancak çizen bilgileri bulunamadı.', '2024-12-03', '24361622'),
(8, 3, '44-002-t-1775436905', '2026-03-05', '2026-02-13 08:00:00', '2026-02-13 18:00:00', '2027-02-13', 'Periyodik Kontrol', 'Temel', 'yağmurlu 5C', 'nemli', 0, 0, 'Villa Mağazası', 'Cevrim empedansi', '', 'Kullanıma uygundur.', 'UYGUNDUR', 1, 'draft', '2026-04-06 00:55:05', 1, NULL, '1', 'Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN, IT)', 'Proje mevcut ancak çizen bilgileri bulunamadı.', '2024-12-03', '24361403'),
(9, 5, '44-004-t-1775438643', '2026-03-05', '2026-02-13 08:00:00', '2026-02-13 18:00:00', '2027-02-13', 'Periyodik Kontrol', 'Temel', 'yağmurlu 5C', 'nemli', 0, 1, 'Dörtyol Mağaza', 'Cevrim empedansi', '', 'Kullanımı uygundur.', 'UYGUNDUR', 1, 'draft', '2026-04-06 01:24:03', 1, NULL, '1', 'Eşpotansiyel topraklama ve beslemenin otomatik kesilmesi (TT, TN, IT)', 'Proje mevcut ancak çizen bilgileri bulunamadı.', '2024-12-03', '24361154'),
(10, 9, '99-99-t-1776672882', '2001-01-01', '2001-01-01 00:00:00', '2001-01-01 00:00:00', '2001-01-01', '', '', '', '', 0, 0, '', 'Cevrim empedansi', '', '', 'UYGUNDUR', 1, 'draft', '2026-04-20 08:14:42', 1, NULL, '', '', '', NULL, 'test');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ic_tesisat_panels`
--

CREATE TABLE `ic_tesisat_panels` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `panel_name` varchar(255) NOT NULL,
  `panel_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ic_tesisat_panels`
--

INSERT INTO `ic_tesisat_panels` (`id`, `report_id`, `panel_name`, `panel_order`, `created_at`, `notes`) VALUES
(1, 2, '1', 1, '2026-02-26 00:23:19', NULL),
(2, 2, '2', 2, '2026-02-26 00:25:39', NULL),
(3, 2, '3', 3, '2026-02-26 00:26:12', NULL),
(4, 5, 'ana pano', 1, '2026-03-03 17:22:38', '123 234 234'),
(5, 5, 'pano 1', 2, '2026-03-03 17:23:00', NULL),
(6, 5, 'pano 2', 3, '2026-03-03 17:23:06', NULL),
(7, 5, 'pano 3', 4, '2026-03-03 17:23:09', '123 324'),
(8, 5, 'pano 4', 5, '2026-03-03 17:23:13', NULL),
(9, 5, 'pano 5', 6, '2026-03-03 17:23:17', NULL),
(10, 6, 'Ana Pano', 1, '2026-03-04 13:07:02', NULL),
(11, 6, 'Tali Pano', 2, '2026-03-04 13:07:07', NULL),
(13, 8, 'Ana sayac panosu', 1, '2026-04-05 15:42:00', NULL),
(14, 8, 'tali pano', 2, '2026-04-05 15:42:17', NULL),
(15, 11, 'a', 1, '2026-04-20 08:10:53', NULL),
(16, 11, 'b', 2, '2026-04-20 08:10:55', NULL),
(17, 11, 'v', 3, '2026-04-26 15:58:26', NULL),
(18, 11, 'cc', 4, '2026-04-26 16:42:58', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ic_tesisat_photos`
--

CREATE TABLE `ic_tesisat_photos` (
  `id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `photo_type` enum('normal','termal') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ic_tesisat_photos`
--

INSERT INTO `ic_tesisat_photos` (`id`, `panel_id`, `photo_type`, `file_path`, `created_at`) VALUES
(1, 1, 'normal', '/uploads/ic_tesisat/2/1/normal_1772065410_327.jpeg', '2026-02-26 00:23:30'),
(2, 1, 'termal', '/uploads/ic_tesisat/2/1/termal_1772065414_542.png', '2026-02-26 00:23:34'),
(3, 1, 'normal', '/uploads/ic_tesisat/2/1/normal_1772065420_803.png', '2026-02-26 00:23:40'),
(4, 2, 'normal', '/uploads/ic_tesisat/2/2/normal_1772065544_765.jpg', '2026-02-26 00:25:44'),
(5, 2, 'termal', '/uploads/ic_tesisat/2/2/termal_1772065546_298.jpeg', '2026-02-26 00:25:46'),
(6, 3, 'normal', '/uploads/ic_tesisat/2/3/normal_1772065578_901.jpg', '2026-02-26 00:26:18'),
(7, 3, 'termal', '/uploads/ic_tesisat/2/3/termal_1772065580_369.jpeg', '2026-02-26 00:26:20'),
(10, 4, 'normal', '/uploads/ic_tesisat/5/4/normal_1772558791_643.png', '2026-03-03 17:26:31'),
(11, 4, 'termal', '/uploads/ic_tesisat/5/4/termal_1772558826_959.jpg', '2026-03-03 17:27:06'),
(14, 5, 'termal', '/uploads/ic_tesisat/5/5/termal_1772558864_226.jpg', '2026-03-03 17:27:44'),
(15, 5, 'termal', '/uploads/ic_tesisat/5/5/termal_1772558864_503.jpg', '2026-03-03 17:27:44'),
(16, 5, 'normal', '/uploads/ic_tesisat/5/5/normal_1772558876_760.png', '2026-03-03 17:27:56'),
(17, 6, 'normal', '/uploads/ic_tesisat/5/6/normal_1772558892_797.png', '2026-03-03 17:28:12'),
(18, 6, 'termal', '/uploads/ic_tesisat/5/6/termal_1772558897_982.jpg', '2026-03-03 17:28:17'),
(19, 7, 'normal', '/uploads/ic_tesisat/5/7/normal_1772558940_109.png', '2026-03-03 17:29:00'),
(20, 7, 'termal', '/uploads/ic_tesisat/5/7/termal_1772558946_380.jpg', '2026-03-03 17:29:06'),
(21, 7, 'termal', '/uploads/ic_tesisat/5/7/termal_1772558946_459.jpg', '2026-03-03 17:29:06'),
(22, 9, 'normal', '/uploads/ic_tesisat/5/9/normal_1772559202_353.png', '2026-03-03 17:33:22'),
(23, 9, 'normal', '/uploads/ic_tesisat/5/9/normal_1772559210_220.png', '2026-03-03 17:33:30'),
(24, 9, 'termal', '/uploads/ic_tesisat/5/9/termal_1772559262_332.jpg', '2026-03-03 17:34:22'),
(25, 9, 'termal', '/uploads/ic_tesisat/5/9/termal_1772559262_910.jpg', '2026-03-03 17:34:22'),
(26, 8, 'termal', '/uploads/ic_tesisat/5/8/termal_1772559302_562.jpg', '2026-03-03 17:35:02'),
(27, 8, 'termal', '/uploads/ic_tesisat/5/8/termal_1772559302_212.jpg', '2026-03-03 17:35:02'),
(28, 8, 'termal', '/uploads/ic_tesisat/5/8/termal_1772559302_451.jpg', '2026-03-03 17:35:02'),
(29, 8, 'termal', '/uploads/ic_tesisat/5/8/termal_1772559302_718.jpg', '2026-03-03 17:35:02'),
(30, 8, 'normal', '/uploads/ic_tesisat/5/8/normal_1772559457_701.jpg', '2026-03-03 17:37:37'),
(31, 10, 'normal', '/uploads/ic_tesisat/6/10/normal_1772629865_564.png', '2026-03-04 13:11:05'),
(32, 10, 'termal', '/uploads/ic_tesisat/6/10/termal_1772629927_175.jpg', '2026-03-04 13:12:07'),
(33, 10, 'termal', '/uploads/ic_tesisat/6/10/termal_1772629927_609.jpg', '2026-03-04 13:12:07'),
(34, 10, 'termal', '/uploads/ic_tesisat/6/10/termal_1772629945_527.jpg', '2026-03-04 13:12:25'),
(35, 11, 'normal', '/uploads/ic_tesisat/6/11/normal_1772629972_214.png', '2026-03-04 13:12:52'),
(36, 11, 'termal', '/uploads/ic_tesisat/6/11/termal_1772629978_419.jpg', '2026-03-04 13:12:58'),
(37, 11, 'termal', '/uploads/ic_tesisat/6/11/termal_1772629978_994.jpg', '2026-03-04 13:12:58'),
(38, 11, 'termal', '/uploads/ic_tesisat/6/11/termal_1772629978_687.jpg', '2026-03-04 13:12:58'),
(39, 13, 'normal', '/uploads/ic_tesisat/8/13/normal_1775403807_227.jpeg', '2026-04-05 15:43:27'),
(40, 14, 'normal', '/uploads/ic_tesisat/8/14/normal_1775403812_567.jpeg', '2026-04-05 15:43:32'),
(41, 15, 'normal', '/uploads/ic_tesisat/11/15/normal_1777210333_712.jpeg', '2026-04-26 13:32:14'),
(42, 15, 'normal', '/uploads/ic_tesisat/11/15/normal_1777210338_729.jpg', '2026-04-26 13:32:18'),
(43, 15, 'normal', '/uploads/ic_tesisat/11/15/normal_1777210354_380.jpg', '2026-04-26 13:32:35'),
(44, 17, 'normal', '/uploads/ic_tesisat/11/17/normal_1777221915_486.jpeg', '2026-04-26 16:45:15'),
(45, 17, 'normal', '/uploads/ic_tesisat/11/17/normal_1777221915_623.jpeg', '2026-04-26 16:45:15'),
(46, 17, 'normal', '/uploads/ic_tesisat/11/17/normal_1777221915_239.jpg', '2026-04-26 16:45:16'),
(47, 17, 'normal', '/uploads/ic_tesisat/11/17/normal_1777221933_205.jpg', '2026-04-26 16:45:33'),
(48, 17, 'normal', '/uploads/ic_tesisat/11/17/normal_1777221935_884.jpeg', '2026-04-26 16:45:35'),
(49, 17, 'normal', '/uploads/ic_tesisat/11/17/normal_1777221940_859.jpeg', '2026-04-26 16:45:40'),
(50, 17, 'normal', '/uploads/ic_tesisat/11/17/normal_1777221943_762.jpg', '2026-04-26 16:45:43');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ic_tesisat_section5`
--

CREATE TABLE `ic_tesisat_section5` (
  `id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `question_key` varchar(100) NOT NULL,
  `answer` enum('U','UD','UG') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ic_tesisat_section5`
--

INSERT INTO `ic_tesisat_section5` (`id`, `panel_id`, `question_key`, `answer`) VALUES
(29, 5, 'kablo_sebeke', 'U'),
(30, 5, 'dis_darbe', 'U'),
(31, 5, 'kablo_donanim', 'U'),
(32, 5, 'yabanci_malzeme', 'U'),
(33, 5, 'pano_sabitleme', 'U'),
(34, 5, 'zemin_izolasyon', 'U'),
(35, 5, 'topraklama_iletken', 'U'),
(36, 5, 'ek_pot_iletken', 'U'),
(37, 5, 'ana_pot_iletken', 'U'),
(38, 5, 'kapak_6mm', 'U'),
(39, 5, 'elektriksel_olmayan', 'U'),
(40, 5, 'guvenlik_devre', 'U'),
(41, 5, 'bant_ayirma', 'U'),
(42, 5, 'pano_kapak_erisim', 'U'),
(43, 5, 'semalar', 'U'),
(44, 5, 'tehlike_isaretleri', 'U'),
(45, 5, 'koruma_etiket', 'U'),
(46, 5, 'tesisat_yontemi', 'U'),
(47, 5, 'kablo_renk', 'U'),
(48, 5, 'yangin_engeli', 'U'),
(49, 5, 'fotograf_tarihi', 'UG'),
(50, 5, 'fotograf_no', 'UG'),
(51, 5, 'kontak_gevsekligi', 'U'),
(52, 5, 'asiri_yuk_isinma', 'U'),
(53, 5, 'yangin_sondurme', 'U'),
(54, 5, 'korozyon', 'U'),
(55, 5, 'ekipman_temizlik', 'U'),
(56, 5, 'acil_aydinlatma', 'U'),
(57, 4, 'kablo_sebeke', 'U'),
(58, 4, 'dis_darbe', 'U'),
(59, 4, 'kablo_donanim', 'U'),
(60, 4, 'yabanci_malzeme', 'U'),
(61, 4, 'pano_sabitleme', 'U'),
(62, 4, 'zemin_izolasyon', 'U'),
(63, 4, 'topraklama_iletken', 'U'),
(64, 4, 'ek_pot_iletken', 'U'),
(65, 4, 'ana_pot_iletken', 'U'),
(66, 4, 'kapak_6mm', 'U'),
(67, 4, 'elektriksel_olmayan', 'U'),
(68, 4, 'guvenlik_devre', 'U'),
(69, 4, 'bant_ayirma', 'U'),
(70, 4, 'pano_kapak_erisim', 'U'),
(71, 4, 'semalar', 'U'),
(72, 4, 'tehlike_isaretleri', 'U'),
(73, 4, 'koruma_etiket', 'U'),
(74, 4, 'kablo_yolu', 'U'),
(75, 4, 'tesisat_yontemi', 'U'),
(76, 4, 'kablo_renk', 'U'),
(77, 4, 'yangin_engeli', 'U'),
(78, 4, 'fotograf_tarihi', 'UG'),
(79, 4, 'fotograf_no', 'UG'),
(80, 4, 'kontak_gevsekligi', 'U'),
(81, 4, 'asiri_yuk_isinma', 'U'),
(82, 4, 'yangin_sondurme', 'U'),
(83, 4, 'korozyon', 'U'),
(84, 4, 'ekipman_temizlik', 'U'),
(85, 4, 'acil_aydinlatma', 'U'),
(86, 6, 'kablo_sebeke', 'U'),
(87, 6, 'dis_darbe', 'U'),
(88, 6, 'kablo_donanim', 'U'),
(89, 6, 'yabanci_malzeme', 'U'),
(90, 6, 'pano_sabitleme', 'U'),
(91, 6, 'zemin_izolasyon', 'U'),
(92, 6, 'topraklama_iletken', 'U'),
(93, 6, 'ek_pot_iletken', 'U'),
(94, 6, 'ana_pot_iletken', 'U'),
(95, 6, 'kapak_6mm', 'U'),
(96, 6, 'elektriksel_olmayan', 'U'),
(97, 6, 'guvenlik_devre', 'U'),
(98, 6, 'bant_ayirma', 'U'),
(99, 6, 'pano_kapak_erisim', 'U'),
(100, 6, 'semalar', 'U'),
(101, 6, 'tehlike_isaretleri', 'U'),
(102, 6, 'koruma_etiket', 'U'),
(103, 6, 'kablo_yolu', 'U'),
(104, 6, 'tesisat_yontemi', 'U'),
(105, 6, 'kablo_renk', 'U'),
(106, 6, 'yangin_engeli', 'U'),
(107, 6, 'fotograf_tarihi', 'UG'),
(108, 6, 'fotograf_no', 'UG'),
(109, 6, 'kontak_gevsekligi', 'U'),
(110, 6, 'asiri_yuk_isinma', 'U'),
(111, 6, 'yangin_sondurme', 'U'),
(112, 6, 'korozyon', 'U'),
(113, 6, 'ekipman_temizlik', 'U'),
(114, 6, 'acil_aydinlatma', 'U'),
(115, 7, 'kablo_sebeke', 'U'),
(116, 7, 'dis_darbe', 'U'),
(117, 7, 'kablo_donanim', 'U'),
(118, 7, 'yabanci_malzeme', 'U'),
(119, 7, 'pano_sabitleme', 'U'),
(120, 7, 'zemin_izolasyon', 'U'),
(121, 7, 'topraklama_iletken', 'U'),
(122, 7, 'ek_pot_iletken', 'U'),
(123, 7, 'ana_pot_iletken', 'U'),
(124, 7, 'kapak_6mm', 'U'),
(125, 7, 'elektriksel_olmayan', 'U'),
(126, 7, 'guvenlik_devre', 'U'),
(127, 7, 'bant_ayirma', 'U'),
(128, 7, 'pano_kapak_erisim', 'U'),
(129, 7, 'semalar', 'U'),
(130, 7, 'tehlike_isaretleri', 'U'),
(131, 7, 'koruma_etiket', 'U'),
(132, 7, 'kablo_yolu', 'U'),
(133, 7, 'tesisat_yontemi', 'U'),
(134, 7, 'kablo_renk', 'U'),
(135, 7, 'yangin_engeli', 'U'),
(136, 7, 'fotograf_tarihi', 'UG'),
(137, 7, 'fotograf_no', 'UG'),
(138, 7, 'kontak_gevsekligi', 'U'),
(139, 7, 'asiri_yuk_isinma', 'U'),
(140, 7, 'yangin_sondurme', 'U'),
(141, 7, 'korozyon', 'U'),
(142, 7, 'ekipman_temizlik', 'U'),
(143, 7, 'acil_aydinlatma', 'U'),
(144, 8, 'kablo_sebeke', 'U'),
(145, 8, 'dis_darbe', 'U'),
(146, 8, 'kablo_donanim', 'U'),
(147, 8, 'yabanci_malzeme', 'U'),
(148, 8, 'pano_sabitleme', 'U'),
(149, 8, 'zemin_izolasyon', 'U'),
(150, 8, 'topraklama_iletken', 'U'),
(151, 8, 'ek_pot_iletken', 'U'),
(152, 8, 'ana_pot_iletken', 'U'),
(153, 8, 'kapak_6mm', 'U'),
(154, 8, 'elektriksel_olmayan', 'U'),
(155, 8, 'guvenlik_devre', 'U'),
(156, 8, 'bant_ayirma', 'U'),
(157, 8, 'pano_kapak_erisim', 'U'),
(158, 8, 'semalar', 'U'),
(159, 8, 'tehlike_isaretleri', 'U'),
(160, 8, 'koruma_etiket', 'U'),
(161, 8, 'kablo_yolu', 'U'),
(162, 8, 'tesisat_yontemi', 'U'),
(163, 8, 'kablo_renk', 'U'),
(164, 8, 'yangin_engeli', 'U'),
(165, 8, 'fotograf_tarihi', 'UG'),
(166, 8, 'fotograf_no', 'UG'),
(167, 8, 'kontak_gevsekligi', 'U'),
(168, 8, 'asiri_yuk_isinma', 'U'),
(169, 8, 'yangin_sondurme', 'U'),
(170, 8, 'korozyon', 'U'),
(171, 8, 'ekipman_temizlik', 'U'),
(172, 8, 'acil_aydinlatma', 'U'),
(173, 9, 'kablo_sebeke', 'U'),
(174, 9, 'dis_darbe', 'U'),
(175, 9, 'kablo_donanim', 'U'),
(176, 9, 'yabanci_malzeme', 'U'),
(177, 9, 'pano_sabitleme', 'U'),
(178, 9, 'zemin_izolasyon', 'U'),
(179, 9, 'topraklama_iletken', 'U'),
(180, 9, 'ek_pot_iletken', 'U'),
(181, 9, 'ana_pot_iletken', 'U'),
(182, 9, 'kapak_6mm', 'U'),
(183, 9, 'elektriksel_olmayan', 'U'),
(184, 9, 'guvenlik_devre', 'U'),
(185, 9, 'bant_ayirma', 'U'),
(186, 9, 'pano_kapak_erisim', 'U'),
(187, 9, 'semalar', 'U'),
(188, 9, 'tehlike_isaretleri', 'U'),
(189, 9, 'koruma_etiket', 'U'),
(190, 9, 'kablo_yolu', 'U'),
(191, 9, 'tesisat_yontemi', 'U'),
(192, 9, 'kablo_renk', 'U'),
(193, 9, 'yangin_engeli', 'U'),
(194, 9, 'fotograf_tarihi', 'UG'),
(195, 9, 'fotograf_no', 'UG'),
(196, 9, 'kontak_gevsekligi', 'U'),
(197, 9, 'asiri_yuk_isinma', 'U'),
(198, 9, 'yangin_sondurme', 'U'),
(199, 9, 'korozyon', 'U'),
(200, 9, 'ekipman_temizlik', 'U'),
(201, 9, 'acil_aydinlatma', 'U'),
(202, 10, 'kablo_sebeke', 'U'),
(203, 10, 'dis_darbe', 'U'),
(204, 10, 'kablo_donanim', 'U'),
(205, 10, 'yabanci_malzeme', 'U'),
(206, 10, 'pano_sabitleme', 'U'),
(207, 10, 'zemin_izolasyon', 'U'),
(208, 10, 'topraklama_iletken', 'U'),
(209, 10, 'ek_pot_iletken', 'U'),
(210, 10, 'ana_pot_iletken', 'U'),
(211, 10, 'kapak_6mm', 'U'),
(212, 10, 'elektriksel_olmayan', 'U'),
(213, 10, 'guvenlik_devre', 'U'),
(214, 10, 'bant_ayirma', 'U'),
(215, 10, 'pano_kapak_erisim', 'U'),
(216, 10, 'semalar', 'U'),
(217, 10, 'tehlike_isaretleri', 'U'),
(218, 10, 'koruma_etiket', 'U'),
(219, 10, 'kablo_yolu', 'U'),
(220, 10, 'tesisat_yontemi', 'U'),
(221, 10, 'kablo_renk', 'U'),
(222, 10, 'yangin_engeli', 'U'),
(223, 10, 'fotograf_tarihi', 'UG'),
(224, 10, 'fotograf_no', 'UG'),
(225, 10, 'kontak_gevsekligi', 'U'),
(226, 10, 'asiri_yuk_isinma', 'U'),
(227, 10, 'yangin_sondurme', 'U'),
(228, 10, 'korozyon', 'U'),
(229, 10, 'ekipman_temizlik', 'U'),
(230, 10, 'acil_aydinlatma', 'U'),
(231, 11, 'kablo_sebeke', 'U'),
(232, 11, 'dis_darbe', 'U'),
(233, 11, 'kablo_donanim', 'U'),
(234, 11, 'yabanci_malzeme', 'U'),
(235, 11, 'pano_sabitleme', 'U'),
(236, 11, 'zemin_izolasyon', 'U'),
(237, 11, 'topraklama_iletken', 'U'),
(238, 11, 'ek_pot_iletken', 'U'),
(239, 11, 'ana_pot_iletken', 'U'),
(240, 11, 'kapak_6mm', 'U'),
(241, 11, 'elektriksel_olmayan', 'U'),
(242, 11, 'guvenlik_devre', 'U'),
(243, 11, 'bant_ayirma', 'U'),
(244, 11, 'pano_kapak_erisim', 'U'),
(245, 11, 'semalar', 'U'),
(246, 11, 'tehlike_isaretleri', 'U'),
(247, 11, 'koruma_etiket', 'U'),
(248, 11, 'kablo_yolu', 'U'),
(249, 11, 'tesisat_yontemi', 'U'),
(250, 11, 'kablo_renk', 'U'),
(251, 11, 'yangin_engeli', 'U'),
(252, 11, 'fotograf_tarihi', 'UG'),
(253, 11, 'fotograf_no', 'UG'),
(254, 11, 'kontak_gevsekligi', 'U'),
(255, 11, 'asiri_yuk_isinma', 'U'),
(256, 11, 'yangin_sondurme', 'U'),
(257, 11, 'korozyon', 'U'),
(258, 11, 'ekipman_temizlik', 'U'),
(259, 11, 'acil_aydinlatma', 'U'),
(318, 13, 'kablo_sebeke', 'U'),
(319, 13, 'dis_darbe', 'U'),
(320, 13, 'kablo_donanim', 'U'),
(321, 13, 'yabanci_malzeme', 'U'),
(322, 13, 'pano_sabitleme', 'U'),
(323, 13, 'zemin_izolasyon', 'U'),
(324, 13, 'topraklama_iletken', 'U'),
(325, 13, 'ek_pot_iletken', 'U'),
(326, 13, 'ana_pot_iletken', 'U'),
(327, 13, 'kapak_6mm', 'U'),
(328, 13, 'elektriksel_olmayan', 'U'),
(329, 13, 'guvenlik_devre', 'U'),
(330, 13, 'bant_ayirma', 'U'),
(331, 13, 'pano_kapak_erisim', 'U'),
(332, 13, 'semalar', 'U'),
(333, 13, 'tehlike_isaretleri', 'U'),
(334, 13, 'koruma_etiket', 'U'),
(335, 13, 'kablo_yolu', 'U'),
(336, 13, 'tesisat_yontemi', 'U'),
(337, 13, 'kablo_renk', 'U'),
(338, 13, 'yangin_engeli', 'U'),
(339, 13, 'fotograf_tarihi', ''),
(340, 13, 'fotograf_no', 'U'),
(341, 13, 'kontak_gevsekligi', 'U'),
(342, 13, 'asiri_yuk_isinma', 'U'),
(343, 13, 'yangin_sondurme', 'U'),
(344, 13, 'korozyon', 'U'),
(345, 13, 'ekipman_temizlik', 'U'),
(346, 13, 'acil_aydinlatma', 'U'),
(347, 14, 'kablo_sebeke', 'U'),
(348, 14, 'dis_darbe', 'U'),
(349, 14, 'kablo_donanim', 'U'),
(350, 14, 'yabanci_malzeme', 'U'),
(351, 14, 'pano_sabitleme', 'U'),
(352, 14, 'zemin_izolasyon', 'U'),
(353, 14, 'topraklama_iletken', 'U'),
(354, 14, 'ek_pot_iletken', 'U'),
(355, 14, 'ana_pot_iletken', 'U'),
(356, 14, 'kapak_6mm', 'U'),
(357, 14, 'elektriksel_olmayan', 'U'),
(358, 14, 'guvenlik_devre', 'U'),
(359, 14, 'bant_ayirma', 'U'),
(360, 14, 'pano_kapak_erisim', 'U'),
(361, 14, 'semalar', 'U'),
(362, 14, 'tehlike_isaretleri', 'U'),
(363, 14, 'koruma_etiket', 'U'),
(364, 14, 'kablo_yolu', 'U'),
(365, 14, 'tesisat_yontemi', 'U'),
(366, 14, 'kablo_renk', 'U'),
(367, 14, 'yangin_engeli', 'U'),
(368, 14, 'fotograf_tarihi', ''),
(369, 14, 'fotograf_no', 'UD'),
(370, 14, 'kontak_gevsekligi', 'U'),
(371, 14, 'asiri_yuk_isinma', 'U'),
(372, 14, 'yangin_sondurme', 'U'),
(373, 14, 'korozyon', 'U'),
(374, 14, 'ekipman_temizlik', 'U'),
(375, 14, 'acil_aydinlatma', 'U');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ic_tesisat_section6_1`
--

CREATE TABLE `ic_tesisat_section6_1` (
  `id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `zx` varchar(50) DEFAULT NULL,
  `zln` varchar(50) DEFAULT NULL,
  `voltage_ff` varchar(50) DEFAULT NULL,
  `voltage_ln` varchar(50) DEFAULT NULL,
  `voltage_npe` varchar(50) DEFAULT NULL,
  `short_circuit_3ph` varchar(50) DEFAULT NULL,
  `dkd_type` varchar(100) DEFAULT NULL,
  `dkd_current` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ic_tesisat_section6_1`
--

INSERT INTO `ic_tesisat_section6_1` (`id`, `panel_id`, `zx`, `zln`, `voltage_ff`, `voltage_ln`, `voltage_npe`, `short_circuit_3ph`, `dkd_type`, `dkd_current`) VALUES
(13, 8, '1.47', '1.89', '254.88', '254.91', '0.09', '1', 'tip2', '1'),
(14, 9, '1.87', '1.46', '254.88', '258.03', '0.12', '1', 'tip2', '1'),
(15, 4, '1.54', '1.46', '254.71', '254.12', '0.05', '1', 'tip2', '1'),
(16, 5, '1.55', '1.49', '254.58', '253.25', '0.12', '1', 'tip2', '1'),
(17, 6, '1.87', '1.79', '254.87', '253.97', '0.14', '1', 'tip2', '1'),
(18, 7, '1.57', '1.69', '255.01', '258.03', '0.11', '1', 'tip2', '1'),
(19, 10, '1.57', '1.49', '254.87', '254.12', '0.14', '1', 'tip2', '1'),
(20, 11, '1.42', '1.67', '254.65', '253.97', '0.13', '1', 'tip2', '1'),
(21, 13, '1.54', '1.79', '231.2', '229.8', '1.0', '-', '-', '-'),
(22, 14, '1.56', '1.88', '231', '229.6', '1.1', '-', '-', '-'),
(24, 17, '2.12', '2.31', '225.3', '227.9', '1.06', '0.70', '-', '-'),
(25, 15, '1.85', '1.81', '234.0', '230.5', '1.71', '1.08', '-', '-'),
(26, 16, '1.99', '1.65', '233.6', '228.5', '1.41', '1.11', '-', '-');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ic_tesisat_section6_1_rows`
--

CREATE TABLE `ic_tesisat_section6_1_rows` (
  `id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `no_col` varchar(20) DEFAULT NULL,
  `linye_adi` varchar(255) DEFAULT NULL,
  `acma_egrisi` varchar(50) DEFAULT NULL,
  `kutup_sayisi` varchar(20) DEFAULT NULL,
  `in_a` varchar(50) DEFAULT NULL,
  `icu` varchar(50) DEFAULT NULL,
  `faz_kesiti` varchar(50) DEFAULT NULL,
  `npen_kesiti` varchar(50) DEFAULT NULL,
  `pe_kesiti` varchar(50) DEFAULT NULL,
  `ib_tasarim` varchar(50) DEFAULT NULL,
  `iz_kapasite` varchar(50) DEFAULT NULL,
  `rcd_ia` varchar(50) DEFAULT NULL,
  `rcd_ta` varchar(50) DEFAULT NULL,
  `sonuc` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ic_tesisat_section6_1_rows`
--

INSERT INTO `ic_tesisat_section6_1_rows` (`id`, `panel_id`, `no_col`, `linye_adi`, `acma_egrisi`, `kutup_sayisi`, `in_a`, `icu`, `faz_kesiti`, `npen_kesiti`, `pe_kesiti`, `ib_tasarim`, `iz_kapasite`, `rcd_ia`, `rcd_ta`, `sonuc`) VALUES
(13, 8, '', '', '-', '-', '-', '-', '16', '-', '25', '-', '-', '30', '30', 'Uygun'),
(14, 9, '', '', '-', '-', '-', '-', '16', '-', '25', '-', '-', '30', '30', 'Uygun'),
(15, 4, '', '', '-', '-', '-', '-', '16', '-', '25', '-', '-', '30', '30', 'Uygun'),
(16, 5, '', '', '-', '-', '-', '-', '16', '-', '25', '-', '-', '30', '30', 'Uygun'),
(17, 6, '', '', '-', '-', '-', '-', '16', '-', '25', '-', '-', '30', '30', 'Uygun'),
(18, 7, '', '', '-', '-', '-', '-', '16', '-', '25', '-', '-', '30', '30', 'Uygun'),
(19, 10, '', 'Ana Pano', '-', '-', '-', '-', '16', '25', '25', '-', '-', '30', '30', 'Uygun'),
(20, 11, '', 'Tali Pano', '-', '-', '-', '-', '16', '25', '25', '-', '-', '30', '30', 'Uygun'),
(21, 13, '1', 'Ana Pano', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', 'Gerekli Değil'),
(22, 14, '1', 'Tali Pano', '-', '-', '-', '-', '25', '16', '16', '-', '-', '27', '29', 'Uygun'),
(24, 17, '1', 'v', 'C', '4', '630', '6kA', '1*10', '1*10', '1*10', '50', '48', '24', '25.29', 'Uygun'),
(25, 15, '1', 'a', 'C', '4', '630', '6kA', '1*10', '1*10', '1*10', '50', '48', '30', '28.55', 'Uygun'),
(26, 16, '1', 'b', 'C', '4', '630', '6kA', '1*10', '1*10', '1*10', '50', '48', '27', '29.36', 'Uygun');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ic_tesisat_section6_2_rows`
--

CREATE TABLE `ic_tesisat_section6_2_rows` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `no_col` varchar(20) DEFAULT NULL,
  `bolum` varchar(255) DEFAULT NULL,
  `pd_kesiti` varchar(50) DEFAULT NULL,
  `pd_sureklilik` varchar(50) DEFAULT NULL,
  `tpd_kesiti` varchar(50) DEFAULT NULL,
  `tpd_sureklilik` varchar(50) DEFAULT NULL,
  `sonuc` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ic_tesisat_section6_2_rows`
--

INSERT INTO `ic_tesisat_section6_2_rows` (`id`, `report_id`, `no_col`, `bolum`, `pd_kesiti`, `pd_sureklilik`, `tpd_kesiti`, `tpd_sureklilik`, `sonuc`) VALUES
(8, 5, '1', 'Ana pano', '25', '0.2', '16', '0.21', 'Uygun'),
(9, 5, '2', 'Pano 1', '25', '0.31', '16', '0.41', 'Uygun'),
(10, 5, '3', 'Pano 2', '25', '0.29', '16', '0.38', 'Uygun'),
(11, 5, '4', 'Pano 3', '25', '0.41', '16', '0.31', 'Uygun'),
(12, 5, '5', 'Pano 4', '25', '0.38', '16', '0.35', 'Uygun'),
(13, 5, '6', 'Pano 5', '25', '0.37', '16', '0.41', 'Uygun'),
(14, 6, '1', 'Ana pano', '25', '0.3', '16', '0.19', 'Uygun'),
(15, 6, '2', 'Tali Pano', '25', '0.29', '16', '0.41', 'Uygun'),
(17, 8, '1', 'Tali Pano', '16', '0.21', '16', '0.19', 'Uygun');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ic_tesisat_section6_3_rows`
--

CREATE TABLE `ic_tesisat_section6_3_rows` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `no_col` varchar(20) DEFAULT NULL,
  `hali_yeri` varchar(255) DEFAULT NULL,
  `eni` varchar(50) DEFAULT NULL,
  `boyu` varchar(50) DEFAULT NULL,
  `direnc` varchar(50) DEFAULT NULL,
  `sonuc` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ic_tesisat_section6_3_rows`
--

INSERT INTO `ic_tesisat_section6_3_rows` (`id`, `report_id`, `no_col`, `hali_yeri`, `eni`, `boyu`, `direnc`, `sonuc`) VALUES
(4, 5, '1', 'Ana pano', '1.5', '3.5', '2510', 'Uygun'),
(5, 5, '2', 'Pano 1', '1.5', '2.5', '2540', 'Uygun'),
(6, 5, '3', 'Pano 2', '1.5', '2.5', '2470', 'Uygun'),
(7, 5, '4', 'Pano 3', '1.5', '3', '2800', 'Uygun'),
(8, 5, '5', 'Pano 4', '1.5', '2.5', '2510', 'Uygun'),
(9, 5, '6', 'Pano 5', '1.5', '2.5', '2780', 'Uygun'),
(10, 6, '1', 'Ana pano', '1.5', '5', '2710', 'Uygun'),
(11, 6, '2', 'Tali pano', '1.5', '2.5', '2610', 'Uygun'),
(12, 8, '1', 'tali pano önü', '1', '2', '2510', 'Uygun');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ic_tesisat_section6_header`
--

CREATE TABLE `ic_tesisat_section6_header` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `measurement_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `ic_tesisat_section6_header`
--

INSERT INTO `ic_tesisat_section6_header` (`id`, `report_id`, `measurement_method`) VALUES
(1, 5, 'Üç Uçlu Karşılaştırma'),
(2, 6, 'Üç Uçlu Karşılaştırma'),
(3, 8, 'Çevrim Empedansı'),
(4, 11, 'Çevrim Empedansı');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `institutions`
--

CREATE TABLE `institutions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `firma_adi` varchar(255) NOT NULL,
  `adresi` text DEFAULT NULL,
  `sgk_sicil_no` varchar(50) DEFAULT NULL,
  `il_kodu` varchar(2) NOT NULL DEFAULT '01',
  `kurum_kodu` varchar(3) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `isg_katip_id` varchar(255) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `institutions`
--

INSERT INTO `institutions` (`id`, `user_id`, `firma_adi`, `adresi`, `sgk_sicil_no`, `il_kodu`, `kurum_kodu`, `created_at`, `isg_katip_id`, `report_date`, `start_date`, `end_date`, `next_control_date`) VALUES
(1, 2, 'DERYA SILAH SANAYI VE TICARET LIMITED SIRKETI', 'BAYAVŞAR MAH. 41959 SK. KONYA BEYŞEHİR NO:24/1/0', '22540010110518050420325000', '42', '001', '2026-02-15 12:43:26', '24303028', '2026-03-01', '2026-02-11 12:25:00', '2026-06-09 12:25:00', '2026-06-09'),
(2, 2, 'Ev Konsept Mağazası -  SAMPIYON EV GEREÇLERI ELEKTRIK ELEKTRONIK TEKSTIL INSAAT TARIM VE HAYVANCILIK SANAYI VE TICARET ANONIM SIRKETI', ': HAMIDIYE MH. INÖNÜ CAD. MALATYA BATTALGAZI NO:34/A/MALATYA', '24754010110331960441095000', '44', '001', '2026-03-05 10:37:26', '24361622', '2026-03-05', '2026-02-12 08:00:00', '2027-02-12 18:00:00', '2026-07-11'),
(3, 2, 'Villa Mağazası - SAMPIYON EV GEREÇLERI ELEKTRIK ELEKTRONIK TEKSTIL INSAAT TARIM VE HAYVANCILIK SANAYI VE TICARET ANONIM SIRKETI', 'TURGUT ÖZAL MAH TURGUT ÖZAL BULVARI MALATYA YESILYURT NO:/ MALATYA', '24759010110168450440940000', '44', '002', '2026-03-05 10:39:25', '24361403', '2026-03-05', '2026-02-13 08:00:00', '2026-02-13 18:00:00', '2026-07-11'),
(4, 2, 'Malatya Park Mağazası - SAMPIYON EV GEREÇLERI ELEKTRIK ELEKTRONIK TEKSTIL INSAAT TARIM VE HAYVANCILIK SANAYI VE TICARET ANONIM SIRKETI', 'INÖNÜ MAH.BUHARA BUL. CAD.1 B Z12 MALATYA YESILYURT NO:247/MALATYA', '24754010110405550440982000', '44', '003', '2026-03-05 10:41:33', '24360984', '2026-03-05', '2026-02-13 08:00:00', '2026-02-13 18:00:00', '2026-07-11'),
(5, 2, 'Dörtyol Mağazası - SAMPIYON EV GEREÇLERI ELEKTRIK ELEKTRONIK TEKSTIL INSAAT TARIM VE HAYVANCILIK SANAYI VE TICARET ANONIM SIRKETI', 'MERKEZ INÖNÜ CAD. MALATYA BATTALGAZI NO:120/MALATYA', '24759010110248290441070000', '44', '004', '2026-03-05 10:45:56', '24361154', '2026-03-05', '2026-02-13 08:00:00', '2026-02-13 18:00:00', '2026-07-11'),
(6, 2, 'SERVIS - SAMPIYON EV GEREÇLERI ELEKTRIK ELEKTRONIK TEKSTIL INSAAT TARIM VE HAYVANCILIK SANAYI VE TICARET ANONIM SIRKETI', '1.Org. San. Böl. 2.Havaalanı Yolu Cad. No:12/1 Yeşilyurt/Malatya', '44100010110852440440954000', '44', '005', '2026-03-05 10:50:29', '24361532', '2026-03-05', '2026-02-13 13:47:00', '2026-07-11 00:00:00', '2026-07-11'),
(7, 2, 'Depo - SAMPIYON EV GEREÇLERI ELEKTRIK ELEKTRONIK TEKSTIL INSAAT TARIM VE HAYVANCILIK SANAYI VE TICARET ANONIM SIRKETI', '1.Org. San. Böl. 2.Havaalanı Yolu Cad. No:12/1 Yeşilyurt/Malatya', '25320010110437730440902000', '44', '006', '2026-03-05 10:54:00', '24360633', '2026-03-05', '2026-02-13 13:52:00', '2026-07-11 00:00:00', '2026-07-11'),
(8, 2, 'Özel Beyşehir Yıldız Anaokulu', 'Müftü Mahallesi 41160.Sokak No:21 Kapı No:1 Beyşehir /Konya', '28510010112997120420397000', '42', '002', '2026-04-03 12:18:24', '25126107', '2026-04-03', '2026-04-03 08:00:00', '2026-04-03 18:00:00', '2027-04-03'),
(9, 2, 'test', 'test', 'test', '99', '99', '2026-04-20 08:09:45', 'test', '2001-01-01', '2001-01-01 00:00:00', '2001-01-01 00:00:00', '2001-01-01');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `internal_installation_reports`
--

CREATE TABLE `internal_installation_reports` (
  `id` int(11) NOT NULL,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(50) NOT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `report_date` date NOT NULL,
  `energy_provider` varchar(100) DEFAULT NULL,
  `sebeke_tipi` varchar(50) DEFAULT NULL,
  `proje_var_mi` tinyint(1) DEFAULT 0,
  `sema_var_mi` tinyint(1) DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `control_reason` varchar(50) DEFAULT NULL,
  `grounding_type` varchar(50) DEFAULT NULL,
  `building_type` varchar(50) DEFAULT NULL,
  `usage_purpose` varchar(255) DEFAULT NULL,
  `prev_control_date` date DEFAULT NULL,
  `phase_count_type` varchar(50) DEFAULT NULL,
  `conductor_type` varchar(100) DEFAULT NULL,
  `grounding_resistance` varchar(50) DEFAULT NULL,
  `additional_electrode_details` text DEFAULT NULL,
  `system_grounding_conductor` varchar(255) DEFAULT NULL,
  `main_equipotential_conductor` varchar(255) DEFAULT NULL,
  `nominal_voltage_kV` varchar(50) DEFAULT NULL,
  `nominal_frequency_Hz` varchar(50) DEFAULT NULL,
  `fault_current_kA` varchar(50) DEFAULT NULL,
  `external_loop_impedance` varchar(50) DEFAULT NULL,
  `main_rcd_rating` varchar(50) DEFAULT NULL,
  `main_breaker_type` varchar(50) DEFAULT NULL,
  `main_breaker_rating` varchar(50) DEFAULT NULL,
  `main_rcd_test_mA` varchar(50) DEFAULT NULL,
  `main_rcd_test_ms` varchar(50) DEFAULT NULL,
  `installation_change` tinyint(1) DEFAULT 0,
  `has_spd` tinyint(1) DEFAULT 0,
  `protection_measures` text DEFAULT NULL,
  `prev_label_exists` tinyint(1) DEFAULT 0,
  `thermal_camera_id` int(11) DEFAULT NULL,
  `device1_id` int(11) DEFAULT NULL,
  `device2_id` int(11) DEFAULT NULL,
  `authorized_person_id` int(11) DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result` enum('UYGUNDUR','UYGUN DEGILDIR') DEFAULT 'UYGUNDUR',
  `result_notes_selection` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `isg_katip_id` varchar(255) DEFAULT NULL,
  `weather_condition` varchar(255) DEFAULT NULL,
  `ground_moisture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `internal_installation_reports`
--

INSERT INTO `internal_installation_reports` (`id`, `kurum_id`, `report_no`, `report_date`, `energy_provider`, `sebeke_tipi`, `proje_var_mi`, `sema_var_mi`, `start_date`, `end_date`, `next_control_date`, `control_reason`, `grounding_type`, `building_type`, `usage_purpose`, `prev_control_date`, `phase_count_type`, `conductor_type`, `grounding_resistance`, `additional_electrode_details`, `system_grounding_conductor`, `main_equipotential_conductor`, `nominal_voltage_kV`, `nominal_frequency_Hz`, `fault_current_kA`, `external_loop_impedance`, `main_rcd_rating`, `main_breaker_type`, `main_breaker_rating`, `main_rcd_test_mA`, `main_rcd_test_ms`, `installation_change`, `has_spd`, `protection_measures`, `prev_label_exists`, `thermal_camera_id`, `device1_id`, `device2_id`, `authorized_person_id`, `defects`, `notes`, `result`, `result_notes_selection`, `created_at`, `isg_katip_id`, `weather_condition`, `ground_moisture`) VALUES
(1, 1, '42-001-it-1771418904', '2026-02-18', 'MEDAŞ', NULL, 0, 0, '2026-02-18 15:46:00', '2026-02-19 15:46:00', '2027-02-18', 'İlk Kontrol', 'Ring', 'Endüstri', 'Fabrika', '2026-02-24', '3 faz, 4 tel', NULL, '25', NULL, NULL, NULL, '400V', '50', '1', '20', '21', 'ac', '10', '30', '31', 0, 0, 'Muhafaza (IPXY, pano kilidi, uyarı vb.),El ulaşma uzaklığı dışına yerleştirme,İlave koruma', 1, NULL, 1, NULL, 1, '', '', 'UYGUNDUR', '', '2026-02-18 12:48:24', NULL, NULL, NULL),
(2, 1, '42-001-it-1771418960', '2026-02-18', 'MEDAŞ', NULL, 0, 0, '2026-02-19 15:48:00', '2026-02-19 15:48:00', '2027-02-18', '', 'Yüzeysel', 'Endüstri', 'Fabrika', '2026-02-25', '3 faz, 4 tel', NULL, '2', NULL, NULL, NULL, '400V', '50', '12', '12', '1', 'ac', '30', '2', '3', 0, 0, 'Muhafaza (IPXY, pano kilidi, uyarı vb.),İlave koruma,30 mA RCD', 0, 2, 1, NULL, 1, '', '', 'UYGUNDUR', '', '2026-02-18 12:49:20', NULL, NULL, NULL),
(3, 1, '42-001-it-1771419087', '2026-02-18', 'MEDAŞ', NULL, 0, 0, '2026-02-19 15:48:00', '2026-02-19 15:48:00', '2027-02-18', '', 'Yüzeysel', 'Endüstri', 'Fabrika', '2026-02-25', '3 faz, 4 tel', NULL, '2', NULL, NULL, NULL, '400V', '50', '12', '12', '1', 'ac', '30', '2', '3', 0, 0, 'Muhafaza (IPXY, pano kilidi, uyarı vb.),İlave koruma,30 mA RCD', 0, 2, 1, NULL, 1, '', '', 'UYGUNDUR', '', '2026-02-18 12:51:27', NULL, NULL, NULL),
(4, 1, '42-001-it-1771419206', '2026-02-18', 'MEDAŞ', 'TN-S', 1, 1, '2026-02-19 15:48:00', '2026-02-19 15:48:00', '2027-02-18', '', 'Yüzeysel', 'Endüstri', 'Fabrika', '2026-02-25', '3 faz, 4 tel', NULL, '2', NULL, NULL, NULL, '400V', '50', '12', '12', '1', 'ac', '30', '2', '3', 0, 0, 'Muhafaza (IPXY, pano kilidi, uyarı vb.),İlave koruma,30 mA RCD', 0, 2, 1, NULL, 1, '', '', 'UYGUNDUR', '', '2026-02-18 12:53:26', NULL, NULL, NULL),
(5, 1, '42-001-it-1772549279', '2026-03-01', 'MEDAŞ', 'TN-S', 1, 1, '2026-02-11 12:25:00', '2026-06-09 12:25:00', '2026-06-09', 'Periyodik Kontrol', 'Temel', 'Endüstri', 'Fabrika', '2025-09-01', '3 faz, 4 tel', '3 kutup', '2.45', 'Her ekipmanın ilave toprak elektrodu mevcut', '22.5mm2', 'Bara kullanılmış', '400V', '50', '0.05', '5', '30', 'TMS', '630', '30', '30', 0, 0, 'Gerilim altındaki bölümlerin yalıtılması,Muhafaza (IPXY, pano kilidi, uyarı vb.),İlave koruma,30 mA RCD', 0, 2, 1, NULL, 1, '', '', 'UYGUNDUR', '', '2026-03-03 14:47:59', '24303028', NULL, NULL),
(6, 1, '42-001-it-1772629525', '2026-03-01', 'MEDAŞ', 'TN-S', 1, 1, '2026-02-11 12:25:00', '2026-06-09 12:25:00', '2026-06-09', 'Periyodik Kontrol', 'Temel', 'Endüstri', 'Fabrika-eski', '2025-09-01', '3 faz, 4 tel', '3 kutup', '2.46', '-', '22.5mm2', 'Bara kullanılmış', '400V', '50', '0.04', '4.9', '30', 'TMS', '630', '30', '30', 0, 0, 'Gerilim altındaki bölümlerin yalıtılması,Muhafaza (IPXY, pano kilidi, uyarı vb.),İlave koruma,30 mA RCD', 1, 2, 1, NULL, 1, '', '', 'UYGUNDUR', '', '2026-03-04 13:05:25', '24303028', NULL, NULL),
(7, 4, '44-003-it-1772711072', '2026-03-05', 'Fırat EDAŞ', 'TN-S', 1, 1, '2026-02-13 13:40:00', '2026-07-11 00:00:00', '2026-07-11', 'Periyodik Kontrol', 'Temel', 'Ticari', 'Perakende Mağaza', '2025-09-01', '3 faz, 4 tel', '3 kutup', '-', '', '25', '-', '400V', '50', '0.04', '4.5', '40', '-', '-', '27', '26.5', 0, 0, 'Gerilim altındaki bölümlerin yalıtılması,Muhafaza (IPXY, pano kilidi, uyarı vb.),30 mA RCD', 0, 2, 1, NULL, 1, '', 'Mağaza, AVM&#039;ye bağlı olduğu için Şebekeden, Mağazaya kadar olan beslemenin sorumluluğu AVM yönetimine aittir.', 'UYGUNDUR', '', '2026-03-05 11:44:32', '24360984', NULL, NULL),
(8, 8, '42-002-it-1775219885', '2026-04-03', 'MEDAŞ', 'TN-S', 1, 1, '2026-04-03 08:00:00', '2026-04-03 18:00:00', '2027-04-03', 'Periyodik Kontrol', 'Temel', 'Ticari', 'Eğitim Hizmetleri', '2024-12-24', '1 faz, 2 tel', '2 kutup', '-', '-', '16mm2', '25mm2', '220V', '50', '0.04', '4.9', '40', 'ac', '40', '30', '30', 0, 0, 'Engel,İlave koruma,30 mA RCD', 0, 2, 1, NULL, 1, '', 'Kullanıma uygundur.', 'UYGUNDUR', '', '2026-04-03 12:38:05', '25126107', NULL, NULL),
(9, 2, '44-001-it-1775434534', '2026-03-05', 'Fırat EDAŞ', 'TN-S', 1, 1, '2026-02-12 08:00:00', '2026-02-12 18:00:00', '2026-07-11', 'Periyodik Kontrol', 'Temel', 'Ticari', 'Perakende Mağaza', '2024-12-03', '3 faz, 4 tel', '3 kutup', '3.59', '-', '25', 'Bara kullanılmış', '400V', '50', '0.02', '3,59', '40', 'AC', '40', '30', '30', 0, 0, 'İlave koruma,30 mA RCD', 1, 2, 1, NULL, 1, '', 'Kullanım uygundur.', 'UYGUNDUR', '', '2026-04-06 00:15:34', '24361622', 'yağmurlu 5C', 'nemli'),
(10, 3, '44-002-it-1775437454', '2026-03-05', 'Fırat EDAŞ', 'TN-S', 1, 1, '2026-02-13 08:00:00', '2026-02-13 18:00:00', '2027-02-13', 'Periyodik Kontrol', 'Temel', 'Ticari', 'Perakende Mağaza', '2024-12-03', '3 faz, 4 tel', '3 kutup', '1.58', '-', '25', 'Bara kullanılmış', '400V', '50', '0.02', '3,68', '40', 'AC', '40', '30', '30', 0, 0, 'Muhafaza (IPXY, pano kilidi, uyarı vb.),30 mA RCD', 0, 2, 1, NULL, 1, '', 'Kullanıma uygundur.', 'UYGUNDUR', '', '2026-04-06 01:04:14', '24361403', 'yağmurlu 5C', 'nemli'),
(11, 9, '99-99-it-1776672644', '2001-01-01', '', '', 0, 0, '2001-01-01 00:00:00', '2001-01-01 00:00:00', '2001-01-01', '', '', '', '', NULL, '', '', '', '', '', '', '', '50', '', '', '', '', '', '', '', 0, 0, '', 0, 2, 1, NULL, 1, '', '', 'UYGUNDUR', '', '2026-04-20 08:10:44', 'test', '', '');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `lightning_protection_reports`
--

CREATE TABLE `lightning_protection_reports` (
  `id` int(11) NOT NULL,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `energy_provider` varchar(255) DEFAULT NULL,
  `sebeke_tipi` varchar(50) DEFAULT NULL,
  `sebeke_voltage` varchar(50) DEFAULT NULL,
  `has_project` varchar(10) DEFAULT NULL,
  `project_details` text DEFAULT NULL,
  `has_risk_analysis` varchar(10) DEFAULT NULL,
  `control_reason` varchar(100) DEFAULT NULL,
  `grounding_type` varchar(100) DEFAULT NULL,
  `building_type` varchar(100) DEFAULT NULL,
  `usage_purpose_yks_type` varchar(255) DEFAULT NULL,
  `prev_control_date` date DEFAULT NULL,
  `weather_condition` varchar(100) DEFAULT NULL,
  `ground_moisture` varchar(100) DEFAULT NULL,
  `installation_change` varchar(10) DEFAULT NULL,
  `prev_label_exists` varchar(10) DEFAULT NULL,
  `equipment_identification` varchar(255) DEFAULT NULL,
  `protection_system_type` varchar(255) DEFAULT NULL,
  `protection_level_eps` varchar(100) DEFAULT NULL,
  `building_usage_details` text DEFAULT NULL,
  `thermal_camera_id` int(11) DEFAULT NULL,
  `device1_id` int(11) DEFAULT NULL,
  `device2_id` int(11) DEFAULT NULL,
  `authorized_person_id` int(11) DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'UYGUNDUR',
  `result_notes_selection` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `lightning_protection_reports`
--

INSERT INTO `lightning_protection_reports` (`id`, `kurum_id`, `report_no`, `report_date`, `start_date`, `end_date`, `next_control_date`, `isg_katip_id`, `energy_provider`, `sebeke_tipi`, `sebeke_voltage`, `has_project`, `project_details`, `has_risk_analysis`, `control_reason`, `grounding_type`, `building_type`, `usage_purpose_yks_type`, `prev_control_date`, `weather_condition`, `ground_moisture`, `installation_change`, `prev_label_exists`, `equipment_identification`, `protection_system_type`, `protection_level_eps`, `building_usage_details`, `thermal_camera_id`, `device1_id`, `device2_id`, `authorized_person_id`, `defects`, `notes`, `result`, `result_notes_selection`, `created_at`) VALUES
(1, 1, '42-001-yk-1771424609', '2026-02-18', '2026-02-13 17:23:00', '2026-03-05 17:23:00', '2027-02-18', '234', 'MEDAŞ', 'TN-S', '400V', 'Yok', '', 'Yok', 'İlk Kontrol', 'Yüzeysel', 'Endüstri', '', '2026-02-18', '25', 'nem var', 'Yok', 'Yok', 'aa', 'ESE (Aktif-Radyoaktif) Paratoner', '2', 'yurt', NULL, 1, NULL, 1, '', '', 'UYGUNDUR', '', '2026-02-18 14:23:29'),
(2, 1, '42-001-yk-1772358042', '2026-03-01', '2026-02-11 12:25:00', '2026-06-09 12:25:00', '2026-06-09', '24303028', 'MEDAŞ', 'TN-S', '400V', 'Var', 'Proje mevcut ancak çizen bilgileri bulunamadı.', 'Var', 'Periyodik Kontrol', 'Derin', 'Endüstri', 'Ayrılmış YKS', '2025-09-01', 'bulutlu 25C', 'nemli', 'Yok', 'Yok', 'Yeni', 'ESE (Aktif-Radyoaktif) Paratoner', 'Seviye 2', 'Endüstriyel Fabrikalar', NULL, 1, NULL, 1, '', 'Ölçüm sonuçları o günün koşulları için geçerlidir', 'UYGUNDUR', '', '2026-03-01 09:40:42'),
(3, 9, '99-99-yk-1776672898', '2001-01-01', '2001-01-01 00:00:00', '2001-01-01 00:00:00', '2001-01-01', 'test', '', '', '', 'Yok', '', 'Yok', '', '', '', '', NULL, '', '', 'Yok', 'Yok', '', '', '', '', 2, 1, NULL, 1, '', '', 'UYGUNDUR', '', '2026-04-20 08:14:58');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `lightning_protection_section4`
--

CREATE TABLE `lightning_protection_section4` (
  `id` int(11) NOT NULL,
  `report_id` int(11) DEFAULT NULL,
  `question_key` varchar(255) DEFAULT NULL,
  `answer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `lightning_protection_section4`
--

INSERT INTO `lightning_protection_section4` (`id`, `report_id`, `question_key`, `answer`) VALUES
(33, 2, 'risk_analizi_varmi', 'Uygun'),
(34, 2, 'kapsama_uygunmu', 'Uygun'),
(35, 2, 'measurement_method', '3 Uçlu topraklama'),
(36, 2, 'ese_a1', 'Uygun'),
(37, 2, 'ese_a2', 'Uygun'),
(38, 2, 'ese_a3', 'Uygun'),
(39, 2, 'ese_a4', 'Uygun'),
(40, 2, 'ese_a5', 'Uygun'),
(41, 2, 'ese_a6', 'Uygun'),
(42, 2, 'ese_a7', 'Uygun'),
(43, 2, 'ese_a8', 'Uygun'),
(44, 2, 'ese_b1', 'Uygun'),
(45, 2, 'ese_b2', 'Uygun'),
(46, 2, 'ese_b3', 'Uygun'),
(47, 2, 'ese_b4', 'Uygun'),
(48, 2, 'ese_b5', 'Uygun'),
(49, 2, 'ese_b6', 'Uygun'),
(50, 2, 'ese_b7', 'Uygun'),
(51, 2, 'ese_c1', 'Uygun'),
(52, 2, 'ese_c2', 'Uygun'),
(53, 2, 'ese_c3', 'Uygun'),
(54, 2, 'ese_c4', 'Uygun'),
(55, 2, 'ese_d1', 'Uygun'),
(56, 2, 'ese_d2', 'Uygun'),
(57, 2, 'ese_d3', 'Uygun'),
(58, 2, 'ese_d4', 'Uygun'),
(59, 2, 'ese_e1', 'Uygun'),
(60, 2, 'ese_e2', 'Uygun'),
(61, 2, 'ese_e3', 'Uygun'),
(62, 2, 'ese_e4', 'Uygun'),
(63, 2, 'ese_e5', 'Uygun'),
(64, 2, 'ese_e6', 'Uygun');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `measurements_5_1`
--

CREATE TABLE `measurements_5_1` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `point_no` int(11) DEFAULT NULL,
  `point_name` varchar(255) DEFAULT NULL,
  `prot_in` varchar(50) DEFAULT NULL,
  `prot_type` varchar(50) DEFAULT NULL,
  `prot_ia` varchar(50) DEFAULT NULL,
  `prot_ik1` varchar(50) DEFAULT NULL,
  `measured_zx_rx` varchar(50) DEFAULT NULL,
  `limit_zs_ra` varchar(50) DEFAULT NULL,
  `rcd_type_limits` varchar(100) DEFAULT NULL,
  `rcd_test_ia` varchar(50) DEFAULT NULL,
  `rcd_test_ta` varchar(50) DEFAULT NULL,
  `result` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `measurements_5_1`
--

INSERT INTO `measurements_5_1` (`id`, `report_id`, `point_no`, `point_name`, `prot_in`, `prot_type`, `prot_ia`, `prot_ik1`, `measured_zx_rx`, `limit_zs_ra`, `rcd_type_limits`, `rcd_test_ia`, `rcd_test_ta`, `result`) VALUES
(17, 2, 1, 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a'),
(22, 1, 1, '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1'),
(23, 1, 2, '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1'),
(24, 1, 3, '1', '2', '2', '2', '2', '2', '2', '2', '2', '2', '2'),
(25, 1, 4, '2', '2', '2', '2', '2', '2', '2', '2', '2', '2', '2'),
(1123, 3, 1, 'DM-38', '30m', 'C', '40', '87,1', '1,02', '250', 'AC/100/30', '21', '28,8', '1'),
(1124, 3, 2, 'P-100B TESTERE', '30m', 'C', '40', '89,4', '1,77', '250', 'AC/100/30', '21', '24,8', '1'),
(1125, 3, 3, 'KARMETAL-300*370', '30m', 'C', '40', '89', '1,02', '250', 'AC/100/30', '21', '26,5', '1'),
(1126, 3, 4, 'KESMAK TESTERE', '30m', 'C', '40', '89,4', '0,67', '250', 'AC/100/30', '24', '27,4', '1'),
(1127, 3, 5, 'DM-175', '30m', 'C', '40', '87,8', '2,04', '250', 'AC/100/30', '21', '28,7', '1'),
(1128, 3, 6, 'DM-212', '30m', 'C', '40', '81,3', '1,6', '250', 'AC/100/30', '27', '27,8', '1'),
(1129, 3, 7, 'DM-015', '30m', 'C', '40', '87,6', '1,89', '250', 'AC/100/30', '27', '27,8', '1'),
(1130, 3, 8, 'DM-03', '30m', 'C', '40', '88,5', '2,22', '250', 'AC/100/30', '24', '24,6', '1'),
(1131, 3, 9, 'DM-82', '30m', 'C', '40', '85', '1,51', '250', 'AC/100/30', '21', '26,4', '1'),
(1132, 3, 10, 'DM-89', '30m', 'C', '40', '89,2', '2,17', '250', 'AC/100/30', '21', '23,9', '1'),
(1133, 3, 11, 'DM-211', '30m', 'C', '40', '82,8', '1,3', '250', 'AC/100/30', '27', '26,9', '1'),
(1134, 3, 12, 'DM-12', '30m', 'C', '40', '82,2', '1,59', '250', 'AC/100/30', '24', '23,6', '1'),
(1135, 3, 13, 'DM-01', '30m', 'C', '40', '80,8', '1,88', '250', 'AC/100/30', '24', '26,3', '1'),
(1136, 3, 14, 'DM-83', '30m', 'C', '40', '80,4', '1,56', '250', 'AC/100/30', '24', '29,3', '1'),
(1137, 3, 15, 'DM-098', '30m', 'C', '40', '83,4', '1,31', '250', 'AC/100/30', '27', '26,7', '1'),
(1138, 3, 16, 'DM-095', '30m', 'C', '40', '82,5', '1,47', '250', 'AC/100/30', '21', '27,8', '1'),
(1139, 3, 17, 'DM-096', '30m', 'C', '40', '88,7', '1,12', '250', 'AC/100/30', '27', '25,9', '1'),
(1140, 3, 18, 'DM-104', '30m', 'C', '40', '81,9', '2,25', '250', 'AC/100/30', '24', '27,8', '1'),
(1141, 3, 19, 'DM-135', '30m', 'C', '40', '89,3', '1,91', '250', 'AC/100/30', '21', '28', '1'),
(1142, 3, 20, 'DM-124', '30m', 'C', '40', '83,2', '1,99', '250', 'AC/100/30', '24', '28,2', '1'),
(1143, 3, 21, 'DM-125', '30m', 'C', '40', '87,9', '1,57', '250', 'AC/100/30', '27', '28,4', '1'),
(1144, 3, 22, 'DM-126', '30m', 'C', '40', '88,7', '2,31', '250', 'AC/100/30', '27', '23,8', '1'),
(1145, 3, 23, 'DM-132', '30m', 'C', '40', '82', '1,32', '250', 'AC/100/30', '24', '25,5', '1'),
(1146, 3, 24, 'DM-085', '30m', 'C', '40', '84,7', '1,07', '250', 'AC/100/30', '24', '27,1', '1'),
(1147, 3, 25, 'DM-135', '30m', 'C', '40', '86,3', '1,69', '250', 'AC/100/30', '24', '27,7', '1'),
(1148, 3, 26, 'DM-86', '30m', 'C', '40', '90', '1,54', '250', 'AC/100/30', '27', '25,9', '1'),
(1149, 3, 27, 'DN-108', '30m', 'C', '40', '83,1', '0,85', '250', 'AC/100/30', '27', '27,8', '1'),
(1150, 3, 28, 'DM-48', '30m', 'C', '40', '82,7', '0,59', '250', 'AC/100/30', '27', '28,9', '1'),
(1151, 3, 29, 'DM-207', '30m', 'C', '40', '85,6', '1,83', '250', 'AC/100/30', '24', '26,7', '1'),
(1152, 3, 30, 'DM-031', '30m', 'C', '40', '89,4', '1,52', '250', 'AC/100/30', '24', '26,9', '1'),
(1153, 3, 31, 'DM-032', '30m', 'C', '40', '82,7', '0,5', '250', 'AC/100/30', '27', '24,8', '1'),
(1154, 3, 32, 'DM-029', '30m', 'C', '40', '82,5', '1,86', '250', 'AC/100/30', '24', '23,6', '1'),
(1155, 3, 33, 'DM-084', '30m', 'C', '40', '85,4', '1,9', '250', 'AC/100/30', '27', '25,5', '1'),
(1156, 3, 34, 'DM-123', '30m', 'C', '40', '83,9', '1,42', '250', 'AC/100/30', '21', '24,9', '1'),
(1157, 3, 35, 'DM-087', '30m', 'C', '40', '89', '1,23', '250', 'AC/100/30', '24', '26,2', '1'),
(1158, 3, 36, 'DM-184', '30m', 'C', '40', '83,6', '0,83', '250', 'AC/100/30', '27', '29,4', '1'),
(1159, 3, 37, 'DM-011', '30m', 'C', '40', '89,9', '0,77', '250', 'AC/100/30', '27', '27,3', '1'),
(1160, 3, 38, 'DM-049', '30m', 'C', '40', '82,6', '2,05', '250', 'AC/100/30', '24', '26,6', '1'),
(1161, 3, 39, 'DM-030', '30m', 'C', '40', '82,6', '0,65', '250', 'AC/100/30', '24', '24,4', '1'),
(1162, 3, 40, 'DM-182', '30m', 'C', '40', '84,9', '2,17', '250', 'AC/100/30', '21', '26,6', '1'),
(1163, 3, 41, 'DM-183', '30m', 'C', '40', '88', '2,16', '250', 'AC/100/30', '27', '24,3', '1'),
(1164, 3, 42, 'DM-099', '30m', 'C', '40', '84,1', '2,27', '250', 'AC/100/30', '21', '23,9', '1'),
(1165, 3, 43, 'DM-100', '30m', 'C', '40', '89', '2,34', '250', 'AC/100/30', '24', '24,1', '1'),
(1166, 3, 44, 'DM-40', '30m', 'C', '40', '89', '2,38', '250', 'AC/100/30', '21', '24,5', '1'),
(1167, 3, 45, 'DM-034', '30m', 'C', '40', '87,9', '2,3', '250', 'AC/100/30', '27', '24,9', '1'),
(1168, 3, 46, 'DM-176', '30m', 'C', '40', '88,7', '2,32', '250', 'AC/100/30', '24', '24,7', '1'),
(1169, 3, 47, 'DM-177', '30m', 'C', '40', '80,9', '1,9', '250', 'AC/100/30', '21', '29', '1'),
(1170, 3, 48, 'DM-029', '30m', 'C', '40', '83,9', '2,47', '250', 'AC/100/30', '27', '24,8', '1'),
(1171, 3, 49, 'DM-030', '30m', 'C', '40', '87,6', '0,58', '250', 'AC/100/30', '24', '24,8', '1'),
(1172, 3, 50, 'DM-191', '30m', 'C', '40', '82,8', '1,98', '250', 'AC/100/30', '21', '23,9', '1'),
(1173, 3, 51, 'DM-133', '30m', 'C', '40', '87,8', '2,19', '250', 'AC/100/30', '24', '24', '1'),
(1174, 3, 52, 'DM-046', '30m', 'C', '40', '82,3', '1,4', '250', 'AC/100/30', '21', '26,4', '1'),
(1175, 3, 53, 'DM-052', '30m', 'C', '63', '84,8', '1,27', '250', 'AC/100/30', '24', '25,6', '1'),
(1176, 3, 54, 'DM-113', '30m', 'C', '63', '88,8', '1,1', '250', 'AC/100/30', '21', '28,3', '1'),
(1177, 3, 55, 'DM-005', '30m', 'C', '63', '85,2', '1,41', '250', 'AC/100/30', '21', '29', '1'),
(1178, 3, 56, 'DM-006', '30m', 'C', '63', '84,4', '2,14', '250', 'AC/100/30', '21', '24,5', '1'),
(1179, 3, 57, 'DM-114', '30m', 'C', '63', '89,4', '2,38', '250', 'AC/100/30', '27', '27,1', '1'),
(1180, 3, 58, 'DM-112', '30m', 'C', '63', '81,9', '0,74', '250', 'AC/100/30', '27', '23,7', '1'),
(1181, 3, 59, 'DM-122', '30m', 'C', '63', '83,3', '0,83', '250', 'AC/100/30', '21', '28,7', '1'),
(1182, 3, 60, 'DM-148', '30m', 'C', '63', '85,3', '1,09', '250', 'AC/100/30', '24', '24,5', '1'),
(1183, 3, 61, 'DM-141', '30m', 'C', '80', '89,8', '0,84', '250', 'AC/100/30', '24', '26,2', '1'),
(1184, 3, 62, 'DM-147', '30m', 'C', '80', '85,8', '0,81', '250', 'AC/100/30', '27', '26', '1'),
(1185, 3, 63, 'DM-139', '30m', 'C', '80', '88,9', '1,14', '250', 'AC/100/30', '27', '27,2', '1'),
(1186, 3, 64, 'DM-066', '30m', 'C', '80', '80,1', '2,14', '250', 'AC/100/30', '21', '23,7', '1'),
(1187, 3, 65, 'DM-004', '30m', 'C', '80', '83,7', '0,57', '250', 'AC/100/30', '21', '28,2', '1'),
(1188, 3, 66, 'DM-138', '30m', 'C', '80', '89,7', '2,35', '250', 'AC/100/30', '27', '27,9', '1'),
(1189, 3, 67, 'DM-042', '30m', 'C', '80', '89,3', '1,67', '250', 'AC/100/30', '24', '24,8', '1'),
(1190, 3, 68, 'DM-146', '30m', 'C', '80', '87,4', '1,94', '250', 'AC/100/30', '21', '27,5', '1'),
(1191, 3, 69, 'DM-067', '30m', 'C', '80', '89,6', '1,86', '250', 'AC/100/30', '21', '25,2', '1'),
(1192, 3, 70, 'DM-051', '30m', 'C', '80', '87,3', '1,86', '250', 'AC/100/30', '21', '27,1', '1'),
(1193, 3, 71, 'DM-140', '30m', 'C', '80', '82,6', '1,2', '250', 'AC/100/30', '21', '26', '1'),
(1194, 3, 72, 'DM-144', '30m', 'C', '80', '81,6', '1,44', '250', 'AC/100/30', '21', '24,4', '1'),
(1195, 3, 73, 'DM-143', '30m', 'C', '80', '80,1', '1,39', '250', 'AC/100/30', '24', '25,1', '1'),
(1196, 3, 74, 'DM-142', '30m', 'C', '80', '83,2', '2,18', '250', 'AC/100/30', '24', '24,5', '1'),
(1197, 3, 75, 'DM-137', '30m', 'C', '80', '88,3', '1,98', '250', 'AC/100/30', '21', '26,7', '1'),
(1198, 3, 76, 'DM-136', '30m', 'C', '80', '88,1', '2,22', '250', 'AC/100/30', '24', '24,7', '1'),
(1199, 3, 77, 'DM-053', '30m', 'C', '80', '81,3', '0,63', '250', 'AC/100/30', '27', '26,7', '1'),
(1200, 3, 78, 'DM-070', '30m', 'C', '80', '89,9', '2,1', '250', 'AC/100/30', '24', '26,7', '1'),
(1201, 3, 79, 'DM-111', '30m', 'C', '80', '85,4', '1,05', '250', 'AC/100/30', '21', '28', '1'),
(1202, 3, 80, 'DM-115', '30m', 'C', '100', '81,5', '2,15', '250', 'AC/100/30', '21', '27', '1'),
(1203, 3, 81, 'DM-116', '30m', 'C', '100', '80,4', '1,73', '250', 'AC/100/30', '27', '24,9', '1'),
(1204, 3, 82, 'DM-117', '30m', 'C', '100', '80,8', '0,53', '250', 'AC/100/30', '24', '26,2', '1'),
(1205, 3, 83, 'DM-008', '30m', 'C', '100', '88,1', '1,29', '250', 'AC/100/30', '27', '28,5', '1'),
(1206, 3, 84, 'DM-210', '30m', 'C', '80', '87,9', '1,49', '250', 'AC/100/30', '21', '26,8', '1'),
(1207, 3, 85, 'DM-71', '30m', 'C', '63', '85', '2,29', '250', 'AC/100/30', '21', '29,3', '1'),
(1208, 3, 86, 'HIGTECH EREZYON', '30m', 'C', '63', '86,4', '1,53', '250', 'AC/100/30', '27', '29', '1'),
(1209, 3, 87, 'HONLAMA 1', '30m', 'C', '40', '82', '1,28', '250', 'AC/100/30', '27', '23,6', '1'),
(1210, 3, 88, 'HONLAMA 2', '30m', 'C', '40', '88,4', '1,39', '250', 'AC/100/30', '21', '26,6', '1'),
(1211, 3, 89, 'HONLAMA 3', '30m', 'C', '40', '89', '0,54', '250', 'AC/100/30', '21', '25,7', '1'),
(1212, 3, 90, 'NAMLU DELME', '30m', 'C', '40', '88,9', '1,83', '250', 'AC/100/30', '27', '27,9', '1'),
(1213, 3, 91, 'HONLAMA 4', '30m', 'C', '40', '87,6', '0,57', '250', 'AC/100/30', '27', '25,8', '1'),
(1214, 3, 92, 'HONLAMA 5', '30m', 'C', '40', '84,7', '1,46', '250', 'AC/100/30', '21', '27,6', '1'),
(1215, 3, 93, 'HONLAMA 6', '30m', 'C', '40', '80,6', '0,82', '250', 'AC/100/30', '27', '27', '1'),
(1216, 3, 94, 'DM-107', '30m', 'C', '40', '81,7', '2,5', '250', 'AC/100/30', '27', '26', '1'),
(1217, 3, 95, 'DM-213', '30m', 'C', '40', '84,4', '2,32', '250', 'AC/100/30', '21', '25,4', '1'),
(1218, 3, 96, 'DM-128', '30m', 'C', '40', '81,5', '1,57', '250', 'AC/100/30', '21', '26,5', '1'),
(1219, 3, 97, 'DM-127', '30m', 'C', '40', '82,7', '2,29', '250', 'AC/100/30', '27', '24,1', '1'),
(1220, 3, 98, 'DM-197', '30m', 'C', '63', '84,4', '2,34', '250', 'AC/100/30', '27', '23,7', '1'),
(1221, 3, 99, 'DM-101', '30m', 'C', '63', '86,2', '2,26', '250', 'AC/100/30', '27', '23,9', '1'),
(1222, 3, 100, 'DM-031', '30m', 'C', '40', '86,7', '2,3', '250', 'AC/100/30', '24', '24,7', '1'),
(1223, 3, 101, 'DM-148', '30m', 'C', '40', '83,7', '0,8', '250', 'AC/100/30', '24', '23,7', '1'),
(1224, 3, 102, 'MASAUSTU MATKAP 1', '30m', 'C', '25', '81,1', '2,34', '250', 'AC/100/30', '27', '26,7', '1'),
(1225, 3, 103, 'MASAUSTU MATKAP 2', '30m', 'C', '25', '81,8', '1,79', '250', 'AC/100/30', '21', '28,5', '1'),
(1226, 3, 104, 'MASAUSTU MATKAP 3', '30m', 'C', '25', '80,1', '1,37', '250', 'AC/100/30', '27', '26,8', '1'),
(1227, 3, 105, 'DM-072', '30m', 'C', '25', '86', '1,41', '250', 'AC/100/30', '27', '28,5', '1'),
(1228, 3, 106, 'DM-059', '30m', 'C', '25', '81,2', '0,8', '250', 'AC/100/30', '27', '29', '1'),
(1229, 3, 107, 'DM-058', '30m', 'C', '25', '80,8', '0,95', '250', 'AC/100/30', '24', '27,2', '1'),
(1230, 3, 108, 'ZIMPARA MAKINESI 1', '30m', 'C', '25', '81,1', '0,68', '250', 'AC/100/30', '24', '25,1', '1'),
(1231, 3, 109, 'ZIMPARA MAKINESI 2', '30m', 'C', '25', '86,2', '1,81', '250', 'AC/100/30', '24', '25,4', '1'),
(1232, 3, 110, 'POLISAJ MOTORU 1', '30m', 'C', '40', '83,5', '1,73', '250', 'AC/100/30', '21', '24,8', '1'),
(1233, 3, 111, 'SULU ZIMPARA', '30m', 'C', '40', '83,8', '1,99', '250', 'AC/100/30', '27', '26,6', '1'),
(1234, 3, 112, 'ZIMPARA MAKINESI 3', '30m', 'C', '25', '85,4', '1,91', '250', 'AC/100/30', '21', '24,8', '1'),
(1235, 3, 113, 'DM-208', '30m', 'C', '40', '87,4', '0,98', '250', 'AC/100/30', '21', '27,8', '1'),
(1236, 3, 114, 'DM-037', '30m', 'C', '40', '84,5', '1,34', '250', 'AC/100/30', '27', '23,8', '1'),
(1237, 3, 115, 'DM-024', '30m', 'C', '25', '82,3', '0,9', '250', 'AC/100/30', '24', '29', '1'),
(1238, 3, 116, 'DM-102', '30m', 'C', '40', '87,5', '1,96', '250', 'AC/100/30', '21', '26,3', '1'),
(1239, 3, 117, 'DM-149', '30m', 'C', '40', '80,3', '1,93', '250', 'AC/100/30', '27', '25', '1'),
(1240, 3, 118, 'DM-056', '30m', 'C', '40', '83,3', '1,14', '250', 'AC/100/30', '24', '24,7', '1'),
(1241, 3, 119, 'DM-027', '30m', 'C', '40', '81', '1,7', '250', 'AC/100/30', '21', '24,2', '1'),
(1242, 3, 120, 'DM-028', '30m', 'C', '40', '81,7', '0,64', '250', 'AC/100/30', '21', '28,4', '1'),
(1243, 3, 121, 'DM-151', '30m', 'C', '25', '81,4', '1,78', '250', 'AC/100/30', '21', '26', '1'),
(1244, 3, 122, 'DM-150', '30m', 'C', '25', '80,8', '2,04', '250', 'AC/100/30', '24', '23,8', '1'),
(1245, 3, 123, 'DM-152', '30m', 'C', '25', '80', '1,54', '250', 'AC/100/30', '21', '26,8', '1'),
(1246, 3, 124, 'DM-153', '30m', 'C', '25', '80,6', '1,37', '250', 'AC/100/30', '21', '25,9', '1'),
(1247, 3, 125, 'DM-154', '30m', 'C', '25', '82,9', '0,54', '250', 'AC/100/30', '27', '29,1', '1'),
(1248, 3, 126, 'DM-155', '30m', 'C', '25', '84,1', '1,12', '250', 'AC/100/30', '27', '26,1', '1'),
(1249, 3, 127, 'DM-200', '30m', 'C', '25', '87,2', '1,48', '250', 'AC/100/30', '21', '28,3', '1'),
(1250, 3, 128, 'INDIKSIYON 1', '30m', 'C', '80', '83,4', '2,16', '250', 'AC/100/30', '24', '23,6', '1'),
(1251, 3, 129, 'INDIKSIYON 2', '30m', 'C', '80', '82', '1,02', '250', 'AC/100/30', '27', '26,5', '1'),
(1252, 3, 130, 'INDIKSIYON 3', '30m', 'C', '80', '88,7', '1,76', '250', 'AC/100/30', '27', '24,5', '1'),
(1253, 3, 131, 'FIRIN', '30m', 'C', '50', '82,6', '2,42', '250', 'AC/100/30', '27', '25,2', '1'),
(1254, 3, 132, 'ANA DAGITIM PANO', '300m', 'TMS', '1600', '80,9', '1,09', '83', 'AC/100/30', '210', '29,2', '1'),
(1255, 3, 133, 'OTOMASYON KUMANDA PANOSU', '300m', 'TMS', '160', '86', '0,52', '83', 'AC/100/30', '240', '27,6', '1'),
(1399, 4, 1, 'DM-105', '30m', 'C', '40', '81', '1,66', '250', 'AC/100/30', '24', '26,4', '1'),
(1400, 4, 2, 'BOYAHANE KABİN 1', '30m', 'C', '40', '82,4', '2,02', '250', 'AC/100/30', '30', '24,8', '1'),
(1401, 4, 3, 'BOYAHANE KABİN 2', '30m', 'C', '40', '86', '2,81', '250', 'AC/100/30', '27', '22,3', '1'),
(1402, 4, 4, 'KAPLAMA BÖLÜMÜ PANO', '0.3', 'TMS', '250', '89,8', '1,92', '83', 'AC/100/300', '27', '23,3', '1'),
(1403, 4, 5, 'KUMLAMA MAKİNESİ 1', '30m', 'C', '40', '88,5', '2,4', '250', 'AC/100/30', '30', '26,9', '1'),
(1404, 4, 6, 'KUMLAMA MAKİNESİ 2', '30m', 'C', '40', '81,8', '1,74', '250', 'AC/100/30', '30', '25,9', '1'),
(1405, 4, 7, 'KUMLAMA MAKİNESİ 3', '30m', 'C', '40', '88,7', '1,56', '250', 'AC/100/30', '24', '24,1', '1'),
(1406, 4, 8, 'KUMLAMA MAKİNESİ 4', '30m', 'C', '40', '81,6', '2,64', '250', 'AC/100/30', '27', '25', '1'),
(1407, 4, 9, 'KUMLAMA MAKİNESİ 5', '30m', 'C', '40', '82,1', '1,22', '250', 'AC/100/30', '30', '24', '1'),
(1408, 4, 10, 'ANA  PANO', '0.3', 'TMS', '630', '88,7', '2,52', '83', 'AC/100/300', '30', '26,6', '1'),
(1409, 5, 1, 'Tezgah', '30m', 'C', '40', '91.5', '2.5', '250', 'AC/30/30', '27', '26.8', '1'),
(1410, 5, 2, 'Tv arkası', '30m', 'C', '40', '91.9', '2.58', '250', 'AC/30/30', '27', '26.5', '1'),
(1411, 5, 3, 'Tv arkası', '30m', 'C', '40', '92.1', '2.89', '250', 'AC/30/30', '27', '26.5', '1'),
(1412, 5, 4, 'Depo önü', '30m', 'C', '40', '91.8', '2.78', '250', 'AC/30/30', '27', '26.7', '1'),
(1413, 5, 5, 'Vitrin arkası', '30m', 'C', '40', '91.3', '3.1', '250', 'AC/30/30', '24', '25.9', '1'),
(1414, 6, 1, 'Lavabo Öğrenci', '40', 'C', '0,03', '-', '1,25', '80', 'AC/30/30', '30', '29,1', '1'),
(1415, 6, 2, 'Etkinlik Alanı', '40', 'C', '0,03', '-', '1,35', '80', 'AC/30/30', '24', '28,9', '1'),
(1416, 6, 3, 'Mutfak', '40', 'C', '0,03', '-', '1,98', '80', 'AC/30/30', '24', '28,9', '1'),
(1417, 6, 4, 'Müdür Odası', '40', 'C', '0,03', '-', '1,42', '80', 'AC/30/30', '30', '28,8', '1'),
(1418, 6, 5, 'Lavabo', '40', 'C', '0,03', '-', '1,76', '80', 'AC/30/30', '27', '29,2', '1'),
(1419, 10, 1, 'a', 'b', '2', '2', '2', '2', '2', '2', '111', '222', '1');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `measurements_5_2`
--

CREATE TABLE `measurements_5_2` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `row_no` int(11) DEFAULT NULL,
  `upstream_panel` varchar(255) DEFAULT NULL,
  `upstream_rcd_type` varchar(50) DEFAULT NULL,
  `upstream_rcd_in` varchar(50) DEFAULT NULL,
  `upstream_rcd_idn` varchar(50) DEFAULT NULL,
  `upstream_rcd_dt` varchar(50) DEFAULT NULL,
  `downstream_panel` varchar(255) DEFAULT NULL,
  `downstream_rcd_type` varchar(50) DEFAULT NULL,
  `downstream_rcd_idn` varchar(50) DEFAULT NULL,
  `downstream_rcd_t` varchar(50) DEFAULT NULL,
  `result` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `measurements_5_2`
--

INSERT INTO `measurements_5_2` (`id`, `report_id`, `row_no`, `upstream_panel`, `upstream_rcd_type`, `upstream_rcd_in`, `upstream_rcd_idn`, `upstream_rcd_dt`, `downstream_panel`, `downstream_rcd_type`, `downstream_rcd_idn`, `downstream_rcd_t`, `result`) VALUES
(9, 2, 1, 'b', 'b', 'b', 'b', 'b', 'b', 'b', 'b', 'b', 'b'),
(10, 2, 2, '1', '1', '1', '1', '1', '1', '1', '1', '1', '1'),
(11, 1, 1, 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a'),
(12, 1, 2, 'b', 'b', '', '', '', '', '', '', '', ''),
(19, 3, 1, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-1(kombinasyon Panosu)', 'C', '27', '24.5', '1'),
(20, 3, 2, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-2', 'C', '30', '25.7', '1'),
(21, 3, 3, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-3', 'C', '27', '28.6', '1'),
(22, 3, 4, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-4', 'C', '24', '25.1', '1'),
(23, 3, 5, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-5', 'C', '30', '28.5', '1'),
(24, 3, 6, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-6', 'C', '24', '26', '1'),
(25, 3, 7, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-7', 'C', '27', '29.3', '1'),
(26, 3, 8, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-8', 'C', '24', '25.5', '1'),
(27, 3, 9, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-9', 'C', '24', '21.8', '1'),
(28, 3, 10, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-10', 'C', '27', '29.9', '1'),
(29, 3, 11, 'Busbar Sistem Panosu (Trafo)', 'C', '40', '30', '300', 'KP-11', 'C', '24', '22.5', '1'),
(31, 4, 1, 'Dağıtım Panosu', 'TMS', '1600', '300', '30', 'Eski Ana Pano', 'C', '30', '30', '1'),
(32, 5, 1, 'AVM Şebekesi', 'AC', '40', '30', '2.5', 'Mağaza Panosu', 'AC', '30', '30', '1'),
(33, 6, 1, 'Ana Dağıtım', 'AC', '40', '300', '30', 'Kapı yanı pano', 'AC', '30', '33', '1');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `measurement_devices`
--

CREATE TABLE `measurement_devices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `cal_date` date DEFAULT NULL,
  `validity_date` date DEFAULT NULL,
  `cal_no` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_thermal_camera` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `measurement_devices`
--

INSERT INTO `measurement_devices` (`id`, `user_id`, `device_name`, `serial_no`, `cal_date`, `validity_date`, `cal_no`, `created_at`, `is_thermal_camera`) VALUES
(1, 2, 'kyoritsu', '0001014', '2025-05-22', '2026-05-22', 'T25759', '2026-02-15 13:02:12', 0),
(2, 2, 'Testo', 'tsto000124', '2025-05-22', '2026-05-22', 'T25759', '2026-02-18 12:47:58', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `report_devices`
--

CREATE TABLE `report_devices` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `cal_date` date DEFAULT NULL,
  `validity_date` date DEFAULT NULL,
  `cal_no` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('active_logo', 'logo_1780600477_385.png'),
('logo_text', 'AHMET TUNA ARIKAN MÜHENDİSLİK'),
('logo_type', 'image');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `uploaded_logos`
--

CREATE TABLE `uploaded_logos` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `uploaded_logos`
--

INSERT INTO `uploaded_logos` (`id`, `filename`, `original_name`, `uploaded_at`) VALUES
(1, 'logo_1780600477_385.png', 'logo_1780600388_298.png', '2026-06-04 19:14:37');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(2, 'admin', '$2y$10$/H1GxSZJeyraVWKUbLWJMe05lRSvpFjte2dSZieM8Vx/iLUNfxd6S', 'admin', '2026-02-15 12:42:28');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `authorized_persons`
--
ALTER TABLE `authorized_persons`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `facility_info`
--
ALTER TABLE `facility_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_kurum` (`kurum_id`);

--
-- Tablo için indeksler `fire_detection_reports`
--
ALTER TABLE `fire_detection_reports`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `fire_detection_section5_2`
--
ALTER TABLE `fire_detection_section5_2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Tablo için indeksler `general_reports`
--
ALTER TABLE `general_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kurum_id` (`kurum_id`);

--
-- Tablo için indeksler `general_report_images`
--
ALTER TABLE `general_report_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Tablo için indeksler `grounding_reports`
--
ALTER TABLE `grounding_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kurum_id` (`kurum_id`),
  ADD KEY `authorized_person_id` (`authorized_person_id`);

--
-- Tablo için indeksler `ic_tesisat_panels`
--
ALTER TABLE `ic_tesisat_panels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Tablo için indeksler `ic_tesisat_photos`
--
ALTER TABLE `ic_tesisat_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `panel_id` (`panel_id`);

--
-- Tablo için indeksler `ic_tesisat_section5`
--
ALTER TABLE `ic_tesisat_section5`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_panel_question` (`panel_id`,`question_key`);

--
-- Tablo için indeksler `ic_tesisat_section6_1`
--
ALTER TABLE `ic_tesisat_section6_1`
  ADD PRIMARY KEY (`id`),
  ADD KEY `panel_id` (`panel_id`);

--
-- Tablo için indeksler `ic_tesisat_section6_1_rows`
--
ALTER TABLE `ic_tesisat_section6_1_rows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `panel_id` (`panel_id`);

--
-- Tablo için indeksler `ic_tesisat_section6_2_rows`
--
ALTER TABLE `ic_tesisat_section6_2_rows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Tablo için indeksler `ic_tesisat_section6_3_rows`
--
ALTER TABLE `ic_tesisat_section6_3_rows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Tablo için indeksler `ic_tesisat_section6_header`
--
ALTER TABLE `ic_tesisat_section6_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_id` (`report_id`);

--
-- Tablo için indeksler `institutions`
--
ALTER TABLE `institutions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_code` (`il_kodu`,`kurum_kodu`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `internal_installation_reports`
--
ALTER TABLE `internal_installation_reports`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `lightning_protection_reports`
--
ALTER TABLE `lightning_protection_reports`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `lightning_protection_section4`
--
ALTER TABLE `lightning_protection_section4`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Tablo için indeksler `measurements_5_1`
--
ALTER TABLE `measurements_5_1`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Tablo için indeksler `measurements_5_2`
--
ALTER TABLE `measurements_5_2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Tablo için indeksler `measurement_devices`
--
ALTER TABLE `measurement_devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `report_devices`
--
ALTER TABLE `report_devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Tablo için indeksler `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Tablo için indeksler `uploaded_logos`
--
ALTER TABLE `uploaded_logos`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `authorized_persons`
--
ALTER TABLE `authorized_persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `facility_info`
--
ALTER TABLE `facility_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `fire_detection_reports`
--
ALTER TABLE `fire_detection_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `fire_detection_section5_2`
--
ALTER TABLE `fire_detection_section5_2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- Tablo için AUTO_INCREMENT değeri `general_reports`
--
ALTER TABLE `general_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `general_report_images`
--
ALTER TABLE `general_report_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `grounding_reports`
--
ALTER TABLE `grounding_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `ic_tesisat_panels`
--
ALTER TABLE `ic_tesisat_panels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `ic_tesisat_photos`
--
ALTER TABLE `ic_tesisat_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Tablo için AUTO_INCREMENT değeri `ic_tesisat_section5`
--
ALTER TABLE `ic_tesisat_section5`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=376;

--
-- Tablo için AUTO_INCREMENT değeri `ic_tesisat_section6_1`
--
ALTER TABLE `ic_tesisat_section6_1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `ic_tesisat_section6_1_rows`
--
ALTER TABLE `ic_tesisat_section6_1_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `ic_tesisat_section6_2_rows`
--
ALTER TABLE `ic_tesisat_section6_2_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Tablo için AUTO_INCREMENT değeri `ic_tesisat_section6_3_rows`
--
ALTER TABLE `ic_tesisat_section6_3_rows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `ic_tesisat_section6_header`
--
ALTER TABLE `ic_tesisat_section6_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `institutions`
--
ALTER TABLE `institutions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `internal_installation_reports`
--
ALTER TABLE `internal_installation_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `lightning_protection_reports`
--
ALTER TABLE `lightning_protection_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `lightning_protection_section4`
--
ALTER TABLE `lightning_protection_section4`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- Tablo için AUTO_INCREMENT değeri `measurements_5_1`
--
ALTER TABLE `measurements_5_1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1420;

--
-- Tablo için AUTO_INCREMENT değeri `measurements_5_2`
--
ALTER TABLE `measurements_5_2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Tablo için AUTO_INCREMENT değeri `measurement_devices`
--
ALTER TABLE `measurement_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `report_devices`
--
ALTER TABLE `report_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `uploaded_logos`
--
ALTER TABLE `uploaded_logos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `facility_info`
--
ALTER TABLE `facility_info`
  ADD CONSTRAINT `facility_info_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `fire_detection_section5_2`
--
ALTER TABLE `fire_detection_section5_2`
  ADD CONSTRAINT `fire_detection_section5_2_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `fire_detection_reports` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `general_reports`
--
ALTER TABLE `general_reports`
  ADD CONSTRAINT `general_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `general_report_images`
--
ALTER TABLE `general_report_images`
  ADD CONSTRAINT `general_report_images_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `general_reports` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `grounding_reports`
--
ALTER TABLE `grounding_reports`
  ADD CONSTRAINT `grounding_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grounding_reports_ibfk_2` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`);

--
-- Tablo kısıtlamaları `ic_tesisat_panels`
--
ALTER TABLE `ic_tesisat_panels`
  ADD CONSTRAINT `ic_tesisat_panels_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `internal_installation_reports` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ic_tesisat_photos`
--
ALTER TABLE `ic_tesisat_photos`
  ADD CONSTRAINT `ic_tesisat_photos_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `ic_tesisat_panels` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ic_tesisat_section5`
--
ALTER TABLE `ic_tesisat_section5`
  ADD CONSTRAINT `ic_tesisat_section5_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `ic_tesisat_panels` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ic_tesisat_section6_1`
--
ALTER TABLE `ic_tesisat_section6_1`
  ADD CONSTRAINT `ic_tesisat_section6_1_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `ic_tesisat_panels` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ic_tesisat_section6_1_rows`
--
ALTER TABLE `ic_tesisat_section6_1_rows`
  ADD CONSTRAINT `ic_tesisat_section6_1_rows_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `ic_tesisat_panels` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ic_tesisat_section6_2_rows`
--
ALTER TABLE `ic_tesisat_section6_2_rows`
  ADD CONSTRAINT `ic_tesisat_section6_2_rows_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `internal_installation_reports` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ic_tesisat_section6_3_rows`
--
ALTER TABLE `ic_tesisat_section6_3_rows`
  ADD CONSTRAINT `ic_tesisat_section6_3_rows_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `internal_installation_reports` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ic_tesisat_section6_header`
--
ALTER TABLE `ic_tesisat_section6_header`
  ADD CONSTRAINT `ic_tesisat_section6_header_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `internal_installation_reports` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `institutions`
--
ALTER TABLE `institutions`
  ADD CONSTRAINT `institutions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `lightning_protection_section4`
--
ALTER TABLE `lightning_protection_section4`
  ADD CONSTRAINT `lightning_protection_section4_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `lightning_protection_reports` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `measurements_5_1`
--
ALTER TABLE `measurements_5_1`
  ADD CONSTRAINT `measurements_5_1_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `grounding_reports` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `measurements_5_2`
--
ALTER TABLE `measurements_5_2`
  ADD CONSTRAINT `measurements_5_2_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `grounding_reports` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `measurement_devices`
--
ALTER TABLE `measurement_devices`
  ADD CONSTRAINT `measurement_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `report_devices`
--
ALTER TABLE `report_devices`
  ADD CONSTRAINT `report_devices_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `grounding_reports` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
