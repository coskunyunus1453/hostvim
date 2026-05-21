-- Hostvim Landing — tam veritabanı dökümü (şema + veri)
-- Kaynak: database/database.sqlite
-- Oluşturulma: 2026-05-21 12:13:01
-- MariaDB / MySQL 10.4+ utf8mb4_unicode_ci

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
-- Table structure for table `blog_categories`
--
DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `meta_title` varchar(255),
  `meta_description` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `locale` varchar(255) NOT NULL DEFAULT 'tr',
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_categories_locale_slug_unique` (`locale`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_categories`
--
LOCK TABLES `blog_categories` WRITE;
/*!40000 ALTER TABLE `blog_categories` DISABLE KEYS */;
INSERT INTO `blog_categories` (`id`, `slug`, `name`, `meta_title`, `meta_description`, `sort_order`, `created_at`, `updated_at`, `locale`) VALUES (1, 'hosting-migration', 'Hosting ve geçiş', 'Hosting ve geçiş — Panelze blog', 'Paylaşımlı hostingden çıkış, sunucu taşıma ve panel geçişi üzerine yazılar.', 10, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'tr');
INSERT INTO `blog_categories` (`id`, `slug`, `name`, `meta_title`, `meta_description`, `sort_order`, `created_at`, `updated_at`, `locale`) VALUES (2, 'hosting-migration', 'Hosting & migration', 'Hosting & migration — Panelze blog', 'Moving off shared hosting, server migrations, and panel transitions.', 10, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'en');
INSERT INTO `blog_categories` (`id`, `slug`, `name`, `meta_title`, `meta_description`, `sort_order`, `created_at`, `updated_at`, `locale`) VALUES (3, 'security', 'Güvenlik', 'Güvenlik — Panelze blog', 'Panel ve sunucu güvenliği, erişim ve sertifika konuları.', 20, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'tr');
INSERT INTO `blog_categories` (`id`, `slug`, `name`, `meta_title`, `meta_description`, `sort_order`, `created_at`, `updated_at`, `locale`) VALUES (4, 'security', 'Security', 'Security — Panelze blog', 'Panel and server security, access control, and certificates.', 20, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'en');
INSERT INTO `blog_categories` (`id`, `slug`, `name`, `meta_title`, `meta_description`, `sort_order`, `created_at`, `updated_at`, `locale`) VALUES (5, 'scaling', 'Ölçeklendirme', 'Ölçeklendirme ve mimari — Panelze blog', 'Tek sunucudan çoklu düzene geçiş ve mimari notları.', 30, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'tr');
INSERT INTO `blog_categories` (`id`, `slug`, `name`, `meta_title`, `meta_description`, `sort_order`, `created_at`, `updated_at`, `locale`) VALUES (6, 'scaling', 'Scaling', 'Scaling & architecture — Panelze blog', 'Growing from one server to multi-node setups.', 30, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'en');
/*!40000 ALTER TABLE `blog_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_posts`
--
DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `excerpt` text,
  `content` longtext NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `blog_category_id` bigint(20) unsigned,
  `meta_title` varchar(255),
  `meta_description` text,
  `canonical_url` varchar(255),
  `og_image` varchar(255),
  `robots` varchar(64),
  `locale` varchar(255) NOT NULL DEFAULT 'tr',
  PRIMARY KEY (`id`),
  CONSTRAINT `blog_posts_blog_category_id_foreign` FOREIGN KEY (`blog_category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL,
  KEY `blog_posts_locale_pub_date` (`locale`, `is_published`, `published_at`),
  UNIQUE KEY `blog_posts_locale_slug_unique` (`locale`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--
LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
INSERT INTO `blog_posts` (`id`, `slug`, `title`, `excerpt`, `content`, `published_at`, `is_published`, `created_at`, `updated_at`, `blog_category_id`, `meta_title`, `meta_description`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (1, 'from-shared-hosting', 'Shared hosting’den kendi panelime', 'Klasik paylaşımlı hostingden çıkıp kendi sunucunuzda Panelze ile nasıl ilerlersiniz?', 'Paylaşımlı hosting uzun yıllar işinizi görür; ta ki tek panelden onlarca siteyi yönetme ihtiyacı doğana kadar.

## Geçiş stratejisi

1. **DNS TTL** düşürün; taşıma günü kesintiyi azaltır.
2. Veritabanını **mysqldump** veya panel araçlarıyla alın.
3. Dosyaları **rsync** ile senkronize edin.
4. Panelze’de site sihirbazını çalıştırıp SSL’i doğrulayın.

Küçük projelerde önce staging subdomain ile test etmek riski ciddi şekilde azaltır.', '2026-05-16 12:13:01', 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 1, NULL, NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `blog_posts` (`id`, `slug`, `title`, `excerpt`, `content`, `published_at`, `is_published`, `created_at`, `updated_at`, `blog_category_id`, `meta_title`, `meta_description`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (2, 'from-shared-hosting', 'From shared hosting to your own panel', 'How to move from classic shared hosting to Panelze on your own server.', 'Shared hosting works for years — until you need to run many sites from one panel.

## Migration strategy

1. Lower **DNS TTL** to reduce cutover pain.
2. Export the database with **mysqldump** or your tools.
3. Sync files with **rsync**.
4. Run the Panelze site wizard and verify TLS.

For smaller projects, test on a staging subdomain first.', '2026-05-16 12:13:01', 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 2, NULL, NULL, NULL, NULL, NULL, 'en');
INSERT INTO `blog_posts` (`id`, `slug`, `title`, `excerpt`, `content`, `published_at`, `is_published`, `created_at`, `updated_at`, `blog_category_id`, `meta_title`, `meta_description`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (3, 'panel-security-basics', 'Panel güvenliğinde temel hatalar', 'Yönetim arayüzünü internete açarken sık yapılan hatalar ve pratik önlemler.', 'Panel URL’sini herkese açık bırakmak yerine:

- **İki faktörlü doğrulama** kullanın
- Yönetim yolunu **rate limit** ile koruyun
- Varsayılan portları değiştirin veya **VPN** arkasına alın

Panelze yönetim hesapları için güçlü şifre politikası ve oturum süresi sınırları önerilir.', '2026-05-18 12:13:01', 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 3, NULL, NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `blog_posts` (`id`, `slug`, `title`, `excerpt`, `content`, `published_at`, `is_published`, `created_at`, `updated_at`, `blog_category_id`, `meta_title`, `meta_description`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (4, 'panel-security-basics', 'Common panel security mistakes', 'Typical pitfalls when exposing an admin UI to the internet — and practical fixes.', 'Before leaving the panel URL wide open:

- Enable **two-factor authentication**
- Protect admin routes with **rate limiting**
- Change default ports or place the panel behind a **VPN**

Strong password policy and session limits are recommended for Panelze admin accounts.', '2026-05-18 12:13:01', 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 4, NULL, NULL, NULL, NULL, NULL, 'en');
INSERT INTO `blog_posts` (`id`, `slug`, `title`, `excerpt`, `content`, `published_at`, `is_published`, `created_at`, `updated_at`, `blog_category_id`, `meta_title`, `meta_description`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (5, 'single-server-to-cluster', 'Tek sunucudan çoklu cluster’a', 'Büyüdükçe mimariyi nasıl parçalayabilirsiniz?', 'İlk aşamada tek sunucu yeterlidir. Trafik ve ekip büyüdükçe:

- Veritabanını ayrı bir **DB host**’a taşıyın
- Statik ve medya için **CDN** ekleyin
- Engine örneklerini **load balancer** arkasında çoğaltın

Panelze bu aşamalarda aynı panel üzerinden çoklu sunucu yönetimini hedefler; roadmap’i ürün duyurularından takip edin.', '2026-05-20 12:13:01', 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 5, NULL, NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `blog_posts` (`id`, `slug`, `title`, `excerpt`, `content`, `published_at`, `is_published`, `created_at`, `updated_at`, `blog_category_id`, `meta_title`, `meta_description`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (6, 'single-server-to-cluster', 'From one server to a multi-node setup', 'How to split the architecture as you grow.', 'A single server is enough at first. As traffic and teams grow:

- Move the database to a dedicated **DB host**
- Add a **CDN** for static assets and media
- Run multiple Engine instances behind a **load balancer**

Panelze aims to manage multiple servers from the same panel over time — follow product announcements for the roadmap.', '2026-05-20 12:13:01', 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 6, NULL, NULL, NULL, NULL, NULL, 'en');
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255),
  `value` text NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

--
-- Table structure for table `cache_locks`
--
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255),
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

--
-- Table structure for table `community_categories`
--
DROP TABLE IF EXISTS `community_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `meta_title` varchar(255),
  `meta_description` text,
  `robots_override` varchar(255),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name_en` varchar(255),
  `description_en` text,
  `meta_title_en` varchar(255),
  `meta_description_en` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_categories`
--

--
-- Table structure for table `community_posts`
--
DROP TABLE IF EXISTS `community_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_topic_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` longtext NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `moderation_status` varchar(255) NOT NULL DEFAULT 'approved',
  PRIMARY KEY (`id`),
  CONSTRAINT `community_posts_community_topic_id_foreign` FOREIGN KEY (`community_topic_id`) REFERENCES `community_topics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  KEY `community_posts_topic_vis_mod` (`community_topic_id`, `is_hidden`, `moderation_status`),
  KEY `community_posts_community_topic_id_is_hidden_index` (`community_topic_id`, `is_hidden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_posts`
--

--
-- Table structure for table `community_site_meta`
--
DROP TABLE IF EXISTS `community_site_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_site_meta` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_title` varchar(255) NOT NULL DEFAULT 'Community',
  `default_meta_title` varchar(255),
  `default_meta_description` text,
  `og_image_url` varchar(255),
  `twitter_site` varchar(255),
  `enable_indexing` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `moderation_new_topics` tinyint(1) NOT NULL DEFAULT '0',
  `moderation_new_posts` tinyint(1) NOT NULL DEFAULT '0',
  `site_title_en` varchar(255),
  `default_meta_title_en` varchar(255),
  `default_meta_description_en` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_site_meta`
--

--
-- Table structure for table `community_tag_topic`
--
DROP TABLE IF EXISTS `community_tag_topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_tag_topic` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_topic_id` bigint(20) unsigned NOT NULL,
  `community_tag_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `community_tag_topic_community_topic_id_foreign` FOREIGN KEY (`community_topic_id`) REFERENCES `community_topics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_tag_topic_community_tag_id_foreign` FOREIGN KEY (`community_tag_id`) REFERENCES `community_tags` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `topic_tag_unique` (`community_topic_id`, `community_tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_tag_topic`
--

--
-- Table structure for table `community_tags`
--
DROP TABLE IF EXISTS `community_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_tags`
--

--
-- Table structure for table `community_topics`
--
DROP TABLE IF EXISTS `community_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_topics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_category_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `excerpt` varchar(255),
  `status` varchar(255) NOT NULL DEFAULT 'published',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `is_solved` tinyint(1) NOT NULL DEFAULT '0',
  `best_answer_post_id` bigint(20) unsigned,
  `view_count` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_title` varchar(255),
  `meta_description` text,
  `canonical_url` varchar(255),
  `robots_override` varchar(255),
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `moderation_status` varchar(255) NOT NULL DEFAULT 'approved',
  PRIMARY KEY (`id`),
  CONSTRAINT `community_topics_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_topics_community_category_id_foreign` FOREIGN KEY (`community_category_id`) REFERENCES `community_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_topics_best_answer_post_id_foreign` FOREIGN KEY (`best_answer_post_id`) REFERENCES `community_posts` (`id`) ON DELETE SET NULL,
  KEY `community_topics_pub_mod_activity` (`status`, `moderation_status`, `last_activity_at`),
  UNIQUE KEY `community_topics_slug_unique` (`slug`),
  KEY `community_topics_last_activity_at_index` (`last_activity_at`),
  KEY `community_topics_community_category_id_status_index` (`community_category_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_topics`
--

--
-- Table structure for table `doc_pages`
--
DROP TABLE IF EXISTS `doc_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255),
  `meta_description` text,
  `locale` varchar(255) NOT NULL DEFAULT 'tr',
  PRIMARY KEY (`id`),
  CONSTRAINT `doc_pages_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `doc_pages` (`id`) ON DELETE SET NULL,
  KEY `doc_pages_locale_pub_sort` (`locale`, `is_published`, `sort_order`),
  UNIQUE KEY `doc_pages_locale_slug_unique` (`locale`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_pages`
--
LOCK TABLES `doc_pages` WRITE;
/*!40000 ALTER TABLE `doc_pages` DISABLE KEYS */;
INSERT INTO `doc_pages` (`id`, `parent_id`, `slug`, `title`, `content`, `sort_order`, `is_published`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `locale`) VALUES (1, NULL, 'getting-started', 'Başlangıç', '# Panelze dokümantasyonu

Panelze; Linux üzerinde **Engine + Panel** bileşenlerinden oluşan bir hosting kontrol paneli yığınıdır. Bu sitedeki dokümanlar; kurulum, mimari, yetenekler ve güvenli operasyon için yol gösterir.

## Nereden başlamalıyım?

| Konu | Sayfa |
| --- | --- |
| Kurulum ve ortam değişkenleri | [Kurulum rehberi](/setup) |
| Paket ve firewall sırası | [Sunucu kurulumu](/docs/server-setup) |
| Bileşenler ve veri akışı | [Mimari](/docs/architecture) |
| Panelde neler yapılabilir? | [Panelze yetenekleri](/docs/platform-features) |

**Başlangıç** altında yer alan sayfalar, üretim öncesi kontrol listesi ve sunucu hazırlığını adım adım anlatır. Sol taraftaki hiyerarşi veya doğrudan bağlantılarla ilerleyebilirsiniz.', 0, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, 'tr');
INSERT INTO `doc_pages` (`id`, `parent_id`, `slug`, `title`, `content`, `sort_order`, `is_published`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `locale`) VALUES (2, NULL, 'getting-started', 'Getting started', '# Panelze documentation

Panelze is a Linux hosting control stack composed of **Engine + Panel**. These guides explain installation, architecture, platform capabilities, and safe day-2 operations.

## Where should I start?

| Topic | Page |
| --- | --- |
| Install flow & environment wiring | [Installation guide](/setup) |
| OS prep, firewall, ordering | [Server setup](/docs/server-setup) |
| Components & trust boundaries | [Architecture](/docs/architecture) |
| What the product can do | [Platform capabilities](/docs/platform-features) |

Pages nested under **Getting started** focus on pre-flight checks and server hardening. Use the sidebar tree or jump directly via the links above.', 0, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, 'en');
INSERT INTO `doc_pages` (`id`, `parent_id`, `slug`, `title`, `content`, `sort_order`, `is_published`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `locale`) VALUES (3, 1, 'server-setup', 'Sunucu kurulumu', '## Amaç

Bu sayfa, Panelze bootstrap betiğini çalıştırmadan önceki **sunucu hazırlığını** ve betik sonrası **doğrulama** adımlarını toplar. Yönergeler Ubuntu tabanlı dağıtımlar içindir; başka bir aile kullanıyorsanız paket ve servis adlarını eşdeğerleriyle değiştirin.

---

## 1. Sistem güncellemesi ve temel paketler

```bash
sudo apt update && sudo apt upgrade -y
```

Uzak erişim için **OpenSSH sunucusunun** çalıştığından ve yalnızca güvendiğiniz IP’lerden (veya VPN üzerinden) erişilebildiğinizden emin olun.

---

## 2. Saat ve zaman dilimi

TLS ve Let’s Encrypt doğrulamaları doğru sistem saatine bağlıdır:

```bash
timedatectl status
```

Gerekirse doğru zaman dilimini ayarlayın ve **NTP** senkronunun *active* olduğunu doğrulayın.

---

## 3. Firewall (örnek: UFW)

HTTP(S) ve SSH dışında gelen trafiği kapatın. Tipik başlangıç:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
# Paneli ayrı bir TCP portunda dinletiyorsanız o portu da ekleyin
sudo ufw enable
sudo ufw status verbose
```

> Üretimde paneli yalnızca iç ağ veya VPN’den erişilebilir yapmak sık tercih edilen bir sertleştirmedir.

---

## 4. Panelze bootstrap

Güncel komutlar sayfanın altındaki **Kurulum komutları** bölümünde listelenir. Önerilen giriş:

- **Tek satır:** `curl -fsSL https://get.panelze.sh | bash`
- **Community:** GitHub `install-community.sh` → `install.sh` → `install-production.sh`

Betiğin tamamlandıktan sonra:

- `sudo systemctl status hostvim-engine` — Engine servisi **active** olmalı
- `sudo cat /root/hostvim-admin-login.txt` — ilk yönetici e-posta/parola
- Tarayıcıdan panel URL’si (Nginx varsayılanında sunucu IP veya `SERVER_NAME`)

---

## 5. Panel `.env` ve Engine eşlemesi

`ENGINE_API_URL`, `ENGINE_INTERNAL_KEY` ve `ENGINE_API_SECRET` değerleri Engine tarafındaki yapılandırma ile **aynı** olmalıdır. Yerel geliştirmede `ENGINE_API_URL` sıklıkla `http://127.0.0.1:9090` biçimindedir; üretimde TLS terminasyonu ve geri plandaki gRPC/HTTP adresleri farklı olabilir.

---

## 6. İlk oturum ve sağlık kontrolleri

1. Tarayıcıdan panele gidin; ilk yöneticiyi oluşturun ve **parola + 2FA** politikanızı uygulayın.
2. İsteğe bağlı: `GET /api/health` uç noktası (panel API önekleri dağıtıma göre `/api/health`) JSON içinde `status: ok` döndürmelidir.
3. Staging alan adıyla bir site oluşturup sertifika çıkışını ve PHP sürümünü test edin.

Sorun çıkarsa günlükleri (panel `storage/logs`, Engine unit journal) ve firewall kurallarını birlikte kontrol edin.', 10, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, 'Ubuntu sunucu hazırlığı, firewall, saat senkronizasyonu ve Panelze bootstrap sonrası doğrulama.', 'tr');
INSERT INTO `doc_pages` (`id`, `parent_id`, `slug`, `title`, `content`, `sort_order`, `is_published`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `locale`) VALUES (4, 2, 'server-setup', 'Server setup', '## Scope

Use this checklist before and after the Panelze bootstrap installer. Commands assume a **Debian/Ubuntu**-style host—swap in the equivalent packages/services for RHEL-derived distros if that is your standard.

---

## 1. Patch baseline & SSH hygiene

```bash
sudo apt update && sudo apt upgrade -y
```

Ensure **OpenSSH** is available only to trusted networks (bastion, VPN, or IP allow-lists). Prefer keys instead of static passwords.

---

## 2. Clock sync

TLS issuance and OCSP rely on accurate time:

```bash
timedatectl status
```

Fix the timezone if needed and confirm NTP synchronization is active.

---

## 3. Firewall sketch (UFW example)

Allow only what must be public. A common template:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
# If the panel listens on a dedicated TCP port, allow that too
sudo ufw enable
sudo ufw status verbose
```

Many teams keep the panel off the public Internet entirely (VPN-only). That is stronger than opening another arbitrary port to the world.

---

## 4. Bootstrap Panelze

Exact commands are listed in the **Install commands** block at the bottom of this page. Recommended entry points:

- **One-liner:** `curl -fsSL https://get.panelze.sh | bash`
- **Community:** GitHub `install-community.sh` chains into `install.sh` and `install-production.sh`

After the script finishes:

- `sudo systemctl status hostvim-engine` — Engine unit should be **active**
- `sudo cat /root/hostvim-admin-login.txt` — first admin email/password
- Open the panel URL in a browser (server IP or your `SERVER_NAME` in Nginx)

---

## 5. Wire `.env` to the Engine

`ENGINE_API_URL`, `ENGINE_INTERNAL_KEY`, and `ENGINE_API_SECRET` must match the Engine configuration on **that same node**. Local stacks often use `http://127.0.0.1:9090` for `ENGINE_API_URL`, but production may terminate TLS elsewhere—mirror whatever your operators documented.

---

## 6. First login & validation

1. Hit the panel URL, finish onboarding, and enforce MFA/password policy for admins.
2. Hit `GET /api/health` (prefixed according to your deployment—commonly `/api/health`) and expect JSON with `status: ok`.
3. Create a throwaway site on a staging hostname to validate DNS + ACME + PHP selection.

If anything fails, inspect the panel log under `storage/logs`, the Engine journal via `journalctl`, and re-check firewall rules—those three catch the majority of incidents.', 10, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, 'Prepare Ubuntu (or your distro), harden SSH and firewall, run bootstrap, wire Engine env vars, verify health.', 'en');
INSERT INTO `doc_pages` (`id`, `parent_id`, `slug`, `title`, `content`, `sort_order`, `is_published`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `locale`) VALUES (5, 1, 'platform-features', 'Panelze yetenekleri', '## Genel bakış

Panelze **müşteri paneli**, alan adı ve site yaşam döngüsünü tek yerden yönetmek için tasarlanmıştır. Arayüz, arka planda **Engine** ile konuşan bir Laravel uygulamasıdır; Engine gerçek sunucu değişikliklerini (Nginx, PHP-FPM, sertifikalar vb.) uygular.

Yetenekler, **rol ve izin modeline** göre kısıtlanır (ör. site oluşturma, veritabanı yazma, yedek alma). Aşağıdaki liste ürün yönünü özetler; tam API yüzeyi sürüme göre genişleyebilir.

---

## Çekirdek hosting

- **Siteler ve alan adları:** Çoklu site; ek subdomain ve alias yönetimi; durum ve sunucu eşleştirme.
- **Web yığını:** PHP sürüm seçimi, document root, Nginx/Apache sanal host içerikleri (gelişmiş modlarda düzenleme ve geri alma).
- **SSL / TLS:** Let’s Encrypt ile sertifika çıkarma, yenileme, iptal; gerektiğinde manuel sertifika yolları.

## Veri ve dosyalar

- **Veritabanları:** MySQL/MariaDB ve PostgreSQL için kullanıcı oluşturma, yetki, içe/dışa aktarma ve parola rotasyonu (sunucu tarafı `MYSQL_*` / `POSTGRES_*` provizyon bayraklarına bağlı).
- **Dosya yöneticisi:** Gezinme, düzenleme, yükleme, sıkıştırma ve çöp kutusu ile geri yükleme (domain bazlı kota politikalarına tabi).
- **Yedekleme:** Anlık ve zamanlanmış yedekler; hedefler ve politikalar; gerektiğinde geri yükleme akışları.

## İletişim ve güvenlik

- **E-posta ve yönlendirme:** Alan adına bağlı posta kutuları ve forwarder’lar.
- **FTP:** İsteğe bağlı klasik FTP hesapları (domain kapsamında).
- **DNS kayıtları:** Basit bölge düzenleme (yetki verildiğinde).
- **Cron:** Kullanıcı düzeyinde zamanlanmış görevler ve çalıştırma geçmişi.
- **İzleme:** Özet sağlık bilgisi, site bazlı durum ve sunucu düzeyinde metrikler (okuma yetkisine bağlı).
- **Kimlik doğrulama:** Oturum açma, parola sıfırlama, isteğe bağlı **2FA** (yönetici politikalarında `ENFORCE_ADMIN_2FA` gibi bayraklarla sıkılaştırılabilir).

## Operasyon ve entegrasyon

- **Dağıtım / webhooks:** Siteler için CI/CD tarzı tetikleyiciler (yetkiye bağlı).
- **Lisanslama:** Merkezi lisans sunucusu URL’si ve anahtar; Stripe faturalandırma ile entegre edilebilir dağıtımlar için hazırlıklar.
- **WHMCS / bayi:** İsteğe bağlı modül ve çok kiracılı senaryolar (kurulumunuza göre açılır).

---

## Freemium ve Pro’dan ne beklenir?

Özet seviyede **Freemium** tek sunucu ve temel limitlerle başlamanıza izin verir; **Pro** daha geniş site/izleme/destek ihtiyaçları içindir. Kesin sayısal limitler paneldeki **lisans / plan** ekranında güncellenir — bu dokümandaki metinler pazarlama özetidir.

Daha teknik ayrıntı için [Mimari](/docs/architecture) sayfasına bakın.', 20, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, 'Site, domain, SSL, veritabanı, yedek, e-posta, cron, izleme ve lisans — panel özellikleri özeti.', 'tr');
INSERT INTO `doc_pages` (`id`, `parent_id`, `slug`, `title`, `content`, `sort_order`, `is_published`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `locale`) VALUES (6, 2, 'platform-features', 'Platform capabilities', '## Overview

The Panelze **customer panel** is a Laravel application that orchestrates day-to-day hosting operations. Persistent changes land on the host through the **Engine**, which applies Nginx/PHP-FPM/Let’s Encrypt mutations and enforces quotas.

Authorisation is **ability-based**—features below map to coarse capability groups (sites, databases, backups, etc.). The public API surface evolves per release; treat this page as the product map, not an endpoint manifest.

---

## Core hosting

- **Sites & domains:** Multi-site accounts, subdomains, aliases, suspend/resume flows, and server placement where multi-node setups exist.
- **Web stack controls:** PHP version selection, document roots, and editable vhost text for Nginx/Apache with guardrails and revert paths.
- **TLS lifecycle:** Issue, renew, revoke, or attach manual certificates—typically backed by Let’s Encrypt with admin-provided contact email defaults.

## Data plane & files

- **Databases:** MySQL/MariaDB and PostgreSQL flows for create/drop users, granular privileges, imports/exports, and credential rotation (subject to `MYSQL_*` / `POSTGRES_*` provisioning toggles on the Engine).
- **File manager:** Browse, edit, upload, archive/unarchive, trash/restore with throttles to protect IO.
- **Backups:** On-demand snapshots, scheduled policies, remote destinations, and selective restores.

## Messaging & edge security

- **Mailbox + forwarding:** Per-domain mail users and forwarders where the mail stack is enabled.
- **FTP accounts:** Classic FTP where policy allows it (scoped to a domain path).
- **DNS records:** Lightweight record editing for zones delegated to the integration.
- **Cron:** User-defined jobs with safety rails and execution history.
- **Observability:** Per-user summaries, per-site health, and deeper server metrics for operators with monitoring permissions.
- **Identity security:** Password policies, Sanctum tokens for API access, optional **TOTP 2FA**, and stricter admin enforcement via settings such as `ENFORCE_ADMIN_2FA`.

## Day-2 automation & GTM

- **Deploy hooks:** Webhook-driven pipelines for modern application releases when enabled for a site.
- **Licensing & billing:** Configurable license hub URL, Stripe keys for checkout, and email flows that deliver keys post-payment.
- **WHMCS / reseller:** Optional provisioning modules and multi-tenant knobs for larger hosters.

---

## Freemium vs licensed tiers

**Freemium** is meant for single-box pilots with conservative limits. **Pro** unlocks higher ceilings for agencies and busy workloads. Authoritative numbers always live in the in-panel **plan / license** module—marketing blurbs on the landing site are summaries only.

For trust-boundary detail, continue with [Architecture](/docs/architecture).', 20, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, 'Sites, SSL, databases, backups, email, cron, monitoring, licensing—what Panelze exposes end-to-end.', 'en');
INSERT INTO `doc_pages` (`id`, `parent_id`, `slug`, `title`, `content`, `sort_order`, `is_published`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `locale`) VALUES (7, NULL, 'architecture', 'Mimari genel bakış', '## Üst düzey bileşenler

| Katman | Sorumluluk |
| --- | --- |
| **Panelze Engine** | Sunucu üzerinde Nginx/Apache sanal hostları, PHP-FPM havuzlarını, dosya yollarını ve Let’s Encrypt yaşam döngüsünü uygular; kota ve politika uygular. |
| **Panel (Laravel + Horizon/queue)** | Web ve API katmanı: kimlik (`sanctum`), rol/ability modeli, faturalama (Stripe), lisans doğrulama, müşteri arayüzü. |
| **Panel veritabanı** | Kiracı, site, domain, kullanıcı ve operasyonel meta veriler — **müşteri sitelerinin kendi MySQL/Postgres veritabanlarından ayrıdır**. |
| **Müşteri veritabanları** | Engine aracılığıyla oluşturulan MySQL/MariaDB veya PostgreSQL örnekleri; yedekleme ve içe/dışa aktarma panel üzerinden tetiklenir. |

Bu ayrım sayesinde **panel güncellemeleri** ile **Engine sürümü** farklı ritimde ilerleyebilir; müşteri trafiği çoğunlukla Engine’in yönettiği web sunucusundan çıkar.

---

## İstek ve güven sınırları

1. Son kullanıcı tarayıcıdan panele gider (HTTPS). Oturum çerezleri ve 2FA politikaları Laravel tarafında uygulanır.
2. Paneldeki bir eylem (ör. “sertifika yenile”) API çağrısına dönüşür; Engine’e giderken **dahili anahtarlar ve imzalar** (`ENGINE_INTERNAL_KEY`, `ENGINE_API_SECRET` vb.) ile korunur.
3. Engine, root ayrıcalıklı işlemleri yerel olarak yapar ve sonucu panele iletir; ayrıntılı audit için hem panel günlükleri hem de `journalctl` kullanılır.

Uzaktan SSH ile doğrudan sunucuya bağlanma ihtiyacı azalır; yine de kilitlenme durumları için **break-glass SSH** prosedürü tanımlayın.

---

## Lisans ve faturalama

- Panel, merkezi **lisans hub** ile konuşabilir (`LICENSE_SERVER_URL`). Checkout **Stripe** üzerinden yapılabilir; başarılı ödeme sonrası anahtar e-posta ile iletilir (landing projesindeki şablonlar ve API uçları bu akışa göre kurgulanmıştır).
- **Freemium / Pro** sınırları plan kayıtlarında tutulur; Engine bu limitleri uygulamak için panelden gelen yetkili isteklere güvenir.

---

## Çoklu sunucu ve yol haritası

Bugünün tipik kurulumu **tek düğüm** (Engine + panel aynı makinede) şeklindedir. Trafik büyüdükçe veritabanını ayırmak, CDN eklemek veya Engine örneklerini yük dengeleyici arkasına almak mümkündür; Panelze ürünü bu evrimleri destekleyecek biçimde genişler — ayrıntılar blog ve sürüm notlarında duyurulur.

Takip edilecek sayfa: [Panelze yetenekleri](/docs/platform-features).', 5, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, 'Engine, panel ve müşteri veritabanları; kimlik doğrulama, lisans ve güven sınırları.', 'tr');
INSERT INTO `doc_pages` (`id`, `parent_id`, `slug`, `title`, `content`, `sort_order`, `is_published`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `locale`) VALUES (8, NULL, 'architecture', 'Architecture overview', '## High-level components

| Layer | Responsibility |
| --- | --- |
| **Panelze Engine** | Applies Nginx/Apache vhosts, PHP-FPM pools, filesystem paths, TLS automation, and host-level quotas. |
| **Panel (Laravel)** | HTTP UI + JSON API: Sanctum auth, ability-based RBAC, Stripe checkout, license verification hooks, queues. |
| **Panel database** | Stores tenants, sites, service metadata — **not** the same thing as customer MySQL/Postgres databases that belong to hosted sites. |
| **Customer DBs** | MySQL/MariaDB or PostgreSQL instances created via Engine provisioning APIs; backups/imports initiated from the panel. |

Because these layers are separate you can ship **panel releases** and **Engine builds** on different schedules; customer HTTP traffic largely terminates on the Engine-managed web stack.

---

## Request path & trust boundaries

1. Operators hit the panel over HTTPS; cookies/MFA enforced in Laravel.
2. Stateful mutations become Engine RPC/HTTP calls protected by **shared secrets** such as `ENGINE_INTERNAL_KEY` / `ENGINE_API_SECRET`.
3. The Engine performs privileged host mutations and returns structured results; troubleshooting pairs `storage/logs` on the panel with `journalctl` on the node.

Day-to-day break-glass SSH should be rare—document it for disaster recovery.

---

## Licensing & billing

- The panel can call a remote **license hub** (`LICENSE_SERVER_URL`) and/or accept keys pasted by admins.
- Checkout may run through **Stripe**; successful orders trigger transactional mail with license material (see landing email templates + billing controllers).

Authoritative **plan limits** live beside the licensing module—marketing copy is illustrative only.

---

## Multi-node roadmap

Most deployments today co-locate Engine + panel on one Linux host. As you grow, split the DB tier, add CDNs, or fan out Engine instances behind load balancers. Panelze’s roadmap targets multi-host orchestration—watch release notes and the blog for timelines.

For capability depth, jump to [Platform capabilities](/docs/platform-features).', 5, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, 'Engine vs panel vs tenant DBs; trust boundaries, licensing hub, Stripe, and scale-out notes.', 'en');
/*!40000 ALTER TABLE `doc_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` text NOT NULL,
  `exception` text NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

--
-- Table structure for table `job_batches`
--
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255),
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` text NOT NULL,
  `options` text,
  `cancelled_at` int(11),
  `created_at` int(11) NOT NULL,
  `finished_at` int(11),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

--
-- Table structure for table `jobs`
--
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` text NOT NULL,
  `attempts` int(11) NOT NULL,
  `reserved_at` int(11),
  `available_at` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

--
-- Table structure for table `landing_site_settings`
--
DROP TABLE IF EXISTS `landing_site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `landing_site_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `landing_site_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landing_site_settings`
--
LOCK TABLES `landing_site_settings` WRITE;
/*!40000 ALTER TABLE `landing_site_settings` DISABLE KEYS */;
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (1, 'landing.default_locale', 'en', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (2, 'landing.enabled_locales', '["en","tr"]', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (3, 'landing.site_name', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (4, 'landing.site_tagline', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (5, 'landing.site_logo_path', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (6, 'landing.site_logo_max_height_px', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (7, 'landing.site_logo_max_width_px', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (8, 'landing.site_logo_footer_max_height_px', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (9, 'landing.site_logo_footer_max_width_px', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (10, 'landing.favicon_path', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (11, 'landing.contact_email', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (12, 'landing.social_twitter_url', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (13, 'landing.social_github_url', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (14, 'landing.social_linkedin_url', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (15, 'landing.analytics_ga4_id', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (16, 'landing.analytics_head_code', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (17, 'landing.analytics_body_code', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (18, 'landing.footer_extra_note', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (19, 'landing.active_theme', 'orange', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (20, 'landing.graphic_motif', 'grid', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (21, 'landing.theme_primary_hex', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (22, 'landing.hero_image_path', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (23, 'landing.hero_image_alt', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (24, 'landing.hero_image_caption', '', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (25, 'landing.page_overrides', '{}', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `landing_site_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES (26, 'landing.home_feature_cards', '[]', '2026-05-21 12:13:01', '2026-05-21 12:13:01');
/*!40000 ALTER TABLE `landing_site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `landing_translations`
--
DROP TABLE IF EXISTS `landing_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `landing_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `landing_translations_locale_key_unique` (`locale`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `landing_translations`
--

--
-- Table structure for table `migrations`
--
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--
LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4, '2026_04_03_110437_add_is_admin_to_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5, '2026_04_03_112231_create_blog_posts_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6, '2026_04_03_112231_create_doc_pages_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7, '2026_04_03_112231_create_plans_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8, '2026_04_03_112231_create_site_pages_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9, '2026_04_03_200000_create_landing_site_settings_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10, '2026_04_03_200001_create_landing_translations_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11, '2026_04_04_120000_create_nav_menu_items_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12, '2026_04_04_140000_create_blog_categories_and_seo_fields', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13, '2026_04_05_120000_add_site_page_full_seo_fields', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14, '2026_04_06_100000_add_locale_and_english_slugs_to_content', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15, '2026_04_07_120000_create_hostvim_saas_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16, '2026_04_08_100000_saas_checkout_and_product_prices', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17, '2026_04_08_160000_create_community_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18, '2026_04_08_180000_add_community_moderation_to_users', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19, '2026_04_08_200000_community_moderation_tags_avatar', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20, '2026_04_10_120000_add_listing_performance_indexes', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21, '2026_04_11_100000_add_locale_fields_to_nav_menu_items', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22, '2026_04_11_120000_community_locale_columns', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23, '2026_04_11_140000_saas_license_product_price_eur', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24, '2026_04_30_000100_force_english_default_locale_for_landing', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25, '2026_05_19_100000_create_panel_releases_table', 1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nav_menu_items`
--
DROP TABLE IF EXISTS `nav_menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nav_menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `zone` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `href` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `open_in_new_tab` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `label_en` varchar(255),
  `href_en` varchar(255),
  PRIMARY KEY (`id`),
  KEY `nav_menu_items_zone_is_active_sort_order_index` (`zone`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nav_menu_items`
--
LOCK TABLES `nav_menu_items` WRITE;
/*!40000 ALTER TABLE `nav_menu_items` DISABLE KEYS */;
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (1, 'header', 'Özellikler', '/#features', 0, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Features', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (2, 'header', 'Fiyatlandırma', '/pricing', 1, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Pricing', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (3, 'header', 'Kurulum', '/setup', 2, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Installation', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (4, 'header', 'Dokümantasyon', '/docs', 3, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Documentation', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (5, 'header', 'Blog', '/blog', 4, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Blog', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (6, 'header', 'SSS', '/#faq', 5, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'FAQ', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (7, 'footer', 'Dokümantasyon', '/docs', 0, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Documentation', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (8, 'footer', 'Blog', '/blog', 1, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Blog', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (9, 'footer', 'SSS', '/#faq', 2, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'FAQ', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (10, 'footer', 'Yönetim girişi', '/admin/login', 3, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Admin login', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (11, 'footer', 'KVKK', '/p/kvkk', 100, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Privacy notice', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (12, 'footer', 'Gizlilik', '/p/gizlilik-politikasi', 101, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Privacy policy', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (13, 'footer', 'Çerezler', '/p/cerez-politikasi', 102, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Cookie policy', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (14, 'footer', 'Mesafeli satış', '/p/mesafeli-satis', 103, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Distance sales', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (15, 'footer', 'Kullanım koşulları', '/p/kullanim-kosullari', 104, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Terms of use', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (16, 'footer', 'SLA', '/p/sla', 105, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'SLA', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (17, 'footer', 'İade ve iptal', '/p/iade-ve-iptal', 106, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Refunds', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (18, 'footer', 'Veri merkezi', '/p/veri-merkezi', 107, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Data centre', NULL);
INSERT INTO `nav_menu_items` (`id`, `zone`, `label`, `href`, `sort_order`, `is_active`, `open_in_new_tab`, `created_at`, `updated_at`, `label_en`, `href_en`) VALUES (19, 'footer', 'Müşteri sözleşmesi', '/p/musteri-sozlesmesi', 108, 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 'Customer agreement', NULL);
/*!40000 ALTER TABLE `nav_menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `panel_releases`
--
DROP TABLE IF EXISTS `panel_releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `panel_releases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(64) NOT NULL,
  `channel` varchar(64) NOT NULL DEFAULT 'stable',
  `profile` varchar(64) NOT NULL DEFAULT 'all',
  `title` varchar(255) NOT NULL,
  `changelog` longtext NOT NULL,
  `artifact_url` varchar(255),
  `artifact_sha256` varchar(64),
  `git_tag` varchar(64),
  `min_panel_version` varchar(64),
  `requires_engine_restart` tinyint(1) NOT NULL DEFAULT '1',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `panel_releases_version_unique` (`version`),
  KEY `panel_releases_is_published_published_at_channel_profile_index` (`is_published`, `published_at`, `channel`, `profile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `panel_releases`
--

--
-- Table structure for table `password_reset_tokens`
--
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255),
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

--
-- Table structure for table `plans`
--
DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `subtitle` varchar(255),
  `price_label` varchar(255) NOT NULL,
  `price_note` varchar(255),
  `features` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plans_active_sort` (`is_active`, `sort_order`),
  UNIQUE KEY `plans_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--
LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` (`id`, `name`, `slug`, `subtitle`, `price_label`, `price_note`, `features`, `sort_order`, `is_featured`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'Freemium', 'freemium', 'Tek sunucu için sınırlı ama yeterli özellikler', '₺0', '/ay', '["1 sunucu","Temel site ve domain y\u00f6netimi","Otomatik SSL (Let''s Encrypt)","S\u0131n\u0131rl\u0131 log ve terminal eri\u015fimi"]', 10, 0, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `plans` (`id`, `name`, `slug`, `subtitle`, `price_label`, `price_note`, `features`, `sort_order`, `is_featured`, `is_active`, `created_at`, `updated_at`) VALUES (2, 'Pro Lisans', 'pro-lisans', 'Ajanslar ve yoğun trafik için', '₺?', '/ay · sunucu başına', '["S\u0131n\u0131rs\u0131z site ve domain","Geli\u015fmi\u015f g\u00fcvenlik profilleri","Detayl\u0131 metrikler ve health checks","\u00d6ncelikli destek"]', 20, 1, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `plans` (`id`, `name`, `slug`, `subtitle`, `price_label`, `price_note`, `features`, `sort_order`, `is_featured`, `is_active`, `created_at`, `updated_at`) VALUES (3, 'Vendor / White-label', 'vendor', 'Kendi markanızla sunmak isteyen paneller için', 'Özel', 'teklif', '["\u00d6zel fiyatland\u0131rma ve SLA","Marka \u00f6zelle\u015ftirme","Roadmap i\u015f birli\u011fi"]', 30, 0, 1, '2026-05-21 12:13:01', '2026-05-21 12:13:01');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saas_checkout_orders`
--
DROP TABLE IF EXISTS `saas_checkout_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_checkout_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_ref` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL DEFAULT 'en',
  `email` varchar(255) NOT NULL,
  `name` varchar(255),
  `phone` varchar(255),
  `saas_license_product_id` bigint(20) unsigned NOT NULL,
  `amount_minor` int(11) NOT NULL,
  `currency` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `stripe_checkout_session_id` varchar(255),
  `saas_license_id` bigint(20) unsigned,
  `paid_at` timestamp NULL DEFAULT NULL,
  `failure_note` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `saas_checkout_orders_saas_license_product_id_foreign` FOREIGN KEY (`saas_license_product_id`) REFERENCES `saas_license_products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `saas_checkout_orders_saas_license_id_foreign` FOREIGN KEY (`saas_license_id`) REFERENCES `saas_licenses` (`id`) ON DELETE SET NULL,
  UNIQUE KEY `saas_checkout_orders_stripe_checkout_session_id_unique` (`stripe_checkout_session_id`),
  UNIQUE KEY `saas_checkout_orders_order_ref_unique` (`order_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_checkout_orders`
--

--
-- Table structure for table `saas_customers`
--
DROP TABLE IF EXISTS `saas_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255),
  `company` varchar(255),
  `phone` varchar(255),
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saas_customers_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_customers`
--

--
-- Table structure for table `saas_license_products`
--
DROP TABLE IF EXISTS `saas_license_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_license_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255),
  `default_limits` text,
  `default_modules` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `price_try_minor` int(11),
  `price_usd_minor` int(11),
  `price_eur_minor` int(11),
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_license_products_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_license_products`
--
LOCK TABLES `saas_license_products` WRITE;
/*!40000 ALTER TABLE `saas_license_products` DISABLE KEYS */;
INSERT INTO `saas_license_products` (`id`, `code`, `name`, `description`, `default_limits`, `default_modules`, `is_active`, `sort_order`, `created_at`, `updated_at`, `price_try_minor`, `price_usd_minor`, `price_eur_minor`) VALUES (1, 'community', 'Panelze Community', 'Freemium barındırma paneli', '{"max_sites":5}', '{"vendor_panel":false,"backups_pro":false,"monitoring_advanced":false,"ai_advisor":false,"stripe_billing":false}', 1, 0, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL);
INSERT INTO `saas_license_products` (`id`, `code`, `name`, `description`, `default_limits`, `default_modules`, `is_active`, `sort_order`, `created_at`, `updated_at`, `price_try_minor`, `price_usd_minor`, `price_eur_minor`) VALUES (2, 'pro', 'Panelze Pro', 'Tam özellik + vendor', '{"max_sites":500}', '{"vendor_panel":true,"backups_pro":true,"monitoring_advanced":true,"ai_advisor":true,"stripe_billing":true,"phpmyadmin_sso":true}', 1, 10, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 199900, 19900, 18500);
/*!40000 ALTER TABLE `saas_license_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saas_licenses`
--
DROP TABLE IF EXISTS `saas_licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_licenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `license_key` varchar(255) NOT NULL,
  `saas_customer_id` bigint(20) unsigned NOT NULL,
  `saas_license_product_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `limits_override` text,
  `modules_override` text,
  `subscription_status` varchar(255),
  `subscription_renews_at` timestamp NULL DEFAULT NULL,
  `billing_provider` varchar(255),
  `billing_reference` varchar(255),
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `saas_licenses_saas_customer_id_foreign` FOREIGN KEY (`saas_customer_id`) REFERENCES `saas_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saas_licenses_saas_license_product_id_foreign` FOREIGN KEY (`saas_license_product_id`) REFERENCES `saas_license_products` (`id`) ON DELETE RESTRICT,
  KEY `saas_licenses_status_expires` (`status`, `expires_at`),
  UNIQUE KEY `saas_licenses_license_key_unique` (`license_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_licenses`
--

--
-- Table structure for table `saas_product_modules`
--
DROP TABLE IF EXISTS `saas_product_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saas_product_modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` varchar(255),
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_product_modules_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_product_modules`
--
LOCK TABLES `saas_product_modules` WRITE;
/*!40000 ALTER TABLE `saas_product_modules` DISABLE KEYS */;
INSERT INTO `saas_product_modules` (`id`, `key`, `label`, `description`, `is_paid`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (1, 'vendor_panel', 'Vendor kontrol düzlemi', NULL, 1, 1, 10, '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `saas_product_modules` (`id`, `key`, `label`, `description`, `is_paid`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (2, 'backups_pro', 'Gelişmiş yedekleme', NULL, 1, 1, 20, '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `saas_product_modules` (`id`, `key`, `label`, `description`, `is_paid`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (3, 'monitoring_advanced', 'Gelişmiş izleme', NULL, 1, 1, 30, '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `saas_product_modules` (`id`, `key`, `label`, `description`, `is_paid`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (4, 'ai_advisor', 'AI danışman', NULL, 1, 1, 40, '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `saas_product_modules` (`id`, `key`, `label`, `description`, `is_paid`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (5, 'stripe_billing', 'Stripe faturalama entegrasyonu', NULL, 1, 1, 50, '2026-05-21 12:13:01', '2026-05-21 12:13:01');
INSERT INTO `saas_product_modules` (`id`, `key`, `label`, `description`, `is_paid`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (6, 'phpmyadmin_sso', 'phpMyAdmin tek tık giriş', NULL, 1, 1, 55, '2026-05-21 12:13:01', '2026-05-21 12:13:01');
/*!40000 ALTER TABLE `saas_product_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255),
  `user_id` bigint(20) unsigned,
  `ip_address` varchar(255),
  `user_agent` text,
  `payload` text NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_last_activity_index` (`last_activity`),
  KEY `sessions_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

--
-- Table structure for table `site_pages`
--
DROP TABLE IF EXISTS `site_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `meta_description` text,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255),
  `canonical_url` varchar(255),
  `og_image` varchar(255),
  `robots` varchar(64),
  `locale` varchar(255) NOT NULL DEFAULT 'tr',
  PRIMARY KEY (`id`),
  KEY `site_pages_locale_pub_sort` (`locale`, `is_published`, `sort_order`),
  UNIQUE KEY `site_pages_locale_slug_unique` (`locale`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_pages`
--
LOCK TABLES `site_pages` WRITE;
/*!40000 ALTER TABLE `site_pages` DISABLE KEYS */;
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (1, 'setup', 'Kurulum rehberi', '## Bu rehberde neler var?

Panelze yığını **iki ana parçadan** oluşur ve üretimde birlikte çalışması gerekir:

| Bileşen | Rol |
| --- | --- |
| **Panelze Engine** | Sunucuda Nginx, PHP-FPM, sertifika ve site düzeyi işlemleri yürüten servis (genelde `127.0.0.1:9090` gibi bir adresten API dinler). |
| **Panel (Laravel)** | Tarayıcıdan yönetim, kullanıcı/rol, lisans ve Engine’e giden API çağrıları. |

Bu sayfa **genel kurulum akışını** özetler; mimari ve ürün özellikleri için [dokümantasyon](/docs) altındaki [Mimari](/docs/architecture) ve [Panelze yetenekleri](/docs/platform-features) sayfalarına bakın.

---

## Ön koşullar

### Sunucu ve sistem

- **İşletim sistemi:** Temiz veya bakımlı bir **Ubuntu 22.04 LTS** önerilir; ekibiniz başka bir LTS dağıtımı onayladıysa ona uygun paket adlarını kullanın.
- **Donanım (kılavuz):** Küçük ekipler için **2 vCPU / 4 GB RAM** genelde yeterli başlangıç değeridir; çok sayıda site veya yoğun PHP iş yükünde kaynakları artırın.
- **Erişim:** `root` veya güvenilir **sudo** yetkisi; uzak SSH için parola yerine **anahtar tabanlı giriş** tercih edin.
- **Saat ve DNS:** Sunucu saatinin doğru olması (NTP); üretim alan adlarınızın **A/AAAA** kayıtları sunucunuzu göstermeli (Let’s Encrypt ve canlı trafik için).

### Güvenlik (kurulum öncesi)

- Sunucuda yalnızca ihtiyaç duyulan portları açın (başlangıçta genelde **22**, **80**, **443**; paneli ayrı bir porttan yayınlıyorsanız onu da tanımlayın).
- Mümkünse paneli yalnızca **VPN**, sabit IP veya **geçici SSH tüneli** üzerinden erişilebilir yapın; en azından yönetim hesaplarında **2FA** ve güçlü oturum politikası kullanın.
- Kurulumdan önce bir **snapshot / yedek** alın; üzerinde önemli veri olan mevcut sunucuları “üstüne yazmadan” önce yedek bulundurun.

---

## Hızlı kurulum

Aşağıdaki **Güncel kurulum komutları** bölümünde deploy betikleriyle uyumlu tüm komutlar listelenir (tek satır, Community, Pro, elle kurulum, güncelleme ve onarım).

> **Üretim:** Betiği çalıştırmadan önce imza / checksum doğrulaması ve betik içeriğinin incelemesi şart sayılmalıdır. Test ortamında önce deneyin. Komutları yalnızca Debian/Ubuntu VPS üzerinde root veya sudo ile çalıştırın.

Kurulum betiği tipik olarak şunları yapar: `git` ile `/var/www/hostvim` altına kodu çeker, `deploy/bootstrap/install-production.sh` ile Nginx, PHP, MariaDB, Engine derlemesi ve frontend build çalıştırır. İlk yönetici bilgisi `/root/hostvim-admin-login.txt` dosyasına yazılır.

---

## Panel ortam değişkenleri (Engine bağlantısı)

Panel deposundaki `.env` dosyasında Engine ile güvenli iletişim için tipik olarak şu alanlar kullanılır:

- `ENGINE_API_URL` — Engine API taban adresi (örn. `http://127.0.0.1:9090`).
- `ENGINE_INTERNAL_KEY` — Engine ile panel arasında paylaşılan dahili anahtar.
- `ENGINE_API_SECRET` — İmzalı istekler ve web terminal JWT gibi akışlar için Engine `security` yapılandırmasıyla eşleşmelidir.

Bu değerler, aynı sunucudaki **Engine yapılandırması** ile birebir uyumlu olmalı; aksi halde site oluşturma, SSL veya terminal işlemleri başarısız olur.

Lisanslama için `LICENSE_SERVER_URL`, `LICENSE_KEY` vb. alanlar kullanılabilir; birçok kurulumda anahtar **panel içindeki lisans ekranından** girilir.

---

## Kurulum sonrası kontrol listesi

1. Panel ön yüzüne gidin ve ilk **yönetici** hesabını oluşturun (veya dağıtımınızdaki ilk oturum adımını tamamlayın).
2. HTTP(S) sonlandırıcıyı doğrulayın; üretimde **HTTPS zorunlu** olmalı.
3. Engine–panel bağlantısını test edin (ör. staging alan adıyla site açma veya panel üzerinden `GET /api/health` — yanıtta `status: ok` içeren bir JSON beklenir).
4. İlk üretim trafiğini açmadan önce **test subdomain** veya düşük riskli alan adıyla DNS, sertifika ve PHP sürümünü doğrulayın.
5. Yedekleme hedeflerini ve güncelleme planını (Engine + panel) netleştirin.

Sorun giderme: firewall, yanlış `ENGINE_*` değerleri, DNS yayılımı ve saat kayması en sık kök nedenlerdir. [Blog](/blog) ve ana sayfadaki [SSS](/#faq) bölümüne de göz atın.', 'Panelze Engine ve panel kurulumu: ön koşullar, güvenlik, ortam değişkenleri ve doğrulama adımları.', 1, 10, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (2, 'setup', 'Installation guide', '## What this guide covers

The Panelze stack has **two cooperating parts** that must be installed and configured together:

| Component | Role |
| --- | --- |
| **Panelze Engine** | Runs on the server and executes changes for Nginx, PHP-FPM, certificates, and per-site operations (typically exposes an HTTP API, e.g. on `127.0.0.1:9090`). |
| **Panel (Laravel)** | Browser UI, user/role management, licensing, and authenticated calls into the Engine. |

This page walks through the **end-to-end install flow**. For deeper architecture and product depth, read [Architecture](/docs/architecture) and [Platform capabilities](/docs/platform-features) under [Documentation](/docs).

---

## Prerequisites

### Server baseline

- **OS:** A clean, patched **Ubuntu 22.04 LTS** is recommended; other LTS distros are fine if your team already standardised on them (adjust package names and service units accordingly).
- **Sizing (rule of thumb):** **2 vCPU / 4 GB RAM** is a reasonable starting point for small fleets; increase CPU/RAM for heavy PHP workloads or very large numbers of sites.
- **Access:** `root` or passwordless **sudo**; prefer **SSH keys** over passwords for remote administration.
- **Time & DNS:** Accurate system time (NTP); production hostnames must resolve to this server (**A/AAAA**) before you rely on Let’s Encrypt and live traffic.

### Security before you install

- Open only required ports at the edge (typically **22**, **80**, **443**, plus whatever port serves the panel if not behind 443).
- Where practical, restrict the panel to a **VPN**, allow-listed IPs, or short-lived **SSH tunnels**; enforce **2FA** and strong session policy on admin-class accounts.
- Take a **snapshot or offline backup** before bootstrap scripts alter system packages or services.

---

## Quick install

Use the **Current install commands** section below — it lists every supported path (one-liner, Community, Pro, manual git clone, updates, and repair) kept in sync with `deploy/` scripts.

> **Production:** Treat every `curl | bash` as privileged code execution — verify checksums / signatures and review the script before it touches production. Always pilot in staging on Debian/Ubuntu with root or sudo.

The installer typically clones into `/var/www/hostvim`, then runs `deploy/bootstrap/install-production.sh` (Nginx, PHP, MariaDB, Engine build, frontend). First admin credentials are written to `/root/hostvim-admin-login.txt`.

---

## Panel environment (Engine linkage)

In the panel’s `.env`, the following variables commonly bind the UI to the Engine (names are illustrative but match the project’s layout):

- `ENGINE_API_URL` — Base URL for Engine API calls (e.g. `http://127.0.0.1:9090`).
- `ENGINE_INTERNAL_KEY` — Shared internal key negotiated between Engine and panel.
- `ENGINE_API_SECRET` — Must align with Engine `security` settings for signed flows (e.g. web terminal JWT).

If any of these diverge from the **live Engine configuration**, provisioning, TLS, or terminal sessions will fail mysteriously.

Licensing may involve `LICENSE_SERVER_URL`, `LICENSE_KEY`, etc.; many deployments paste the key in the **in-panel license** screen instead of keeping keys only in `.env`.

---

## Post-install checklist

1. Open the panel, complete bootstrap, and create (or import) the first **administrator** account.
2. Terminate TLS correctly at Nginx/Apache; production user traffic should be **HTTPS-only**.
3. Prove Engine connectivity with a harmless action — e.g. create a **staging site**, issue a certificate, or call `GET /api/health` on the panel (`status` should be `ok` in JSON).
4. Before production cutover, validate DNS, TLS, and PHP versions on a **throwaway subdomain**.
5. Configure backup targets/schedules and document how you will **roll Engine and panel updates**.

Troubleshooting tips: firewall rules, typoed `ENGINE_*` values, DNS/TTL drift, and clock skew are the usual culprits. See the [blog](/blog), the landing [FAQ](/#faq), and nested docs for next steps.', 'Install Panelze Engine and panel: prerequisites, hardening, environment variables, and post-install verification.', 1, 10, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (3, 'pricing', 'Fiyatlandırma özeti', 'Bu metin, **fiyatlandırma** sayfasındaki giriş bölümünü besler. Aşağıdaki plan kartları ise yönetim panelinde tanımlı kayıtlardan **otomatik üretilir**; buradaki kopya ürün yönünü özetler.

## Planların anlamı

- **Freemium** — Tek sunucu, temel site/domain/SSL/terminal akışları ve makul kota ile pilot veya küçük iş yükleri için. Ücret alınmadan başlarsınız; yükseltme aynı panel üzerinden yapılır.
- **Pro lisans** — Ajanslar, yüksek trafik veya sıkı SLA beklentisi olan müşteriler için genişletilmiş limitler, gelişmiş izleme ve öncelikli destek sütunları (kart üzerindeki maddeler veritabanından gelir).
- **Vendor / White-label** — Kendi markanızla hizmet vermek, özel fiyat, hukuki çerçeve ve yol haritası ortaklığı için satış ekibiyle **kurumsal teklif** üzerinden ilerlenir.

## Lisans ve ödeme

- Çevrimiçi ödeme **Stripe** ile yapılabilir; başarılı işlemden sonra lisans anahtarı e-posta ile iletilir.
- Anahtar çoğu zaman **panel → lisans** ekranına yapıştırılır; merkezi doğrulama için `LICENSE_SERVER_URL` yapılandırması kullanılabilir.

**Kesin sayısal limitler** (site adedi, yedek saklama, API hızı vb.) paneldeki **plan / lisans** kayıtlarında tutulur; bu sayfadaki rakamlar yalnızca özet niteliğindedir.', 'Freemium, Pro ve Vendor katmanları; limitler, lisans ve ödeme akışı özeti.', 1, 20, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (4, 'pricing', 'Pricing overview', 'This copy powers the introductory blurb on the public **pricing** page. Feature bullets on each card are generated from the database rows managed in the landing admin—what you see here is narrative context.

## How the tiers differ

- **Freemium** — One server, core hosting workflows (sites, TLS, databases, limited observability) with conservative quotas. Zero licence fee to start; upgrades keep the same panel tenant.
- **Pro licence** — Higher ceilings for agencies and demanding workloads: richer monitoring, security profiles, and support tiers (exact bullets pull from the `plans` table).
- **Vendor / white-label** — Brand packaging, custom commercials, and roadmap partnership. Reach sales for an enterprise quote when you resell Panelze to your own customers.

## Licensing & payments

- Card checkout can run through **Stripe**; successful orders trigger transactional email with licence material.
- Keys are usually pasted into the in-panel **License** screen. Large deployments can pin a central hub via `LICENSE_SERVER_URL`.

Authoritative numeric limits (sites, backup retention, API throttles) always live beside the licensing module—treat marketing tables as summaries, not contracts.', 'Freemium, Pro, and Vendor tiers; how cards, licensing, and Stripe checkout fit together.', 1, 20, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (5, 'kvkk', 'KVKK Aydınlatma Metni', '> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.

## Veri sorumlusu

**[TİCARİ ÜNVAN]** (bundan böyle “Şirket”), 6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) kapsamında veri sorumlusudur.

- **Adres:** [ADRES]
- **E-posta:** [E-POSTA]
- **MERSİS:** [MERSİS NO] · **Vergi no:** [VERGİ KİMLİK / NO]

## İşlenen kişisel veriler

Örnek kategoriler: kimlik / iletişim (ad, soyad, e-posta, telefon), müşteri işlem (sipariş, fatura, ödeme kaydı özetleri), teknik loglar (IP, tarayıcı, cihaz bilgisi, tarih-saat), destek talebi içerikleri, pazarlama izinleri (varsa).

## İşleme amaçları

Hizmetin sunulması ve sözleşmenin ifası; müşteri desteği; faturalandırma ve muhasebe; güvenlik ve kötüye kullanımın önlenmesi; yasal yükümlülüklerin yerine getirilmesi; (açık rızanız varsa) pazarlama ve iletişim.

## Hukuki sebepler

KVKK m.5/2 (c) sözleşmenin kurulması veya ifası; (ç) veri sorumlusunun hukuki yükümlülüğü; (f) meşru menfaat; (a) açık rıza (pazarlama çerezleri / bülten vb. için).

## Aktarım

Hizmetin gerektirdiği ölçüde; barındırma / ödeme / e-posta sağlayıcıları gibi **hizmet sağlayıcılarına** (yurt içi/yurt dışı, KVKK ve sözleşmelere uygun) aktarım yapılabilir. Yurt dışına aktarımda KVKK’da öngörülen şartlar uygulanır.

## Saklama süresi

İlgili mevzuatta öngörülen süreler ve meşru menfaat / sözleşme gereği gerekli süre boyunca; süre sonunda silme, yok etme veya anonimleştirme.

## Haklarınız

KVKK m.11 kapsamında; verilerinizin işlenip işlenmediğini öğrenme, bilgi talep etme, düzeltme/silme, itiraz, zararın giderilmesi talebi vb. **[E-POSTA]** üzerinden başvurabilirsiniz. Şikâyet için Kişisel Verileri Koruma Kurulu’na başvuru hakkınız saklıdır.

**Son güncelleme:** 2026-05-21', '6698 sayılı KVKK kapsamında kişisel verilerin işlenmesine ilişkin aydınlatma.', 1, 31, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (6, 'gizlilik-politikasi', 'Gizlilik Politikası', '> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.

Bu politika, **Panelze** markası altında sunulan web sitesi, demo, iletişim formları ve bağlantılı dijital hizmetler için geçerlidir.

## Toplanan bilgiler

Formlar, hesap oluşturma, destek talepleri, çerezler ve sunucu logları aracılığıyla toplanan veriler (kimlik/iletişim, teknik veriler, kullanım istatistikleri).

## Kullanım amaçları

Hizmet sunumu, güvenlik, analitik (anonim/aggregate), iletişim, yasal uyum.

## Üçüncü taraflar

Barındırma, CDN, analitik, ödeme ve e-posta sağlayıcıları. Listeler sözleşme ekinde veya talep üzerine güncellenir.

## Güvenlik

Şifreleme (TLS), erişim kontrolleri ve sınırlı yetkilendirme prensipleri uygulanır; mutlak güvenlik taahhüdü verilmez.

## Haklar ve iletişim

KVKK başvuruları **[E-POSTA]** üzerinden. Politika güncellenebilir; önemli değişiklikler sitede duyurulur.

**Son güncelleme:** 2026-05-21', 'Web sitesi ve hizmet kullanımında kişisel verilerin korunması.', 1, 32, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (7, 'cerez-politikasi', 'Çerez Politikası', '> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.

## Çerez nedir?

Çerezler, cihazınıza kaydedilen küçük metin dosyalarıdır.

## Kullandığımız çerez türleri

- **Zorunlu:** Oturum, güvenlik, dil tercihi.
- **İşlevsel:** Form ve tercih hatırlama.
- **Analitik:** Ziyaret istatistikleri (anonimleştirilmiş olabilir).
- **Pazarlama:** (Yalnızca açık rıza ile) yeniden pazarlama.

## Yönetim

Tarayıcı ayarlarından çerezleri silebilir veya engelleyebilirsiniz. Zorunlu çerezleri kapatmak bazı özellikleri etkileyebilir.

**Son güncelleme:** 2026-05-21', 'Çerez türleri, amaçları ve tercih yönetimi.', 1, 33, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (8, 'mesafeli-satis', 'Mesafeli Satış Sözleşmesi', '> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.

## Taraflar

**SATICI:** [TİCARİ ÜNVAN], [ADRES], [E-POSTA]

**ALICI:** Sipariş sırasında bildirdiği bilgilerle tanımlanan gerçek/tüzel kişi.

## Konu

Dijital ürün / lisans / abonelik (hosting paneli yazılımı ve ilişkili hizmetler) satışına ilişkin mesafeli sözleşme hükümleri.

## Cayma hakkı

Mesafeli Sözleşmeler Yönetmeliği kapsamında, **elektronik ortamda anında ifa edilen** veya dijital içerikte tüketicinin onayı ile ifaya başlanan hizmetlerde cayma hakkı istisnaları bulunabilir. Gerçek uygulama ürün tipinize (lisans, kurulum, SaaS) göre hukukçunuzca netleştirilmelidir.

## Ödeme ve fiyat

Fiyatlar sitede veya teklifte belirtilir; KDV ve yasal kesintiler ayrıca gösterilir.

## Uyuşmuzluk

Tüketici işlemlerinde Tüketici Hakem Heyeti / Tüketici Mahkemeleri yetkilidir (mevzuata göre).

**Son güncelleme:** 2026-05-21', '6502 sayılı Kanun ve Mesafeli Sözleşmeler Yönetmeliği kapsamı.', 1, 34, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (9, 'kullanim-kosullari', 'Kullanım Koşulları', '> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.

## Kapsam

Web sitesi, dokümantasyon ve **Panelze** hosting kontrol paneli yazılımının kullanımına ilişkin şartlar.

## Lisans

Yazılım, satın alınan lisans tipine (ör. tek sunucu, vendor) göre kullanılır. Kaynak kodu, tersine mühendislik, lisans dışı çoğaltma yasaktır (sözleşme ve lisans metnine tabi).

## Kabul edilebilir kullanım

Yasadışı içerik barındırma, spam, güvenlik açığı taraması (izinsiz), başkalarının sistemlerine zarar verme yasaktır. İhlal halinde hizmet askıya alınabilir veya feshedilebilir.

## Sorumluluk reddi

Yazılım “olduğu gibi” sunulur; iş sürekliliği ve üçüncü taraf hizmetlerinden doğan dolaylı zararlar için sorumluluk, mevzuatın izin verdiği azami ölçüde sınırlıdır.

## Değişiklik

Şartlar güncellenebilir; yayın tarihi sitede belirtilir.

**Son güncelleme:** 2026-05-21', 'Yazılım, web sitesi ve hizmetlerin kullanım şartları.', 1, 35, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (10, 'sla', 'Hizmet Seviyesi (SLA)', '> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.

## Hedefler (örnek — gerçek rakamları sözleşmede netleştirin)

- **Aylık erişilebilirlik hedefi:** %99,5 (planlı bakım hariç, aşağıda).
- **Planlı bakım:** Hafta içi [SAAT ARALIĞI], önceden [X] saat/gün bildirim (mümkün olduğunca).
- **Destek ilk yanıt hedefi:** İş günü içinde [X] saat (e-posta / ticket kanalı).

## Kapsam dışı

Müşteri kodu, üçüncü taraf eklentileri, DNS/ISP kesintileri, DDoS ve müşteri kaynaklı yapılandırma hataları.

## Kredi / tazminat

SLA ihlali halinde tazminat veya hizmet kredisi yalnızca **yazılı sözleşmede** açıkça düzenlenmişse geçerlidir.

**Son güncelleme:** 2026-05-21', 'Erişilebilirlik hedefleri, bakım ve destek çerçevesi.', 1, 36, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (11, 'iade-ve-iptal', 'Ücret İadesi ve İptal Koşulları', '> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.

## Genel

Ödeme tipi (kart, havale, fatura) ve ürün (lisans, kurulum, aylık SaaS) modelinize göre iade kuralları değişir; aşağıdaki çerçeve şablondur.

## Cayma ve iptal

Tüketici işlemlerinde mevzuattaki cayma süreleri uygulanır; dijital içerik / anında ifa istisnaları için Mesafeli Sözleşmeler Yönetmeliği’ne uyulur.

## Kurumsal / B2B

Cayma hakkı olmayan sözleşmelerde iptal, sözleşme feshi hükümlerine tabidir.

## İade süreci

Talepler **[E-POSTA]** ile yapılır; uygun görülen ödemeler [X] iş günü içinde aynı kanala iade edilir (banka süreleri hariç).

**Son güncelleme:** 2026-05-21', 'Cayma, iptal ve iade süreçleri.', 1, 37, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (12, 'veri-merkezi', 'Veri Merkezi ve Altyapı', '> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.

## Lokasyon

Müşteri verileri ve yedeklerin tutulduğu birincil bölge: **[ÜLKE / ŞEHİR veya bulut bölgesi]** (örn. Avrupa Birliği içi veri merkezi).

## Alt işlemciler

Barındırma, yedekleme, izleme ve e-posta için sınırlı erişimli alt işlemciler kullanılabilir. Güncel liste talep üzerine veya müşteri sözleşmesi ekinde paylaşılır.

## Güvenlik önlemleri

Erişim kontrolü, şifreleme, günlükleme ve yedekleme politikaları uygulanır.

**Son güncelleme:** 2026-05-21', 'Barındırma lokasyonu ve alt işlemci bilgisi.', 1, 38, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (13, 'musteri-sozlesmesi', 'Müşteri Hizmet Sözleşmesi', '> **Önemli:** Bu metin bilgilendirme amaçlı şablondur. Şirket unvanı, adres, iletişim, ürün ve ödeme modelinize göre **mutlaka bir hukuk danışmanı** tarafından gözden geçirilmelidir.

## Taraflar ve tanımlar

**Sağlayıcı:** [TİCARİ ÜNVAN]  
**Müşteri:** Lisans veya hizmet sözleşmesini onaylayan taraf.

## Hizmetin kapsamı

Panelze hosting kontrol paneli yazılımının sağlanması, güncellemeler (lisansa bağlı) ve belirlenen destek kanalları.

## Ücretlendirme ve ödeme

Plan, lisans veya teklif ekindeki fiyatlandırma geçerlidir; gecikmede fesih ve faiz hakları sözleşmede düzenlenir.

## Hizmetin askıya alınması

Ödeme gecikmesi, yasadışı kullanım veya güvenlik riski halinde geçici askıya alma.

## Gizlilik ve veri işleme

Kişisel veriler KVKK Aydınlatma Metni ve Gizlilik Politikası’na uygun işlenir.

## Süre ve fesih

Sözleşme süresi ve yenileme koşulları sipariş formunda; fesih bildirim süreleri sözleşmede belirtilir.

## Uygulanacak hukuk ve yetki

**[TÜRKİYE / İSTANBUL]** (örnek) — hukukçunuzca güncellenmelidir.

**Son güncelleme:** 2026-05-21', 'Lisans / SaaS hosting paneli hizmet sözleşmesi çerçevesi.', 1, 39, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'tr');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (14, 'kvkk', 'Privacy & data protection notice', '> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.

## Controller

**[LEGAL ENTITY NAME]** (“we”, “us”) is the controller of personal data for this website and related services.

- **Address:** [ADDRESS]
- **Contact:** [EMAIL]

## Data we process

Examples: identity/contact details, account and billing metadata, technical logs (IP, user agent), support messages, and—if you consent—marketing preferences.

## Purposes and legal bases

Service delivery (contract), legal obligations, legitimate interests (security, analytics in aggregated form), and consent where required (e.g. non-essential cookies / newsletters).

## Recipients

Hosting, payment, email, and analytics providers acting as processors/sub-processors, including transfers outside your country where legally permitted and safeguarded.

## Retention

As required by law and as long as necessary for the purposes described, then deleted or anonymised.

## Your rights

Depending on applicable law, you may request access, rectification, erasure, restriction, portability, or object to processing. Contact **[EMAIL]**. You may lodge a complaint with your supervisory authority.

**Last updated:** 2026-05-21', 'How we process personal data in line with applicable law.', 1, 31, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (15, 'gizlilik-politikasi', 'Privacy policy', '> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.

This policy describes how **Panelze** collects and uses personal data when you use our website, demos, and related digital services.

## What we collect

Information you submit in forms, account creation, support tickets, cookies, and server logs.

## How we use it

To provide the service, secure our systems, analyse aggregated usage, communicate with you, and comply with law.

## Sharing

With infrastructure, payment, email, and analytics vendors under appropriate agreements.

## Security

We apply technical and organisational measures (e.g. TLS, access control). No method is 100% secure.

## Contact

**[EMAIL]** · [ADDRESS]

**Last updated:** 2026-05-21', 'How we collect, use, and protect personal data.', 1, 32, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (16, 'cerez-politikasi', 'Cookie policy', '> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.

## What are cookies?

Small text files stored on your device.

## Types

Strictly necessary, functional, analytics, and—only with consent—marketing.

## Managing cookies

You can block or delete cookies in your browser. Disabling strictly necessary cookies may break parts of the site.

**Last updated:** 2026-05-21', 'Cookies we use and how to manage preferences.', 1, 33, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (17, 'mesafeli-satis', 'Distance / online sales terms', '> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.

## Parties

**Seller:** [LEGAL ENTITY NAME], [ADDRESS], [EMAIL]  
**Buyer:** The person or entity identified in the order.

## Subject

Online purchase of digital services or software licenses related to the Panelze hosting control panel.

## Withdrawal / cooling-off

Rules depend on your jurisdiction and whether delivery is instant digital content. Many laws exclude or limit withdrawal once performance has started with the buyer’s consent—confirm with counsel.

## Price and taxes

As shown at checkout or in the written quote, including applicable taxes.

## Disputes

As specified under applicable consumer or commercial law.

**Last updated:** 2026-05-21', 'Terms for online purchase of digital services or licenses.', 1, 34, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (18, 'kullanim-kosullari', 'Terms of service', '> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.

## Scope

Use of the website, documentation, and Panelze software under the purchased license.

## License

Use is limited to the purchased tier (e.g. per server, vendor). No reverse engineering, circumvention, or redistribution beyond the license.

## Acceptable use

No illegal content, spam, unauthorised intrusion attempts, or activities harming third parties. We may suspend or terminate for breach.

## Disclaimer

Software is provided as available; liability is limited to the extent permitted by law.

## Changes

We may update these terms; the publication date will be indicated.

**Last updated:** 2026-05-21', 'Rules for using our website, software, and services.', 1, 35, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (19, 'sla', 'Service level agreement (SLA)', '> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.

## Targets (examples — fix in your contract)

- **Monthly availability target:** 99.5% excluding scheduled maintenance.
- **Scheduled maintenance:** Preferably off-peak with prior notice where practical.
- **First response target (business hours):** [X] hours via email/ticket.

## Exclusions

Customer code, third-party plugins, DNS/ISP issues, DDoS, and misconfiguration by the customer.

## Remedies

Service credits or penalties apply only if explicitly stated in a signed agreement.

**Last updated:** 2026-05-21', 'Availability targets, maintenance, and support response goals.', 1, 36, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (20, 'iade-ve-iptal', 'Refunds & cancellation', '> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.

## General

Refund rules depend on payment method and product type (perpetual license, setup fee, monthly SaaS).

## Consumer rights

Local consumer laws may grant cooling-off rights with exceptions for digital content delivered immediately with consent.

## Business customers

Often governed by contract rather than consumer withdrawal rules.

## Process

Contact **[EMAIL]** with order details. Approved refunds are returned to the original payment method within [X] business days (bank timelines may apply).

**Last updated:** 2026-05-21', 'Cooling-off, cancellation, and refund rules.', 1, 37, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (21, 'veri-merkezi', 'Data centre & infrastructure', '> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.

## Location

Primary region for production data and backups: **[REGION / CLOUD AREA]** (e.g. EU).

## Subprocessors

Hosting, backups, monitoring, and email providers with limited access. An up-to-date list is available on request or in the data processing agreement.

## Security

Access controls, encryption in transit, logging, and backup policies.

**Last updated:** 2026-05-21', 'Hosting location and subprocessors (summary).', 1, 38, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
INSERT INTO `site_pages` (`id`, `slug`, `title`, `content`, `meta_description`, `is_published`, `sort_order`, `created_at`, `updated_at`, `meta_title`, `canonical_url`, `og_image`, `robots`, `locale`) VALUES (22, 'musteri-sozlesmesi', 'Customer agreement', '> **Important:** This is a template for information only. Have it reviewed by qualified legal counsel for your entity, product, and jurisdiction.

## Parties

**Provider:** [LEGAL ENTITY NAME]  
**Customer:** The entity accepting the order or master agreement.

## Service

Provision of the Panelze hosting control panel software, updates as covered by the license, and agreed support channels.

## Fees

Per order, quote, or subscription plan; late payment may trigger suspension as described in the agreement.

## Suspension

For non-payment, illegal use, or material security risk.

## Data protection

Processing of personal data follows our privacy notice and, where required, a data processing agreement.

## Term and termination

As set out in the order form or master agreement.

## Governing law

**[JURISDICTION]** — replace with counsel-approved wording.

**Last updated:** 2026-05-21', 'Framework agreement for licensing / SaaS of the hosting control panel.', 1, 39, '2026-05-21 12:13:01', '2026-05-21 12:13:01', NULL, NULL, NULL, NULL, 'en');
/*!40000 ALTER TABLE `site_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `community_banned_at` timestamp NULL DEFAULT NULL,
  `community_ban_reason` varchar(255),
  `community_admin_notes` longtext,
  `community_shadowbanned_at` timestamp NULL DEFAULT NULL,
  `avatar_url` varchar(255),
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `is_admin`, `community_banned_at`, `community_ban_reason`, `community_admin_notes`, `community_shadowbanned_at`, `avatar_url`) VALUES (1, 'Admin', 'admin@hostvim.local', '2026-05-21 12:13:01', '$2y$12$dCBKBHDHxpDGj1QZPggo.epFm1BlqMyyvpV1O8Uuncz/Oq1MxsDVS', NULL, '2026-05-21 12:13:01', '2026-05-21 12:13:01', 1, NULL, NULL, NULL, NULL, NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
