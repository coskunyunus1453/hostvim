-- MySQL dump 10.13  Distrib 8.4.9, for macos26.4 (arm64)
--
-- Host: localhost    Database: hostvim_panel
-- ------------------------------------------------------
-- Server version	8.4.9

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'default','created','App\\Models\\User','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Admin\", \"email\": \"coskunuygun@hotmail.com\", \"status\": \"active\"}}',NULL,'2026-06-09 08:30:29','2026-06-09 08:30:29'),(2,'default','updated','App\\Models\\User','updated',1,NULL,NULL,'{\"old\": [], \"attributes\": []}',NULL,'2026-06-09 08:30:29','2026-06-09 08:30:29');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_chat_messages`
--

DROP TABLE IF EXISTS `ai_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_chat_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `role` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prompt_tokens` int unsigned NOT NULL DEFAULT '0',
  `completion_tokens` int unsigned NOT NULL DEFAULT '0',
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_chat_messages_session_id_created_at_index` (`session_id`,`created_at`),
  CONSTRAINT `ai_chat_messages_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `ai_chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_chat_messages`
--

LOCK TABLES `ai_chat_messages` WRITE;
/*!40000 ALTER TABLE `ai_chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_chat_sessions`
--

DROP TABLE IF EXISTS `ai_chat_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_chat_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `domain_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yeni sohbet',
  `context_mode` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'server',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_chat_sessions_user_id_foreign` (`user_id`),
  KEY `ai_chat_sessions_domain_id_foreign` (`domain_id`),
  CONSTRAINT `ai_chat_sessions_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_chat_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_chat_sessions`
--

