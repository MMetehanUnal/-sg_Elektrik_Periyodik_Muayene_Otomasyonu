-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: factory_automation
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `api_tokens`
--

DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_user_expires` (`user_id`,`expires_at`),
  CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_tokens`
--

LOCK TABLES `api_tokens` WRITE;
/*!40000 ALTER TABLE `api_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authorized_person_documents`
--

DROP TABLE IF EXISTS `authorized_person_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authorized_person_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `person_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `person_id` (`person_id`),
  CONSTRAINT `authorized_person_documents_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authorized_person_documents`
--

LOCK TABLES `authorized_person_documents` WRITE;
/*!40000 ALTER TABLE `authorized_person_documents` DISABLE KEYS */;
INSERT INTO `authorized_person_documents` VALUES (1,1,'yetkili','auth_doc_1_1783449453_7283.pdf',1479201,'2026-07-07 18:37:33');
/*!40000 ALTER TABLE `authorized_person_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authorized_persons`
--

DROP TABLE IF EXISTS `authorized_persons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `authorized_persons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adi_soyadi` varchar(255) NOT NULL,
  `meslegi` varchar(255) DEFAULT NULL,
  `kayit_no` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `diploma_no` varchar(100) DEFAULT NULL,
  `oda_sicil_no` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authorized_persons`
--

LOCK TABLES `authorized_persons` WRITE;
/*!40000 ALTER TABLE `authorized_persons` DISABLE KEYS */;
INSERT INTO `authorized_persons` VALUES (1,'Test User','Makine Müh.','K12345678','2026-07-07 08:24:21','D-123456','O-9999');
/*!40000 ALTER TABLE `authorized_persons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `boyler_tanki_reports`
--

DROP TABLE IF EXISTS `boyler_tanki_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boyler_tanki_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT 'Periyodik Kontrol',
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mevzuat` varchar(255) DEFAULT 'İş Ekipmanlarının Kullanımında Sağlık ve Güv. Şartları Yön. Sayısı: 28628 / 25.04.2013, TS EN 13445-5',
  `brand` varchar(100) DEFAULT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `operating_pressure` varchar(50) DEFAULT NULL,
  `production_year` varchar(50) DEFAULT NULL,
  `test_pressure` varchar(50) DEFAULT NULL,
  `capacity` varchar(50) DEFAULT NULL,
  `tank_donanimlari` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tank_donanimlari`)),
  `inspection_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inspection_results`)),
  `hydrostatic_test` text DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `result_text` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'UYGUNDUR',
  `authorized_person_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_no` (`report_no`),
  KEY `kurum_id` (`kurum_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `boyler_tanki_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boyler_tanki_reports_ibfk_2` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `boyler_tanki_reports`
--

LOCK TABLES `boyler_tanki_reports` WRITE;
/*!40000 ALTER TABLE `boyler_tanki_reports` DISABLE KEYS */;
INSERT INTO `boyler_tanki_reports` VALUES (2,1,'01-12-btank-1783413889','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','','','İş Ekipmanlarının Kullanımında Sağlık ve Güv. Şartları Yön. Sayısı: 28628 / 25.04.2013, TS EN 13445-5','','','','','','12','','{\"d1\":{\"status\":\"Var\",\"amount\":\"1\"},\"d2\":{\"status\":\"Var\",\"amount\":\"1\"},\"d3\":{\"status\":\"Var\",\"amount\":\"1\"},\"d4\":{\"status\":\"Var\",\"amount\":\"1\"},\"d5\":{\"status\":\"Var\",\"amount\":\"1\"},\"d6\":{\"status\":\"Var\",\"amount\":\"1\"},\"d7\":{\"status\":\"Var\",\"amount\":\"1\"}}','{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"-\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"-\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\"}','Boyler Tankının bütün bağlantıları kapatıldı. Tank 20 °C su ile 12 Bar basınç altında 1/2 saat bekletildi. Boyler Tankında deformasyon ve sızıntıların olmadığı görüldü.','','Yukarıda teknik özellikleri verilen ve kontrol tarihinde durumu belirtilen Boyler Tankının testi 12 Bar basınç altında yapılmış olup bir sonraki kontrol tarihine kadar kullanılmasında İŞÇİ SAĞLIĞI ve İŞ GÜVENLİĞİ açısından risk taşımamaktadır. İşletmesi UYGUNDUR.','UYGUNDUR',1,'2026-07-07 08:44:49');
/*!40000 ALTER TABLE `boyler_tanki_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_documents`
--

DROP TABLE IF EXISTS `company_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_documents`
--

LOCK TABLES `company_documents` WRITE;
/*!40000 ALTER TABLE `company_documents` DISABLE KEYS */;
INSERT INTO `company_documents` VALUES (1,'şirket','company_doc_1783449471_1681.pdf',300766,'2026-07-07 18:37:51');
/*!40000 ALTER TABLE `company_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device_calibration_documents`
--

DROP TABLE IF EXISTS `device_calibration_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `device_calibration_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `device_id` (`device_id`),
  CONSTRAINT `device_calibration_documents_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `measurement_devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `device_calibration_documents`
--

LOCK TABLES `device_calibration_documents` WRITE;
/*!40000 ALTER TABLE `device_calibration_documents` DISABLE KEYS */;
INSERT INTO `device_calibration_documents` VALUES (1,2,'kalibrasyon termal','cal_doc_2_1783449493_2847.pdf',928562,'2026-07-07 18:38:13'),(2,1,'kalibrasyon normal','cal_doc_1_1783449513_4579.pdf',928562,'2026-07-07 18:38:33');
/*!40000 ALTER TABLE `device_calibration_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `engelli_rampasi_reports`
--

DROP TABLE IF EXISTS `engelli_rampasi_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `engelli_rampasi_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT 'Periyodik Kontrol',
  `report_text` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'UYGUNDUR',
  `authorized_person_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_no` (`report_no`),
  KEY `kurum_id` (`kurum_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `engelli_rampasi_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `engelli_rampasi_reports_ibfk_2` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `engelli_rampasi_reports`
--

LOCK TABLES `engelli_rampasi_reports` WRITE;
/*!40000 ALTER TABLE `engelli_rampasi_reports` DISABLE KEYS */;
INSERT INTO `engelli_rampasi_reports` VALUES (2,1,'01-12-ramp-1783413735','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','x yetkililerinin müracatları ile kontrolü yapılan yurt girişindeki engelliler ve hareket kısıklığı bulunan kişiler için mevcut olan rampanın standartlara uygun ve güvenli olduğu tarafımızdan görülmüştür.','UYGUNDUR',1,'2026-07-07 08:42:15'),(3,1,'01-12-ramp-1783425626','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','x yetkililerinin müracatları ile kontrolü yapılan yurt girişindeki engelliler ve hareket kısıklığı bulunan kişiler için mevcut olan rampanın standartlara uygun ve güvenli olduğu tarafımızdan görülmüştür.','UYGUNDUR',1,'2026-07-07 12:00:26');
/*!40000 ALTER TABLE `engelli_rampasi_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facility_info`
--

DROP TABLE IF EXISTS `facility_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facility_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `enerji_saglayan` varchar(255) DEFAULT NULL,
  `sebeke_tipi` varchar(20) DEFAULT NULL,
  `sebeke_gerilimi` varchar(50) DEFAULT NULL,
  `proje_var_mi` tinyint(1) DEFAULT 0,
  `sema_var_mi` tinyint(1) DEFAULT 0,
  `yapi_cinsi` varchar(50) DEFAULT NULL,
  `kullanim_amaci` varchar(255) DEFAULT NULL,
  `sozlesme_id` varchar(100) DEFAULT NULL,
  `son_kontrol_tarihi` date DEFAULT NULL,
  `weather_condition` varchar(255) DEFAULT NULL,
  `ground_moisture` varchar(255) DEFAULT NULL,
  `grounding_type` varchar(50) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `default_authorized_person_id` int(11) DEFAULT NULL,
  `default_device_id` int(11) DEFAULT NULL,
  `default_thermal_device_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kurum_id` (`kurum_id`),
  CONSTRAINT `facility_info_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_info`
--

LOCK TABLES `facility_info` WRITE;
/*!40000 ALTER TABLE `facility_info` DISABLE KEYS */;
INSERT INTO `facility_info` VALUES (1,1,'','','',0,0,'','','',NULL,'','','','',NULL,'2026-07-07 18:35:59',1,1,2);
/*!40000 ALTER TABLE `facility_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fire_detection_reports`
--

DROP TABLE IF EXISTS `fire_detection_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fire_detection_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `algilama_sistemi` varchar(50) DEFAULT NULL,
  `uyari_sistemi` varchar(50) DEFAULT NULL,
  `sistem_calisma_tipi` varchar(50) DEFAULT NULL,
  `proje_onay_kurumu` varchar(255) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT NULL,
  `proje_onay_bilgileri` varchar(255) DEFAULT NULL,
  `panel_marka_model` varchar(255) DEFAULT NULL,
  `ilk_kontrol_tarihi` date DEFAULT NULL,
  `prev_control_date` date DEFAULT NULL,
  `weather_condition` varchar(255) DEFAULT NULL,
  `ground_moisture` varchar(255) DEFAULT NULL,
  `panel_seri_no` varchar(100) DEFAULT NULL,
  `panel_calisma_gerilimi` varchar(50) DEFAULT NULL,
  `algilama_ekipmanlari` text DEFAULT NULL,
  `panel_yeri` varchar(255) DEFAULT NULL,
  `uyari_ekipmanlari` text DEFAULT NULL,
  `sondurme_ekipmanlari` text DEFAULT NULL,
  `installation_change` varchar(50) DEFAULT NULL,
  `prev_label_exists` varchar(50) DEFAULT NULL,
  `bina_kullanma_sinifi` varchar(255) DEFAULT NULL,
  `bina_tehlike_sinifi` varchar(100) DEFAULT NULL,
  `tehlike_kategorisi` varchar(10) DEFAULT NULL,
  `toplam_alan` varchar(50) DEFAULT NULL,
  `kat_sayisi` varchar(20) DEFAULT NULL,
  `bina_yuksekligi` varchar(50) DEFAULT NULL,
  `yapi_kullanma_izin_tarihi` date DEFAULT NULL,
  `bolum_sayisi` varchar(20) DEFAULT NULL,
  `diger_tespitler` text DEFAULT NULL,
  `device1_id` int(11) DEFAULT NULL,
  `device2_id` int(11) DEFAULT NULL,
  `authorized_person_id` int(11) DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result` varchar(50) DEFAULT NULL,
  `inspection_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inspection_results`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kurum_id` (`kurum_id`),
  KEY `device1_id` (`device1_id`),
  KEY `device2_id` (`device2_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `fire_detection_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fire_detection_reports_ibfk_2` FOREIGN KEY (`device1_id`) REFERENCES `measurement_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fire_detection_reports_ibfk_3` FOREIGN KEY (`device2_id`) REFERENCES `measurement_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fire_detection_reports_ibfk_4` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fire_detection_reports`
--

LOCK TABLES `fire_detection_reports` WRITE;
/*!40000 ALTER TABLE `fire_detection_reports` DISABLE KEYS */;
INSERT INTO `fire_detection_reports` VALUES (1,1,'01-12-ya-1783425702','2026-07-07','','2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','4536','','','','','','','',NULL,NULL,'','','','','','','','','','','','','','','','',NULL,'','',NULL,NULL,1,'','','UYGUNDUR',NULL,'2026-07-07 12:01:42'),(2,1,'01-12-ya-1783503140','2026-07-07','','2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','','','','','','','','',NULL,NULL,'','','','','','','','','','','','','','','','',NULL,'','',1,NULL,1,'','','UYGUNDUR',NULL,'2026-07-08 09:32:20');
/*!40000 ALTER TABLE `fire_detection_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fire_detection_section5_2`
--

DROP TABLE IF EXISTS `fire_detection_section5_2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fire_detection_section5_2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `loop_no` varchar(50) DEFAULT NULL,
  `bolum_adi` varchar(255) DEFAULT NULL,
  `ekipman_adi` varchar(255) DEFAULT NULL,
  `projede_mi` varchar(10) DEFAULT NULL,
  `erisim_durumu` varchar(10) DEFAULT NULL,
  `montaj_durumu` varchar(10) DEFAULT NULL,
  `test` varchar(10) DEFAULT NULL,
  `sesli_uyari` varchar(10) DEFAULT NULL,
  `isikli_uyari` varchar(10) DEFAULT NULL,
  `adresleme` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `fire_detection_section5_2_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `fire_detection_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fire_detection_section5_2`
--

LOCK TABLES `fire_detection_section5_2` WRITE;
/*!40000 ALTER TABLE `fire_detection_section5_2` DISABLE KEYS */;
/*!40000 ALTER TABLE `fire_detection_section5_2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gaz_tesisat_reports`
--

DROP TABLE IF EXISTS `gaz_tesisat_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gaz_tesisat_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT 'Periyodik Kontrol',
  `kurum_yoneticisi` varchar(255) DEFAULT NULL,
  `kurum_kapasitesi` int(11) DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'GÜVENLİDİR',
  `authorized_person_id` int(11) DEFAULT NULL,
  `inspection_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inspection_results`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_no` (`report_no`),
  KEY `kurum_id` (`kurum_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `gaz_tesisat_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gaz_tesisat_reports_ibfk_2` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gaz_tesisat_reports`
--

LOCK TABLES `gaz_tesisat_reports` WRITE;
/*!40000 ALTER TABLE `gaz_tesisat_reports` DISABLE KEYS */;
INSERT INTO `gaz_tesisat_reports` VALUES (2,1,'01-12-gt-1783413156','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','',NULL,'','','GÜVENLİDİR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\"}','2026-07-07 08:32:36'),(3,1,'01-12-gt-1783413198','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','',NULL,'','','GÜVENLİDİR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\"}','2026-07-07 08:33:18'),(4,1,'01-12-gt-1783425745','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','',NULL,'','','GÜVENLİDİR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\"}','2026-07-07 12:02:25'),(5,1,'01-12-gt-1783425821','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','',NULL,'','','GÜVENLİDİR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\"}','2026-07-07 12:03:41');
/*!40000 ALTER TABLE `gaz_tesisat_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `general_report_images`
--

DROP TABLE IF EXISTS `general_report_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `general_report_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `general_report_images_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `general_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `general_report_images`
--

LOCK TABLES `general_report_images` WRITE;
/*!40000 ALTER TABLE `general_report_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `general_report_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `general_reports`
--

DROP TABLE IF EXISTS `general_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `general_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `title` varchar(500) NOT NULL,
  `content` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `yurt_yoneticisi` varchar(255) DEFAULT NULL,
  `yatak_kapasitesi` varchar(50) DEFAULT NULL,
  `is_guvenligi_uzmani` varchar(255) DEFAULT NULL,
  `ada` varchar(50) DEFAULT NULL,
  `pafta` varchar(50) DEFAULT NULL,
  `parsel` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `report_no` varchar(100) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `control_date` date DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `mekanik_uzman_id` int(11) DEFAULT NULL,
  `elektrik_uzman_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kurum_id` (`kurum_id`),
  KEY `fk_general_reports_mekanik` (`mekanik_uzman_id`),
  KEY `fk_general_reports_elektrik` (`elektrik_uzman_id`),
  CONSTRAINT `fk_general_reports_elektrik` FOREIGN KEY (`elektrik_uzman_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_general_reports_mekanik` FOREIGN KEY (`mekanik_uzman_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `general_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `general_reports`
--

LOCK TABLES `general_reports` WRITE;
/*!40000 ALTER TABLE `general_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `general_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genlesme_tanki_reports`
--

DROP TABLE IF EXISTS `genlesme_tanki_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `genlesme_tanki_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT 'Periyodik Kontrol',
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mevzuat` varchar(255) DEFAULT 'İş Ekipmanlarının Kullanımında Sağlık ve Güv. Şartları Yön. Sayısı: 28628 / 25.04.2013, TS EN 13445-5',
  `brand` varchar(100) DEFAULT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `operating_pressure` varchar(50) DEFAULT NULL,
  `production_year` varchar(50) DEFAULT NULL,
  `test_pressure` varchar(50) DEFAULT NULL,
  `capacity` varchar(50) DEFAULT NULL,
  `tank_donanimlari` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tank_donanimlari`)),
  `inspection_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inspection_results`)),
  `hydrostatic_test` text DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `result_text` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'UYGUNDUR',
  `authorized_person_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_no` (`report_no`),
  KEY `kurum_id` (`kurum_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `genlesme_tanki_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `genlesme_tanki_reports_ibfk_2` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genlesme_tanki_reports`
--

LOCK TABLES `genlesme_tanki_reports` WRITE;
/*!40000 ALTER TABLE `genlesme_tanki_reports` DISABLE KEYS */;
INSERT INTO `genlesme_tanki_reports` VALUES (2,1,'01-12-gtank-1783413592','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','','','İş Ekipmanlarının Kullanımında Sağlık ve Güv. Şartları Yön. Sayısı: 28628 / 25.04.2013, TS EN 13445-5','','','','','','12','','{\"d1\":{\"status\":\"Var\",\"amount\":\"1\"},\"d2\":{\"status\":\"Var\",\"amount\":\"1\"},\"d3\":{\"status\":\"Var\",\"amount\":\"1\"},\"d4\":{\"status\":\"Var\",\"amount\":\"1\"},\"d5\":{\"status\":\"Var\",\"amount\":\"1\"},\"d6\":{\"status\":\"Var\",\"amount\":\"1\"},\"d7\":{\"status\":\"Var\",\"amount\":\"1\"}}','{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"-\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"-\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\"}','Genleşme Tankının bütün bağlantıları kapatıldı. Tank 20 °C su ile 12 Bar basınç altında 1/2 saat bekletildi. Genleşme Tankında deformasyon ve sızıntıların olmadığı görüldü.','','Yukarıda teknik özellikleri verilen ve kontrol tarihinde durumu belirtilen Genleşme Tankının testi 12 Bar basınç altında yapılmış olup bir sonraki kontrol tarihine kadar kullanılmasında İŞÇİ SAĞLIĞI ve İŞ GÜVENLİĞİ açısından risk taşımamaktadır. İşletmesi UYGUNDUR.','UYGUNDUR',1,'2026-07-07 08:39:52');
/*!40000 ALTER TABLE `genlesme_tanki_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grounding_reports`
--

DROP TABLE IF EXISTS `grounding_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grounding_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT NULL,
  `grounding_type` varchar(50) DEFAULT NULL,
  `weather` varchar(255) DEFAULT NULL,
  `soil_moisture` varchar(255) DEFAULT NULL,
  `sebeke_tipi` varchar(20) DEFAULT NULL,
  `proje_var_mi` tinyint(1) DEFAULT 0,
  `sema_var_mi` tinyint(1) DEFAULT 0,
  `yapi_cinsi` varchar(50) DEFAULT NULL,
  `protection_measure` text DEFAULT NULL,
  `changes_exist` tinyint(1) DEFAULT 0,
  `prev_label_exists` tinyint(1) DEFAULT 0,
  `panel_id` varchar(100) DEFAULT NULL,
  `device1_id` int(11) DEFAULT NULL,
  `device2_id` int(11) DEFAULT NULL,
  `measurement_method` varchar(100) DEFAULT NULL,
  `project_info` text DEFAULT NULL,
  `prev_control_date` date DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result` varchar(50) DEFAULT NULL,
  `result_notes_selection` text DEFAULT NULL,
  `authorized_person_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kurum_id` (`kurum_id`),
  KEY `device1_id` (`device1_id`),
  KEY `device2_id` (`device2_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `grounding_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grounding_reports_ibfk_2` FOREIGN KEY (`device1_id`) REFERENCES `measurement_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grounding_reports_ibfk_3` FOREIGN KEY (`device2_id`) REFERENCES `measurement_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grounding_reports_ibfk_4` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grounding_reports`
--

LOCK TABLES `grounding_reports` WRITE;
/*!40000 ALTER TABLE `grounding_reports` DISABLE KEYS */;
INSERT INTO `grounding_reports` VALUES (1,1,'01-12-t-1783413185','2026-07-07','','2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','4536','','','','',NULL,0,0,NULL,'',0,0,'',NULL,NULL,'Cevrim empedansi','',NULL,'','','UYGUNDUR','',1,'2026-07-07 08:33:05'),(2,1,'01-12-t-1783414701','2026-07-07','','2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','4536','','','','',NULL,0,0,NULL,'',0,0,'',NULL,NULL,'Cevrim empedansi','',NULL,'','','UYGUNDUR','',1,'2026-07-07 08:58:21'),(3,1,'01-12-t-1783425659','2026-07-07','','2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','4536','','','','',NULL,0,0,NULL,'',0,0,'',NULL,NULL,'Cevrim empedansi','',NULL,'','','UYGUNDUR','',1,'2026-07-07 12:00:59'),(4,1,'01-12-t-1783503113','2026-07-07','','2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','','','','','',NULL,0,0,NULL,'',0,0,'',1,NULL,'Cevrim empedansi','',NULL,'','','UYGUNDUR','',1,'2026-07-08 09:31:53');
/*!40000 ALTER TABLE `grounding_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ic_tesisat_panels`
--

DROP TABLE IF EXISTS `ic_tesisat_panels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ic_tesisat_panels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `panel_name` varchar(255) NOT NULL,
  `panel_order` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `thermal_numbers` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `ic_tesisat_panels_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `internal_installation_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ic_tesisat_panels`
--

LOCK TABLES `ic_tesisat_panels` WRITE;
/*!40000 ALTER TABLE `ic_tesisat_panels` DISABLE KEYS */;
/*!40000 ALTER TABLE `ic_tesisat_panels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ic_tesisat_photos`
--

DROP TABLE IF EXISTS `ic_tesisat_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ic_tesisat_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `panel_id` int(11) NOT NULL,
  `photo_type` enum('normal','termal') DEFAULT 'normal',
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `panel_id` (`panel_id`),
  CONSTRAINT `ic_tesisat_photos_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `ic_tesisat_panels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ic_tesisat_photos`
--

LOCK TABLES `ic_tesisat_photos` WRITE;
/*!40000 ALTER TABLE `ic_tesisat_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `ic_tesisat_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ic_tesisat_section5`
--

DROP TABLE IF EXISTS `ic_tesisat_section5`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ic_tesisat_section5` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `panel_id` int(11) NOT NULL,
  `question_key` varchar(100) NOT NULL,
  `answer` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `panel_id` (`panel_id`),
  CONSTRAINT `ic_tesisat_section5_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `ic_tesisat_panels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ic_tesisat_section5`
--

LOCK TABLES `ic_tesisat_section5` WRITE;
/*!40000 ALTER TABLE `ic_tesisat_section5` DISABLE KEYS */;
/*!40000 ALTER TABLE `ic_tesisat_section5` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ic_tesisat_section6_1`
--

DROP TABLE IF EXISTS `ic_tesisat_section6_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ic_tesisat_section6_1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `panel_id` int(11) NOT NULL,
  `zx` varchar(50) DEFAULT NULL,
  `zln` varchar(50) DEFAULT NULL,
  `voltage_ff` varchar(50) DEFAULT NULL,
  `voltage_ln` varchar(50) DEFAULT NULL,
  `voltage_npe` varchar(50) DEFAULT NULL,
  `short_circuit_3ph` varchar(50) DEFAULT NULL,
  `dkd_type` varchar(100) DEFAULT NULL,
  `dkd_current` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `panel_id` (`panel_id`),
  CONSTRAINT `ic_tesisat_section6_1_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `ic_tesisat_panels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ic_tesisat_section6_1`
--

LOCK TABLES `ic_tesisat_section6_1` WRITE;
/*!40000 ALTER TABLE `ic_tesisat_section6_1` DISABLE KEYS */;
/*!40000 ALTER TABLE `ic_tesisat_section6_1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ic_tesisat_section6_1_rows`
--

DROP TABLE IF EXISTS `ic_tesisat_section6_1_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ic_tesisat_section6_1_rows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `panel_id` int(11) NOT NULL,
  `no_col` varchar(10) DEFAULT NULL,
  `linye_adi` varchar(255) DEFAULT NULL,
  `acma_egrisi` varchar(50) DEFAULT NULL,
  `kutup_sayisi` varchar(10) DEFAULT NULL,
  `in_a` varchar(50) DEFAULT NULL,
  `icu` varchar(50) DEFAULT NULL,
  `faz_kesiti` varchar(50) DEFAULT NULL,
  `npen_kesiti` varchar(50) DEFAULT NULL,
  `pe_kesiti` varchar(50) DEFAULT NULL,
  `ib_tasarim` varchar(50) DEFAULT NULL,
  `iz_kapasite` varchar(50) DEFAULT NULL,
  `rcd_ia` varchar(50) DEFAULT NULL,
  `rcd_ta` varchar(50) DEFAULT NULL,
  `sonuc` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `panel_id` (`panel_id`),
  CONSTRAINT `ic_tesisat_section6_1_rows_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `ic_tesisat_panels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ic_tesisat_section6_1_rows`
--

LOCK TABLES `ic_tesisat_section6_1_rows` WRITE;
/*!40000 ALTER TABLE `ic_tesisat_section6_1_rows` DISABLE KEYS */;
/*!40000 ALTER TABLE `ic_tesisat_section6_1_rows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ic_tesisat_section6_2_rows`
--

DROP TABLE IF EXISTS `ic_tesisat_section6_2_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ic_tesisat_section6_2_rows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `no_col` varchar(10) DEFAULT NULL,
  `bolum` varchar(255) DEFAULT NULL,
  `pd_kesiti` varchar(50) DEFAULT NULL,
  `pd_sureklilik` varchar(50) DEFAULT NULL,
  `tpd_kesiti` varchar(50) DEFAULT NULL,
  `tpd_sureklilik` varchar(50) DEFAULT NULL,
  `sonuc` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `ic_tesisat_section6_2_rows_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `internal_installation_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ic_tesisat_section6_2_rows`
--

LOCK TABLES `ic_tesisat_section6_2_rows` WRITE;
/*!40000 ALTER TABLE `ic_tesisat_section6_2_rows` DISABLE KEYS */;
/*!40000 ALTER TABLE `ic_tesisat_section6_2_rows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ic_tesisat_section6_3_rows`
--

DROP TABLE IF EXISTS `ic_tesisat_section6_3_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ic_tesisat_section6_3_rows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `no_col` varchar(10) DEFAULT NULL,
  `hali_yeri` varchar(255) DEFAULT NULL,
  `eni` varchar(50) DEFAULT NULL,
  `boyu` varchar(50) DEFAULT NULL,
  `direnc` varchar(50) DEFAULT NULL,
  `sonuc` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `ic_tesisat_section6_3_rows_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `internal_installation_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ic_tesisat_section6_3_rows`
--

LOCK TABLES `ic_tesisat_section6_3_rows` WRITE;
/*!40000 ALTER TABLE `ic_tesisat_section6_3_rows` DISABLE KEYS */;
/*!40000 ALTER TABLE `ic_tesisat_section6_3_rows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ic_tesisat_section6_header`
--

DROP TABLE IF EXISTS `ic_tesisat_section6_header`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ic_tesisat_section6_header` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `measurement_method` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_id` (`report_id`),
  CONSTRAINT `ic_tesisat_section6_header_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `internal_installation_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ic_tesisat_section6_header`
--

LOCK TABLES `ic_tesisat_section6_header` WRITE;
/*!40000 ALTER TABLE `ic_tesisat_section6_header` DISABLE KEYS */;
/*!40000 ALTER TABLE `ic_tesisat_section6_header` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `institutions`
--

DROP TABLE IF EXISTS `institutions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `institutions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `firma_adi` varchar(255) NOT NULL,
  `adresi` text DEFAULT NULL,
  `sgk_sicil_no` varchar(50) DEFAULT NULL,
  `il_kodu` varchar(2) DEFAULT '01',
  `kurum_kodu` varchar(3) DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `contract_pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `institutions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=509 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institutions`
--

LOCK TABLES `institutions` WRITE;
/*!40000 ALTER TABLE `institutions` DISABLE KEYS */;
INSERT INTO `institutions` VALUES (1,1,'x','konya','12314','01','001','4536','2026-07-07','2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','2026-07-07 08:01:18',NULL),(2,1,'a','a','','42','001','','2026-04-03','2026-04-03 08:00:00','2026-04-03 17:00:00','2027-04-03','2026-07-07 19:00:48','contract_1783450827_8643.pdf'),(4,1,'AYD OTOMOTİV ENDÜSTRİ SANAYİ VE TİCARET ANONİM ŞİRKETİ','BÜYÜKKAYACIKOSB MAH. VALİ İHSAN DEDE CAD KONYA SELÇUKLU NO:7/1','22932010110846610422195000','42','002','25126590','2026-04-04','2026-04-04 08:00:00','2026-04-04 17:00:00','2027-04-04','2026-07-07 20:02:20','contract_1783454529_2331.pdf'),(5,1,'AKSARAY - ACARLAR','AKSARAY / Türkiye','D','68','001','13001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(6,1,'AKSARAY - AĞAÇÖREN','AKSARAY / Türkiye','','68','002','13002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(7,1,'AKSARAY - AĞAÇÖREN İLKOKUL MERKEZİ','AKSARAY / Türkiye','','68','003','13003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(8,1,'AKSARAY - AKSARAY ARİFİYE','AKSARAY / Türkiye','','68','004','13004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(9,1,'AKSARAY - AKSARAY ÇAMLICA ÇOCUK','AKSARAY / Türkiye','','68','005','13005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(10,1,'AKSARAY - AKSARAY FERHATLAR','AKSARAY / Türkiye','','68','006','13006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(11,1,'AKSARAY - AKSARAY İSABET OKULLARI','AKSARAY / Türkiye','','68','007','13007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(12,1,'AKSARAY - AKSARAY RÜZGARGÜLÜ','AKSARAY / Türkiye','','68','008','13008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(13,1,'AKSARAY - AKSARAY SARAY ÇİÇEKLERİ ANAOKULU','AKSARAY / Türkiye','','68','009','13009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(14,1,'AKSARAY - AKSARAY SÜLEYMANİYE','AKSARAY / Türkiye','','68','010','13010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(15,1,'AKSARAY - AKSARAY VALİDE SULTAN','AKSARAY / Türkiye','','68','011','13011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(16,1,'AKSARAY - ARMUTLU','AKSARAY / Türkiye','','68','012','13012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(17,1,'AKSARAY - ARMUTLU KIZ','AKSARAY / Türkiye','','68','013','13013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(18,1,'AKSARAY - ARMUTLU KIZ ANAOKULU','AKSARAY / Türkiye','','68','014','13014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(19,1,'AKSARAY - BAHÇESARAY KIZ','AKSARAY / Türkiye','','68','015','13015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(20,1,'AKSARAY - BALCI ANAOKULU','AKSARAY / Türkiye','','68','016','13016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(21,1,'AKSARAY - BEYAZ ZAMBAK İLKOKUL MERKEZİ','AKSARAY / Türkiye','','68','017','13017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(22,1,'AKSARAY - ÇINAR PROJE','AKSARAY / Türkiye','','68','018','13018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(23,1,'AKSARAY - DEMİRCİ','AKSARAY / Türkiye','','68','019','13019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(24,1,'AKSARAY - DEMİRCİ ANAOKULU','AKSARAY / Türkiye','','68','020','13020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(25,1,'AKSARAY - ESKİL','AKSARAY / Türkiye','','68','021','13021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(26,1,'AKSARAY - ESKİL KIZ','AKSARAY / Türkiye','','68','022','13022',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(27,1,'AKSARAY - ESKİL KIZ ANAOKULU','AKSARAY / Türkiye','','68','023','13023',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(28,1,'AKSARAY - EŞMEKAYA KIZ','AKSARAY / Türkiye','','68','024','13024',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(29,1,'AKSARAY - EŞMEKAYA KIZ ANAOKULU','AKSARAY / Türkiye','','68','025','13025',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(30,1,'AKSARAY - GÜLAĞAÇ','AKSARAY / Türkiye','','68','026','13026',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(31,1,'AKSARAY - GÜLAĞAÇ KIZ','AKSARAY / Türkiye','','68','027','13027',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(32,1,'AKSARAY - GÜLAĞAÇ KIZ ANAOKULU','AKSARAY / Türkiye','','68','028','13028',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(33,1,'AKSARAY - GÜZELYURT','AKSARAY / Türkiye','','68','029','13029',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(34,1,'AKSARAY - GÜZELYURT ANAOKULU','AKSARAY / Türkiye','','68','030','13030',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(35,1,'AKSARAY - HARMANDALI','AKSARAY / Türkiye','','68','031','13031',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(36,1,'AKSARAY - HASBAHÇE ANAOKULU','AKSARAY / Türkiye','','68','032','13032',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(37,1,'AKSARAY - HELVADERE','AKSARAY / Türkiye','','68','033','13033',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(38,1,'AKSARAY - HELVADERE KIZ','AKSARAY / Türkiye','','68','034','13034',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(39,1,'AKSARAY - İNCESU','AKSARAY / Türkiye','','68','035','13035',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(40,1,'AKSARAY - İNCESU İLKOKUL MERKEZİ','AKSARAY / Türkiye','','68','036','13036',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(41,1,'AKSARAY - KARTANELERİ ANAOKULU','AKSARAY / Türkiye','','68','037','13037',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(42,1,'AKSARAY - KURTULUŞ BEDİA SULTAN KIZ','AKSARAY / Türkiye','','68','038','13038',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(43,1,'AKSARAY - NAKKAŞ KIZ','AKSARAY / Türkiye','','68','039','13039',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(44,1,'AKSARAY - NAKKAŞ KIZ ANAOKULU','AKSARAY / Türkiye','','68','040','13040',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(45,1,'AKSARAY - NAKKAŞ KIZ NEHARİ MERKEZİ','AKSARAY / Türkiye','','68','041','13041',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(46,1,'AKSARAY - ORTAKÖY FERAH','AKSARAY / Türkiye','','68','042','13042',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(47,1,'AKSARAY - ORTAKÖY İSABET ANAOKULU','AKSARAY / Türkiye','','68','043','13043',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(48,1,'AKSARAY - ORTAKÖY LALE KIZ','AKSARAY / Türkiye','','68','044','13044',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(49,1,'AKSARAY - ORTAKÖY LALE KIZ ANAOKULU','AKSARAY / Türkiye','','68','045','13045',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(50,1,'AKSARAY - RÜZGARGÜLÜ NEHARİ MERKEZİ','AKSARAY / Türkiye','','68','046','13046',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(51,1,'AKSARAY - SARAY','AKSARAY / Türkiye','','68','047','13047',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(52,1,'AKSARAY - SARAY ANAOKULU','AKSARAY / Türkiye','','68','048','13048',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(53,1,'AKSARAY - SARAY NEHARİ MERKEZİ','AKSARAY / Türkiye','','68','049','13049',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(54,1,'AKSARAY - SARIYAHŞİ','AKSARAY / Türkiye','','68','050','13050',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(55,1,'AKSARAY - SULTANHANI','AKSARAY / Türkiye','','68','051','13051',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(56,1,'AKSARAY - SULTANHANI KIZ','AKSARAY / Türkiye','','68','052','13052',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(57,1,'AKSARAY - SULTANHANI KIZ ANAOKULU','AKSARAY / Türkiye','','68','053','13053',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(58,1,'AKSARAY - YENİKENT','AKSARAY / Türkiye','','68','054','13054',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(59,1,'AKSARAY - YENİKENT KIZ','AKSARAY / Türkiye','','68','055','13055',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(60,1,'AKSARAY - YENİKENT KIZ ANAOKULU','AKSARAY / Türkiye','','68','056','13056',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(61,1,'AKSARAY - ZAFER BEYAZ ZAMBAK 2','AKSARAY / Türkiye','','68','057','13057',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(62,1,'AKSARAY - ZAFER PROJE','AKSARAY / Türkiye','','68','058','13058',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(63,1,'AKŞEHİR - AKŞEHİR ARİFİYE','KONYA / Türkiye','','42','003','4001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(64,1,'AKŞEHİR - AKŞEHİR KİRAZLI','KONYA / Türkiye','','42','004','4002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(65,1,'AKŞEHİR - AKŞEHİR KİRAZLI NEHARİ','KONYA / Türkiye','','42','005','4003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(66,1,'AKŞEHİR - AKŞEHİR SEYRAN PROJE','KONYA / Türkiye','','42','006','4004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(67,1,'AKŞEHİR - ALTUNTAŞ','KONYA / Türkiye','','42','007','4005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(68,1,'AKŞEHİR - ÇELTİK ANAOKULU','KONYA / Türkiye','','42','008','4006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(69,1,'AKŞEHİR - ÇELTİK BAHÇELİEVLER','KONYA / Türkiye','','42','009','4007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(70,1,'AKŞEHİR - DOĞRUGÖZ','KONYA / Türkiye','','42','010','4008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(71,1,'AKŞEHİR - DOLUNAY','KONYA / Türkiye','','42','011','4009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(72,1,'AKŞEHİR - HIDIRLIK KIZ','KONYA / Türkiye','','42','012','4010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(73,1,'AKŞEHİR - HİLAL KIZ','KONYA / Türkiye','','42','013','4011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(74,1,'AKŞEHİR - İMAMOĞLU ANAOKULU','KONYA / Türkiye','','42','014','4012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(75,1,'AKŞEHİR - İMAMOĞLU KIZ','KONYA / Türkiye','','42','015','4013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(76,1,'AKŞEHİR - KİRAZ ÇİÇEKLERİ','KONYA / Türkiye','','42','016','4014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(77,1,'AKŞEHİR - KÜÇÜKHASAN İLKOKUL MERKEZİ','KONYA / Türkiye','','42','017','4015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(78,1,'AKŞEHİR - MİNİK ADIMLAR','KONYA / Türkiye','','42','018','4016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(79,1,'AKŞEHİR - NADİR','KONYA / Türkiye','','42','019','4017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(80,1,'AKŞEHİR - NASREDDİN HOCA ANAOKULU','KONYA / Türkiye','','42','020','4018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(81,1,'AKŞEHİR - YUNAK ÇAMLICA','KONYA / Türkiye','','42','021','4019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(82,1,'AKŞEHİR - YUNAK ÇAMLICA ANAOKULU','KONYA / Türkiye','','42','022','4020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(83,1,'AKŞEHİR - YUNAK İNCİ TANELERİ','KONYA / Türkiye','','42','023','4021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(84,1,'BEYŞEHİR - AKÇABELEN','KONYA / Türkiye','','42','024','5001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(85,1,'BEYŞEHİR - BEYŞEHİR ARİFİYE KIZ','KONYA / Türkiye','','42','025','5002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(86,1,'BEYŞEHİR - BEYŞEHİR BADEMLİ','KONYA / Türkiye','','42','026','5003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(87,1,'BEYŞEHİR - BEYŞEHİR FERHATLAR KIZ','KONYA / Türkiye','','42','027','5004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(88,1,'BEYŞEHİR - BEYŞEHİR HAMİDİYE','KONYA / Türkiye','','42','028','5005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(89,1,'BEYŞEHİR - BEYŞEHİR İSABET','KONYA / Türkiye','','42','029','5006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(90,1,'BEYŞEHİR - BEYŞEHİR İSABET OKULU','KONYA / Türkiye','','42','030','5007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(91,1,'BEYŞEHİR - BEYŞEHİR MERKEZ','KONYA / Türkiye','','42','031','5008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(92,1,'BEYŞEHİR - BEYŞEHİR NEHARİ MERKEZİ','KONYA / Türkiye','','42','032','5009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(93,1,'BEYŞEHİR - BEYŞEHİR ÜZÜMLÜ','KONYA / Türkiye','','42','033','5010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(94,1,'BEYŞEHİR - BEYŞEHİR ÜZÜMLÜ KIZ','KONYA / Türkiye','','42','034','5011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(95,1,'BEYŞEHİR - BEYŞEHİR VALİDE SULTAN KIZ','KONYA / Türkiye','','42','035','5012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(96,1,'BEYŞEHİR - BEYŞEHİR YILDIZ ANAOKULU','KONYA / Türkiye','','42','036','5013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(97,1,'BEYŞEHİR - ÇAMLICA ÇOCUK AKADEMİSİ','KONYA / Türkiye','','42','037','5015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(98,1,'BEYŞEHİR - CENNET BAHÇESİ','KONYA / Türkiye','','42','038','5014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(99,1,'BEYŞEHİR - DEREBUCAK KIZ','KONYA / Türkiye','','42','039','5016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(100,1,'BEYŞEHİR - DEREBUCAK SELİMİYE','KONYA / Türkiye','','42','040','5017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(101,1,'BEYŞEHİR - DEREBUCAK SÜLEYMANİYE','KONYA / Türkiye','','42','041','5018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(102,1,'BEYŞEHİR - DOĞANBEY','KONYA / Türkiye','','42','042','5019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(103,1,'BEYŞEHİR - GENCEK','KONYA / Türkiye','','42','043','5020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(104,1,'BEYŞEHİR - GÖÇERİ KIZ','KONYA / Türkiye','','42','044','5021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(105,1,'BEYŞEHİR - GÖKÇİMEN KIZ','KONYA / Türkiye','','42','045','5022',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(106,1,'BEYŞEHİR - GÖL İNCİLERİ ANAOKULU','KONYA / Türkiye','','42','046','5023',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(107,1,'BEYŞEHİR - HUĞLU','KONYA / Türkiye','','42','047','5024',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(108,1,'BEYŞEHİR - HUĞLU FERHAN SULTAN KIZ','KONYA / Türkiye','','42','048','5025',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(109,1,'BEYŞEHİR - HÜYÜK','KONYA / Türkiye','','42','049','5026',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(110,1,'BEYŞEHİR - HÜYÜK KARDELEN','KONYA / Türkiye','','42','050','5027',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(111,1,'BEYŞEHİR - HÜYÜK KIZ','KONYA / Türkiye','','42','051','5028',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(112,1,'BEYŞEHİR - İMRENLER','KONYA / Türkiye','','42','052','5029',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(113,1,'BEYŞEHİR - KARAALİ','KONYA / Türkiye','','42','053','5030',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(114,1,'BEYŞEHİR - KAYABAŞI','KONYA / Türkiye','','42','054','5031',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(115,1,'BEYŞEHİR - KIRELİ','KONYA / Türkiye','','42','055','5032',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(116,1,'BEYŞEHİR - KIRELİ KIR ÇİÇEKLERİ','KONYA / Türkiye','','42','056','5033',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(117,1,'BEYŞEHİR - KÖPRÜBAŞI','KONYA / Türkiye','','42','057','5034',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(118,1,'BEYŞEHİR - KURUCUOVA','KONYA / Türkiye','','42','058','5035',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(119,1,'BEYŞEHİR - MİNİK KUŞLAR','KONYA / Türkiye','','42','059','5036',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(120,1,'BEYŞEHİR - SADIKHACI','KONYA / Türkiye','','42','060','5037',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(121,1,'BEYŞEHİR - SADIKHACI ANAOKULU','KONYA / Türkiye','','42','061','5038',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(122,1,'BEYŞEHİR - SELKİ GÜLDERENLER','KONYA / Türkiye','','42','062','5039',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(123,1,'BEYŞEHİR - ÜSTÜNLER','KONYA / Türkiye','','42','063','5042',NULL,NULL,NULL,NULL,'2026-07-08 14:34:24',NULL),(124,1,'BEYŞEHİR - UYAROĞLU','KONYA / Türkiye','','42','064','5040',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(125,1,'BEYŞEHİR - UYAROĞLU İLKOKUL MERKEZİ','KONYA / Türkiye','','42','065','5041',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(126,1,'BEYŞEHİR - ÜZÜMLÜ GÜLBAHÇESİ','KONYA / Türkiye','','42','066','5043',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(127,1,'BEYŞEHİR - VALİDE SULTAN KIZ NEHARİ MERKEZİ','KONYA / Türkiye','','42','067','5044',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(128,1,'BEYŞEHİR - YEŞİLDAĞ','KONYA / Türkiye','','42','068','5045',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(129,1,'BEYŞEHİR - YUKARIKAYALAR','KONYA / Türkiye','','42','069','5046',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(130,1,'CİHANBEYLİ - ALTINEKİN AKINCILAR KIZ','KONYA / Türkiye','','42','070','9001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(131,1,'CİHANBEYLİ - CİHANBEYLİ AHMEDİYE','KONYA / Türkiye','','42','071','9002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(132,1,'CİHANBEYLİ - CİHANBEYLİ BEYAZ ZAMBAK','KONYA / Türkiye','','42','072','9003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(133,1,'CİHANBEYLİ - CİHANBEYLİ FERHAN SULTAN KIZ','KONYA / Türkiye','','42','073','9004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(134,1,'CİHANBEYLİ - CİHANBEYLİ GÜLDEREN','KONYA / Türkiye','2','42','074','9005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(135,1,'CİHANBEYLİ - KARABAĞ','KONYA / Türkiye','','42','075','9006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(136,1,'CİHANBEYLİ - KULU KARDELEN','KONYA / Türkiye','','42','076','9007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(137,1,'CİHANBEYLİ - KULU KEMALİYE KIZ EV YURDU','KONYA / Türkiye','','42','077','9008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(138,1,'CİHANBEYLİ - KULU SÜLEYMANİYE','KONYA / Türkiye','','42','078','9009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(139,1,'CİHANBEYLİ - KULU SÜREYYA YILDIZLARI ANAOKULU','KONYA / Türkiye','','42','079','9010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(140,1,'CİHANBEYLİ - SÜLÜKLÜ İLKOKUL MERKEZİ','KONYA / Türkiye','','42','080','9011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(141,1,'ÇUMRA - ALİBEYHÜYÜĞÜ','KONYA / Türkiye','','42','081','6001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(142,1,'ÇUMRA - ALİBEYHÜYÜĞÜ KIR ÇİÇEKLERİ ANAOKULU','KONYA / Türkiye','','42','082','6002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(143,1,'ÇUMRA - AVŞAR','KONYA / Türkiye','','42','083','6003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(144,1,'ÇUMRA - AVŞAR KIZ','KONYA / Türkiye','','42','084','6004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(145,1,'ÇUMRA - BAĞLIK KIZ','KONYA / Türkiye','','42','085','6005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(146,1,'ÇUMRA - BALCILAR','KONYA / Türkiye','','42','086','6006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(147,1,'ÇUMRA - BALCILAR ANAOKULU','KONYA / Türkiye','','42','087','6007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(148,1,'ÇUMRA - BALCILAR KIZ','KONYA / Türkiye','','42','088','6008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(149,1,'ÇUMRA - BARAJ KIZ','KONYA / Türkiye','','42','089','6009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(150,1,'ÇUMRA - ÇUMRA BAHÇELİEVLER','KONYA / Türkiye','','42','090','6010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(151,1,'ÇUMRA - ÇUMRA BAHÇELİEVLER ANAOKULU','KONYA / Türkiye','','42','091','6011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(152,1,'ÇUMRA - ÇUMRA LALE','KONYA / Türkiye','','42','092','6012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(153,1,'ÇUMRA - ÇUMRA NAR ÇİÇEKLERİ','KONYA / Türkiye','','42','093','6013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(154,1,'ÇUMRA - ÇUMRA VALİDE SULTAN NEHARİ','KONYA / Türkiye','','42','094','6014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(155,1,'ÇUMRA - ÇUMRA VALİDE SULTAN PROJE','KONYA / Türkiye','','42','095','6015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(156,1,'ÇUMRA - GÜNEYSINIR','KONYA / Türkiye','','42','096','6016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(157,1,'ÇUMRA - GÜNEYSINIR İNCİ TANELERİ','KONYA / Türkiye','','42','097','6017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(158,1,'ÇUMRA - GÜNEYSINIR KIZ','KONYA / Türkiye','','42','098','6018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(159,1,'ÇUMRA - HADİM BADEMLİ','KONYA / Türkiye','','42','099','6019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(160,1,'ÇUMRA - HADİM MERKEZ','KONYA / Türkiye','','42','100','6020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(161,1,'ÇUMRA - HADİM MERKEZ KIZ','KONYA / Türkiye','','42','101','6021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(162,1,'ÇUMRA - HAMZALAR','KONYA / Türkiye','','42','102','6022',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(163,1,'ÇUMRA - İÇERİ ÇUMRA KIZ','KONYA / Türkiye','','42','103','6023',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(164,1,'ÇUMRA - İÇERİ ÇUMRA YAĞMUR TANELERİ','KONYA / Türkiye','','42','104','6024',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(165,1,'ÇUMRA - İRFANİYE','KONYA / Türkiye','','42','105','6025',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(166,1,'ÇUMRA - KARKIN KAR TANELERİ','KONYA / Türkiye','','42','106','6026',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(167,1,'ÇUMRA - KARKIN KIZ','KONYA / Türkiye','','42','107','6027',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(168,1,'ÇUMRA - KORUALAN','KONYA / Türkiye','','42','108','6028',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(169,1,'ÇUMRA - MEHMET KILINÇ ANAOKULU','KONYA / Türkiye','','42','109','6029',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(170,1,'ÇUMRA - MİNİK YILDIZLAR','KONYA / Türkiye','','42','110','6030',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(171,1,'ÇUMRA - OKÇU ARİFİYE','KONYA / Türkiye','','42','111','6031',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(172,1,'ÇUMRA - OKÇU ARİFİYE ANAOKULU','KONYA / Türkiye','','42','112','6032',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(173,1,'ÇUMRA - TAŞAĞIL KIZ','KONYA / Türkiye','','42','113','6033',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(174,1,'ÇUMRA - TAŞAĞIL PARLAYAN YILDIZLAR','KONYA / Türkiye','','42','114','6034',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(175,1,'ÇUMRA - TAŞKENT KIZ','KONYA / Türkiye','','42','115','6035',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(176,1,'ÇUMRA - YAĞCI KIZ','KONYA / Türkiye','','42','116','6036',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(177,1,'DİĞER - AKDENİZ TOROS','KONYA / Türkiye','','42','117','90001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(178,1,'DİĞER - EMAR PETROL','KONYA / Türkiye','','42','118','90002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(179,1,'DİĞER - HİSAR AMBALAJ VE HAC UMRE','KONYA / Türkiye','','42','119','90003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(180,1,'DİĞER - HİSAR COLLECTİON','KONYA / Türkiye','','42','120','90004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(181,1,'DİĞER - KONYA ÇAMLICA KİTAP','KONYA / Türkiye','','42','121','90005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(182,1,'DİĞER - KONYA HİSAR SİGORTA','KONYA / Türkiye','','42','122','90006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(183,1,'DİĞER - KONYA HİSAR TURİZM','KONYA / Türkiye','','42','123','90007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(184,1,'DİĞER - KONYA MERKEZ ADAK','KONYA / Türkiye','','42','124','90008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(185,1,'DİĞER - OCAKBAŞI','KONYA / Türkiye','','42','125','90009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(186,1,'EREĞLİ - BARBAROS PROJE','KONYA / Türkiye','','42','126','7001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(187,1,'EREĞLİ - BAŞAK ANAOKULU','KONYA / Türkiye','','42','127','7003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(188,1,'EREĞLİ - BASTIRIK','KONYA / Türkiye','','42','128','7002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(189,1,'EREĞLİ - BEYKÖY ANAOKULU','KONYA / Türkiye','','42','129','7004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(190,1,'EREĞLİ - ÇİFTEHAN','NİĞDE / Türkiye','','42','130','7005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(191,1,'EREĞLİ - ELAGÖZLÜ','KONYA / Türkiye','','42','131','7006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(192,1,'EREĞLİ - ELAGÖZLÜ ANAOKULU','KONYA / Türkiye','','42','132','7007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(193,1,'EREĞLİ - ELAGÖZLÜ İLKOKUL MERKEZİ','KONYA / Türkiye','','42','133','7008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(194,1,'EREĞLİ - EMİRGAZİ','KONYA / Türkiye','','42','134','7009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(195,1,'EREĞLİ - EMİRGAZİ KIZ','KONYA / Türkiye','','42','135','7010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(196,1,'EREĞLİ - EREĞLİ ÇAMLICA ANAOKULU','KONYA / Türkiye','','42','136','7011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(197,1,'EREĞLİ - EREĞLİ FERHATLAR','KONYA / Türkiye','','42','137','7012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(198,1,'EREĞLİ - EREĞLİ FERHATLAR NEHARİ MERKEZİ','KONYA / Türkiye','','42','138','7013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(199,1,'EREĞLİ - EREĞLİ GÜLBAHÇE ANAOKULU','KONYA / Türkiye','','42','139','7014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(200,1,'EREĞLİ - EREĞLİ GÜLBAHÇE KIZ','KONYA / Türkiye','','42','140','7015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(201,1,'EREĞLİ - EREĞLİ GÜLBAHÇE KIZ NEHARİ MERKEZİ','KONYA / Türkiye','','42','141','7016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(202,1,'EREĞLİ - EREĞLİ İSABET OKULLARI','KONYA / Türkiye','','42','142','7017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(203,1,'EREĞLİ - EREĞLİ NİSAN YAĞMURLARI','KONYA / Türkiye','','42','143','7018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(204,1,'EREĞLİ - EREĞLİ SÜLEYMANİYE','KONYA / Türkiye','','42','144','7019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(205,1,'EREĞLİ - EREĞLİ SÜLEYMANİYE ANAOKULU','KONYA / Türkiye','','42','145','7020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(206,1,'EREĞLİ - EREĞLİ YILDIZ ANAOKULU','KONYA / Türkiye','','42','146','7021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(207,1,'EREĞLİ - GÖLBOĞAZI','NİĞDE / Türkiye','','42','147','7022',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(208,1,'EREĞLİ - GÜVENÇ','KONYA / Türkiye','','42','148','7023',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(209,1,'EREĞLİ - GÜVENÇ ANAOKULU','KONYA / Türkiye','','42','149','7024',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(210,1,'EREĞLİ - HALKAPINAR','KONYA / Türkiye','','42','150','7025',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(211,1,'EREĞLİ - HALKAPINAR KARDELEN','KONYA / Türkiye','','42','151','7026',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(212,1,'EREĞLİ - KARAPINAR ARİFİYE','KONYA / Türkiye','','42','152','7027',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(213,1,'EREĞLİ - KILAN','NİĞDE / Türkiye','','42','153','7028',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(214,1,'EREĞLİ - KILAN KIZ','NİĞDE / Türkiye','','42','154','7029',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(215,1,'EREĞLİ - KILAN SUBAŞI ANAOKULU','NİĞDE / Türkiye','','42','155','7030',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(216,1,'EREĞLİ - SULTANİYE KIZ','KONYA / Türkiye','','42','156','7031',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(217,1,'EREĞLİ - TAHTAKÖPRÜ KIZ','KONYA / Türkiye','','42','157','7032',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(218,1,'EREĞLİ - TOROSLAR A BLOK PROJE','KONYA / Türkiye','','42','158','7033',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(219,1,'EREĞLİ - TOROSLAR ANAOKULU','KONYA / Türkiye','','42','159','7034',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(220,1,'EREĞLİ - TOROSLAR B BLOK','KONYA / Türkiye','','42','160','7035',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(221,1,'EREĞLİ - TOROSLAR İLKOKUL MERKEZİ','KONYA / Türkiye','','42','161','7036',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(222,1,'EREĞLİ - ULUKIŞLA KERVANSARAY KIZ','NİĞDE / Türkiye','','42','162','7037',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(223,1,'EREĞLİ - ULUKIŞLA RÜZGARGÜLÜ','NİĞDE / Türkiye','','42','163','7038',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(224,1,'EREĞLİ - YAĞMUR DAMLALARI','KONYA / Türkiye','','42','164','7039',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(225,1,'ERMENEK - AKDENİZ İNCİLERİ','MERSİN / Türkiye','','42','165','12001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(226,1,'ERMENEK - AŞAĞIKÖSELERLİ','MERSİN / Türkiye','','42','166','12002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(227,1,'ERMENEK - BAŞYAYLA','KARAMAN / Türkiye','','70','001','12003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(228,1,'ERMENEK - BAŞYAYLA KARDELEN','KARAMAN / Türkiye','','70','002','12004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(229,1,'ERMENEK - DUMLUGÖZE','KARAMAN / Türkiye','b','70','003','12005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(230,1,'ERMENEK - ERMENEK ÇAMLICA','KARAMAN / Türkiye','','70','004','12006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(231,1,'ERMENEK - ERMENEK FATİH PROJE','KARAMAN / Türkiye','','70','005','12007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(232,1,'ERMENEK - ERMENEK NAR ÇİÇEKLERİ','KARAMAN / Türkiye','','70','006','12008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(233,1,'ERMENEK - ERMENEK SEYRAN','KARAMAN / Türkiye','','70','007','12009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(234,1,'ERMENEK - ERMENEK SEYRAN İLKOKUL MERKEZİ','KARAMAN / Türkiye','','70','008','12010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(235,1,'ERMENEK - ERMENEK ÜZÜMLÜ KIZ','KARAMAN / Türkiye','','70','009','12011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(236,1,'ERMENEK - FATİH ANAOKULU','KARAMAN / Türkiye','','70','010','12012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(237,1,'ERMENEK - GÖKSU İNCİLERİ','MERSİN / Türkiye','','42','167','12013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(238,1,'ERMENEK - GÜLLÜK','MERSİN / Türkiye','','42','168','12014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(239,1,'ERMENEK - GÜLLÜK ANAOKULU','MERSİN / Türkiye','','42','169','12015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(240,1,'ERMENEK - GÜNAŞIĞI İLKOKUL MERKEZİ','MERSİN / Türkiye','','42','170','12016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(241,1,'ERMENEK - GÜNEYYURT','KARAMAN / Türkiye','','70','011','12017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(242,1,'ERMENEK - GÜNEYYURT KIZ','KARAMAN / Türkiye','','70','012','12018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(243,1,'ERMENEK - HACITABAK KIZ','MERSİN / Türkiye','','42','171','12019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(244,1,'ERMENEK - KALE ANAOKULU','MERSİN / Türkiye','','42','172','12020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(245,1,'ERMENEK - KALE PROJE','MERSİN / Türkiye','','42','173','12021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(246,1,'ERMENEK - KARDELEN ÇİÇEĞİ','KARAMAN / Türkiye','','70','013','12022',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(247,1,'ERMENEK - KIRÇİÇEĞİ','KARAMAN / Türkiye','','70','014','12023',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(248,1,'ERMENEK - KRAVGA','MERSİN / Türkiye','','42','174','12024',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(249,1,'ERMENEK - MUT GÜLBAHÇE','MERSİN / Türkiye','','42','175','12025',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(250,1,'ERMENEK - MUTLU ÇOCUK','MERSİN / Türkiye','','42','176','12026',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(251,1,'ERMENEK - MUTLU ÇOCUK İLKOKUL MERKEZİ','MERSİN / Türkiye','','42','177','12027',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(252,1,'ERMENEK - PINARBAŞI','MERSİN / Türkiye','','42','178','12028',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(253,1,'ERMENEK - SAKIZ NEHARİ','MERSİN / Türkiye','','42','179','12029',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(254,1,'ERMENEK - SARIVELİLER','KARAMAN / Türkiye','','70','015','12030',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(255,1,'ERMENEK - SARIVELİLER KAR TANELERİ','KARAMAN / Türkiye','','70','016','12031',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(256,1,'ERMENEK - SARIVELİLER KIZ','KARAMAN / Türkiye','','70','017','12032',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(257,1,'ERMENEK - SUSAKLI KIZ','KARAMAN / Türkiye','25590010110132640700484000','70','018','12033',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(258,1,'ERMENEK - TAŞELİ','KARAMAN / Türkiye','25590010110081860700450000','70','019','12034',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(259,1,'ERMENEK - TAŞELİ KIZ','KARAMAN / Türkiye','','70','020','12035',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(260,1,'ERMENEK - TAŞELİ ŞAHİN SONER UYAR ANAOKULU','KARAMAN / Türkiye','','70','021','12036',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(261,1,'ERMENEK - ZÜMRÜT KIZ','MERSİN / Türkiye','','42','180','12037',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(262,1,'ILGIN - BALKI KIZ','KONYA / Türkiye','','42','181','8001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(263,1,'ILGIN - ÇİĞİL KIZ','KONYA / Türkiye','','42','182','8002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(264,1,'ILGIN - DOĞANHİSAR','KONYA / Türkiye','','42','183','8003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(265,1,'ILGIN - GÜNEŞ ANAOKULU','KONYA / Türkiye','','42','184','8004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(266,1,'ILGIN - GÜNEŞ İLKOKUL MERKEZİ','KONYA / Türkiye','','42','185','8005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(267,1,'ILGIN - ILGIN GÜLBAHÇE','KONYA / Türkiye','','42','186','8006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(268,1,'ILGIN - ILGIN GÜLBAHÇE ANAOKULU','KONYA / Türkiye','','42','187','8007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(269,1,'ILGIN - KADINHANI MERKEZ','KONYA / Türkiye','','42','188','8008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(270,1,'ILGIN - KADINHANI VALİDE SULTAN KIZ','KONYA / Türkiye','','42','189','8009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(271,1,'ILGIN - LADİK KIZ','KONYA / Türkiye','','42','190','8010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(272,1,'ILGIN - MİNİK KALPLER','KONYA / Türkiye','','42','191','8011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(273,1,'ILGIN - MİNİKELLER','KONYA / Türkiye','','42','192','8012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(274,1,'ILGIN - MİNİKHANLAR ANAOKULU','KONYA / Türkiye','','42','193','8013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(275,1,'ILGIN - OSMANCIK','KONYA / Türkiye','','42','194','8014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(276,1,'ILGIN - OSMANCIK ANAOKULU','KONYA / Türkiye','','42','195','8015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(277,1,'ILGIN - SARAYÖNÜ FATİH','KONYA / Türkiye','','42','196','8016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(278,1,'ILGIN - TOPRAKKALE','KONYA / Türkiye','','42','197','8017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(279,1,'ILGIN - UFUK ANAOKULU','KONYA / Türkiye','','42','198','8018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(280,1,'ILGIN - UFUK KIZ','KONYA / Türkiye','','42','199','8019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(281,1,'KARAMAN - ADA','KARAMAN / Türkiye','','70','022','11001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(282,1,'KARAMAN - AKÇAALAN KIZ','KARAMAN / Türkiye','','70','023','11002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(283,1,'KARAMAN - AYRANCI','KARAMAN / Türkiye','','70','024','11003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(284,1,'KARAMAN - AYRANCI ANAOKULU','KARAMAN / Türkiye','','70','025','11004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(285,1,'KARAMAN - BAYIR','KARAMAN / Türkiye','','70','026','11005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(286,1,'KARAMAN - DEREKÖY','KARAMAN / Türkiye','','70','027','11006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(287,1,'KARAMAN - HİSAR','KARAMAN / Türkiye','','70','028','11007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(288,1,'KARAMAN - HİZMET','KARAMAN / Türkiye','','70','029','11008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(289,1,'KARAMAN - HİZMET ANAOKULU','KARAMAN / Türkiye','','70','030','11009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(290,1,'KARAMAN - İMARET ANAOKULU','KARAMAN / Türkiye','','70','031','11010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(291,1,'KARAMAN - İMARET PROJE','KARAMAN / Türkiye','','70','032','11011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(292,1,'KARAMAN - KARAMAN ARİFİYE','KARAMAN / Türkiye','','70','033','11012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(293,1,'KARAMAN - KARAMAN ARİFİYE ANAOKULU','KARAMAN / Türkiye','','70','034','11013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(294,1,'KARAMAN - KARAMAN BEYAZ ZAMBAK','KARAMAN / Türkiye','','70','035','11014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(295,1,'KARAMAN - KARAMAN ÇAMLICA','KARAMAN / Türkiye','','70','036','11015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(296,1,'KARAMAN - KARAMAN ÇAMLICA ANAOKULU','KARAMAN / Türkiye','','70','037','11016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(297,1,'KARAMAN - KARAMAN ÇAMLICA KIZ','KARAMAN / Türkiye','','70','038','11017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(298,1,'KARAMAN - KARAMAN ESENTEPE ANAOKULU','KARAMAN / Türkiye','','70','039','11018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(299,1,'KARAMAN - KARAMAN FATİH KIZ','KARAMAN / Türkiye','','70','040','11019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(300,1,'KARAMAN - KARAMAN FERHATLAR','KARAMAN / Türkiye','','70','041','11020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(301,1,'KARAMAN - KARAMAN FERHATLAR ANAOKULU','KARAMAN / Türkiye','','70','042','11021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(302,1,'KARAMAN - KARAMAN GÜLBAHÇESİ','KARAMAN / Türkiye','','70','043','11022',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(303,1,'KARAMAN - KARAMAN GÜLVEREN','KARAMAN / Türkiye','','70','044','11023',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(304,1,'KARAMAN - KARAMAN GÜLVEREN İLKOKUL MERKEZİ','KARAMAN / Türkiye','','70','045','11024',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(305,1,'KARAMAN - KARAMAN HAMİDİYE KIZ','KARAMAN / Türkiye','','70','046','11025',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(306,1,'KARAMAN - KARAMAN HAMİDİYE NEHARİ MERKEZİ','KARAMAN / Türkiye','','70','047','11026',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(307,1,'KARAMAN - KARAMAN HİSAR TURİZM','KARAMAN / Türkiye','','70','048','11027',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(308,1,'KARAMAN - KARAMAN İSABET OKULLARI','KARAMAN / Türkiye','','70','049','11028',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(309,1,'KARAMAN - KARAMAN İSABET PROJE','KARAMAN / Türkiye','','70','050','11029',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(310,1,'KARAMAN - KARAMAN KÖŞK','KARAMAN / Türkiye','','70','051','11030',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(311,1,'KARAMAN - KARAMAN NAR ÇİÇEKLERİ','KARAMAN / Türkiye','','70','052','11031',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(312,1,'KARAMAN - KARAMAN SUBAŞI','KARAMAN / Türkiye','','70','053','11032',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(313,1,'KARAMAN - KARAMAN SÜLEYMANİYE','KARAMAN / Türkiye','','70','054','11033',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(314,1,'KARAMAN - KARAMAN VALİDE SULTAN KIZ','KARAMAN / Türkiye','','70','055','11034',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(315,1,'KARAMAN - KAZIMKARABEKİR HİLAL','KARAMAN / Türkiye','','70','056','11035',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(316,1,'KARAMAN - KAZIMKARABEKİR HİLAL ANAOKULU','KARAMAN / Türkiye','','70','057','11036',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(317,1,'KARAMAN - KILBASAN','KARAMAN / Türkiye','','70','058','11037',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(318,1,'KARAMAN - KIZILCA','KARAMAN / Türkiye','','70','059','11038',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(319,1,'KARAMAN - KIZILCA ANAOKULU','KARAMAN / Türkiye','','70','060','11039',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(320,1,'KARAMAN - LARENDE ANAOKULU','KARAMAN / Türkiye','','70','061','11040',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(321,1,'KARAMAN - LARENDE KIZ','KARAMAN / Türkiye','','70','062','11041',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(322,1,'KARAMAN - MASARA KIZ','KARAMAN / Türkiye','','70','063','11042',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(323,1,'KARAMAN - MEKANI LEZZET','KARAMAN / Türkiye','','70','064','11043',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(324,1,'KARAMAN - ŞELALE','KARAMAN / Türkiye','','70','065','11045',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(325,1,'KARAMAN - ŞELALE SANAYİ MESCİDİ','KARAMAN / Türkiye','','70','066','11046',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(326,1,'KARAMAN - SUBAŞI NEHARİ','KARAMAN / Türkiye','','70','067','11044',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(327,1,'KARAMAN - VADİ BEYAZ ZAMBAK','KARAMAN / Türkiye','','70','068','11047',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(328,1,'KARAMAN - VADİ NEHARİ','KARAMAN / Türkiye','','70','069','11048',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(329,1,'KARAMAN - YENİ ESER','KARAMAN / Türkiye','','70','070','11049',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(330,1,'KARAMAN - YENİ ESER ANAOKULU','KARAMAN / Türkiye','','70','071','11050',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(331,1,'KARAMAN - YOLLARBAŞI','KARAMAN / Türkiye','','70','072','11051',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(332,1,'KARATAY - AZİZİYE ENDERUN ORTAOKUL','KONYA / Türkiye','','42','200','1001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(333,1,'KARATAY - BÜYÜKSOYLU','KONYA / Türkiye','','42','201','1002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(334,1,'KARATAY - ÇAMLICA ÇOCUK ANAOKULU','KONYA / Türkiye','','42','202','1003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(335,1,'KARATAY - ERENLER','KONYA / Türkiye','','42','203','1004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(336,1,'KARATAY - ERENLER KIZ NEHARİ','KONYA / Türkiye','','42','204','1005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(337,1,'KARATAY - FERAH ÇOCUKEVİ','KONYA / Türkiye','','42','205','1006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(338,1,'KARATAY - FERAH İLKOKUL MERKEZİ','KONYA / Türkiye','','42','206','1007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(339,1,'KARATAY - FERHATLAR ÇOCUKEVİ','KONYA / Türkiye','','42','207','1008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(340,1,'KARATAY - FERHATLAR KIZ NEHARİ','KONYA / Türkiye','','42','208','1009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(341,1,'KARATAY - GÜLBAHAR','KONYA / Türkiye','','42','209','1010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(342,1,'KARATAY - İSMİL','KONYA / Türkiye','','42','210','1011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(343,1,'KARATAY - İSMİL ANAOKULU','KONYA / Türkiye','','42','211','1012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(344,1,'KARATAY - KARAASLAN ANAOKULU','KONYA / Türkiye','','42','212','1013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(345,1,'KARATAY - KARAASLAN İLKOKUL MERKEZİ','KONYA / Türkiye','','42','213','1014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(346,1,'KARATAY - KARAASLAN KIZ','KONYA / Türkiye','','42','214','1015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(347,1,'KARATAY - KARATAY FERAH PROJE','KONYA / Türkiye','','42','215','1016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(348,1,'KARATAY - KARATAY FERHATLAR','KONYA / Türkiye','','42','216','1017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(349,1,'KARATAY - KARATAY GÜLVEREN','KONYA / Türkiye','','42','217','1018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(350,1,'KARATAY - MİNİK ERENLER','KONYA / Türkiye','','42','218','1019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(351,1,'KARATAY - SARAÇOĞLU SADABAT','KONYA / Türkiye','','42','219','1020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(352,1,'KARATAY - ŞÜKRAN ANAOKULU','KONYA / Türkiye','','42','220','1021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(353,1,'KARATAY - ŞÜKRAN KIZ','KONYA / Türkiye','','42','221','1022',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(354,1,'KARATAY - TOPRAKLIK A BLOK','KONYA / Türkiye','','42','222','1023',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(355,1,'KARATAY - TOPRAKLIK B BLOK','KONYA / Türkiye','','42','223','1024',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(356,1,'KARATAY - TOPRAKLIK NEHARİ MERKEZİ','KONYA / Türkiye','','42','224','1025',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(357,1,'KARATAY - ULUIRMAK NEHARİ MERKEZİ','KONYA / Türkiye','','42','225','1026',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(358,1,'KARATAY - YAKUT ÇOCUK','KONYA / Türkiye','','42','226','1027',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(359,1,'KARATAY - YAMANLAR KIZ','KONYA / Türkiye','','42','227','1028',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(360,1,'KARATAY - ZERAFET HANIMELİ','KONYA / Türkiye','','42','228','1029',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(361,1,'MERAM - AHMET ŞERİFE BAYBAL PROJE','KONYA / Türkiye','','42','229','2001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(362,1,'MERAM - AKÖREN ARİFİYE','KONYA / Türkiye','','42','230','2002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(363,1,'MERAM - AKÖREN FERHAN SULTAN ANAOKULU','KONYA / Türkiye','','42','231','2003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(364,1,'MERAM - AKÖREN FERHAN SULTAN KIZ','KONYA / Türkiye','','42','232','2004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(365,1,'MERAM - ALAKOVA GÜLBAHÇESİ','KONYA / Türkiye','','42','233','2005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(366,1,'MERAM - ARİF AHMET DENİZOLGUN','KONYA / Türkiye','','42','234','2006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(367,1,'MERAM - BEYAZ YAKA','KONYA / Türkiye','','42','235','2007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(368,1,'MERAM - ÇAMLIBAHÇE ANAOKULU','KONYA / Türkiye','','42','236','2008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(369,1,'MERAM - DENİZ İNCİLERİ','KONYA / Türkiye','','42','237','2009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(370,1,'MERAM - DERBENT ERKEK NEHARİ MERKEZİ','KONYA / Türkiye','','42','238','2010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(371,1,'MERAM - DERBENT KIZ NEHARİ MERKEZİ','KONYA / Türkiye','','42','239','2011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(372,1,'MERAM - GÜL YAPRAKLARI','KONYA / Türkiye','','42','240','2012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(373,1,'MERAM - HASBAHÇE','KONYA / Türkiye','','42','241','2013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(374,1,'MERAM - HASBAHÇE AKADEMİK','KONYA / Türkiye','','42','242','2014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(375,1,'MERAM - KAŞINHANI KIZ','KONYA / Türkiye','','42','243','2015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(376,1,'MERAM - KİRAZLI İLKOKUL MERKEZİ','KONYA / Türkiye','','42','244','2016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(377,1,'MERAM - KÖŞK','KONYA / Türkiye','','42','245','2017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(378,1,'MERAM - MERAM AHMEDİYE','KONYA / Türkiye','','42','246','2018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(379,1,'MERAM - MERAM C BLOK','KONYA / Türkiye','','42','247','2019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(380,1,'MERAM - MERAM FERHAN SULTAN KIZ','KONYA / Türkiye','','42','248','2020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(381,1,'MERAM - MERAM KEMALİYE KIZ','KONYA / Türkiye','','42','249','2021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(382,1,'MERAM - MERAM KİRAZLI KIZ','KONYA / Türkiye','','42','250','2022',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(383,1,'MERAM - MERAM LALELERİ','KONYA / Türkiye','','42','251','2023',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(384,1,'MERAM - MERAM NAR ÇİÇEKLERİ','KONYA / Türkiye','','42','252','2024',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(385,1,'MERAM - MERAM RÜZGARGÜLÜ ANAOKULU','KONYA / Türkiye','','42','253','2025',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(386,1,'MERAM - MERAM VALİDE SULTAN ANAOKULU','KONYA / Türkiye','','42','254','2026',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(387,1,'MERAM - MERAM VALİDE SULTAN KIZ','KONYA / Türkiye','','42','255','2027',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(388,1,'MERAM - SEYYİT KAMİL BEY','KONYA / Türkiye','','42','256','2028',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(389,1,'MERAM - SÜLEYMANŞAH','KONYA / Türkiye','','42','257','2029',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(390,1,'MERAM - TEPEKENT','KONYA / Türkiye','','42','258','2030',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(391,1,'MERAM - TEPEKENT ANAOKULU','KONYA / Türkiye','','42','259','2031',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(392,1,'MERAM - TEPEKENT KIZ','KONYA / Türkiye','','42','260','2032',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(393,1,'MERAM - YAYLAPINAR ANAOKULU','KONYA / Türkiye','','42','261','2033',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(394,1,'NİĞDE - ALTUNHİSAR','NİĞDE / Türkiye','','42','262','14001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(395,1,'NİĞDE - ALTUNHİSAR KARDELEN','NİĞDE / Türkiye','','42','263','14002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(396,1,'NİĞDE - ARMAĞAN DİLMECİ','NİĞDE / Türkiye','','42','264','14003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(397,1,'NİĞDE - BAŞPINAR ANAOKULU','NİĞDE / Türkiye','','42','265','14004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(398,1,'NİĞDE - BOR BEYAZ ZAMBAK','NİĞDE / Türkiye','','42','266','14005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(399,1,'NİĞDE - BOR FATİH','NİĞDE / Türkiye','','42','267','14006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(400,1,'NİĞDE - BOR FATİH NEHARİ','NİĞDE / Türkiye','','42','268','14007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(401,1,'NİĞDE - BOR İRFANİYE KIZ','NİĞDE / Türkiye','','42','269','14008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(402,1,'NİĞDE - BURÇ','NİĞDE / Türkiye','','42','270','14009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(403,1,'NİĞDE - ÇAMARDI GÜLBAHÇE','NİĞDE / Türkiye','','42','271','14011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(404,1,'NİĞDE - ÇAMARDI GÜLBAHÇE ANAOKULU','NİĞDE / Türkiye','','42','272','14012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(405,1,'NİĞDE - ÇAMARDI GÜLBAHÇE KIZ','NİĞDE / Türkiye','','42','273','14013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(406,1,'NİĞDE - ÇAMARDI GÜLBAHÇE KIZ ANAOKULU','NİĞDE / Türkiye','','42','274','14014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(407,1,'NİĞDE - CELALLER','NİĞDE / Türkiye','','42','275','14010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(408,1,'NİĞDE - ÇİFTLİK AHRA KIZ','NİĞDE / Türkiye','','42','276','14015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(409,1,'NİĞDE - ÇİFTLİK ARİFİYE','NİĞDE / Türkiye','','42','277','14016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(410,1,'NİĞDE - ÇİFTLİK GÜL ÇOCUK','NİĞDE / Türkiye','','42','278','14017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(411,1,'NİĞDE - ÇUKURKUYU','NİĞDE / Türkiye','','42','279','14018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(412,1,'NİĞDE - GÜLTEPE KIZ','NİĞDE / Türkiye','','42','280','14019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(413,1,'NİĞDE - HACI ABDULLAH','NİĞDE / Türkiye','','42','281','14020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(414,1,'NİĞDE - KEMERHİSAR','NİĞDE / Türkiye','','42','282','14021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(415,1,'NİĞDE - KOYUNLU KIZ','NİĞDE / Türkiye','','42','283','14022',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(416,1,'NİĞDE - LEBİBE ARUK KIZ','NİĞDE / Türkiye','','42','284','14023',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(417,1,'NİĞDE - MURATEVLER','NİĞDE / Türkiye','','42','285','14024',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(418,1,'NİĞDE - NİĞDE ARİFİYE A BLOK PROJE','NİĞDE / Türkiye','','42','286','14025',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(419,1,'NİĞDE - NİĞDE ARİFİYE B BLOK','NİĞDE / Türkiye','','42','287','14026',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(420,1,'NİĞDE - NİĞDE ÇAMLICA KİTAP','NİĞDE / Türkiye','','42','288','14027',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(421,1,'NİĞDE - NİĞDE SANAYİ MESCİDİ','NİĞDE / Türkiye','','42','289','14028',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(422,1,'NİĞDE - ÖZEL AKPINAR İLKOKULU','NİĞDE / Türkiye','','42','290','14029',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(423,1,'NİĞDE - PINAR','NİĞDE / Türkiye','','42','291','14030',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(424,1,'NİĞDE - PINAR ANAOKULU','NİĞDE / Türkiye','','42','292','14031',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(425,1,'NİĞDE - REMZİYE GÜVENÇ ANAOKULU','NİĞDE / Türkiye','','42','293','14032',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(426,1,'NİĞDE - SELÇUKLU ÜÇERLER','NİĞDE / Türkiye','','42','294','14033',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(427,1,'NİĞDE - SELÇUKLU ÜÇERLER ANAOKULU','NİĞDE / Türkiye','','42','295','14034',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(428,1,'NİĞDE - YELATAN','NİĞDE / Türkiye','','42','296','14035',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(429,1,'NİĞDE - YELATAN ANAOKULU','NİĞDE / Türkiye','','42','297','14036',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(430,1,'NİĞDE - YENİCE ÜÇERLER','NİĞDE / Türkiye','','42','298','14037',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(431,1,'SELÇUKLU - AKINCILAR ENDERUN ORTAOKUL','KONYA / Türkiye','','42','299','3001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(432,1,'SELÇUKLU - AKINCILAR GÜL ÇOCUK','KONYA / Türkiye','','42','300','3002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(433,1,'SELÇUKLU - AKINCILAR NEHARİ','KONYA / Türkiye','','42','301','3003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(434,1,'SELÇUKLU - AMİNE HATUN KIZ','KONYA / Türkiye','','42','302','3004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(435,1,'SELÇUKLU - AYDINLIKEVLER','KONYA / Türkiye','','42','303','3005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(436,1,'SELÇUKLU - AYDINLIKEVLER ANAOKULU','KONYA / Türkiye','','42','304','3006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(437,1,'SELÇUKLU - BUHARA PROJE','KONYA / Türkiye','','42','305','3007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(438,1,'SELÇUKLU - ÇAMLIK NEHARİ MERKEZİ','KONYA / Türkiye','','42','306','3008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:25',NULL),(439,1,'SELÇUKLU - ÇOKYÜRÜR KIZ','KONYA / Türkiye','','42','307','3009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(440,1,'SELÇUKLU - DELİBAY KIZ','KONYA / Türkiye','','42','308','3010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(441,1,'SELÇUKLU - DÜRİYE SULTAN','KONYA / Türkiye','','42','309','3011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(442,1,'SELÇUKLU - EKER ANAOKULU','KONYA / Türkiye','','42','310','3012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(443,1,'SELÇUKLU - EKER KIZ','KONYA / Türkiye','','42','311','3013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(444,1,'SELÇUKLU - ESENKAYA PROJE','KONYA / Türkiye','','42','312','3014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(445,1,'SELÇUKLU - ESENTEPE KIZ','KONYA / Türkiye','','42','313','3015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(446,1,'SELÇUKLU - GÜLHAN ANAOKULU','KONYA / Türkiye','','42','314','3016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(447,1,'SELÇUKLU - HAMİDİYE ANAOKULU','KONYA / Türkiye','','42','315','3017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(448,1,'SELÇUKLU - İNŞAAT OFİSİ','KONYA / Türkiye','','42','316','3018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(449,1,'SELÇUKLU - KARDELEN','KONYA / Türkiye','','42','317','3019',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(450,1,'SELÇUKLU - KARDELEN ANAOKULU','KONYA / Türkiye','','42','318','3020',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(451,1,'SELÇUKLU - KILIÇARSLAN ANAOKULU','KONYA / Türkiye','','42','319','3021',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(452,1,'SELÇUKLU - KONYA İNCİLERİ','KONYA / Türkiye','','42','320','3022',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(453,1,'SELÇUKLU - KONYA İSABET OKULLARI','KONYA / Türkiye','','42','321','3023',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(454,1,'SELÇUKLU - KÜLLİYE GÜL','KONYA / Türkiye','','42','322','3024',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(455,1,'SELÇUKLU - KÜLLİYE GÜZİDE ENDERUN','KONYA / Türkiye','','42','323','3025',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(456,1,'SELÇUKLU - KÜLLİYE KARANFİL','KONYA / Türkiye','','42','324','3026',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(457,1,'SELÇUKLU - KÜLLİYE LALE ENDERUN','KONYA / Türkiye','','42','325','3027',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(458,1,'SELÇUKLU - KÜLLİYE MENEKŞE','KONYA / Türkiye','','42','326','3028',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(459,1,'SELÇUKLU - KÜLLİYE NERGİS','KONYA / Türkiye','','42','327','3029',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(460,1,'SELÇUKLU - KÜLLİYE SAFRAN','KONYA / Türkiye','','42','328','3030',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(461,1,'SELÇUKLU - KÜLLİYE SOSYAL TESİS','KONYA / Türkiye','','42','329','3031',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(462,1,'SELÇUKLU - KÜLLİYE SÜMBÜL PROJE','KONYA / Türkiye','','42','330','3032',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(463,1,'SELÇUKLU - KÜLLİYE ZAMBAK','KONYA / Türkiye','','42','331','3033',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(464,1,'SELÇUKLU - MİNİK KARDELEN','KONYA / Türkiye','','42','332','3034',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(465,1,'SELÇUKLU - SANCAK HAFİZE SULTAN KIZ','KONYA / Türkiye','','42','333','3035',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(466,1,'SELÇUKLU - SANCAK İLKOKUL MERKEZİ','KONYA / Türkiye','','42','334','3036',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(467,1,'SELÇUKLU - SARAY ÇİÇEKLERİ','KONYA / Türkiye','','42','335','3037',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(468,1,'SELÇUKLU - SELÇUKLU ARİFİYE','KONYA / Türkiye','','42','336','3038',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(469,1,'SELÇUKLU - SELÇUKLU ARİFİYE NEHARİ','KONYA / Türkiye','','42','337','3039',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(470,1,'SELÇUKLU - SELÇUKLU BEDİA SULTAN ANAOKULU','KONYA / Türkiye','','42','338','3040',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(471,1,'SELÇUKLU - SELÇUKLU BEDİA SULTAN KIZ','KONYA / Türkiye','','42','339','3041',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(472,1,'SELÇUKLU - SELÇUKLU BEYAZ ZAMBAK','KONYA / Türkiye','','42','340','3042',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(473,1,'SELÇUKLU - SELÇUKLU GÜLBAHÇESİ','KONYA / Türkiye','','42','341','3043',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(474,1,'SELÇUKLU - SELÇUKLU GÜLDEREN','KONYA / Türkiye','','42','342','3044',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(475,1,'SELÇUKLU - SELÇUKLU GÜLVEREN','KONYA / Türkiye','','42','343','3045',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(476,1,'SELÇUKLU - SELÇUKLU HAMİDİYE','KONYA / Türkiye','','42','344','3046',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(477,1,'SELÇUKLU - SELÇUKLU SÜLEYMANİYE','KONYA / Türkiye','','42','345','3047',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(478,1,'SELÇUKLU - SELÇUKLU VALİDE SULTAN KIZ','KONYA / Türkiye','','42','346','3048',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(479,1,'SELÇUKLU - SIZMA','KONYA / Türkiye','','42','347','3049',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(480,1,'SELÇUKLU - SIZMA GÜLLERİ','KONYA / Türkiye','','42','348','3050',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(481,1,'SELÇUKLU - SOĞANLI ADAK MERKEZİ','KONYA / Türkiye','','42','349','3051',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(482,1,'SELÇUKLU - SUHULET KIZ NEHARİ','KONYA / Türkiye','','42','350','3052',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(483,1,'SELÇUKLU - TOPATAN KIZ','KONYA / Türkiye','','42','351','3053',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(484,1,'SELÇUKLU - YILDIZ ANAOKULU','KONYA / Türkiye','','42','352','3054',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(485,1,'SELÇUKLU - YILDIZ ÇOCUKEVİ','KONYA / Türkiye','','42','353','3055',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(486,1,'SEYDİŞEHİR - AKKİSE','KONYA / Türkiye','','42','354','10001',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(487,1,'SEYDİŞEHİR - AKKİSE ANAOKULU','KONYA / Türkiye','','42','355','10002',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(488,1,'SEYDİŞEHİR - BAĞARASI ANAOKULU','KONYA / Türkiye','','42','356','10003',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(489,1,'SEYDİŞEHİR - BAĞARASI İLKOKUL MERKEZİ','KONYA / Türkiye','','42','357','10004',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(490,1,'SEYDİŞEHİR - BAĞARASI KIZ','KONYA / Türkiye','','42','358','10005',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(491,1,'SEYDİŞEHİR - BAHAR ÇİÇEKLERİ','KONYA / Türkiye','','42','359','10006',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(492,1,'SEYDİŞEHİR - BOZKIR BAHÇELİEVLER ANAOKULU','KONYA / Türkiye','','42','360','10007',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(493,1,'SEYDİŞEHİR - BOZKIR BAHÇELİEVLER KIZ','KONYA / Türkiye','','42','361','10008',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(494,1,'SEYDİŞEHİR - DÖRTYOL','KONYA / Türkiye','','42','362','10009',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(495,1,'SEYDİŞEHİR - ENES','KONYA / Türkiye','','42','363','10010',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(496,1,'SEYDİŞEHİR - ERDEMLER','KONYA / Türkiye','','42','364','10011',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(497,1,'SEYDİŞEHİR - HACILAR KIZ','KONYA / Türkiye','','42','365','10012',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(498,1,'SEYDİŞEHİR - HİSARLIK','KONYA / Türkiye','','42','366','10013',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(499,1,'SEYDİŞEHİR - HİSARLIK ANAOKULU','KONYA / Türkiye','','42','367','10014',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(500,1,'SEYDİŞEHİR - MADENLİ KIZ','KONYA / Türkiye','','42','368','10015',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(501,1,'SEYDİŞEHİR - SEYİT HARUN','KONYA / Türkiye','','42','369','10016',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(502,1,'SEYDİŞEHİR - ÜÇPINAR','KONYA / Türkiye','','42','370','10018',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL),(503,1,'SEYDİŞEHİR - ULUKAPI PROJE','KONYA / Türkiye','','42','371','10017',NULL,NULL,NULL,NULL,'2026-07-08 14:34:26',NULL);
/*!40000 ALTER TABLE `institutions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internal_installation_reports`
--

DROP TABLE IF EXISTS `internal_installation_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internal_installation_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `energy_provider` varchar(255) DEFAULT NULL,
  `sebeke_tipi` varchar(20) DEFAULT NULL,
  `proje_var_mi` tinyint(1) DEFAULT 0,
  `sema_var_mi` tinyint(1) DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT NULL,
  `grounding_type` varchar(50) DEFAULT NULL,
  `building_type` varchar(50) DEFAULT NULL,
  `usage_purpose` varchar(255) DEFAULT NULL,
  `prev_control_date` date DEFAULT NULL,
  `weather_condition` varchar(255) DEFAULT NULL,
  `ground_moisture` varchar(255) DEFAULT NULL,
  `phase_count_type` varchar(50) DEFAULT NULL,
  `conductor_type` varchar(50) DEFAULT NULL,
  `grounding_resistance` varchar(50) DEFAULT NULL,
  `additional_electrode_details` text DEFAULT NULL,
  `system_grounding_conductor` varchar(100) DEFAULT NULL,
  `main_equipotential_conductor` varchar(100) DEFAULT NULL,
  `nominal_voltage_kV` varchar(50) DEFAULT NULL,
  `nominal_frequency_Hz` varchar(50) DEFAULT NULL,
  `fault_current_kA` varchar(50) DEFAULT NULL,
  `external_loop_impedance` varchar(50) DEFAULT NULL,
  `main_rcd_rating` varchar(50) DEFAULT NULL,
  `main_breaker_type` varchar(100) DEFAULT NULL,
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
  `result` varchar(50) DEFAULT NULL,
  `result_notes_selection` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kurum_id` (`kurum_id`),
  KEY `thermal_camera_id` (`thermal_camera_id`),
  KEY `device1_id` (`device1_id`),
  KEY `device2_id` (`device2_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `internal_installation_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `internal_installation_reports_ibfk_2` FOREIGN KEY (`thermal_camera_id`) REFERENCES `measurement_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `internal_installation_reports_ibfk_3` FOREIGN KEY (`device1_id`) REFERENCES `measurement_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `internal_installation_reports_ibfk_4` FOREIGN KEY (`device2_id`) REFERENCES `measurement_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `internal_installation_reports_ibfk_5` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internal_installation_reports`
--

LOCK TABLES `internal_installation_reports` WRITE;
/*!40000 ALTER TABLE `internal_installation_reports` DISABLE KEYS */;
INSERT INTO `internal_installation_reports` VALUES (1,1,'01-12-it-1783414679','2026-07-07','','','',0,0,'2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','4536','','','','',NULL,'','','','','','','','','','50','','','','','','','',0,0,'',0,NULL,NULL,NULL,1,'','','UYGUNDUR','','2026-07-07 08:57:59'),(2,1,'01-12-it-1783425678','2026-07-07','','','',0,0,'2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','4536','','','','',NULL,'','','','','','','','','','50','','','','','','','',0,0,'',0,NULL,NULL,NULL,1,'','','UYGUNDUR','','2026-07-07 12:01:18'),(3,1,'01-12-it-1783503118','2026-07-07','','','',0,0,'2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','','','','','',NULL,'','','','','','','','','','50','','','','','','','',0,0,'',0,2,1,NULL,1,'','','UYGUNDUR','','2026-07-08 09:31:58'),(4,1,'01-12-it-1783505306','2026-07-07','','','',0,0,'2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','','','','','',NULL,'','','','','','','','','','50','','','','','','','',0,0,'',0,2,1,NULL,1,'','','UYGUNDUR','','2026-07-08 10:08:26');
/*!40000 ALTER TABLE `internal_installation_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isinma_tesisat_reports`
--

DROP TABLE IF EXISTS `isinma_tesisat_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isinma_tesisat_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT 'Periyodik Kontrol',
  `kurum_yoneticisi` varchar(255) DEFAULT NULL,
  `kurum_kapasitesi` int(11) DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'GÜVENLİDİR',
  `authorized_person_id` int(11) DEFAULT NULL,
  `inspection_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inspection_results`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_no` (`report_no`),
  KEY `kurum_id` (`kurum_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `isinma_tesisat_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `isinma_tesisat_reports_ibfk_2` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isinma_tesisat_reports`
--

LOCK TABLES `isinma_tesisat_reports` WRITE;
/*!40000 ALTER TABLE `isinma_tesisat_reports` DISABLE KEYS */;
INSERT INTO `isinma_tesisat_reports` VALUES (2,1,'01-12-it-1783413360','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','',NULL,'','','GÜVENLİDİR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\",\"q15\":\"UYGUN\"}','2026-07-07 08:36:00');
/*!40000 ALTER TABLE `isinma_tesisat_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jenarator_reports`
--

DROP TABLE IF EXISTS `jenarator_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jenarator_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT 'Periyodik Kontrol',
  `brand_model` varchar(255) DEFAULT NULL,
  `production_year` varchar(50) DEFAULT NULL,
  `capacity` varchar(50) DEFAULT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `inspection_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inspection_results`)),
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result_text` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'UYGUNDUR',
  `authorized_person_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_no` (`report_no`),
  KEY `kurum_id` (`kurum_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `jenarator_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jenarator_reports_ibfk_2` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jenarator_reports`
--

LOCK TABLES `jenarator_reports` WRITE;
/*!40000 ALTER TABLE `jenarator_reports` DISABLE KEYS */;
INSERT INTO `jenarator_reports` VALUES (2,1,'01-12-jen-1783414279','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','','','','','{\"q1\":\"Evet\",\"q2\":\"Evet\",\"q3\":\"Evet\",\"q4\":\"Evet\",\"q5\":\"Evet\",\"q6\":\"Evet\",\"q7\":\"Evet\",\"q8\":\"Evet\",\"q9\":\"Evet\",\"q10\":\"Evet\",\"q11\":\"Evet\",\"q12\":\"Evet\"}','Periyodik bakım ve kontrolleri düzenli olarak yapılmalı ve kayıtları muhafaza edilmelidir.','','Yukarıda teknik özellikleri verilen ve kontrol tarihinde durumu belirtilen JENARATÖR &#039;ün kontrolü yapılmış olup bir sonraki kontrol tarihine kadar kullanılması İŞÇİ SAĞLIĞI ve İŞ GÜVENLİĞİ açısından risk taşımamaktadır. İşletmesi UYGUNDUR.','UYGUNDUR',1,'2026-07-07 08:51:19');
/*!40000 ALTER TABLE `jenarator_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kamera_bakim_reports`
--

DROP TABLE IF EXISTS `kamera_bakim_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kamera_bakim_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT 'Periyodik Kontrol',
  `yurt_yoneticisi` varchar(255) DEFAULT NULL,
  `report_text` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'UYGUNDUR',
  `authorized_person_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_no` (`report_no`),
  KEY `kurum_id` (`kurum_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `kamera_bakim_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kamera_bakim_reports_ibfk_2` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kamera_bakim_reports`
--

LOCK TABLES `kamera_bakim_reports` WRITE;
/*!40000 ALTER TABLE `kamera_bakim_reports` DISABLE KEYS */;
INSERT INTO `kamera_bakim_reports` VALUES (2,1,'01-12-kmr-1783414573','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','','Yukarıda adresi verilen kurumun güvenlik sistemleri Wellbox Nvr analog dvr kayıt cihazına 4 Tb kapasiteli harddisk takılı olup bu cihazın kontrolleri yapılmış ve cihaz 3 Ay süreli 7/24 kayıt için aktif durumdadır.','UYGUNDUR',1,'2026-07-07 08:56:13'),(3,1,'01-12-kmr-1783505154','2026-07-08','2026-07-08 09:00:00','2026-07-08 17:00:00','2027-07-06','4536','','Periyodik Kontrol','','Yukarıda adresi verilen kurumun güvenlik sistemleri Wellbox Nvr analog dvr kayıt cihazına 4 Tb kapasiteli harddisk takılı olup bu cihazın kontrolleri yapılmış ve cihaz 3 Ay süreli 7/24 kayıt için aktif durumdadır.','UYGUNDUR',1,'2026-07-08 10:05:54');
/*!40000 ALTER TABLE `kamera_bakim_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lightning_protection_reports`
--

DROP TABLE IF EXISTS `lightning_protection_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lightning_protection_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `energy_provider` varchar(255) DEFAULT NULL,
  `sebeke_tipi` varchar(20) DEFAULT NULL,
  `sebeke_voltage` varchar(50) DEFAULT NULL,
  `has_project` varchar(10) DEFAULT 'Yok',
  `project_details` text DEFAULT NULL,
  `has_risk_analysis` varchar(10) DEFAULT 'Yok',
  `control_reason` varchar(255) DEFAULT NULL,
  `grounding_type` varchar(50) DEFAULT NULL,
  `building_type` varchar(50) DEFAULT NULL,
  `usage_purpose_yks_type` varchar(255) DEFAULT NULL,
  `prev_control_date` date DEFAULT NULL,
  `weather_condition` varchar(255) DEFAULT NULL,
  `ground_moisture` varchar(255) DEFAULT NULL,
  `installation_change` varchar(10) DEFAULT 'Yok',
  `prev_label_exists` varchar(10) DEFAULT 'Yok',
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
  `result` varchar(50) DEFAULT NULL,
  `result_notes_selection` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kurum_id` (`kurum_id`),
  KEY `thermal_camera_id` (`thermal_camera_id`),
  KEY `device1_id` (`device1_id`),
  KEY `device2_id` (`device2_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `lightning_protection_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lightning_protection_reports_ibfk_2` FOREIGN KEY (`thermal_camera_id`) REFERENCES `measurement_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lightning_protection_reports_ibfk_3` FOREIGN KEY (`device1_id`) REFERENCES `measurement_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lightning_protection_reports_ibfk_4` FOREIGN KEY (`device2_id`) REFERENCES `measurement_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lightning_protection_reports_ibfk_5` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lightning_protection_reports`
--

LOCK TABLES `lightning_protection_reports` WRITE;
/*!40000 ALTER TABLE `lightning_protection_reports` DISABLE KEYS */;
INSERT INTO `lightning_protection_reports` VALUES (1,1,'01-12-yk-1783414716','2026-07-07','','2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','4536','','','','Yok','','Yok','','','','',NULL,'','','Yok','Yok','','','','',NULL,NULL,NULL,1,'','','UYGUNDUR','','2026-07-07 08:58:36'),(2,1,'01-12-yk-1783425689','2026-07-07','','2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','4536','','','','Yok','','Yok','','','','',NULL,'','','Yok','Yok','','','','',NULL,NULL,NULL,1,'','','UYGUNDUR','','2026-07-07 12:01:29'),(3,1,'01-12-yk-1783503133','2026-07-07','','2026-07-07 11:01:00','2026-07-07 11:01:00','2027-07-07','','','','','Yok','','Yok','','','','',NULL,'','','Yok','Yok','','','','',2,1,NULL,1,'','','UYGUNDUR','','2026-07-08 09:32:13');
/*!40000 ALTER TABLE `lightning_protection_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lightning_protection_section4`
--

DROP TABLE IF EXISTS `lightning_protection_section4`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lightning_protection_section4` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `question_key` varchar(100) NOT NULL,
  `answer` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `lightning_protection_section4_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `lightning_protection_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lightning_protection_section4`
--

LOCK TABLES `lightning_protection_section4` WRITE;
/*!40000 ALTER TABLE `lightning_protection_section4` DISABLE KEYS */;
INSERT INTO `lightning_protection_section4` VALUES (1,1,'risk_analizi_varmi','Uygun'),(2,1,'kapsama_uygunmu','Uygun'),(3,1,'ese_a1','Uygun'),(4,1,'ese_a2','Uygun'),(5,1,'ese_a3','Uygun'),(6,1,'ese_a4','Uygun'),(7,1,'ese_a5','Uygun'),(8,1,'ese_a6','Uygun'),(9,1,'ese_a7','Uygun'),(10,1,'ese_a8','Uygun'),(11,1,'ese_b1','Uygun'),(12,1,'ese_b2','Uygun'),(13,1,'ese_b3','Uygun'),(14,1,'ese_b4','Uygun'),(15,1,'ese_b5','Uygun'),(16,1,'ese_b6','Uygun'),(17,1,'ese_b7','Uygun'),(18,1,'ese_c1','Uygun'),(19,1,'ese_c2','Uygun'),(20,1,'ese_c3','Uygun'),(21,1,'ese_c4','Uygun'),(22,1,'ese_d1','Uygun'),(23,1,'ese_d2','Uygun'),(24,1,'ese_d3','Uygun'),(25,1,'ese_d4','Uygun'),(26,1,'ese_e1','Uygun'),(27,1,'ese_e2','Uygun'),(28,1,'ese_e3','Uygun'),(29,1,'ese_e4','Uygun'),(30,1,'ese_e5','Uygun'),(31,1,'ese_e6','Uygun'),(32,1,'fa_a1','Uygun'),(33,1,'fa_a2','Uygun'),(34,1,'fa_a3','Uygun'),(35,1,'fa_a4','Uygun'),(36,1,'fa_b1','Uygun'),(37,1,'fa_b2','Uygun'),(38,1,'fa_b3','Uygun'),(39,1,'fa_b4','Uygun'),(40,1,'fa_b5','Uygun'),(41,1,'fa_b6','Uygun'),(42,1,'fa_c1','Uygun'),(43,1,'fa_c2','Uygun'),(44,1,'fa_c3','Uygun'),(45,1,'fa_c4','Uygun'),(46,1,'fa_d1','Uygun'),(47,1,'fa_d2','Uygun'),(48,2,'risk_analizi_varmi','Uygun'),(49,2,'kapsama_uygunmu','Uygun'),(50,2,'ese_a1','Uygun'),(51,2,'ese_a2','Uygun'),(52,2,'ese_a3','Uygun'),(53,2,'ese_a4','Uygun'),(54,2,'ese_a5','Uygun'),(55,2,'ese_a6','Uygun'),(56,2,'ese_a7','Uygun'),(57,2,'ese_a8','Uygun'),(58,2,'ese_b1','Uygun'),(59,2,'ese_b2','Uygun'),(60,2,'ese_b3','Uygun'),(61,2,'ese_b4','Uygun'),(62,2,'ese_b5','Uygun'),(63,2,'ese_b6','Uygun'),(64,2,'ese_b7','Uygun'),(65,2,'ese_c1','Uygun'),(66,2,'ese_c2','Uygun'),(67,2,'ese_c3','Uygun'),(68,2,'ese_c4','Uygun'),(69,2,'ese_d1','Uygun'),(70,2,'ese_d2','Uygun'),(71,2,'ese_d3','Uygun'),(72,2,'ese_d4','Uygun'),(73,2,'ese_e1','Uygun'),(74,2,'ese_e2','Uygun'),(75,2,'ese_e3','Uygun'),(76,2,'ese_e4','Uygun'),(77,2,'ese_e5','Uygun'),(78,2,'ese_e6','Uygun'),(79,2,'fa_a1','Uygun'),(80,2,'fa_a2','Uygun'),(81,2,'fa_a3','Uygun'),(82,2,'fa_a4','Uygun'),(83,2,'fa_b1','Uygun'),(84,2,'fa_b2','Uygun'),(85,2,'fa_b3','Uygun'),(86,2,'fa_b4','Uygun'),(87,2,'fa_b5','Uygun'),(88,2,'fa_b6','Uygun'),(89,2,'fa_c1','Uygun'),(90,2,'fa_c2','Uygun'),(91,2,'fa_c3','Uygun'),(92,2,'fa_c4','Uygun'),(93,2,'fa_d1','Uygun'),(94,2,'fa_d2','Uygun'),(95,3,'risk_analizi_varmi','Uygun'),(96,3,'kapsama_uygunmu','Uygun'),(97,3,'ese_a1','Uygun'),(98,3,'ese_a2','Uygun'),(99,3,'ese_a3','Uygun'),(100,3,'ese_a4','Uygun'),(101,3,'ese_a5','Uygun'),(102,3,'ese_a6','Uygun'),(103,3,'ese_a7','Uygun'),(104,3,'ese_a8','Uygun'),(105,3,'ese_b1','Uygun'),(106,3,'ese_b2','Uygun'),(107,3,'ese_b3','Uygun'),(108,3,'ese_b4','Uygun'),(109,3,'ese_b5','Uygun'),(110,3,'ese_b6','Uygun'),(111,3,'ese_b7','Uygun'),(112,3,'ese_c1','Uygun'),(113,3,'ese_c2','Uygun'),(114,3,'ese_c3','Uygun'),(115,3,'ese_c4','Uygun'),(116,3,'ese_d1','Uygun'),(117,3,'ese_d2','Uygun'),(118,3,'ese_d3','Uygun'),(119,3,'ese_d4','Uygun'),(120,3,'ese_e1','Uygun'),(121,3,'ese_e2','Uygun'),(122,3,'ese_e3','Uygun'),(123,3,'ese_e4','Uygun'),(124,3,'ese_e5','Uygun'),(125,3,'ese_e6','Uygun'),(126,3,'fa_a1','Uygun'),(127,3,'fa_a2','Uygun'),(128,3,'fa_a3','Uygun'),(129,3,'fa_a4','Uygun'),(130,3,'fa_b1','Uygun'),(131,3,'fa_b2','Uygun'),(132,3,'fa_b3','Uygun'),(133,3,'fa_b4','Uygun'),(134,3,'fa_b5','Uygun'),(135,3,'fa_b6','Uygun'),(136,3,'fa_c1','Uygun'),(137,3,'fa_c2','Uygun'),(138,3,'fa_c3','Uygun'),(139,3,'fa_c4','Uygun'),(140,3,'fa_d1','Uygun'),(141,3,'fa_d2','Uygun');
/*!40000 ALTER TABLE `lightning_protection_section4` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `measurement_devices`
--

DROP TABLE IF EXISTS `measurement_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `measurement_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `cal_date` date DEFAULT NULL,
  `validity_date` date DEFAULT NULL,
  `cal_no` varchar(100) DEFAULT NULL,
  `is_thermal_camera` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `measurement_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `measurement_devices`
--

LOCK TABLES `measurement_devices` WRITE;
/*!40000 ALTER TABLE `measurement_devices` DISABLE KEYS */;
INSERT INTO `measurement_devices` VALUES (1,1,'asd','123','2026-07-07','2027-07-07','321',0,'2026-07-07 14:25:59'),(2,1,'dsa','321','2026-07-07','2027-07-07','123',1,'2026-07-07 14:26:14');
/*!40000 ALTER TABLE `measurement_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `measurements_5_1`
--

DROP TABLE IF EXISTS `measurements_5_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `measurements_5_1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `result` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `measurements_5_1_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `grounding_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `measurements_5_1`
--

LOCK TABLES `measurements_5_1` WRITE;
/*!40000 ALTER TABLE `measurements_5_1` DISABLE KEYS */;
/*!40000 ALTER TABLE `measurements_5_1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `measurements_5_2`
--

DROP TABLE IF EXISTS `measurements_5_2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `measurements_5_2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `result` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `measurements_5_2_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `grounding_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `measurements_5_2`
--

LOCK TABLES `measurements_5_2` WRITE;
/*!40000 ALTER TABLE `measurements_5_2` DISABLE KEYS */;
/*!40000 ALTER TABLE `measurements_5_2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sihhi_tesisat_reports`
--

DROP TABLE IF EXISTS `sihhi_tesisat_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sihhi_tesisat_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT 'Periyodik Kontrol',
  `kurum_yoneticisi` varchar(255) DEFAULT NULL,
  `kurum_kapasitesi` int(11) DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'GÜVENLİDİR',
  `authorized_person_id` int(11) DEFAULT NULL,
  `inspection_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inspection_results`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_no` (`report_no`),
  KEY `kurum_id` (`kurum_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `sihhi_tesisat_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sihhi_tesisat_reports_ibfk_2` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sihhi_tesisat_reports`
--

LOCK TABLES `sihhi_tesisat_reports` WRITE;
/*!40000 ALTER TABLE `sihhi_tesisat_reports` DISABLE KEYS */;
INSERT INTO `sihhi_tesisat_reports` VALUES (2,1,'01-12-st-1783412703','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','',NULL,'','','GÜVENLİDİR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\",\"q15\":\"UYGUN\"}','2026-07-07 08:25:03'),(3,1,'01-12-st-1783425721','2026-07-07','2026-07-07 09:00:00','2026-07-07 17:00:00','2027-07-05','4536','','Periyodik Kontrol','',NULL,'','','GÜVENLİDİR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\",\"q15\":\"UYGUN\"}','2026-07-07 12:02:01'),(4,1,'01-12-st-1783503231','2026-07-08','2026-07-08 09:00:00','2026-07-08 17:00:00','2027-07-06','4536','','Periyodik Kontrol','',NULL,'','','UYGUNDUR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\",\"q15\":\"UYGUN\"}','2026-07-08 09:33:51'),(5,1,'01-12-st-1783503247','2026-07-08','2026-07-08 09:00:00','2026-07-08 17:00:00','2027-07-06','4536','','Periyodik Kontrol','',NULL,'','','UYGUNDUR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\",\"q15\":\"UYGUN\"}','2026-07-08 09:34:07');
/*!40000 ALTER TABLE `sihhi_tesisat_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'logo_text','LOGO','2026-07-07 07:29:37'),(2,'logo_type','text','2026-07-07 07:29:37'),(3,'active_logo','','2026-07-07 07:29:37');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uploaded_logos`
--

DROP TABLE IF EXISTS `uploaded_logos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploaded_logos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uploaded_logos`
--

LOCK TABLES `uploaded_logos` WRITE;
/*!40000 ALTER TABLE `uploaded_logos` DISABLE KEYS */;
/*!40000 ALTER TABLE `uploaded_logos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','2026-07-07 07:29:37');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yangin_tesisat_reports`
--

DROP TABLE IF EXISTS `yangin_tesisat_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yangin_tesisat_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kurum_id` int(11) NOT NULL,
  `report_no` varchar(100) NOT NULL,
  `report_date` date NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `next_control_date` date DEFAULT NULL,
  `isg_katip_id` varchar(100) DEFAULT NULL,
  `firma_adi_eki` varchar(255) DEFAULT NULL,
  `control_reason` varchar(255) DEFAULT 'Periyodik Kontrol',
  `kurum_yoneticisi` varchar(255) DEFAULT NULL,
  `kurum_kapasitesi` int(11) DEFAULT NULL,
  `defects` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `result` varchar(50) DEFAULT 'UYGUNDUR',
  `authorized_person_id` int(11) DEFAULT NULL,
  `inspection_results` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_no` (`report_no`),
  KEY `kurum_id` (`kurum_id`),
  KEY `authorized_person_id` (`authorized_person_id`),
  CONSTRAINT `yangin_tesisat_reports_ibfk_1` FOREIGN KEY (`kurum_id`) REFERENCES `institutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `yangin_tesisat_reports_ibfk_2` FOREIGN KEY (`authorized_person_id`) REFERENCES `authorized_persons` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yangin_tesisat_reports`
--

LOCK TABLES `yangin_tesisat_reports` WRITE;
/*!40000 ALTER TABLE `yangin_tesisat_reports` DISABLE KEYS */;
INSERT INTO `yangin_tesisat_reports` VALUES (2,1,'01-12-yt-1783503952','2026-07-08','2026-07-08 09:00:00','2026-07-08 17:00:00','2027-07-06','4536','','Periyodik Kontrol','',NULL,'','','UYGUNDUR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\",\"q15\":\"UYGUN\",\"q16\":\"UYGUN\"}','2026-07-08 09:45:52'),(3,1,'01-12-yt-1783504025','2026-07-08','2026-07-08 09:00:00','2026-07-08 17:00:00','2027-07-06','4536','','Periyodik Kontrol','',NULL,'','','UYGUNDUR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\",\"q15\":\"UYGUN\",\"q16\":\"UYGUN\"}','2026-07-08 09:47:05'),(4,1,'01-12-yt-1783505323','2026-07-08','2026-07-08 09:00:00','2026-07-08 17:00:00','2027-07-06','4536','','Periyodik Kontrol','',NULL,'','','UYGUNDUR',1,'{\"q1\":\"UYGUN\",\"q2\":\"UYGUN\",\"q3\":\"UYGUN\",\"q4\":\"UYGUN\",\"q5\":\"UYGUN\",\"q6\":\"UYGUN\",\"q7\":\"UYGUN\",\"q8\":\"UYGUN\",\"q9\":\"UYGUN\",\"q10\":\"UYGUN\",\"q11\":\"UYGUN\",\"q12\":\"UYGUN\",\"q13\":\"UYGUN\",\"q14\":\"UYGUN\",\"q15\":\"UYGUN\",\"q16\":\"UYGUN\"}','2026-07-08 10:08:43');
/*!40000 ALTER TABLE `yangin_tesisat_reports` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-20 14:27:52
