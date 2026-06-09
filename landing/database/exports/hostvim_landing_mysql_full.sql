-- MySQL dump 10.13  Distrib 8.4.9, for macos26.4 (arm64)
--
-- Host: localhost    Database: hostvim_landing
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
-- Table structure for table `blog_categories`
--

DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tr',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_categories_locale_slug_unique` (`locale`,`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_categories`
--

LOCK TABLES `blog_categories` WRITE;
/*!40000 ALTER TABLE `blog_categories` DISABLE KEYS */;
INSERT INTO `blog_categories` VALUES (1,'tr','hosting-migration','Hosting ve geçiş','Hosting ve geçiş — Panelze blog','Paylaşımlı hostingden çıkış, sunucu taşıma ve panel geçişi üzerine yazılar.',10,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(2,'en','hosting-migration','Hosting & migration','Hosting & migration — Panelze blog','Moving off shared hosting, server migrations, and panel transitions.',10,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(3,'tr','security','Güvenlik','Güvenlik — Panelze blog','Panel ve sunucu güvenliği, erişim ve sertifika konuları.',20,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(4,'en','security','Security','Security — Panelze blog','Panel and server security, access control, and certificates.',20,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(5,'tr','scaling','Ölçeklendirme','Ölçeklendirme ve mimari — Panelze blog','Tek sunucudan çoklu düzene geçiş ve mimari notları.',30,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(6,'en','scaling','Scaling','Scaling & architecture — Panelze blog','Growing from one server to multi-node setups.',30,'2026-06-09 08:30:20','2026-06-09 08:30:20');
/*!40000 ALTER TABLE `blog_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tr',
  `blog_category_id` bigint unsigned DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `canonical_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_image` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `robots` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_posts_locale_slug_unique` (`locale`,`slug`),
  KEY `blog_posts_blog_category_id_foreign` (`blog_category_id`),
  KEY `blog_posts_locale_pub_date` (`locale`,`is_published`,`published_at`),
  CONSTRAINT `blog_posts_blog_category_id_foreign` FOREIGN KEY (`blog_category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
INSERT INTO `blog_posts` VALUES (1,'tr',1,'from-shared-hosting','Shared hosting’den kendi panelime',NULL,NULL,NULL,NULL,NULL,'Klasik paylaşımlı hostingden çıkıp kendi sunucunuzda Panelze ile nasıl ilerlersiniz?','Paylaşımlı hosting uzun yıllar işinizi görür; ta ki tek panelden onlarca siteyi yönetme ihtiyacı doğana kadar.\n\n## Geçiş stratejisi\n\n1. **DNS TTL** düşürün; taşıma günü kesintiyi azaltır.\n2. Veritabanını **mysqldump** veya panel araçlarıyla alın.\n3. Dosyaları **rsync** ile senkronize edin.\n4. Panelze’de site sihirbazını çalıştırıp SSL’i doğrulayın.\n\nKüçük projelerde önce staging subdomain ile test etmek riski ciddi şekilde azaltır.','2026-06-04 08:30:20',1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(2,'en',2,'from-shared-hosting','From shared hosting to your own panel',NULL,NULL,NULL,NULL,NULL,'How to move from classic shared hosting to Panelze on your own server.','Shared hosting works for years — until you need to run many sites from one panel.\n\n## Migration strategy\n\n1. Lower **DNS TTL** to reduce cutover pain.\n2. Export the database with **mysqldump** or your tools.\n3. Sync files with **rsync**.\n4. Run the Panelze site wizard and verify TLS.\n\nFor smaller projects, test on a staging subdomain first.','2026-06-04 08:30:20',1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(3,'tr',3,'panel-security-basics','Panel güvenliğinde temel hatalar',NULL,NULL,NULL,NULL,NULL,'Yönetim arayüzünü internete açarken sık yapılan hatalar ve pratik önlemler.','Panel URL’sini herkese açık bırakmak yerine:\n\n- **İki faktörlü doğrulama** kullanın\n- Yönetim yolunu **rate limit** ile koruyun\n- Varsayılan portları değiştirin veya **VPN** arkasına alın\n\nPanelze yönetim hesapları için güçlü şifre politikası ve oturum süresi sınırları önerilir.','2026-06-06 08:30:20',1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(4,'en',4,'panel-security-basics','Common panel security mistakes',NULL,NULL,NULL,NULL,NULL,'Typical pitfalls when exposing an admin UI to the internet — and practical fixes.','Before leaving the panel URL wide open:\n\n- Enable **two-factor authentication**\n- Protect admin routes with **rate limiting**\n- Change default ports or place the panel behind a **VPN**\n\nStrong password policy and session limits are recommended for Panelze admin accounts.','2026-06-06 08:30:20',1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(5,'tr',5,'single-server-to-cluster','Tek sunucudan çoklu cluster’a',NULL,NULL,NULL,NULL,NULL,'Büyüdükçe mimariyi nasıl parçalayabilirsiniz?','İlk aşamada tek sunucu yeterlidir. Trafik ve ekip büyüdükçe:\n\n- Veritabanını ayrı bir **DB host**’a taşıyın\n- Statik ve medya için **CDN** ekleyin\n- Engine örneklerini **load balancer** arkasında çoğaltın\n\nPanelze bu aşamalarda aynı panel üzerinden çoklu sunucu yönetimini hedefler; roadmap’i ürün duyurularından takip edin.','2026-06-08 08:30:20',1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(6,'en',6,'single-server-to-cluster','From one server to a multi-node setup',NULL,NULL,NULL,NULL,NULL,'How to split the architecture as you grow.','A single server is enough at first. As traffic and teams grow:\n\n- Move the database to a dedicated **DB host**\n- Add a **CDN** for static assets and media\n- Run multiple Engine instances behind a **load balancer**\n\nPanelze aims to manage multiple servers from the same panel over time — follow product announcements for the roadmap.','2026-06-08 08:30:20',1,'2026-06-09 08:30:20','2026-06-09 08:30:20');
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
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
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
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
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
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
-- Table structure for table `community_categories`
--

DROP TABLE IF EXISTS `community_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `description_en` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_title_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_description_en` text COLLATE utf8mb4_unicode_ci,
  `robots_override` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_categories`
--

LOCK TABLES `community_categories` WRITE;
/*!40000 ALTER TABLE `community_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_posts`
--

DROP TABLE IF EXISTS `community_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `community_topic_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `moderation_status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `community_posts_user_id_foreign` (`user_id`),
  KEY `community_posts_community_topic_id_is_hidden_index` (`community_topic_id`,`is_hidden`),
  KEY `community_posts_topic_vis_mod` (`community_topic_id`,`is_hidden`,`moderation_status`),
  CONSTRAINT `community_posts_community_topic_id_foreign` FOREIGN KEY (`community_topic_id`) REFERENCES `community_topics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_posts`
--

LOCK TABLES `community_posts` WRITE;
/*!40000 ALTER TABLE `community_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_site_meta`
--

DROP TABLE IF EXISTS `community_site_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_site_meta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Community',
  `site_title_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_meta_title_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_meta_description` text COLLATE utf8mb4_unicode_ci,
  `default_meta_description_en` text COLLATE utf8mb4_unicode_ci,
  `og_image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_site` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enable_indexing` tinyint(1) NOT NULL DEFAULT '1',
  `moderation_new_topics` tinyint(1) NOT NULL DEFAULT '0',
  `moderation_new_posts` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_site_meta`
--

LOCK TABLES `community_site_meta` WRITE;
/*!40000 ALTER TABLE `community_site_meta` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_site_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_tag_topic`
--

DROP TABLE IF EXISTS `community_tag_topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_tag_topic` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `community_topic_id` bigint unsigned NOT NULL,
  `community_tag_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `topic_tag_unique` (`community_topic_id`,`community_tag_id`),
  KEY `community_tag_topic_community_tag_id_foreign` (`community_tag_id`),
  CONSTRAINT `community_tag_topic_community_tag_id_foreign` FOREIGN KEY (`community_tag_id`) REFERENCES `community_tags` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_tag_topic_community_topic_id_foreign` FOREIGN KEY (`community_topic_id`) REFERENCES `community_topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_tag_topic`
--

LOCK TABLES `community_tag_topic` WRITE;
/*!40000 ALTER TABLE `community_tag_topic` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_tag_topic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_tags`
--

DROP TABLE IF EXISTS `community_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_tags`
--

LOCK TABLES `community_tags` WRITE;
/*!40000 ALTER TABLE `community_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_topics`
--

DROP TABLE IF EXISTS `community_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_topics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `community_category_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `moderation_status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `is_solved` tinyint(1) NOT NULL DEFAULT '0',
  `best_answer_post_id` bigint unsigned DEFAULT NULL,
  `view_count` int unsigned NOT NULL DEFAULT '0',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `canonical_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `robots_override` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_topics_slug_unique` (`slug`),
  KEY `community_topics_user_id_foreign` (`user_id`),
  KEY `community_topics_community_category_id_status_index` (`community_category_id`,`status`),
  KEY `community_topics_last_activity_at_index` (`last_activity_at`),
  KEY `community_topics_best_answer_post_id_foreign` (`best_answer_post_id`),
  KEY `community_topics_pub_mod_activity` (`status`,`moderation_status`,`last_activity_at`),
  CONSTRAINT `community_topics_best_answer_post_id_foreign` FOREIGN KEY (`best_answer_post_id`) REFERENCES `community_posts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `community_topics_community_category_id_foreign` FOREIGN KEY (`community_category_id`) REFERENCES `community_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_topics_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_topics`
--

LOCK TABLES `community_topics` WRITE;
/*!40000 ALTER TABLE `community_topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_pages`
--

DROP TABLE IF EXISTS `doc_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tr',
  `parent_id` bigint unsigned DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc_pages_locale_slug_unique` (`locale`,`slug`),
  KEY `doc_pages_parent_id_foreign` (`parent_id`),
  KEY `doc_pages_locale_pub_sort` (`locale`,`is_published`,`sort_order`),
  CONSTRAINT `doc_pages_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `doc_pages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_pages`
--

LOCK TABLES `doc_pages` WRITE;
/*!40000 ALTER TABLE `doc_pages` DISABLE KEYS */;
INSERT INTO `doc_pages` VALUES (1,'tr',NULL,'getting-started','Başlangıç',NULL,NULL,'# Panelze dokümantasyonu\n\nPanelze; Linux üzerinde **Engine + Panel** bileşenlerinden oluşan bir hosting kontrol paneli yığınıdır. Bu sitedeki dokümanlar; kurulum, mimari, yetenekler ve güvenli operasyon için yol gösterir.\n\n## Nereden başlamalıyım?\n\n| Konu | Sayfa |\n| --- | --- |\n| Kurulum ve ortam değişkenleri | [Kurulum rehberi](/setup) |\n| Paket ve firewall sırası | [Sunucu kurulumu](/docs/server-setup) |\n| Bileşenler ve veri akışı | [Mimari](/docs/architecture) |\n| Panelde neler yapılabilir? | [Panelze yetenekleri](/docs/platform-features) |\n\n**Başlangıç** altında yer alan sayfalar, üretim öncesi kontrol listesi ve sunucu hazırlığını adım adım anlatır. Sol taraftaki hiyerarşi veya doğrudan bağlantılarla ilerleyebilirsiniz.',0,1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(2,'en',NULL,'getting-started','Getting started',NULL,NULL,'# Panelze documentation\n\nPanelze is a Linux hosting control stack composed of **Engine + Panel**. These guides explain installation, architecture, platform capabilities, and safe day-2 operations.\n\n## Where should I start?\n\n| Topic | Page |\n| --- | --- |\n| Install flow & environment wiring | [Installation guide](/setup) |\n| OS prep, firewall, ordering | [Server setup](/docs/server-setup) |\n| Components & trust boundaries | [Architecture](/docs/architecture) |\n| What the product can do | [Platform capabilities](/docs/platform-features) |\n\nPages nested under **Getting started** focus on pre-flight checks and server hardening. Use the sidebar tree or jump directly via the links above.',0,1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(3,'tr',1,'server-setup','Sunucu kurulumu',NULL,'Ubuntu sunucu hazırlığı, firewall, saat senkronizasyonu ve Panelze bootstrap sonrası doğrulama.','## Amaç\n\nBu sayfa, Panelze bootstrap betiğini çalıştırmadan önceki **sunucu hazırlığını** ve betik sonrası **doğrulama** adımlarını toplar. Yönergeler Ubuntu tabanlı dağıtımlar içindir; başka bir aile kullanıyorsanız paket ve servis adlarını eşdeğerleriyle değiştirin.\n\n---\n\n## 1. Sistem güncellemesi ve temel paketler\n\n```bash\nsudo apt update && sudo apt upgrade -y\n```\n\nUzak erişim için **OpenSSH sunucusunun** çalıştığından ve yalnızca güvendiğiniz IP’lerden (veya VPN üzerinden) erişilebildiğinizden emin olun.\n\n---\n\n## 2. Saat ve zaman dilimi\n\nTLS ve Let’s Encrypt doğrulamaları doğru sistem saatine bağlıdır:\n\n```bash\ntimedatectl status\n```\n\nGerekirse doğru zaman dilimini ayarlayın ve **NTP** senkronunun *active* olduğunu doğrulayın.\n\n---\n\n## 3. Firewall (örnek: UFW)\n\nHTTP(S) ve SSH dışında gelen trafiği kapatın. Tipik başlangıç:\n\n```bash\nsudo ufw allow OpenSSH\nsudo ufw allow 80/tcp\nsudo ufw allow 443/tcp\n# Paneli ayrı bir TCP portunda dinletiyorsanız o portu da ekleyin\nsudo ufw enable\nsudo ufw status verbose\n```\n\n> Üretimde paneli yalnızca iç ağ veya VPN’den erişilebilir yapmak sık tercih edilen bir sertleştirmedir.\n\n---\n\n## 4. Panelze bootstrap\n\nGüncel komutlar sayfanın altındaki **Kurulum komutları** bölümünde listelenir. Önerilen giriş:\n\n- **Tek satır:** `curl -fsSL https://get.panelze.sh | bash`\n- **Community:** GitHub `install-community.sh` → `install.sh` → `install-production.sh`\n\nBetiğin tamamlandıktan sonra:\n\n- `sudo systemctl status hostvim-engine` — Engine servisi **active** olmalı\n- `sudo cat /root/hostvim-admin-login.txt` — ilk yönetici e-posta/parola\n- Tarayıcıdan panel URL’si (Nginx varsayılanında sunucu IP veya `SERVER_NAME`)\n\n---\n\n## 5. Panel `.env` ve Engine eşlemesi\n\n`ENGINE_API_URL`, `ENGINE_INTERNAL_KEY` ve `ENGINE_API_SECRET` değerleri Engine tarafındaki yapılandırma ile **aynı** olmalıdır. Yerel geliştirmede `ENGINE_API_URL` sıklıkla `http://127.0.0.1:9090` biçimindedir; üretimde TLS terminasyonu ve geri plandaki gRPC/HTTP adresleri farklı olabilir.\n\n---\n\n## 6. İlk oturum ve sağlık kontrolleri\n\n1. Tarayıcıdan panele gidin; ilk yöneticiyi oluşturun ve **parola + 2FA** politikanızı uygulayın.\n2. İsteğe bağlı: `GET /api/health` uç noktası (panel API önekleri dağıtıma göre `/api/health`) JSON içinde `status: ok` döndürmelidir.\n3. Staging alan adıyla bir site oluşturup sertifika çıkışını ve PHP sürümünü test edin.\n\nSorun çıkarsa günlükleri (panel `storage/logs`, Engine unit journal) ve firewall kurallarını birlikte kontrol edin.',10,1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(4,'en',2,'server-setup','Server setup',NULL,'Prepare Ubuntu (or your distro), harden SSH and firewall, run bootstrap, wire Engine env vars, verify health.','## Scope\n\nUse this checklist before and after the Panelze bootstrap installer. Commands assume a **Debian/Ubuntu**-style host—swap in the equivalent packages/services for RHEL-derived distros if that is your standard.\n\n---\n\n## 1. Patch baseline & SSH hygiene\n\n```bash\nsudo apt update && sudo apt upgrade -y\n```\n\nEnsure **OpenSSH** is available only to trusted networks (bastion, VPN, or IP allow-lists). Prefer keys instead of static passwords.\n\n---\n\n## 2. Clock sync\n\nTLS issuance and OCSP rely on accurate time:\n\n```bash\ntimedatectl status\n```\n\nFix the timezone if needed and confirm NTP synchronization is active.\n\n---\n\n## 3. Firewall sketch (UFW example)\n\nAllow only what must be public. A common template:\n\n```bash\nsudo ufw allow OpenSSH\nsudo ufw allow 80/tcp\nsudo ufw allow 443/tcp\n# If the panel listens on a dedicated TCP port, allow that too\nsudo ufw enable\nsudo ufw status verbose\n```\n\nMany teams keep the panel off the public Internet entirely (VPN-only). That is stronger than opening another arbitrary port to the world.\n\n---\n\n## 4. Bootstrap Panelze\n\nExact commands are listed in the **Install commands** block at the bottom of this page. Recommended entry points:\n\n- **One-liner:** `curl -fsSL https://get.panelze.sh | bash`\n- **Community:** GitHub `install-community.sh` chains into `install.sh` and `install-production.sh`\n\nAfter the script finishes:\n\n- `sudo systemctl status hostvim-engine` — Engine unit should be **active**\n- `sudo cat /root/hostvim-admin-login.txt` — first admin email/password\n- Open the panel URL in a browser (server IP or your `SERVER_NAME` in Nginx)\n\n---\n\n## 5. Wire `.env` to the Engine\n\n`ENGINE_API_URL`, `ENGINE_INTERNAL_KEY`, and `ENGINE_API_SECRET` must match the Engine configuration on **that same node**. Local stacks often use `http://127.0.0.1:9090` for `ENGINE_API_URL`, but production may terminate TLS elsewhere—mirror whatever your operators documented.\n\n---\n\n## 6. First login & validation\n\n1. Hit the panel URL, finish onboarding, and enforce MFA/password policy for admins.\n2. Hit `GET /api/health` (prefixed according to your deployment—commonly `/api/health`) and expect JSON with `status: ok`.\n3. Create a throwaway site on a staging hostname to validate DNS + ACME + PHP selection.\n\nIf anything fails, inspect the panel log under `storage/logs`, the Engine journal via `journalctl`, and re-check firewall rules—those three catch the majority of incidents.',10,1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(5,'tr',1,'platform-features','Panelze yetenekleri',NULL,'Site, domain, SSL, veritabanı, yedek, e-posta, cron, izleme ve lisans — panel özellikleri özeti.','## Genel bakış\n\nPanelze **müşteri paneli**, alan adı ve site yaşam döngüsünü tek yerden yönetmek için tasarlanmıştır. Arayüz, arka planda **Engine** ile konuşan bir Laravel uygulamasıdır; Engine gerçek sunucu değişikliklerini (Nginx, PHP-FPM, sertifikalar vb.) uygular.\n\nYetenekler, **rol ve izin modeline** göre kısıtlanır (ör. site oluşturma, veritabanı yazma, yedek alma). Aşağıdaki liste ürün yönünü özetler; tam API yüzeyi sürüme göre genişleyebilir.\n\n---\n\n## Çekirdek hosting\n\n- **Siteler ve alan adları:** Çoklu site; ek subdomain ve alias yönetimi; durum ve sunucu eşleştirme.\n- **Web yığını:** PHP sürüm seçimi, document root, Nginx/Apache sanal host içerikleri (gelişmiş modlarda düzenleme ve geri alma).\n- **SSL / TLS:** Let’s Encrypt ile sertifika çıkarma, yenileme, iptal; gerektiğinde manuel sertifika yolları.\n\n## Veri ve dosyalar\n\n- **Veritabanları:** MySQL/MariaDB ve PostgreSQL için kullanıcı oluşturma, yetki, içe/dışa aktarma ve parola rotasyonu (sunucu tarafı `MYSQL_*` / `POSTGRES_*` provizyon bayraklarına bağlı).\n- **Dosya yöneticisi:** Gezinme, düzenleme, yükleme, sıkıştırma ve çöp kutusu ile geri yükleme (domain bazlı kota politikalarına tabi).\n- **Yedekleme:** Anlık ve zamanlanmış yedekler; hedefler ve politikalar; gerektiğinde geri yükleme akışları.\n\n## İletişim ve güvenlik\n\n- **E-posta ve yönlendirme:** Alan adına bağlı posta kutuları ve forwarder’lar.\n- **FTP:** İsteğe bağlı klasik FTP hesapları (domain kapsamında).\n- **DNS kayıtları:** Basit bölge düzenleme (yetki verildiğinde).\n- **Cron:** Kullanıcı düzeyinde zamanlanmış görevler ve çalıştırma geçmişi.\n- **İzleme:** Özet sağlık bilgisi, site bazlı durum ve sunucu düzeyinde metrikler (okuma yetkisine bağlı).\n- **Kimlik doğrulama:** Oturum açma, parola sıfırlama, isteğe bağlı **2FA** (yönetici politikalarında `ENFORCE_ADMIN_2FA` gibi bayraklarla sıkılaştırılabilir).\n\n## Operasyon ve entegrasyon\n\n- **Dağıtım / webhooks:** Siteler için CI/CD tarzı tetikleyiciler (yetkiye bağlı).\n- **Lisanslama:** Merkezi lisans sunucusu URL’si ve anahtar; Stripe faturalandırma ile entegre edilebilir dağıtımlar için hazırlıklar.\n- **WHMCS / bayi:** İsteğe bağlı modül ve çok kiracılı senaryolar (kurulumunuza göre açılır).\n\n---\n\n## Freemium ve Pro’dan ne beklenir?\n\nÖzet seviyede **Freemium** tek sunucu ve temel limitlerle başlamanıza izin verir; **Pro** daha geniş site/izleme/destek ihtiyaçları içindir. Kesin sayısal limitler paneldeki **lisans / plan** ekranında güncellenir — bu dokümandaki metinler pazarlama özetidir.\n\nDaha teknik ayrıntı için [Mimari](/docs/architecture) sayfasına bakın.',20,1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(6,'en',2,'platform-features','Platform capabilities',NULL,'Sites, SSL, databases, backups, email, cron, monitoring, licensing—what Panelze exposes end-to-end.','## Overview\n\nThe Panelze **customer panel** is a Laravel application that orchestrates day-to-day hosting operations. Persistent changes land on the host through the **Engine**, which applies Nginx/PHP-FPM/Let’s Encrypt mutations and enforces quotas.\n\nAuthorisation is **ability-based**—features below map to coarse capability groups (sites, databases, backups, etc.). The public API surface evolves per release; treat this page as the product map, not an endpoint manifest.\n\n---\n\n## Core hosting\n\n- **Sites & domains:** Multi-site accounts, subdomains, aliases, suspend/resume flows, and server placement where multi-node setups exist.\n- **Web stack controls:** PHP version selection, document roots, and editable vhost text for Nginx/Apache with guardrails and revert paths.\n- **TLS lifecycle:** Issue, renew, revoke, or attach manual certificates—typically backed by Let’s Encrypt with admin-provided contact email defaults.\n\n## Data plane & files\n\n- **Databases:** MySQL/MariaDB and PostgreSQL flows for create/drop users, granular privileges, imports/exports, and credential rotation (subject to `MYSQL_*` / `POSTGRES_*` provisioning toggles on the Engine).\n- **File manager:** Browse, edit, upload, archive/unarchive, trash/restore with throttles to protect IO.\n- **Backups:** On-demand snapshots, scheduled policies, remote destinations, and selective restores.\n\n## Messaging & edge security\n\n- **Mailbox + forwarding:** Per-domain mail users and forwarders where the mail stack is enabled.\n- **FTP accounts:** Classic FTP where policy allows it (scoped to a domain path).\n- **DNS records:** Lightweight record editing for zones delegated to the integration.\n- **Cron:** User-defined jobs with safety rails and execution history.\n- **Observability:** Per-user summaries, per-site health, and deeper server metrics for operators with monitoring permissions.\n- **Identity security:** Password policies, Sanctum tokens for API access, optional **TOTP 2FA**, and stricter admin enforcement via settings such as `ENFORCE_ADMIN_2FA`.\n\n## Day-2 automation & GTM\n\n- **Deploy hooks:** Webhook-driven pipelines for modern application releases when enabled for a site.\n- **Licensing & billing:** Configurable license hub URL, Stripe keys for checkout, and email flows that deliver keys post-payment.\n- **WHMCS / reseller:** Optional provisioning modules and multi-tenant knobs for larger hosters.\n\n---\n\n## Freemium vs licensed tiers\n\n**Freemium** is meant for single-box pilots with conservative limits. **Pro** unlocks higher ceilings for agencies and busy workloads. Authoritative numbers always live in the in-panel **plan / license** module—marketing blurbs on the landing site are summaries only.\n\nFor trust-boundary detail, continue with [Architecture](/docs/architecture).',20,1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(7,'tr',NULL,'architecture','Mimari genel bakış',NULL,'Engine, panel ve müşteri veritabanları; kimlik doğrulama, lisans ve güven sınırları.','## Üst düzey bileşenler\n\n| Katman | Sorumluluk |\n| --- | --- |\n| **Panelze Engine** | Sunucu üzerinde Nginx/Apache sanal hostları, PHP-FPM havuzlarını, dosya yollarını ve Let’s Encrypt yaşam döngüsünü uygular; kota ve politika uygular. |\n| **Panel (Laravel + Horizon/queue)** | Web ve API katmanı: kimlik (`sanctum`), rol/ability modeli, faturalama (Stripe), lisans doğrulama, müşteri arayüzü. |\n| **Panel veritabanı** | Kiracı, site, domain, kullanıcı ve operasyonel meta veriler — **müşteri sitelerinin kendi MySQL/Postgres veritabanlarından ayrıdır**. |\n| **Müşteri veritabanları** | Engine aracılığıyla oluşturulan MySQL/MariaDB veya PostgreSQL örnekleri; yedekleme ve içe/dışa aktarma panel üzerinden tetiklenir. |\n\nBu ayrım sayesinde **panel güncellemeleri** ile **Engine sürümü** farklı ritimde ilerleyebilir; müşteri trafiği çoğunlukla Engine’in yönettiği web sunucusundan çıkar.\n\n---\n\n## İstek ve güven sınırları\n\n1. Son kullanıcı tarayıcıdan panele gider (HTTPS). Oturum çerezleri ve 2FA politikaları Laravel tarafında uygulanır.\n2. Paneldeki bir eylem (ör. “sertifika yenile”) API çağrısına dönüşür; Engine’e giderken **dahili anahtarlar ve imzalar** (`ENGINE_INTERNAL_KEY`, `ENGINE_API_SECRET` vb.) ile korunur.\n3. Engine, root ayrıcalıklı işlemleri yerel olarak yapar ve sonucu panele iletir; ayrıntılı audit için hem panel günlükleri hem de `journalctl` kullanılır.\n\nUzaktan SSH ile doğrudan sunucuya bağlanma ihtiyacı azalır; yine de kilitlenme durumları için **break-glass SSH** prosedürü tanımlayın.\n\n---\n\n## Lisans ve faturalama\n\n- Panel, merkezi **lisans hub** ile konuşabilir (`LICENSE_SERVER_URL`). Checkout **Stripe** üzerinden yapılabilir; başarılı ödeme sonrası anahtar e-posta ile iletilir (landing projesindeki şablonlar ve API uçları bu akışa göre kurgulanmıştır).\n- **Freemium / Pro** sınırları plan kayıtlarında tutulur; Engine bu limitleri uygulamak için panelden gelen yetkili isteklere güvenir.\n\n---\n\n## Çoklu sunucu ve yol haritası\n\nBugünün tipik kurulumu **tek düğüm** (Engine + panel aynı makinede) şeklindedir. Trafik büyüdükçe veritabanını ayırmak, CDN eklemek veya Engine örneklerini yük dengeleyici arkasına almak mümkündür; Panelze ürünü bu evrimleri destekleyecek biçimde genişler — ayrıntılar blog ve sürüm notlarında duyurulur.\n\nTakip edilecek sayfa: [Panelze yetenekleri](/docs/platform-features).',5,1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(8,'en',NULL,'architecture','Architecture overview',NULL,'Engine vs panel vs tenant DBs; trust boundaries, licensing hub, Stripe, and scale-out notes.','## High-level components\n\n| Layer | Responsibility |\n| --- | --- |\n| **Panelze Engine** | Applies Nginx/Apache vhosts, PHP-FPM pools, filesystem paths, TLS automation, and host-level quotas. |\n| **Panel (Laravel)** | HTTP UI + JSON API: Sanctum auth, ability-based RBAC, Stripe checkout, license verification hooks, queues. |\n| **Panel database** | Stores tenants, sites, service metadata — **not** the same thing as customer MySQL/Postgres databases that belong to hosted sites. |\n| **Customer DBs** | MySQL/MariaDB or PostgreSQL instances created via Engine provisioning APIs; backups/imports initiated from the panel. |\n\nBecause these layers are separate you can ship **panel releases** and **Engine builds** on different schedules; customer HTTP traffic largely terminates on the Engine-managed web stack.\n\n---\n\n## Request path & trust boundaries\n\n1. Operators hit the panel over HTTPS; cookies/MFA enforced in Laravel.\n2. Stateful mutations become Engine RPC/HTTP calls protected by **shared secrets** such as `ENGINE_INTERNAL_KEY` / `ENGINE_API_SECRET`.\n3. The Engine performs privileged host mutations and returns structured results; troubleshooting pairs `storage/logs` on the panel with `journalctl` on the node.\n\nDay-to-day break-glass SSH should be rare—document it for disaster recovery.\n\n---\n\n## Licensing & billing\n\n- The panel can call a remote **license hub** (`LICENSE_SERVER_URL`) and/or accept keys pasted by admins.\n- Checkout may run through **Stripe**; successful orders trigger transactional mail with license material (see landing email templates + billing controllers).\n\nAuthoritative **plan limits** live beside the licensing module—marketing copy is illustrative only.\n\n---\n\n## Multi-node roadmap\n\nMost deployments today co-locate Engine + panel on one Linux host. As you grow, split the DB tier, add CDNs, or fan out Engine instances behind load balancers. Panelze’s roadmap targets multi-host orchestration—watch release notes and the blog for timelines.\n\nFor capability depth, jump to [Platform capabilities](/docs/platform-features).',5,1,'2026-06-09 08:30:20','2026-06-09 08:30:20');
/*!40000 ALTER TABLE `doc_pages` ENABLE KEYS */;
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
-- Table structure for table `landing_site_settings`
--

DROP TABLE IF EXISTS `landing_site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `landing_site_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `landing_site_settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landing_site_settings`
--

LOCK TABLES `landing_site_settings` WRITE;
/*!40000 ALTER TABLE `landing_site_settings` DISABLE KEYS */;
INSERT INTO `landing_site_settings` VALUES (1,'landing.default_locale','en','2026-06-09 08:30:20','2026-06-09 08:30:20'),(2,'landing.enabled_locales','[\"en\",\"tr\"]','2026-06-09 08:30:20','2026-06-09 08:30:20'),(3,'landing.site_name','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(4,'landing.site_tagline','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(5,'landing.site_logo_path','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(6,'landing.site_logo_max_height_px','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(7,'landing.site_logo_max_width_px','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(8,'landing.site_logo_footer_max_height_px','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(9,'landing.site_logo_footer_max_width_px','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(10,'landing.favicon_path','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(11,'landing.contact_email','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(12,'landing.social_twitter_url','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(13,'landing.social_github_url','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(14,'landing.social_linkedin_url','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(15,'landing.analytics_ga4_id','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(16,'landing.analytics_head_code','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(17,'landing.analytics_body_code','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(18,'landing.footer_extra_note','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(19,'landing.active_theme','orange','2026-06-09 08:30:20','2026-06-09 08:30:20'),(20,'landing.graphic_motif','grid','2026-06-09 08:30:20','2026-06-09 08:30:20'),(21,'landing.theme_primary_hex','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(22,'landing.hero_image_path','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(23,'landing.hero_image_alt','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(24,'landing.hero_image_caption','','2026-06-09 08:30:20','2026-06-09 08:30:20'),(25,'landing.page_overrides','{}','2026-06-09 08:30:20','2026-06-09 08:30:20'),(26,'landing.home_feature_cards','[]','2026-06-09 08:30:20','2026-06-09 08:30:20');
/*!40000 ALTER TABLE `landing_site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `landing_translations`
--

DROP TABLE IF EXISTS `landing_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `landing_translations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `landing_translations_locale_key_unique` (`locale`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landing_translations`
--

LOCK TABLES `landing_translations` WRITE;
/*!40000 ALTER TABLE `landing_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `landing_translations` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_04_03_110437_add_is_admin_to_users_table',1),(5,'2026_04_03_112231_create_blog_posts_table',1),(6,'2026_04_03_112231_create_doc_pages_table',1),(7,'2026_04_03_112231_create_plans_table',1),(8,'2026_04_03_112231_create_site_pages_table',1),(9,'2026_04_03_200000_create_landing_site_settings_table',1),(10,'2026_04_03_200001_create_landing_translations_table',1),(11,'2026_04_04_120000_create_nav_menu_items_table',1),(12,'2026_04_04_140000_create_blog_categories_and_seo_fields',1),(13,'2026_04_05_120000_add_site_page_full_seo_fields',1),(14,'2026_04_06_100000_add_locale_and_english_slugs_to_content',1),(15,'2026_04_07_120000_create_hostvim_saas_tables',1),(16,'2026_04_08_100000_saas_checkout_and_product_prices',1),(17,'2026_04_08_160000_create_community_tables',1),(18,'2026_04_08_180000_add_community_moderation_to_users',1),(19,'2026_04_08_200000_community_moderation_tags_avatar',1),(20,'2026_04_10_120000_add_listing_performance_indexes',1),(21,'2026_04_11_100000_add_locale_fields_to_nav_menu_items',1),(22,'2026_04_11_120000_community_locale_columns',1),(23,'2026_04_11_140000_saas_license_product_price_eur',1),(24,'2026_04_30_000100_force_english_default_locale_for_landing',1),(25,'2026_05_19_100000_create_panel_releases_table',1),(26,'2026_05_22_120000_add_panel_integration_to_saas_product_modules',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nav_menu_items`
--

DROP TABLE IF EXISTS `nav_menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nav_menu_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `href` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
  `href_en` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `open_in_new_tab` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nav_menu_items_zone_is_active_sort_order_index` (`zone`,`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nav_menu_items`
--

LOCK TABLES `nav_menu_items` WRITE;
/*!40000 ALTER TABLE `nav_menu_items` DISABLE KEYS */;
INSERT INTO `nav_menu_items` VALUES (1,'header','Özellikler','Features','/#features',NULL,0,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(2,'header','Fiyatlandırma','Pricing','/pricing',NULL,1,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(3,'header','Kurulum','Installation','/setup',NULL,2,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(4,'header','Dokümantasyon','Documentation','/docs',NULL,3,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(5,'header','Blog','Blog','/blog',NULL,4,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(6,'header','SSS','FAQ','/#faq',NULL,5,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(7,'footer','Dokümantasyon','Documentation','/docs',NULL,0,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(8,'footer','Blog','Blog','/blog',NULL,1,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(9,'footer','SSS','FAQ','/#faq',NULL,2,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(10,'footer','Yönetim girişi','Admin login','/admin/login',NULL,3,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(11,'footer','KVKK','Privacy notice','/p/kvkk',NULL,100,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(12,'footer','Gizlilik','Privacy policy','/p/gizlilik-politikasi',NULL,101,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(13,'footer','Çerezler','Cookie policy','/p/cerez-politikasi',NULL,102,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(14,'footer','Mesafeli satış','Distance sales','/p/mesafeli-satis',NULL,103,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(15,'footer','Kullanım koşulları','Terms of use','/p/kullanim-kosullari',NULL,104,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(16,'footer','SLA','SLA','/p/sla',NULL,105,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(17,'footer','İade ve iptal','Refunds','/p/iade-ve-iptal',NULL,106,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(18,'footer','Veri merkezi','Data centre','/p/veri-merkezi',NULL,107,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(19,'footer','Müşteri sözleşmesi','Customer agreement','/p/musteri-sozlesmesi',NULL,108,1,0,'2026-06-09 08:30:20','2026-06-09 08:30:20');
/*!40000 ALTER TABLE `nav_menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `panel_releases`
--

DROP TABLE IF EXISTS `panel_releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `panel_releases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stable',
  `profile` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changelog` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `artifact_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `artifact_sha256` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `git_tag` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_panel_version` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_engine_restart` tinyint(1) NOT NULL DEFAULT '1',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `panel_releases_version_unique` (`version`),
  KEY `panel_releases_is_published_published_at_channel_profile_index` (`is_published`,`published_at`,`channel`,`profile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `panel_releases`
--

LOCK TABLES `panel_releases` WRITE;
/*!40000 ALTER TABLE `panel_releases` DISABLE KEYS */;
/*!40000 ALTER TABLE `panel_releases` ENABLE KEYS */;
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
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `features` json DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plans_slug_unique` (`slug`),
  KEY `plans_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES (1,'Freemium','freemium','Tek sunucu için sınırlı ama yeterli özellikler','₺0','/ay','[\"1 sunucu\", \"Temel site ve domain yönetimi\", \"Otomatik SSL (Let\'s Encrypt)\", \"Sınırlı log ve terminal erişimi\"]',10,0,1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(2,'Pro Lisans','pro-lisans','Ajanslar ve yoğun trafik için','₺?','/ay · sunucu başına','[\"Sınırsız site ve domain\", \"Gelişmiş güvenlik profilleri\", \"Detaylı metrikler ve health checks\", \"Öncelikli destek\"]',20,1,1,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(3,'Vendor / White-label','vendor','Kendi markanızla sunmak isteyen paneller için','Özel','teklif','[\"Özel fiyatlandırma ve SLA\", \"Marka özelleştirme\", \"Roadmap iş birliği\"]',30,0,1,'2026-06-09 08:30:20','2026-06-09 08:30:20');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saas_checkout_orders`
--

DROP TABLE IF EXISTS `saas_checkout_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_checkout_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_ref` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saas_license_product_id` bigint unsigned NOT NULL,
  `amount_minor` int unsigned NOT NULL,
  `currency` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `stripe_checkout_session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saas_license_id` bigint unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `failure_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_checkout_orders_order_ref_unique` (`order_ref`),
  UNIQUE KEY `saas_checkout_orders_stripe_checkout_session_id_unique` (`stripe_checkout_session_id`),
  KEY `saas_checkout_orders_saas_license_product_id_foreign` (`saas_license_product_id`),
  KEY `saas_checkout_orders_saas_license_id_foreign` (`saas_license_id`),
  CONSTRAINT `saas_checkout_orders_saas_license_id_foreign` FOREIGN KEY (`saas_license_id`) REFERENCES `saas_licenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `saas_checkout_orders_saas_license_product_id_foreign` FOREIGN KEY (`saas_license_product_id`) REFERENCES `saas_license_products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_checkout_orders`
--

LOCK TABLES `saas_checkout_orders` WRITE;
/*!40000 ALTER TABLE `saas_checkout_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `saas_checkout_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saas_customers`
--

DROP TABLE IF EXISTS `saas_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saas_customers_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_customers`
--

LOCK TABLES `saas_customers` WRITE;
/*!40000 ALTER TABLE `saas_customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `saas_customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saas_license_products`
--

DROP TABLE IF EXISTS `saas_license_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_license_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_limits` json DEFAULT NULL,
  `default_modules` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `price_try_minor` int unsigned DEFAULT NULL,
  `price_usd_minor` int unsigned DEFAULT NULL,
  `price_eur_minor` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_license_products_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_license_products`
--

LOCK TABLES `saas_license_products` WRITE;
/*!40000 ALTER TABLE `saas_license_products` DISABLE KEYS */;
INSERT INTO `saas_license_products` VALUES (1,'community','Hostvim Community','Freemium — Pro modüller görünür, lisans ile açılır','{\"max_sites\": 5}','{\"ai_advisor\": false, \"backups_pro\": false, \"vendor_panel\": false, \"curious_tools\": false, \"phpmyadmin_sso\": false, \"stripe_billing\": false, \"monitoring_advanced\": false}',1,0,NULL,NULL,NULL,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(2,'pro','Hostvim Pro','Tüm Pro modüller','{\"max_sites\": 500}','{\"ai_advisor\": true, \"backups_pro\": true, \"vendor_panel\": true, \"curious_tools\": true, \"phpmyadmin_sso\": true, \"stripe_billing\": true, \"monitoring_advanced\": true}',1,10,199900,19900,18500,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(3,'pro-monthly','Hostvim Pro (Aylık)','Tüm Pro modüller','{\"max_sites\": 500}','{\"ai_advisor\": true, \"backups_pro\": true, \"vendor_panel\": true, \"curious_tools\": true, \"phpmyadmin_sso\": true, \"stripe_billing\": true, \"monitoring_advanced\": true}',1,11,199900,19900,18500,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(4,'pro-yearly','Hostvim Pro (Yıllık)','Tüm Pro modüller','{\"max_sites\": 500}','{\"ai_advisor\": true, \"backups_pro\": true, \"vendor_panel\": true, \"curious_tools\": true, \"phpmyadmin_sso\": true, \"stripe_billing\": true, \"monitoring_advanced\": true}',1,12,1999000,199000,185000,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(5,'pro-lifetime','Hostvim Pro (Sınırsız)','Tüm Pro modüller','{\"max_sites\": 500}','{\"ai_advisor\": true, \"backups_pro\": true, \"vendor_panel\": true, \"curious_tools\": true, \"phpmyadmin_sso\": true, \"stripe_billing\": true, \"monitoring_advanced\": true}',1,13,4999000,499000,459000,'2026-06-09 08:30:20','2026-06-09 08:30:20');
/*!40000 ALTER TABLE `saas_license_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saas_licenses`
--

DROP TABLE IF EXISTS `saas_licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_licenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `license_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `saas_customer_id` bigint unsigned NOT NULL,
  `saas_license_product_id` bigint unsigned NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `limits_override` json DEFAULT NULL,
  `modules_override` json DEFAULT NULL,
  `subscription_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscription_renews_at` timestamp NULL DEFAULT NULL,
  `billing_provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_licenses_license_key_unique` (`license_key`),
  KEY `saas_licenses_saas_customer_id_foreign` (`saas_customer_id`),
  KEY `saas_licenses_saas_license_product_id_foreign` (`saas_license_product_id`),
  KEY `saas_licenses_status_expires` (`status`,`expires_at`),
  CONSTRAINT `saas_licenses_saas_customer_id_foreign` FOREIGN KEY (`saas_customer_id`) REFERENCES `saas_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saas_licenses_saas_license_product_id_foreign` FOREIGN KEY (`saas_license_product_id`) REFERENCES `saas_license_products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_licenses`
--

LOCK TABLES `saas_licenses` WRITE;
/*!40000 ALTER TABLE `saas_licenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `saas_licenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saas_product_modules`
--

DROP TABLE IF EXISTS `saas_product_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_product_modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ui_paths` json DEFAULT NULL,
  `api_route_prefixes` json DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_product_modules_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_product_modules`
--

LOCK TABLES `saas_product_modules` WRITE;
/*!40000 ALTER TABLE `saas_product_modules` DISABLE KEYS */;
INSERT INTO `saas_product_modules` VALUES (1,'vendor_panel','Vendor kontrol düzlemi',NULL,'[]','[\"vendor\"]',1,1,10,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(2,'backups_pro','Gelişmiş yedekleme (Drive / uzak)',NULL,'[\"/backups\"]','[\"backups/google-drive\", \"backups/restore-remote\"]',1,1,20,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(3,'monitoring_advanced','Gelişmiş izleme',NULL,'[\"/monitoring\"]','[\"monitoring/server\"]',1,1,30,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(4,'ai_advisor','PanelZeka / AI',NULL,'[\"/ai-advisor\"]','[\"ai\", \"ai-assistant\"]',1,1,40,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(5,'curious_tools','Meraklısına',NULL,'[\"/curious\"]','[\"curious\"]',1,1,45,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(6,'stripe_billing','Stripe faturalama',NULL,'[\"/billing\"]','[\"billing/checkout\"]',1,1,50,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(7,'phpmyadmin_sso','phpMyAdmin tek tık giriş',NULL,'[]','[\"databases\"]',1,1,55,'2026-06-09 08:30:20','2026-06-09 08:30:20');
/*!40000 ALTER TABLE `saas_product_modules` ENABLE KEYS */;
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
-- Table structure for table `site_pages`
--

DROP TABLE IF EXISTS `site_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tr',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canonical_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_image` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `robots` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_pages_locale_slug_unique` (`locale`,`slug`),
  KEY `site_pages_locale_pub_sort` (`locale`,`is_published`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_pages`
--

LOCK TABLES `site_pages` WRITE;
/*!40000 ALTER TABLE `site_pages` DISABLE KEYS */;
INSERT INTO `site_pages` VALUES (1,'tr','setup','Kurulum rehberi',NULL,NULL,NULL,NULL,'## Bu rehberde neler var?\n\nPanelze yığını **iki ana parçadan** oluşur ve üretimde birlikte çalışması gerekir:\n\n| Bileşen | Rol |\n| --- | --- |\n| **Panelze Engine** | Sunucuda Nginx, PHP-FPM, sertifika ve site düzeyi işlemleri yürüten servis (genelde `127.0.0.1:9090` gibi bir adresten API dinler). |\n| **Panel (Laravel)** | Tarayıcıdan yönetim, kullanıcı/rol, lisans ve Engine’e giden API çağrıları. |\n\nBu sayfa **genel kurulum akışını** özetler; mimari ve ürün özellikleri için [dokümantasyon](/docs) altındaki [Mimari](/docs/architecture) ve [Panelze yetenekleri](/docs/platform-features) sayfalarına bakın.\n\n---\n\n## Ön koşullar\n\n### Sunucu ve sistem\n\n- **İşletim sistemi:** Temiz veya bakımlı bir **Ubuntu 22.04 LTS** önerilir; ekibiniz başka bir LTS dağıtımı onayladıysa ona uygun paket adlarını kullanın.\n- **Donanım (kılavuz):** Küçük ekipler için **2 vCPU / 4 GB RAM** genelde yeterli başlangıç değeridir; çok sayıda site veya yoğun PHP iş yükünde kaynakları artırın.\n- **Erişim:** `root` veya güvenilir **sudo** yetkisi; uzak SSH için parola yerine **anahtar tabanlı giriş** tercih edin.\n- **Saat ve DNS:** Sunucu saatinin doğru olması (NTP); üretim alan adlarınızın **A/AAAA** kayıtları sunucunuzu göstermeli (Let’s Encrypt ve canlı trafik için).\n\n### Güvenlik (kurulum öncesi)\n\n- Sunucuda yalnızca ihtiyaç duyulan portları açın (başlangıçta genelde **22**, **80**, **443**; paneli ayrı bir porttan yayınlıyorsanız onu da tanımlayın).\n- Mümkünse paneli yalnızca **VPN**, sabit IP veya **geçici SSH tüneli** üzerinden erişilebilir yapın; en azından yönetim hesaplarında **2FA** ve güçlü oturum politikası kullanın.\n- Kurulumdan önce bir **snapshot / yedek** alın; üzerinde önemli veri olan mevcut sunucuları “üstüne yazmadan” önce yedek bulundurun.\n\n---\n\n## Hızlı kurulum\n\nAşağıdaki **Güncel kurulum komutları** bölümünde deploy betikleriyle uyumlu tüm komutlar listelenir (tek satır, Community, Pro, elle kurulum, güncelleme ve onarım).\n\n> **Üretim:** Betiği çalıştırmadan önce imza / checksum doğrulaması ve betik içeriğinin incelemesi şart sayılmalıdır. Test ortamında önce deneyin. Komutları yalnızca Debian/Ubuntu VPS üzerinde root veya sudo ile çalıştırın.\n\nKurulum betiği tipik olarak şunları yapar: `git` ile `/var/www/hostvim` altına kodu çeker, `deploy/bootstrap/install-production.sh` ile Nginx, PHP, MariaDB, Engine derlemesi ve frontend build çalıştırır. İlk yönetici bilgisi `/root/hostvim-admin-login.txt` dosyasına yazılır.\n\n---\n\n## Panel ortam değişkenleri (Engine bağlantısı)\n\nPanel deposundaki `.env` dosyasında Engine ile güvenli iletişim için tipik olarak şu alanlar kullanılır:\n\n- `ENGINE_API_URL` — Engine API taban adresi (örn. `http://127.0.0.1:9090`).\n- `ENGINE_INTERNAL_KEY` — Engine ile panel arasında paylaşılan dahili anahtar.\n- `ENGINE_API_SECRET` — İmzalı istekler ve web terminal JWT gibi akışlar için Engine `security` yapılandırmasıyla eşleşmelidir.\n\nBu değerler, aynı sunucudaki **Engine yapılandırması** ile birebir uyumlu olmalı; aksi halde site oluşturma, SSL veya terminal işlemleri başarısız olur.\n\nLisanslama için `LICENSE_SERVER_URL`, `LICENSE_KEY` vb. alanlar kullanılabilir; birçok kurulumda anahtar **panel içindeki lisans ekranından** girilir.\n\n---\n\n## Kurulum sonrası kontrol listesi\n\n1. Panel ön yüzüne gidin ve ilk **yönetici** hesabını oluşturun (veya dağıtımınızdaki ilk oturum adımını tamamlayın).\n2. HTTP(S) sonlandırıcıyı doğrulayın; üretimde **HTTPS zorunlu** olmalı.\n3. Engine–panel bağlantısını test edin (ör. staging alan adıyla site açma veya panel üzerinden `GET /api/health` — yanıtta `status: ok` içeren bir JSON beklenir).\n4. İlk üretim trafiğini açmadan önce **test subdomain** veya düşük riskli alan adıyla DNS, sertifika ve PHP sürümünü doğrulayın.\n5. Yedekleme hedeflerini ve güncelleme planını (Engine + panel) netleştirin.\n\nSorun giderme: firewall, yanlış `ENGINE_*` değerleri, DNS yayılımı ve saat kayması en sık kök nedenlerdir. [Blog](/blog) ve ana sayfadaki [SSS](/#faq) bölümüne de göz atın.','Panelze Engine ve panel kurulumu: ön koşullar, güvenlik, ortam değişkenleri ve doğrulama adımları.',1,10,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(2,'en','setup','Installation guide',NULL,NULL,NULL,NULL,'## What this guide covers\n\nThe Panelze stack has **two cooperating parts** that must be installed and configured together:\n\n| Component | Role |\n| --- | --- |\n| **Panelze Engine** | Runs on the server and executes changes for Nginx, PHP-FPM, certificates, and per-site operations (typically exposes an HTTP API, e.g. on `127.0.0.1:9090`). |\n| **Panel (Laravel)** | Browser UI, user/role management, licensing, and authenticated calls into the Engine. |\n\nThis page walks through the **end-to-end install flow**. For deeper architecture and product depth, read [Architecture](/docs/architecture) and [Platform capabilities](/docs/platform-features) under [Documentation](/docs).\n\n---\n\n## Prerequisites\n\n### Server baseline\n\n- **OS:** A clean, patched **Ubuntu 22.04 LTS** is recommended; other LTS distros are fine if your team already standardised on them (adjust package names and service units accordingly).\n- **Sizing (rule of thumb):** **2 vCPU / 4 GB RAM** is a reasonable starting point for small fleets; increase CPU/RAM for heavy PHP workloads or very large numbers of sites.\n- **Access:** `root` or passwordless **sudo**; prefer **SSH keys** over passwords for remote administration.\n- **Time & DNS:** Accurate system time (NTP); production hostnames must resolve to this server (**A/AAAA**) before you rely on Let’s Encrypt and live traffic.\n\n### Security before you install\n\n- Open only required ports at the edge (typically **22**, **80**, **443**, plus whatever port serves the panel if not behind 443).\n- Where practical, restrict the panel to a **VPN**, allow-listed IPs, or short-lived **SSH tunnels**; enforce **2FA** and strong session policy on admin-class accounts.\n- Take a **snapshot or offline backup** before bootstrap scripts alter system packages or services.\n\n---\n\n## Quick install\n\nUse the **Current install commands** section below — it lists every supported path (one-liner, Community, Pro, manual git clone, updates, and repair) kept in sync with `deploy/` scripts.\n\n> **Production:** Treat every `curl | bash` as privileged code execution — verify checksums / signatures and review the script before it touches production. Always pilot in staging on Debian/Ubuntu with root or sudo.\n\nThe installer typically clones into `/var/www/hostvim`, then runs `deploy/bootstrap/install-production.sh` (Nginx, PHP, MariaDB, Engine build, frontend). First admin credentials are written to `/root/hostvim-admin-login.txt`.\n\n---\n\n## Panel environment (Engine linkage)\n\nIn the panel’s `.env`, the following variables commonly bind the UI to the Engine (names are illustrative but match the project’s layout):\n\n- `ENGINE_API_URL` — Base URL for Engine API calls (e.g. `http://127.0.0.1:9090`).\n- `ENGINE_INTERNAL_KEY` — Shared internal key negotiated between Engine and panel.\n- `ENGINE_API_SECRET` — Must align with Engine `security` settings for signed flows (e.g. web terminal JWT).\n\nIf any of these diverge from the **live Engine configuration**, provisioning, TLS, or terminal sessions will fail mysteriously.\n\nLicensing may involve `LICENSE_SERVER_URL`, `LICENSE_KEY`, etc.; many deployments paste the key in the **in-panel license** screen instead of keeping keys only in `.env`.\n\n---\n\n## Post-install checklist\n\n1. Open the panel, complete bootstrap, and create (or import) the first **administrator** account.\n2. Terminate TLS correctly at Nginx/Apache; production user traffic should be **HTTPS-only**.\n3. Prove Engine connectivity with a harmless action — e.g. create a **staging site**, issue a certificate, or call `GET /api/health` on the panel (`status` should be `ok` in JSON).\n4. Before production cutover, validate DNS, TLS, and PHP versions on a **throwaway subdomain**.\n5. Configure backup targets/schedules and document how you will **roll Engine and panel updates**.\n\nTroubleshooting tips: firewall rules, typoed `ENGINE_*` values, DNS/TTL drift, and clock skew are the usual culprits. See the [blog](/blog), the landing [FAQ](/#faq), and nested docs for next steps.','Install Panelze Engine and panel: prerequisites, hardening, environment variables, and post-install verification.',1,10,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(3,'tr','pricing','Fiyatlandırma özeti',NULL,NULL,NULL,NULL,'Bu metin, **fiyatlandırma** sayfasındaki giriş bölümünü besler. Aşağıdaki plan kartları ise yönetim panelinde tanımlı kayıtlardan **otomatik üretilir**; buradaki kopya ürün yönünü özetler.\n\n## Planların anlamı\n\n- **Freemium** — Tek sunucu, temel site/domain/SSL/terminal akışları ve makul kota ile pilot veya küçük iş yükleri için. Ücret alınmadan başlarsınız; yükseltme aynı panel üzerinden yapılır.\n- **Pro lisans** — Ajanslar, yüksek trafik veya sıkı SLA beklentisi olan müşteriler için genişletilmiş limitler, gelişmiş izleme ve öncelikli destek sütunları (kart üzerindeki maddeler veritabanından gelir).\n- **Vendor / White-label** — Kendi markanızla hizmet vermek, özel fiyat, hukuki çerçeve ve yol haritası ortaklığı için satış ekibiyle **kurumsal teklif** üzerinden ilerlenir.\n\n## Lisans ve ödeme\n\n- Çevrimiçi ödeme **Stripe** ile yapılabilir; başarılı işlemden sonra lisans anahtarı e-posta ile iletilir.\n- Anahtar çoğu zaman **panel → lisans** ekranına yapıştırılır; merkezi doğrulama için `LICENSE_SERVER_URL` yapılandırması kullanılabilir.\n\n**Kesin sayısal limitler** (site adedi, yedek saklama, API hızı vb.) paneldeki **plan / lisans** kayıtlarında tutulur; bu sayfadaki rakamlar yalnızca özet niteliğindedir.','Freemium, Pro ve Vendor katmanları; limitler, lisans ve ödeme akışı özeti.',1,20,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(4,'en','pricing','Pricing overview',NULL,NULL,NULL,NULL,'This copy powers the introductory blurb on the public **pricing** page. Feature bullets on each card are generated from the database rows managed in the landing admin—what you see here is narrative context.\n\n## How the tiers differ\n\n- **Freemium** — One server, core hosting workflows (sites, TLS, databases, limited observability) with conservative quotas. Zero licence fee to start; upgrades keep the same panel tenant.\n- **Pro licence** — Higher ceilings for agencies and demanding workloads: richer monitoring, security profiles, and support tiers (exact bullets pull from the `plans` table).\n- **Vendor / white-label** — Brand packaging, custom commercials, and roadmap partnership. Reach sales for an enterprise quote when you resell Panelze to your own customers.\n\n## Licensing & payments\n\n- Card checkout can run through **Stripe**; successful orders trigger transactional email with licence material.\n- Keys are usually pasted into the in-panel **License** screen. Large deployments can pin a central hub via `LICENSE_SERVER_URL`.\n\nAuthoritative numeric limits (sites, backup retention, API throttles) always live beside the licensing module—treat marketing tables as summaries, not contracts.','Freemium, Pro, and Vendor tiers; how cards, licensing, and Stripe checkout fit together.',1,20,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(5,'tr','kvkk','KVKK Aydınlatma Metni',NULL,NULL,NULL,NULL,'> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.\n\n## Veri sorumlusu\n\n**[TİCARİ ÜNVAN]** (bundan böyle “Şirket”), 6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) kapsamında veri sorumlusudur.\n\n- **Adres:** [ADRES]\n- **E-posta:** [E-POSTA]\n- **MERSİS:** [MERSİS NO] · **Vergi no:** [VERGİ KİMLİK / NO]\n\n## İşlenen kişisel veriler\n\nÖrnek kategoriler: kimlik / iletişim (ad, soyad, e-posta, telefon), müşteri işlem (sipariş, fatura, ödeme kaydı özetleri), teknik loglar (IP, tarayıcı, cihaz bilgisi, tarih-saat), destek talebi içerikleri, pazarlama izinleri (varsa).\n\n## İşleme amaçları\n\nHizmetin sunulması ve sözleşmenin ifası; müşteri desteği; faturalandırma ve muhasebe; güvenlik ve kötüye kullanımın önlenmesi; yasal yükümlülüklerin yerine getirilmesi; (açık rızanız varsa) pazarlama ve iletişim.\n\n## Hukuki sebepler\n\nKVKK m.5/2 (c) sözleşmenin kurulması veya ifası; (ç) veri sorumlusunun hukuki yükümlülüğü; (f) meşru menfaat; (a) açık rıza (pazarlama çerezleri / bülten vb. için).\n\n## Aktarım\n\nHizmetin gerektirdiği ölçüde; barındırma / ödeme / e-posta sağlayıcıları gibi **hizmet sağlayıcılarına** (yurt içi/yurt dışı, KVKK ve sözleşmelere uygun) aktarım yapılabilir. Yurt dışına aktarımda KVKK’da öngörülen şartlar uygulanır.\n\n## Saklama süresi\n\nİlgili mevzuatta öngörülen süreler ve meşru menfaat / sözleşme gereği gerekli süre boyunca; süre sonunda silme, yok etme veya anonimleştirme.\n\n## Haklarınız\n\nKVKK m.11 kapsamında; verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltme/silme, itiraz, zararın giderilmesi talebi vb. **[E-POSTA]** üzerinden başvurabilirsiniz. Şikâyet için Kişisel Verileri Koruma Kurulu’na başvuru hakkınız saklıdır.\n\n**Son güncelleme:** 2026-06-09','6698 sayılı KVKK kapsamında kişisel verilerin işlenmesine ilişkin aydınlatma.',1,31,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(6,'tr','gizlilik-politikasi','Gizlilik Politikası',NULL,NULL,NULL,NULL,'> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.\n\nBu politika, **Panelze** markası altında sunulan web sitesi, demo, iletişim formları ve bağlantılı dijital hizmetler için geçerlidir.\n\n## Toplanan bilgiler\n\nFormlar, hesap oluşturma, destek talepleri, çerezler ve sunucu logları aracılığıyla toplanan veriler (kimlik/iletişim, teknik veriler, kullanım istatistikleri).\n\n## Kullanım amaçları\n\nHizmet sunumu, güvenlik, analitik (anonim/aggregate), iletişim, yasal uyum.\n\n## Üçüncü taraflar\n\nBarındırma, CDN, analitik, ödeme ve e-posta sağlayıcıları. Listeler sözleşme ekinde veya talep üzerine güncellenir.\n\n## Güvenlik\n\nŞifreleme (TLS), erişim kontrolleri ve sınırlı yetkilendirme prensipleri uygulanır; mutlak güvenlik taahhüdü verilmez.\n\n## Haklar ve iletişim\n\nKVKK başvuruları **[E-POSTA]** üzerinden. Politika güncellenebilir; önemli değişiklikler sitede duyurulur.\n\n**Son güncelleme:** 2026-06-09','Web sitesi ve hizmet kullanımında kişisel verilerin korunması.',1,32,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(7,'tr','cerez-politikasi','Çerez Politikası',NULL,NULL,NULL,NULL,'> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.\n\n## Çerez nedir?\n\nÇerezler, cihazınıza kaydedilen küçük metin dosyalarıdır.\n\n## Kullandığımız çerez türleri\n\n- **Zorunlu:** Oturum, güvenlik, dil tercihi.\n- **İşlevsel:** Form ve tercih hatırlama.\n- **Analitik:** Ziyaret istatistikleri (anonimleştirilmiş olabilir).\n- **Pazarlama:** (Yalnızca açık rıza ile) yeniden pazarlama.\n\n## Yönetim\n\nTarayıcı ayarlarından çerezleri silebilir veya engelleyebilirsiniz. Zorunlu çerezleri kapatmak bazı özellikleri etkileyebilir.\n\n**Son güncelleme:** 2026-06-09','Çerez türleri, amaçları ve tercih yönetimi.',1,33,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(8,'tr','mesafeli-satis','Mesafeli Satış Sözleşmesi',NULL,NULL,NULL,NULL,'> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.\n\n## Taraflar\n\n**SATICI:** [TİCARİ ÜNVAN], [ADRES], [E-POSTA]\n\n**ALICI:** Sipariş sırasında bildirdiği bilgilerle tanımlanan gerçek/tüzel kişi.\n\n## Konu\n\nDijital ürün / lisans / abonelik (hosting paneli yazılımı ve ilişkili hizmetler) satışına ilişkin mesafeli sözleşme hükümleri.\n\n## Cayma hakkı\n\nMesafeli Sözleşmeler Yönetmeliği kapsamında, **elektronik ortamda anında ifa edilen** veya dijital içerikte tüketicinin onayı ile ifaya başlanan hizmetlerde cayma hakkı istisnaları bulunabilir. Gerçek uygulama ürün tipinize (lisans, kurulum, SaaS) göre hukukçunuzca netleştirilmelidir.\n\n## Ödeme ve fiyat\n\nFiyatlar sitede veya teklifte belirtilir; KDV ve yasal kesintiler ayrıca gösterilir.\n\n## Uyuşmuzluk\n\nTüketici işlemlerinde Tüketici Hakem Heyeti / Tüketici Mahkemeleri yetkilidir (mevzuata göre).\n\n**Son güncelleme:** 2026-06-09','6502 sayılı Kanun ve Mesafeli Sözleşmeler Yönetmeliği kapsamı.',1,34,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(9,'tr','kullanim-kosullari','Kullanım Koşulları',NULL,NULL,NULL,NULL,'> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.\n\n## Kapsam\n\nWeb sitesi, dokümantasyon ve **Panelze** hosting kontrol paneli yazılımının kullanımına ilişkin şartlar.\n\n## Lisans\n\nYazılım, satın alınan lisans tipine (ör. tek sunucu, vendor) göre kullanılır. Kaynak kodu, tersine mühendislik, lisans dışı çoğaltma yasaktır (sözleşme ve lisans metnine tabi).\n\n## Kabul edilebilir kullanım\n\nYasadışı içerik barındırma, spam, güvenlik açığı taraması (izinsiz), başkalarının sistemlerine zarar verme yasaktır. İhlal halinde hizmet askıya alınabilir veya feshedilebilir.\n\n## Sorumluluk reddi\n\nYazılım “olduğu gibi” sunulur; iş sürekliliği ve üçüncü taraf hizmetlerinden doğan dolaylı zararlar için sorumluluk, mevzuatın izin verdiği azami ölçüde sınırlıdır.\n\n## Değişiklik\n\nŞartlar güncellenebilir; yayın tarihi sitede belirtilir.\n\n**Son güncelleme:** 2026-06-09','Yazılım, web sitesi ve hizmetlerin kullanım şartları.',1,35,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(10,'tr','sla','Hizmet Seviyesi (SLA)',NULL,NULL,NULL,NULL,'> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.\n\n## Hedefler (örnek — gerçek rakamları sözleşmede netleştirin)\n\n- **Aylık erişilebilirlik hedefi:** %99,5 (planlı bakım hariç, aşağıda).\n- **Planlı bakım:** Hafta içi [SAAT ARALIĞI], önceden [X] saat/gün bildirim (mümkün olduğunca).\n- **Destek ilk yanıt hedefi:** İş günü içinde [X] saat (e-posta / ticket kanalı).\n\n## Kapsam dışı\n\nMüşteri kodu, üçüncü taraf eklentileri, DNS/ISP kesintileri, DDoS ve müşteri kaynaklı yapılandırma hataları.\n\n## Kredi / tazminat\n\nSLA ihlali halinde tazminat veya hizmet kredisi yalnızca **yazılı sözleşmede** açıkça düzenlenmişse geçerlidir.\n\n**Son güncelleme:** 2026-06-09','Erişilebilirlik hedefleri, bakım ve destek çerçevesi.',1,36,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(11,'tr','iade-ve-iptal','Ücret İadesi ve İptal Koşulları',NULL,NULL,NULL,NULL,'> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.\n\n## Genel\n\nÖdeme tipi (kart, havale, fatura) ve ürün (lisans, kurulum, aylık SaaS) modelinize göre iade kuralları değişir; aşağıdaki çerçeve şablondur.\n\n## Cayma ve iptal\n\nTüketici işlemlerinde mevzuattaki cayma süreleri uygulanır; dijital içerik / anında ifa istisnaları için Mesafeli Sözleşmeler Yönetmeliği’ne uyulur.\n\n## Kurumsal / B2B\n\nCayma hakkı olmayan sözleşmelerde iptal, sözleşme feshi hükümlerine tabidir.\n\n## İade süreci\n\nTalepler **[E-POSTA]** ile yapılır; uygun görülen ödemeler [X] iş günü içinde aynı kanala iade edilir (banka süreleri hariç).\n\n**Son güncelleme:** 2026-06-09','Cayma, iptal ve iade süreçleri.',1,37,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(12,'tr','veri-merkezi','Veri Merkezi ve Altyapı',NULL,NULL,NULL,NULL,'> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.\n\n## Lokasyon\n\nMüşteri verileri ve yedeklerin tutulduğu birincil bölge: **[ÜLKE / ŞEHİR veya bulut bölgesi]** (örn. Avrupa Birliği içi veri merkezi).\n\n## Alt işlemciler\n\nBarındırma, yedekleme, izleme ve e-posta için sınırlı erişimli alt işlemciler kullanılabilir. Güncel liste talep üzerine veya müşteri sözleşmesi ekinde paylaşılır.\n\n## Güvenlik önlemleri\n\nErişim kontrolü, şifreleme, günlükleme ve yedekleme politikaları uygulanır.\n\n**Son güncelleme:** 2026-06-09','Barındırma lokasyonu ve alt işlemci bilgisi.',1,38,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(13,'tr','musteri-sozlesmesi','Müşteri Hizmet Sözleşmesi',NULL,NULL,NULL,NULL,'> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.\n\n## Taraflar ve tanımlar\n\n**Sağlayıcı:** [TİCARİ ÜNVAN]  \n**Müşteri:** Lisans veya hizmet sözleşmesini onaylayan taraf.\n\n## Hizmetin kapsamı\n\nPanelze hosting kontrol paneli yazılımının sağlanması, güncellemeler (lisansa bağlı) ve belirlenen destek kanalları.\n\n## Ücretlendirme ve ödeme\n\nPlan, lisans veya teklif ekindeki fiyatlandırma geçerlidir; gecikmede fesih ve faiz hakları sözleşmede düzenlenir.\n\n## Hizmetin askıya alınması\n\nÖdeme gecikmesi, yasadışı kullanım veya güvenlik riski halinde geçici askıya alma.\n\n## Gizlilik ve veri işleme\n\nKişisel veriler KVKK Aydınlatma Metni ve Gizlilik Politikası’na uygun işlenir.\n\n## Süre ve fesih\n\nSözleşme süresi ve yenileme koşulları sipariş formunda; fesih bildirim süreleri sözleşmede belirtilir.\n\n## Uygulanacak hukuk ve yetki\n\n**[TÜRKİYE / İSTANBUL]** (örnek) — hukukçunuzca güncellenmelidir.\n\n**Son güncelleme:** 2026-06-09','Lisans / SaaS hosting paneli hizmet sözleşmesi çerçevesi.',1,39,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(14,'en','kvkk','Privacy & data protection notice',NULL,NULL,NULL,NULL,'> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.\n\n## Controller\n\n**[LEGAL ENTITY NAME]** (“we”, “us”) is the controller of personal data for this website and related services.\n\n- **Address:** [ADDRESS]\n- **Contact:** [EMAIL]\n\n## Data we process\n\nExamples: identity/contact details, account and billing metadata, technical logs (IP, user agent), support messages, and—if you consent—marketing preferences.\n\n## Purposes and legal bases\n\nService delivery (contract), legal obligations, legitimate interests (security, analytics in aggregated form), and consent where required (e.g. non-essential cookies / newsletters).\n\n## Recipients\n\nHosting, payment, email, and analytics providers acting as processors/sub-processors, including transfers outside your country where legally permitted and safeguarded.\n\n## Retention\n\nAs required by law and as long as necessary for the purposes described, then deleted or anonymised.\n\n## Your rights\n\nDepending on applicable law, you may request access, rectification, erasure, restriction, portability, or object to processing. Contact **[EMAIL]**. You may lodge a complaint with your supervisory authority.\n\n**Last updated:** 2026-06-09','How we process personal data in line with applicable law.',1,31,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(15,'en','gizlilik-politikasi','Privacy policy',NULL,NULL,NULL,NULL,'> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.\n\nThis policy describes how **Panelze** collects and uses personal data when you use our website, demos, and related digital services.\n\n## What we collect\n\nInformation you submit in forms, account creation, support tickets, cookies, and server logs.\n\n## How we use it\n\nTo provide the service, secure our systems, analyse aggregated usage, communicate with you, and comply with law.\n\n## Sharing\n\nWith infrastructure, payment, email, and analytics vendors under appropriate agreements.\n\n## Security\n\nWe apply technical and organisational measures (e.g. TLS, access control). No method is 100% secure.\n\n## Contact\n\n**[EMAIL]** · [ADDRESS]\n\n**Last updated:** 2026-06-09','How we collect, use, and protect personal data.',1,32,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(16,'en','cerez-politikasi','Cookie policy',NULL,NULL,NULL,NULL,'> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.\n\n## What are cookies?\n\nSmall text files stored on your device.\n\n## Types\n\nStrictly necessary, functional, analytics, and—only with consent—marketing.\n\n## Managing cookies\n\nYou can block or delete cookies in your browser. Disabling strictly necessary cookies may break parts of the site.\n\n**Last updated:** 2026-06-09','Cookies we use and how to manage preferences.',1,33,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(17,'en','mesafeli-satis','Distance / online sales terms',NULL,NULL,NULL,NULL,'> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.\n\n## Parties\n\n**Seller:** [LEGAL ENTITY NAME], [ADDRESS], [EMAIL]  \n**Buyer:** The person or entity identified in the order.\n\n## Subject\n\nOnline purchase of digital services or software licenses related to the Panelze hosting control panel.\n\n## Withdrawal / cooling-off\n\nRules depend on your jurisdiction and whether delivery is instant digital content. Many laws exclude or limit withdrawal once performance has started with the buyer’s consent—confirm with counsel.\n\n## Price and taxes\n\nAs shown at checkout or in the written quote, including applicable taxes.\n\n## Disputes\n\nAs specified under applicable consumer or commercial law.\n\n**Last updated:** 2026-06-09','Terms for online purchase of digital services or licenses.',1,34,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(18,'en','kullanim-kosullari','Terms of service',NULL,NULL,NULL,NULL,'> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.\n\n## Scope\n\nUse of the website, documentation, and Panelze software under the purchased license.\n\n## License\n\nUse is limited to the purchased tier (e.g. per server, vendor). No reverse engineering, circumvention, or redistribution beyond the license.\n\n## Acceptable use\n\nNo illegal content, spam, unauthorised intrusion attempts, or activities harming third parties. We may suspend or terminate for breach.\n\n## Disclaimer\n\nSoftware is provided as available; liability is limited to the extent permitted by law.\n\n## Changes\n\nWe may update these terms; the publication date will be indicated.\n\n**Last updated:** 2026-06-09','Rules for using our website, software, and services.',1,35,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(19,'en','sla','Service level agreement (SLA)',NULL,NULL,NULL,NULL,'> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.\n\n## Targets (examples — fix in your contract)\n\n- **Monthly availability target:** 99.5% excluding scheduled maintenance.\n- **Scheduled maintenance:** Preferably off-peak with prior notice where practical.\n- **First response target (business hours):** [X] hours via email/ticket.\n\n## Exclusions\n\nCustomer code, third-party plugins, DNS/ISP issues, DDoS, and misconfiguration by the customer.\n\n## Remedies\n\nService credits or penalties apply only if explicitly stated in a signed agreement.\n\n**Last updated:** 2026-06-09','Availability targets, maintenance, and support response goals.',1,36,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(20,'en','iade-ve-iptal','Refunds & cancellation',NULL,NULL,NULL,NULL,'> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.\n\n## General\n\nRefund rules depend on payment method and product type (perpetual license, setup fee, monthly SaaS).\n\n## Consumer rights\n\nLocal consumer laws may grant cooling-off rights with exceptions for digital content delivered immediately with consent.\n\n## Business customers\n\nOften governed by contract rather than consumer withdrawal rules.\n\n## Process\n\nContact **[EMAIL]** with order details. Approved refunds are returned to the original payment method within [X] business days (bank timelines may apply).\n\n**Last updated:** 2026-06-09','Cooling-off, cancellation, and refund rules.',1,37,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(21,'en','veri-merkezi','Data centre & infrastructure',NULL,NULL,NULL,NULL,'> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.\n\n## Location\n\nPrimary region for production data and backups: **[REGION / CLOUD AREA]** (e.g. EU).\n\n## Subprocessors\n\nHosting, backups, monitoring, and email providers with limited access. An up-to-date list is available on request or in the data processing agreement.\n\n## Security\n\nAccess controls, encryption in transit, logging, and backup policies.\n\n**Last updated:** 2026-06-09','Hosting location and subprocessors (summary).',1,38,'2026-06-09 08:30:20','2026-06-09 08:30:20'),(22,'en','musteri-sozlesmesi','Customer agreement',NULL,NULL,NULL,NULL,'> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.\n\n## Parties\n\n**Provider:** [LEGAL ENTITY NAME]  \n**Customer:** The entity accepting the order or master agreement.\n\n## Service\n\nProvision of the Panelze hosting control panel software, updates as covered by the license, and agreed support channels.\n\n## Fees\n\nPer order, quote, or subscription plan; late payment may trigger suspension as described in the agreement.\n\n## Suspension\n\nFor non-payment, illegal use, or material security risk.\n\n## Data protection\n\nProcessing of personal data follows our privacy notice and, where required, a data processing agreement.\n\n## Term and termination\n\nAs set out in the order form or master agreement.\n\n## Governing law\n\n**[JURISDICTION]** — replace with counsel-approved wording.\n\n**Last updated:** 2026-06-09','Framework agreement for licensing / SaaS of the hosting control panel.',1,39,'2026-06-09 08:30:20','2026-06-09 08:30:20');
/*!40000 ALTER TABLE `site_pages` ENABLE KEYS */;
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
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `community_banned_at` timestamp NULL DEFAULT NULL,
  `community_ban_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `community_admin_notes` text COLLATE utf8mb4_unicode_ci,
  `community_shadowbanned_at` timestamp NULL DEFAULT NULL,
  `avatar_url` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','coskunuygun@hotmail.com','2026-06-09 08:30:20','$2y$12$6Jy6WOt2782MQtnFQMvqreX0lz7N1rAF/rVtUq5Px3iPS3yzV26uy',1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-09 08:30:20','2026-06-09 08:30:20');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'hostvim_landing'
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