LOCK TABLES `ai_chat_sessions` WRITE;
/*!40000 ALTER TABLE `ai_chat_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_chat_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_provider_configs`
--

DROP TABLE IF EXISTS `ai_provider_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_provider_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `provider` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_key` text COLLATE utf8mb4_unicode_ci,
  `model` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `last_test_at` timestamp NULL DEFAULT NULL,
  `last_test_ok` tinyint(1) DEFAULT NULL,
  `last_test_message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_provider_configs_user_id_provider_unique` (`user_id`,`provider`),
  CONSTRAINT `ai_provider_configs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_provider_configs`
--

LOCK TABLES `ai_provider_configs` WRITE;
/*!40000 ALTER TABLE `ai_provider_configs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_provider_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_destinations`
--

DROP TABLE IF EXISTS `backup_destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_destinations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `driver` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `config` text COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `backup_destinations_user_id_foreign` (`user_id`),
  CONSTRAINT `backup_destinations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_destinations`
--

LOCK TABLES `backup_destinations` WRITE;
/*!40000 ALTER TABLE `backup_destinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_destinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_schedules`
--

DROP TABLE IF EXISTS `backup_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `domain_id` bigint unsigned NOT NULL,
  `destination_id` bigint unsigned DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full',
  `schedule` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0 3 * * *',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `backup_schedules_user_id_foreign` (`user_id`),
  KEY `backup_schedules_domain_id_foreign` (`domain_id`),
  KEY `backup_schedules_destination_id_foreign` (`destination_id`),
  CONSTRAINT `backup_schedules_destination_id_foreign` FOREIGN KEY (`destination_id`) REFERENCES `backup_destinations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `backup_schedules_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `backup_schedules_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_schedules`
--

LOCK TABLES `backup_schedules` WRITE;
/*!40000 ALTER TABLE `backup_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `backups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `domain_id` bigint unsigned DEFAULT NULL,
  `destination_id` bigint unsigned DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remote_path` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remote_file_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `engine_backup_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_mb` bigint NOT NULL DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `backups_domain_id_foreign` (`domain_id`),
  KEY `backups_destination_id_foreign` (`destination_id`),
  KEY `backups_user_id_id_index` (`user_id`,`id`),
  CONSTRAINT `backups_destination_id_foreign` FOREIGN KEY (`destination_id`) REFERENCES `backup_destinations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `backups_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE SET NULL,
  CONSTRAINT `backups_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cloudflare_connections`
--

DROP TABLE IF EXISTS `cloudflare_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cloudflare_connections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `api_token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cloudflare_connections_user_id_unique` (`user_id`),
  CONSTRAINT `cloudflare_connections_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cloudflare_connections`
--

LOCK TABLES `cloudflare_connections` WRITE;
/*!40000 ALTER TABLE `cloudflare_connections` DISABLE KEYS */;
/*!40000 ALTER TABLE `cloudflare_connections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_items`
--

DROP TABLE IF EXISTS `cms_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `locale` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `section` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body_markdown` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_pages_kind_slug_locale_unique` (`kind`,`slug`,`locale`),
  KEY `cms_pages_kind_locale_index` (`kind`,`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_items`
--

LOCK TABLES `cms_items` WRITE;
/*!40000 ALTER TABLE `cms_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cron_job_runs`
--

DROP TABLE IF EXISTS `cron_job_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cron_job_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cron_job_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'running',
  `exit_code` int DEFAULT NULL,
  `output` longtext COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cron_job_runs_cron_job_id_created_at_index` (`cron_job_id`,`created_at`),
  KEY `cron_job_runs_user_id_id_index` (`user_id`,`id`),
  CONSTRAINT `cron_job_runs_cron_job_id_foreign` FOREIGN KEY (`cron_job_id`) REFERENCES `cron_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cron_job_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cron_job_runs`
--

LOCK TABLES `cron_job_runs` WRITE;
/*!40000 ALTER TABLE `cron_job_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `cron_job_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cron_jobs`
--

DROP TABLE IF EXISTS `cron_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cron_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `schedule` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `command` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `engine_job_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `system_key` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cron_jobs_system_key_unique` (`system_key`),
  KEY `cron_jobs_is_system_index` (`is_system`),
  KEY `cron_jobs_user_status_index` (`user_id`,`status`),
  CONSTRAINT `cron_jobs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cron_jobs`
--

LOCK TABLES `cron_jobs` WRITE;
/*!40000 ALTER TABLE `cron_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `cron_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curious_speed_results`
--

DROP TABLE IF EXISTS `curious_speed_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curious_speed_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `client_ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `panel_ping_ms` smallint unsigned DEFAULT NULL,
  `panel_download_mbps` decimal(10,2) DEFAULT NULL,
  `panel_upload_mbps` decimal(10,2) DEFAULT NULL,
  `server_ping_ms` smallint unsigned DEFAULT NULL,
  `server_download_mbps` decimal(10,2) DEFAULT NULL,
  `server_upload_mbps` decimal(10,2) DEFAULT NULL,
  `delta_ping_ms` decimal(8,1) DEFAULT NULL,
  `delta_download_mbps` decimal(10,2) DEFAULT NULL,
  `delta_upload_mbps` decimal(10,2) DEFAULT NULL,
  `server_label` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server_from_cache` tinyint(1) NOT NULL DEFAULT '0',
  `server_error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `curious_speed_results_client_ip_created_at_index` (`client_ip`,`created_at`),
  KEY `curious_speed_results_user_id_client_ip_created_at_index` (`user_id`,`client_ip`,`created_at`),
  CONSTRAINT `curious_speed_results_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curious_speed_results`
--

LOCK TABLES `curious_speed_results` WRITE;
/*!40000 ALTER TABLE `curious_speed_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `curious_speed_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `database_import_runs`
--

DROP TABLE IF EXISTS `database_import_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `database_import_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `database_id` bigint unsigned NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `progress` tinyint unsigned NOT NULL DEFAULT '0',
  `phase` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `database_import_runs_database_id_status_created_at_index` (`database_id`,`status`,`created_at`),
  KEY `database_import_runs_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `database_import_runs_database_id_foreign` FOREIGN KEY (`database_id`) REFERENCES `databases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `database_import_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `database_import_runs`
--

LOCK TABLES `database_import_runs` WRITE;
/*!40000 ALTER TABLE `database_import_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `database_import_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `databases`
--

DROP TABLE IF EXISTS `databases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `databases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `domain_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mysql',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '127.0.0.1',
  `port` int NOT NULL DEFAULT '3306',
  `grant_host` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_mb` bigint NOT NULL DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `databases_name_unique` (`name`),
  KEY `databases_domain_id_foreign` (`domain_id`),
  KEY `databases_user_id_index` (`user_id`),
  CONSTRAINT `databases_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE SET NULL,
  CONSTRAINT `databases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `databases`
--

LOCK TABLES `databases` WRITE;
/*!40000 ALTER TABLE `databases` DISABLE KEYS */;
/*!40000 ALTER TABLE `databases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deployment_configs`
--

DROP TABLE IF EXISTS `deployment_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deployment_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` bigint unsigned NOT NULL,
  `repo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'main',
  `branch_whitelist` json DEFAULT NULL,
  `runtime` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'laravel',
  `webhook_token` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auto_deploy` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deployment_configs_domain_id_unique` (`domain_id`),
  CONSTRAINT `deployment_configs_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deployment_configs`
--

LOCK TABLES `deployment_configs` WRITE;
/*!40000 ALTER TABLE `deployment_configs` DISABLE KEYS */;
/*!40000 ALTER TABLE `deployment_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deployment_runs`
--

DROP TABLE IF EXISTS `deployment_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deployment_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `trigger` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'running',
  `commit_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `output` longtext COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deployment_runs_domain_id_created_at_index` (`domain_id`,`created_at`),
  KEY `deployment_runs_user_id_id_index` (`user_id`,`id`),
  KEY `deployment_runs_domain_id_id_index` (`domain_id`,`id`),
  CONSTRAINT `deployment_runs_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deployment_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deployment_runs`
--

LOCK TABLES `deployment_runs` WRITE;
/*!40000 ALTER TABLE `deployment_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `deployment_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dns_records`
--

DROP TABLE IF EXISTS `dns_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dns_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` bigint unsigned NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ttl` int NOT NULL DEFAULT '3600',
  `priority` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dns_records_domain_id_foreign` (`domain_id`),
  CONSTRAINT `dns_records_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dns_records`
--

LOCK TABLES `dns_records` WRITE;
/*!40000 ALTER TABLE `dns_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `dns_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `domain_cloudflare_zones`
--

DROP TABLE IF EXISTS `domain_cloudflare_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `domain_cloudflare_zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` bigint unsigned NOT NULL,
  `cloudflare_connection_id` bigint unsigned NOT NULL,
  `zone_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ssl_mode` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `linked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_cloudflare_zones_domain_id_unique` (`domain_id`),
  KEY `domain_cloudflare_zones_cloudflare_connection_id_zone_id_index` (`cloudflare_connection_id`,`zone_id`),
  CONSTRAINT `domain_cloudflare_zones_cloudflare_connection_id_foreign` FOREIGN KEY (`cloudflare_connection_id`) REFERENCES `cloudflare_connections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `domain_cloudflare_zones_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `domain_cloudflare_zones`
--

LOCK TABLES `domain_cloudflare_zones` WRITE;
/*!40000 ALTER TABLE `domain_cloudflare_zones` DISABLE KEYS */;
/*!40000 ALTER TABLE `domain_cloudflare_zones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `domains`
--

DROP TABLE IF EXISTS `domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `domains` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_root` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `php_version` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '8.2',
  `ssl_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `ssl_expiry` timestamp NULL DEFAULT NULL,
  `force_https` tinyint(1) NOT NULL DEFAULT '1',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `server_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nginx',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domains_name_unique` (`name`),
  KEY `domains_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `domains_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `domains`
--

LOCK TABLES `domains` WRITE;
/*!40000 ALTER TABLE `domains` DISABLE KEYS */;
/*!40000 ALTER TABLE `domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_accounts`
--

DROP TABLE IF EXISTS `email_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `domain_id` bigint unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `quota_mb` int NOT NULL DEFAULT '500',
  `used_mb` int NOT NULL DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `forwarding_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `autoresponder_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `autoresponder_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_accounts_email_unique` (`email`),
  KEY `email_accounts_domain_id_foreign` (`domain_id`),
  KEY `email_accounts_user_id_domain_id_index` (`user_id`,`domain_id`),
  CONSTRAINT `email_accounts_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_accounts`
--

LOCK TABLES `email_accounts` WRITE;
/*!40000 ALTER TABLE `email_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_forwarders`
--

DROP TABLE IF EXISTS `email_forwarders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_forwarders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `domain_id` bigint unsigned NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keep_copy` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_forwarders_domain_id_source_destination_unique` (`domain_id`,`source`,`destination`),
  KEY `email_forwarders_user_id_foreign` (`user_id`),
  KEY `email_forwarders_domain_id_index` (`domain_id`),
  CONSTRAINT `email_forwarders_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_forwarders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_forwarders`
--

LOCK TABLES `email_forwarders` WRITE;
/*!40000 ALTER TABLE `email_forwarders` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_forwarders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ftp_accounts`
--

DROP TABLE IF EXISTS `ftp_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ftp_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `domain_id` bigint unsigned DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `home_directory` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quota_mb` int NOT NULL DEFAULT '-1',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ftp_accounts_username_unique` (`username`),
  KEY `ftp_accounts_user_id_foreign` (`user_id`),
  KEY `ftp_accounts_domain_id_foreign` (`domain_id`),
  CONSTRAINT `ftp_accounts_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ftp_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ftp_accounts`
--

LOCK TABLES `ftp_accounts` WRITE;
/*!40000 ALTER TABLE `ftp_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `ftp_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hosting_packages`
--

DROP TABLE IF EXISTS `hosting_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hosting_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `disk_space_mb` int NOT NULL DEFAULT '-1',
  `bandwidth_mb` int NOT NULL DEFAULT '-1',
  `max_domains` int NOT NULL DEFAULT '1',
  `max_subdomains` int NOT NULL DEFAULT '5',
  `max_databases` int NOT NULL DEFAULT '1',
  `max_email_accounts` int NOT NULL DEFAULT '5',
  `max_ftp_accounts` int NOT NULL DEFAULT '1',
  `max_cron_jobs` int NOT NULL DEFAULT '3',
  `cpu_limit` int DEFAULT NULL,
  `memory_limit_mb` int DEFAULT NULL,
  `php_versions` json DEFAULT NULL,
  `ssl_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `backup_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `price_monthly` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_yearly` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `reseller_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hosting_packages_slug_unique` (`slug`),
  KEY `hosting_packages_reseller_id_foreign` (`reseller_id`),
  CONSTRAINT `hosting_packages_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hosting_packages`
--

LOCK TABLES `hosting_packages` WRITE;
/*!40000 ALTER TABLE `hosting_packages` DISABLE KEYS */;
INSERT INTO `hosting_packages` VALUES (1,'Starter','starter','Perfect for small websites',5120,51200,1,3,2,5,2,3,NULL,NULL,'[\"8.1\", \"8.2\", \"8.3\"]',1,1,4.99,49.99,'USD',1,1,NULL,'2026-06-09 08:30:29','2026-06-09 08:30:29'),(2,'Professional','professional','For growing businesses',25600,256000,10,25,10,50,10,10,NULL,NULL,'[\"7.4\", \"8.0\", \"8.1\", \"8.2\", \"8.3\"]',1,1,14.99,149.99,'USD',1,2,NULL,'2026-06-09 08:30:29','2026-06-09 08:30:29'),(3,'Enterprise','enterprise','Unlimited resources for large projects',-1,-1,-1,-1,-1,-1,-1,-1,NULL,NULL,'[\"7.4\", \"8.0\", \"8.1\", \"8.2\", \"8.3\"]',1,1,49.99,499.99,'USD',1,3,NULL,'2026-06-09 08:30:29','2026-06-09 08:30:29');
/*!40000 ALTER TABLE `hosting_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `installer_runs`
--

DROP TABLE IF EXISTS `installer_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `installer_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `domain_id` bigint unsigned NOT NULL,
  `app` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `output` longtext COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `installer_runs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `installer_runs_status_created_at_index` (`status`,`created_at`),
  KEY `installer_runs_user_id_id_index` (`user_id`,`id`),
  KEY `installer_runs_domain_id_id_index` (`domain_id`,`id`),
  CONSTRAINT `installer_runs_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `installer_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `installer_runs`
--

LOCK TABLES `installer_runs` WRITE;
/*!40000 ALTER TABLE `installer_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `installer_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_03_28_214935_create_personal_access_tokens_table',1),(5,'2026_03_28_214939_create_permission_tables',1),(6,'2026_03_28_214940_create_activity_log_table',1),(7,'2026_03_28_214941_add_event_column_to_activity_log_table',1),(8,'2026_03_28_214942_add_batch_uuid_column_to_activity_log_table',1),(9,'2026_03_28_215010_create_hosting_packages_table',1),(10,'2026_03_28_215020_create_domains_table',1),(11,'2026_03_28_215030_create_databases_table',1),(12,'2026_03_28_215040_create_ssl_certificates_table',1),(13,'2026_03_28_215050_create_email_accounts_table',1),(14,'2026_03_28_215060_create_remaining_tables',1),(15,'2026_03_29_120000_add_engine_backup_id_to_backups_table',1),(16,'2026_03_29_120000_add_grant_host_to_databases_table',1),(17,'2026_03_29_180000_add_billing_sync_fields',1),(18,'2026_03_29_181001_subscriptions_external_subscription_id',1),(19,'2026_03_29_181002_fix_subscriptions_provider_external_index',1),(20,'2026_03_29_200000_create_panel_settings_table',1),(21,'2026_03_30_120000_create_site_subdomains_and_aliases_tables',1),(22,'2026_03_31_100000_add_panel_columns_to_roles_table',1),(23,'2026_03_31_180000_create_cron_job_runs_table',1),(24,'2026_03_31_220000_create_email_forwarders_table',1),(25,'2026_03_31_230000_create_deployment_tables',1),(26,'2026_04_01_000000_add_backup_destinations_and_schedules',1),(27,'2026_04_01_003000_add_branch_whitelist_to_deployment_configs',1),(28,'2026_04_01_020000_create_plugin_modules_tables',1),(29,'2026_04_01_030000_create_plugin_migration_runs_table',1),(30,'2026_04_01_040000_add_target_domain_to_plugin_migration_runs',1),(31,'2026_04_01_050000_create_installer_runs_table',1),(32,'2026_04_01_060000_create_stack_install_runs_table',1),(33,'2026_04_01_070000_add_progress_and_cancel_to_stack_install_runs',1),(34,'2026_04_01_080000_add_system_fields_to_cron_jobs',1),(35,'2026_04_01_090000_create_system_alerts_table',1),(36,'2026_04_01_100000_create_vendor_control_plane_tables',1),(37,'2026_04_01_110000_create_vendor_billing_and_support_tables',1),(38,'2026_04_01_220000_add_panel_user_to_vendor_tenants',1),(39,'2026_04_02_000001_create_two_factor_backup_codes_table',1),(40,'2026_04_03_000001_create_cms_pages_table',1),(41,'2026_04_03_120000_extend_cms_and_rename_to_cms_items',1),(42,'2026_04_08_120000_create_reseller_white_labels_and_onboarding',1),(43,'2026_04_08_180000_add_force_password_change_to_users_table',1),(44,'2026_05_19_100000_create_panel_update_runs_table',1),(45,'2026_05_20_100000_create_cloudflare_plugin_tables',1),(46,'2026_05_20_120000_add_backup_remote_fields',1),(47,'2026_05_20_120000_create_database_import_runs_table',1),(48,'2026_05_20_140000_add_force_https_to_domains',1),(49,'2026_05_20_160000_create_ai_assistant_tables',1),(50,'2026_05_20_170000_add_performance_indexes',1),(51,'2026_05_21_100000_create_site_stack_alerts_table',1),(52,'2026_05_21_120000_add_subdomain_ssl_support',1),(53,'2026_05_22_100000_create_curious_speed_results_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `panel_settings`
--

DROP TABLE IF EXISTS `panel_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `panel_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `panel_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `panel_settings`
--

LOCK TABLES `panel_settings` WRITE;
/*!40000 ALTER TABLE `panel_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `panel_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `panel_update_runs`
--

DROP TABLE IF EXISTS `panel_update_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `panel_update_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `from_version` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_version` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `progress` tinyint unsigned NOT NULL DEFAULT '0',
  `message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `output` longtext COLLATE utf8mb4_unicode_ci,
  `release_payload` json DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `panel_update_runs_user_id_foreign` (`user_id`),
  KEY `panel_update_runs_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `panel_update_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `panel_update_runs`
--

LOCK TABLES `panel_update_runs` WRITE;
/*!40000 ALTER TABLE `panel_update_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `panel_update_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(2,'sites:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(3,'sites:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(4,'domains:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(5,'domains:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(6,'databases:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(7,'databases:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(8,'email:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(9,'email:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(10,'ftp:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(11,'ftp:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(12,'files:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(13,'files:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(14,'dns:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(15,'dns:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(16,'ssl:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(17,'ssl:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(18,'backups:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(19,'backups:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(20,'cron:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(21,'cron:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(22,'monitoring:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(23,'monitoring:server','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(24,'security:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(25,'security:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(26,'installer:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(27,'installer:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(28,'tools:run','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(29,'curious:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(30,'billing:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(31,'billing:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(32,'webserver:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(33,'webserver:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(34,'php:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(35,'php:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(36,'reseller:users','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(37,'reseller:packages','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(38,'reseller:roles','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(39,'reseller:white_label','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(40,'vendor:read','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(41,'vendor:write','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(42,'vendor:nodes','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(43,'vendor:billing','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(44,'vendor:support','web','2026-06-09 08:30:29','2026-06-09 08:30:29'),(45,'vendor:audit','web','2026-06-09 08:30:29','2026-06-09 08:30:29');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_migration_runs`
--

DROP TABLE IF EXISTS `plugin_migration_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugin_migration_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plugin_module_id` bigint unsigned NOT NULL,
  `target_domain_id` bigint unsigned DEFAULT NULL,
  `source_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_port` smallint unsigned NOT NULL DEFAULT '22',
  `source_user` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `dry_run` tinyint(1) NOT NULL DEFAULT '1',
  `progress` tinyint unsigned NOT NULL DEFAULT '0',
  `options` json DEFAULT NULL,
  `output` longtext COLLATE utf8mb4_unicode_ci,
  `error_message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_migration_runs_plugin_module_id_foreign` (`plugin_module_id`),
  KEY `plugin_migration_runs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `plugin_migration_runs_status_created_at_index` (`status`,`created_at`),
  KEY `plugin_migration_runs_target_domain_id_foreign` (`target_domain_id`),
  KEY `plugin_migration_runs_user_id_target_domain_id_index` (`user_id`,`target_domain_id`),
  CONSTRAINT `plugin_migration_runs_plugin_module_id_foreign` FOREIGN KEY (`plugin_module_id`) REFERENCES `plugin_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `plugin_migration_runs_target_domain_id_foreign` FOREIGN KEY (`target_domain_id`) REFERENCES `domains` (`id`) ON DELETE SET NULL,
  CONSTRAINT `plugin_migration_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_migration_runs`
--

LOCK TABLES `plugin_migration_runs` WRITE;
/*!40000 ALTER TABLE `plugin_migration_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_migration_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_modules`
--

DROP TABLE IF EXISTS `plugin_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugin_modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'utility',
  `version` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `price_cents` int unsigned NOT NULL DEFAULT '0',
  `currency` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `is_public` tinyint(1) NOT NULL DEFAULT '1',
  `config` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plugin_modules_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_modules`
--

LOCK TABLES `plugin_modules` WRITE;
/*!40000 ALTER TABLE `plugin_modules` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reseller_white_labels`
--

DROP TABLE IF EXISTS `reseller_white_labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_white_labels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `slug` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hostname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_customer_basename` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_admin_basename` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_subtitle` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_footer_plain` text COLLATE utf8mb4_unicode_ci,
  `onboarding_html` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reseller_white_labels_user_id_unique` (`user_id`),
  UNIQUE KEY `reseller_white_labels_slug_unique` (`slug`),
  UNIQUE KEY `reseller_white_labels_hostname_unique` (`hostname`),
  CONSTRAINT `reseller_white_labels_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reseller_white_labels`
--

LOCK TABLES `reseller_white_labels` WRITE;
/*!40000 ALTER TABLE `reseller_white_labels` DISABLE KEYS */;
/*!40000 ALTER TABLE `reseller_white_labels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(7,2),(8,2),(9,2),(10,2),(11,2),(12,2),(13,2),(14,2),(15,2),(16,2),(17,2),(18,2),(19,2),(20,2),(21,2),(22,2),(24,2),(26,2),(27,2),(28,2),(29,2),(30,2),(31,2),(36,2),(37,2),(38,2),(39,2),(40,2),(41,2),(42,2),(43,2),(44,2),(45,2),(1,3),(2,3),(3,3),(4,3),(5,3),(6,3),(7,3),(8,3),(9,3),(10,3),(11,3),(12,3),(13,3),(14,3),(15,3),(16,3),(17,3),(18,3),(19,3),(20,3),(21,3),(22,3),(24,3),(26,3),(27,3),(28,3),(29,3),(30,3),(31,3),(40,3),(41,3),(42,3),(43,3),(44,3),(45,3);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `assignable_by_reseller` tinyint(1) NOT NULL DEFAULT '0',
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`),
  KEY `roles_owner_user_id_assignable_by_reseller_index` (`owner_user_id`,`assignable_by_reseller`),
  CONSTRAINT `roles_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','web',1,0,'Yönetici',NULL,'2026-06-09 08:30:29','2026-06-09 08:30:29'),(2,'reseller','web',1,0,'Bayi',NULL,'2026-06-09 08:30:29','2026-06-09 08:30:29'),(3,'user','web',1,1,'Müşteri',NULL,'2026-06-09 08:30:29','2026-06-09 08:30:29');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_domain_aliases`
--

DROP TABLE IF EXISTS `site_domain_aliases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_domain_aliases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` bigint unsigned NOT NULL,
  `hostname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_domain_aliases_hostname_unique` (`hostname`),
  KEY `site_domain_aliases_domain_id_index` (`domain_id`),
  CONSTRAINT `site_domain_aliases_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_domain_aliases`
--

LOCK TABLES `site_domain_aliases` WRITE;
/*!40000 ALTER TABLE `site_domain_aliases` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_domain_aliases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_stack_alerts`
--

DROP TABLE IF EXISTS `site_stack_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_stack_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `domain_id` bigint unsigned NOT NULL,
  `domain_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `severity` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `issue_codes` json NOT NULL,
  `issue_count` smallint unsigned NOT NULL DEFAULT '0',
  `notified_at` timestamp NULL DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_stack_alerts_user_id_status_created_at_index` (`user_id`,`status`,`created_at`),
  KEY `site_stack_alerts_domain_id_fingerprint_index` (`domain_id`,`fingerprint`),
  CONSTRAINT `site_stack_alerts_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `site_stack_alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_stack_alerts`
--

LOCK TABLES `site_stack_alerts` WRITE;
/*!40000 ALTER TABLE `site_stack_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_stack_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_subdomains`
--

DROP TABLE IF EXISTS `site_subdomains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_subdomains` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` bigint unsigned NOT NULL,
  `hostname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path_segment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_root` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `php_version` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ssl_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `ssl_expiry` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_subdomains_hostname_unique` (`hostname`),
  KEY `site_subdomains_domain_id_index` (`domain_id`),
  CONSTRAINT `site_subdomains_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_subdomains`
--

LOCK TABLES `site_subdomains` WRITE;
/*!40000 ALTER TABLE `site_subdomains` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_subdomains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ssl_certificates`
--

DROP TABLE IF EXISTS `ssl_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ssl_certificates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` bigint unsigned NOT NULL,
  `site_subdomain_id` bigint unsigned DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'letsencrypt',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dv',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `issued_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT '1',
  `certificate_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `private_key_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ssl_certificates_site_subdomain_id_foreign` (`site_subdomain_id`),
  KEY `ssl_certificates_domain_id_site_subdomain_id_index` (`domain_id`,`site_subdomain_id`),
  CONSTRAINT `ssl_certificates_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ssl_certificates_site_subdomain_id_foreign` FOREIGN KEY (`site_subdomain_id`) REFERENCES `site_subdomains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ssl_certificates`
--

LOCK TABLES `ssl_certificates` WRITE;
/*!40000 ALTER TABLE `ssl_certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `ssl_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stack_install_runs`
--

DROP TABLE IF EXISTS `stack_install_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stack_install_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `bundle_id` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `progress` tinyint unsigned NOT NULL DEFAULT '0',
  `cancel_requested` tinyint(1) NOT NULL DEFAULT '0',
  `message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `output` longtext COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stack_install_runs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `stack_install_runs_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `stack_install_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stack_install_runs`
--

LOCK TABLES `stack_install_runs` WRITE;
/*!40000 ALTER TABLE `stack_install_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `stack_install_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `hosting_package_id` bigint unsigned NOT NULL,
  `stripe_subscription_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `billing_cycle` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `starts_at` timestamp NOT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payment_provider` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `external_subscription_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_hosting_package_id_foreign` (`hosting_package_id`),
  KEY `subscriptions_user_id_status_index` (`user_id`,`status`),
  KEY `subscriptions_payment_provider_external_subscription_id_index` (`payment_provider`,`external_subscription_id`),
  CONSTRAINT `subscriptions_hosting_package_id_foreign` FOREIGN KEY (`hosting_package_id`) REFERENCES `hosting_packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_alerts`
--

DROP TABLE IF EXISTS `system_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dedupe_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_alerts_dedupe_key_index` (`dedupe_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_alerts`
--

LOCK TABLES `system_alerts` WRITE;
/*!40000 ALTER TABLE `system_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `two_factor_backup_codes`
--

DROP TABLE IF EXISTS `two_factor_backup_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `two_factor_backup_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `two_factor_backup_codes_user_id_used_at_index` (`user_id`,`used_at`),
  CONSTRAINT `two_factor_backup_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `two_factor_backup_codes`
--

LOCK TABLES `two_factor_backup_codes` WRITE;
/*!40000 ALTER TABLE `two_factor_backup_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `two_factor_backup_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_plugin_modules`
--

DROP TABLE IF EXISTS `user_plugin_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_plugin_modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plugin_module_id` bigint unsigned NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'installed',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `installed_at` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_plugin_modules_user_id_plugin_module_id_unique` (`user_id`,`plugin_module_id`),
  KEY `user_plugin_modules_plugin_module_id_foreign` (`plugin_module_id`),
  KEY `user_plugin_modules_user_id_is_active_index` (`user_id`,`is_active`),
  CONSTRAINT `user_plugin_modules_plugin_module_id_foreign` FOREIGN KEY (`plugin_module_id`) REFERENCES `plugin_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_plugin_modules_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_plugin_modules`
--

LOCK TABLES `user_plugin_modules` WRITE;
/*!40000 ALTER TABLE `user_plugin_modules` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_plugin_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT '0',
  `locale` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `parent_id` bigint unsigned DEFAULT NULL,
  `hosting_package_id` bigint unsigned DEFAULT NULL,
  `two_factor_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `hosting_package_manual_override` tinyint(1) NOT NULL DEFAULT '0',
  `onboarding_completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_status_index` (`status`),
  KEY `users_parent_id_index` (`parent_id`),
  KEY `users_hosting_package_id_foreign` (`hosting_package_id`),
  CONSTRAINT `users_hosting_package_id_foreign` FOREIGN KEY (`hosting_package_id`) REFERENCES `hosting_packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','coskunuygun@hotmail.com','2026-06-09 08:30:29','$2y$12$6j00/WH4Iihavxw8ZQ2xkevLOzfDTpvdi8b2KG19LAu2Pv7HlSwh.',1,'en','active',NULL,NULL,NULL,0,NULL,'2026-06-09 08:30:29','2026-06-09 08:30:29',0,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_audit_events`
--

DROP TABLE IF EXISTS `vendor_audit_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_audit_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `license_id` bigint unsigned DEFAULT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `event` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `ip_address` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_audit_events_tenant_id_foreign` (`tenant_id`),
  KEY `vendor_audit_events_license_id_foreign` (`license_id`),
  KEY `vendor_audit_events_actor_user_id_foreign` (`actor_user_id`),
  KEY `vendor_audit_events_event_index` (`event`),
  KEY `vendor_audit_events_severity_index` (`severity`),
  CONSTRAINT `vendor_audit_events_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_audit_events_license_id_foreign` FOREIGN KEY (`license_id`) REFERENCES `vendor_licenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_audit_events_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `vendor_tenants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_audit_events`
--

LOCK TABLES `vendor_audit_events` WRITE;
/*!40000 ALTER TABLE `vendor_audit_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_audit_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_features`
--

DROP TABLE IF EXISTS `vendor_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_features` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `kind` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'boolean',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_features_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_features`
--

LOCK TABLES `vendor_features` WRITE;
/*!40000 ALTER TABLE `vendor_features` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_invoices`
--

DROP TABLE IF EXISTS `vendor_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `subscription_id` bigint unsigned DEFAULT NULL,
  `provider` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `external_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `amount_minor` int unsigned NOT NULL DEFAULT '0',
  `currency` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `due_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_invoices_provider_external_id_unique` (`provider`,`external_id`),
  KEY `vendor_invoices_tenant_id_foreign` (`tenant_id`),
  KEY `vendor_invoices_subscription_id_foreign` (`subscription_id`),
  KEY `vendor_invoices_provider_index` (`provider`),
  KEY `vendor_invoices_external_id_index` (`external_id`),
  KEY `vendor_invoices_status_index` (`status`),
  CONSTRAINT `vendor_invoices_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `vendor_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_invoices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `vendor_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_invoices`
--

LOCK TABLES `vendor_invoices` WRITE;
/*!40000 ALTER TABLE `vendor_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_licenses`
--

DROP TABLE IF EXISTS `vendor_licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_licenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `license_key` varchar(96) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_verified_at` timestamp NULL DEFAULT NULL,
  `constraints` json DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_licenses_license_key_unique` (`license_key`),
  KEY `vendor_licenses_tenant_id_foreign` (`tenant_id`),
  KEY `vendor_licenses_plan_id_foreign` (`plan_id`),
  KEY `vendor_licenses_status_index` (`status`),
  KEY `vendor_licenses_expires_at_index` (`expires_at`),
  CONSTRAINT `vendor_licenses_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `vendor_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_licenses_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `vendor_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_licenses`
--

LOCK TABLES `vendor_licenses` WRITE;
/*!40000 ALTER TABLE `vendor_licenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_licenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_nodes`
--

DROP TABLE IF EXISTS `vendor_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_nodes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `license_id` bigint unsigned NOT NULL,
  `instance_id` varchar(96) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fingerprint` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hostname` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `public_ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_version` varchar(48) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'online',
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `capabilities` json DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_nodes_license_id_instance_id_unique` (`license_id`,`instance_id`),
  KEY `vendor_nodes_instance_id_index` (`instance_id`),
  KEY `vendor_nodes_fingerprint_index` (`fingerprint`),
  KEY `vendor_nodes_status_index` (`status`),
  KEY `vendor_nodes_last_seen_at_index` (`last_seen_at`),
  CONSTRAINT `vendor_nodes_license_id_foreign` FOREIGN KEY (`license_id`) REFERENCES `vendor_licenses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_nodes`
--

LOCK TABLES `vendor_nodes` WRITE;
/*!40000 ALTER TABLE `vendor_nodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_nodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_payments`
--

DROP TABLE IF EXISTS `vendor_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `provider` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `external_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'succeeded',
  `amount_minor` int unsigned NOT NULL DEFAULT '0',
  `currency` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `paid_at` timestamp NULL DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_payments_provider_external_id_unique` (`provider`,`external_id`),
  KEY `vendor_payments_tenant_id_foreign` (`tenant_id`),
  KEY `vendor_payments_invoice_id_foreign` (`invoice_id`),
  KEY `vendor_payments_provider_index` (`provider`),
  KEY `vendor_payments_external_id_index` (`external_id`),
  KEY `vendor_payments_status_index` (`status`),
  CONSTRAINT `vendor_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `vendor_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_payments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `vendor_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_payments`
--

LOCK TABLES `vendor_payments` WRITE;
/*!40000 ALTER TABLE `vendor_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_plan_features`
--

DROP TABLE IF EXISTS `vendor_plan_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_plan_features` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint unsigned NOT NULL,
  `feature_id` bigint unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `quota` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_plan_features_plan_id_feature_id_unique` (`plan_id`,`feature_id`),
  KEY `vendor_plan_features_feature_id_foreign` (`feature_id`),
  CONSTRAINT `vendor_plan_features_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `vendor_features` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_plan_features_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `vendor_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_plan_features`
--

LOCK TABLES `vendor_plan_features` WRITE;
/*!40000 ALTER TABLE `vendor_plan_features` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_plan_features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_plans`
--

DROP TABLE IF EXISTS `vendor_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_cycle` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `price_minor` int unsigned NOT NULL DEFAULT '0',
  `currency` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `is_public` tinyint(1) NOT NULL DEFAULT '1',
  `limits` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_plans_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_plans`
--

LOCK TABLES `vendor_plans` WRITE;
/*!40000 ALTER TABLE `vendor_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_subscriptions`
--

DROP TABLE IF EXISTS `vendor_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `license_id` bigint unsigned DEFAULT NULL,
  `provider` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `external_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `amount_minor` int unsigned NOT NULL DEFAULT '0',
  `currency` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `billing_cycle` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_subscriptions_provider_external_id_unique` (`provider`,`external_id`),
  KEY `vendor_subscriptions_tenant_id_foreign` (`tenant_id`),
  KEY `vendor_subscriptions_license_id_foreign` (`license_id`),
  KEY `vendor_subscriptions_provider_index` (`provider`),
  KEY `vendor_subscriptions_external_id_index` (`external_id`),
  KEY `vendor_subscriptions_status_index` (`status`),
  CONSTRAINT `vendor_subscriptions_license_id_foreign` FOREIGN KEY (`license_id`) REFERENCES `vendor_licenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_subscriptions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `vendor_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_subscriptions`
--

LOCK TABLES `vendor_subscriptions` WRITE;
/*!40000 ALTER TABLE `vendor_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_support_messages`
--

DROP TABLE IF EXISTS `vendor_support_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_support_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `author_user_id` bigint unsigned DEFAULT NULL,
  `author_type` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vendor',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_support_messages_ticket_id_foreign` (`ticket_id`),
  KEY `vendor_support_messages_author_user_id_foreign` (`author_user_id`),
  KEY `vendor_support_messages_author_type_index` (`author_type`),
  CONSTRAINT `vendor_support_messages_author_user_id_foreign` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_support_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `vendor_support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_support_messages`
--

LOCK TABLES `vendor_support_messages` WRITE;
/*!40000 ALTER TABLE `vendor_support_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_support_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_support_tickets`
--

DROP TABLE IF EXISTS `vendor_support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_support_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `license_id` bigint unsigned DEFAULT NULL,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `priority` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `last_message` text COLLATE utf8mb4_unicode_ci,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_support_tickets_tenant_id_foreign` (`tenant_id`),
  KEY `vendor_support_tickets_license_id_foreign` (`license_id`),
  KEY `vendor_support_tickets_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `vendor_support_tickets_status_index` (`status`),
  KEY `vendor_support_tickets_priority_index` (`priority`),
  KEY `vendor_support_tickets_last_activity_at_index` (`last_activity_at`),
  CONSTRAINT `vendor_support_tickets_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_support_tickets_license_id_foreign` FOREIGN KEY (`license_id`) REFERENCES `vendor_licenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_support_tickets_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `vendor_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_support_tickets`
--

LOCK TABLES `vendor_support_tickets` WRITE;
/*!40000 ALTER TABLE `vendor_support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_tenants`
--

DROP TABLE IF EXISTS `vendor_tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_tenants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `panel_user_id` bigint unsigned DEFAULT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `contact_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_tenants_slug_unique` (`slug`),
  KEY `vendor_tenants_status_index` (`status`),
  KEY `1` (`panel_user_id`),
  CONSTRAINT `1` FOREIGN KEY (`panel_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_tenants`
--

LOCK TABLES `vendor_tenants` WRITE;
/*!40000 ALTER TABLE `vendor_tenants` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_tenants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'hostvim_panel'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-09 14:30:42
