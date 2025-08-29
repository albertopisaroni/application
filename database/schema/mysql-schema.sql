/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_tokens_token_unique` (`token`),
  KEY `api_tokens_company_id_foreign` (`company_id`),
  CONSTRAINT `api_tokens_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ateco_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ateco_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ateco_codes_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT 0,
  `address` varchar(255) DEFAULT NULL,
  `cap` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT 'IT',
  `piva` varchar(255) DEFAULT NULL,
  `sdi` varchar(255) DEFAULT NULL,
  `pec` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stripe_account_id` bigint(20) unsigned DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `origin` varchar(255) NOT NULL DEFAULT 'internal',
  PRIMARY KEY (`id`),
  KEY `clients_company_id_foreign` (`company_id`),
  KEY `clients_stripe_account_id_foreign` (`stripe_account_id`),
  CONSTRAINT `clients_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clients_stripe_account_id_foreign` FOREIGN KEY (`stripe_account_id`) REFERENCES `stripe_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `openapi_id` varchar(255) DEFAULT NULL,
  `callbacks` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `piva` varchar(255) DEFAULT NULL,
  `forfettario` tinyint(1) NOT NULL DEFAULT 1,
  `regime_fiscale` varchar(4) NOT NULL DEFAULT 'RF19',
  `tax_code` varchar(255) DEFAULT NULL,
  `pec_email` varchar(255) DEFAULT NULL,
  `sdi_code` varchar(255) DEFAULT NULL,
  `legal_zip` varchar(255) DEFAULT NULL,
  `legal_city` varchar(255) DEFAULT NULL,
  `legal_province` varchar(255) DEFAULT NULL,
  `legal_country` varchar(255) NOT NULL DEFAULT 'IT',
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `primary_color` varchar(255) DEFAULT NULL,
  `secondary_color` varchar(255) DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `long_description` longtext DEFAULT NULL,
  `coefficiente` decimal(5,2) NOT NULL DEFAULT 78.00,
  `startup` tinyint(1) NOT NULL DEFAULT 0,
  `fatturato_annuale` decimal(15,2) DEFAULT NULL,
  `agevolazione_inps` tinyint(1) NOT NULL DEFAULT 0,
  `inps_type` enum('GESTIONE_SEPARATA','ARTIGIANI','COMMERCIANTI') DEFAULT NULL,
  `subscription_plan_id` bigint(20) unsigned DEFAULT NULL,
  `subscription_renewal_date` date DEFAULT NULL,
  `subscription_expiration_date` date DEFAULT NULL,
  `subscription_status` enum('active','expired','past_due','trial') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `codice_fiscale` varchar(16) DEFAULT NULL,
  `rea_ufficio` varchar(2) DEFAULT NULL,
  `rea_numero` varchar(10) DEFAULT NULL,
  `rea_stato_liquidazione` varchar(2) DEFAULT NULL,
  `legal_street` varchar(255) DEFAULT NULL,
  `legal_number` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_slug_unique` (`slug`),
  KEY `companies_subscription_plan_id_foreign` (`subscription_plan_id`),
  CONSTRAINT `companies_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_operational_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_operational_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `address` varchar(255) NOT NULL,
  `zip` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'IT',
  `label` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_operational_addresses_company_id_foreign` (`company_id`),
  CONSTRAINT `company_operational_addresses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_user_user_id_foreign` (`user_id`),
  KEY `company_user_company_id_foreign` (`company_id`),
  CONSTRAINT `company_user_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `receives_invoice_copy` tinyint(1) NOT NULL DEFAULT 1,
  `is_main_contact` tinyint(1) NOT NULL DEFAULT 0,
  `receives_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contacts_client_id_foreign` (`client_id`),
  CONSTRAINT `contacts_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `imap_host` varchar(255) NOT NULL,
  `imap_port` int(11) NOT NULL DEFAULT 993,
  `imap_username` varchar(255) NOT NULL,
  `imap_password` text NOT NULL,
  `imap_encryption` varchar(255) NOT NULL DEFAULT 'ssl',
  `smtp_host` varchar(255) NOT NULL,
  `smtp_port` int(11) NOT NULL DEFAULT 993,
  `smtp_username` varchar(255) NOT NULL,
  `smtp_password` text NOT NULL,
  `smtp_encryption` varchar(255) NOT NULL DEFAULT 'ssl',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_accounts_company_id_foreign` (`company_id`),
  CONSTRAINT `email_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `f24s`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `f24s` (
  `id` char(36) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `s3_path` varchar(500) DEFAULT NULL,
  `s3_url` varchar(500) DEFAULT NULL,
  `receipt_s3_path` varchar(500) DEFAULT NULL,
  `receipt_filename` varchar(255) DEFAULT NULL,
  `receipt_uploaded_at` timestamp NULL DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `payment_status` enum('PENDING','PARTIALLY_PAID','PAID','OVERDUE','CANCELLED') DEFAULT 'PENDING',
  `payment_reference` varchar(255) DEFAULT NULL,
  `sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sections`)),
  `reference_years` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reference_years`)),
  `notes` text DEFAULT NULL,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_status` (`company_id`,`payment_status`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_imported_at` (`imported_at`),
  CONSTRAINT `f24s_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fiscoapi_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiscoapi_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `id_sessione` varchar(255) NOT NULL,
  `stato` varchar(255) NOT NULL,
  `ente` varchar(255) NOT NULL,
  `tipo_login` varchar(255) NOT NULL,
  `qr_code` text DEFAULT NULL,
  `refresh_token` varchar(255) DEFAULT NULL,
  `response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `post_login_executed` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fiscoapi_sessions_id_sessione_unique` (`id_sessione`),
  KEY `fiscoapi_sessions_user_id_foreign` (`user_id`),
  CONSTRAINT `fiscoapi_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inps_parameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inps_parameters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `minimale_commercianti_artigiani` decimal(10,2) NOT NULL,
  `aliquota_commercianti` decimal(8,5) NOT NULL,
  `aliquota_commercianti_ridotta` decimal(8,5) NOT NULL,
  `aliquota_commercianti_maggiorata` decimal(8,5) NOT NULL,
  `aliquota_commercianti_maggiorata_ridotta` decimal(8,5) NOT NULL,
  `aliquota_artigiani` decimal(8,5) NOT NULL,
  `aliquota_artigiani_ridotta` decimal(8,5) NOT NULL,
  `aliquota_artigiani_maggiorata` decimal(8,5) NOT NULL,
  `aliquota_artigiani_maggiorata_ridotta` decimal(8,5) NOT NULL,
  `aliquota_gestione_separata` decimal(8,5) NOT NULL,
  `aliquota_gestione_separata_ridotta` decimal(8,5) NOT NULL,
  `addizionale_ivs_percentuale` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `contributo_fisso_commercianti` decimal(10,2) NOT NULL,
  `contributo_fisso_commercianti_ridotto` decimal(10,2) NOT NULL,
  `contributo_fisso_artigiani` decimal(10,2) NOT NULL,
  `contributo_fisso_artigiani_ridotto` decimal(10,2) NOT NULL,
  `contributo_maternita_annuo` decimal(10,2) NOT NULL,
  `massimale_commercianti_artigiani` decimal(10,2) NOT NULL DEFAULT 91187.00,
  `massimale_reddituale` decimal(10,2) NOT NULL DEFAULT 0.00,
  `maggiorazione_oltre_massimale` decimal(5,4) NOT NULL DEFAULT 0.0000,
  `calcolo_trimestrale_attivo` tinyint(1) NOT NULL DEFAULT 1,
  `massimale_gestione_separata` decimal(10,2) NOT NULL DEFAULT 125807.00,
  `soglia_aliquota_maggiorata` decimal(10,2) DEFAULT NULL,
  `diritto_annuale_cciaa` decimal(10,2) NOT NULL DEFAULT 53.21,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inps_parameters_year_unique` (`year`),
  KEY `inps_parameters_year_index` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_of_measure` varchar(10) NOT NULL DEFAULT 'pz',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 22.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_numberings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_numberings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `type` enum('standard','custom') NOT NULL DEFAULT 'standard',
  `prefix` varchar(255) DEFAULT NULL,
  `default_header_notes` text DEFAULT NULL,
  `default_footer_notes` text DEFAULT NULL,
  `contact_info` text DEFAULT NULL,
  `default_payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `current_number_invoice` int(10) NOT NULL DEFAULT 1,
  `current_number_autoinvoice` int(10) NOT NULL DEFAULT 1,
  `current_number_credit` int(10) NOT NULL DEFAULT 1,
  `save_notes_for_future` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `template_invoice_id` bigint(20) DEFAULT NULL,
  `template_autoinvoice_id` bigint(20) DEFAULT NULL,
  `template_credit_id` bigint(20) DEFAULT NULL,
  `logo_base64` longtext DEFAULT NULL,
  `logo_square_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_invoice_year` int(10) unsigned NOT NULL DEFAULT 2025,
  PRIMARY KEY (`id`),
  KEY `invoice_numberings_company_id_foreign` (`company_id`),
  KEY `invoice_numberings_default_payment_method_id_foreign` (`default_payment_method_id`),
  CONSTRAINT `invoice_numberings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_numberings_default_payment_method_id_foreign` FOREIGN KEY (`default_payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_passive_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_passive_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_passive_id` bigint(20) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `file_extension` varchar(10) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_hash` varchar(255) DEFAULT NULL,
  `s3_path` varchar(255) NOT NULL,
  `s3_url` varchar(255) DEFAULT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 1,
  `attachment_type` varchar(255) NOT NULL DEFAULT 'pdf',
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_passive_attach_type` (`invoice_passive_id`,`attachment_type`),
  KEY `idx_primary_attach_type` (`is_primary`,`attachment_type`),
  CONSTRAINT `invoice_passive_attachments_invoice_passive_id_foreign` FOREIGN KEY (`invoice_passive_id`) REFERENCES `invoices_passive` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_passive_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_passive_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_passive_id` bigint(20) unsigned NOT NULL,
  `line_number` int(11) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,5) NOT NULL DEFAULT 1.00000,
  `unit_of_measure` varchar(10) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `vat_rate` decimal(5,2) NOT NULL,
  `vat_amount` decimal(10,2) NOT NULL,
  `product_code` varchar(255) DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `discount_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`discount_data`)),
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_passive_items_invoice_passive_id_line_number_index` (`invoice_passive_id`,`line_number`),
  CONSTRAINT `invoice_passive_items_invoice_passive_id_foreign` FOREIGN KEY (`invoice_passive_id`) REFERENCES `invoices_passive` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_passive_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_passive_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_passive_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `iban` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_passive_payments_payment_method_id_foreign` (`payment_method_id`),
  KEY `invoice_passive_payments_invoice_passive_id_status_index` (`invoice_passive_id`,`status`),
  KEY `invoice_passive_payments_payment_date_index` (`payment_date`),
  KEY `invoice_passive_payments_due_date_index` (`due_date`),
  CONSTRAINT `invoice_passive_payments_invoice_passive_id_foreign` FOREIGN KEY (`invoice_passive_id`) REFERENCES `invoices_passive` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_passive_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_payment_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_payment_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('percent','amount') NOT NULL DEFAULT 'amount',
  `percent` tinyint(3) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_payment_schedules_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_payment_schedules_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `method` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_payments_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_payments_payment_method_id_foreign` (`payment_method_id`),
  CONSTRAINT `invoice_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `type` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `numbering_id` bigint(20) unsigned NOT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `document_type` varchar(10) NOT NULL DEFAULT 'TD01',
  `original_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `issue_date` date NOT NULL,
  `data_accoglienza_file` date DEFAULT NULL,
  `fiscal_year` year(4) NOT NULL,
  `withholding_tax` tinyint(1) NOT NULL DEFAULT 0,
  `inps_contribution` tinyint(1) NOT NULL DEFAULT 0,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `vat` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `global_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `header_notes` text DEFAULT NULL,
  `footer_notes` text DEFAULT NULL,
  `contact_info` text DEFAULT NULL,
  `save_notes_for_future` tinyint(1) NOT NULL DEFAULT 0,
  `pdf_path` varchar(250) DEFAULT NULL,
  `pdf_url` varchar(255) DEFAULT NULL,
  `sdi_uuid` char(36) DEFAULT NULL,
  `sdi_id_invio` varchar(255) DEFAULT NULL,
  `sdi_status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'Stato SDI della fattura. Valori possibili:\n            - pending: In attesa di invio\n            - sent: Inviata al SDI\n            - received: Ricevuta dal SDI (accettata)\n            - delivered: Consegnata al destinatario (RC)\n            - rejected: Rifiutata/Scartata dal SDI (NS)\n            - delivery_failed: Mancata consegna (MC)\n            - error: Errore generico\n            - processed: Processata\n            - unknown: Stato sconosciuto',
  `sdi_error` varchar(255) DEFAULT NULL,
  `sdi_error_description` text DEFAULT NULL,
  `legal_storage_status` varchar(50) DEFAULT NULL COMMENT 'Stato conservazione sostitutiva: null, pending, stored, failed',
  `legal_storage_uuid` varchar(255) DEFAULT NULL COMMENT 'UUID della ricevuta di conservazione',
  `legal_storage_completed_at` timestamp NULL DEFAULT NULL COMMENT 'Data completamento conservazione',
  `legal_storage_error` text DEFAULT NULL COMMENT 'Messaggio di errore conservazione se presente',
  `notification_type` varchar(10) DEFAULT NULL COMMENT 'Tipo notifica SDI: RC (Ricevuta Consegna), NS (Notifica Scarto), MC (Mancata Consegna)',
  `notification_file_name` varchar(255) DEFAULT NULL COMMENT 'Nome file della notifica ricevuta',
  `sdi_identificativo` varchar(50) DEFAULT NULL COMMENT 'Identificativo SDI dalla ricevuta',
  `sdi_data_ricezione` timestamp NULL DEFAULT NULL COMMENT 'Data/ora ricezione dal SDI',
  `sdi_data_consegna` timestamp NULL DEFAULT NULL COMMENT 'Data/ora consegna al destinatario',
  `sdi_message_id` varchar(50) DEFAULT NULL COMMENT 'Message ID della notifica SDI',
  `sdi_destinatario` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dati destinatario dalla ricevuta' CHECK (json_valid(`sdi_destinatario`)),
  `sdi_sent_at` timestamp NULL DEFAULT NULL,
  `sdi_received_at` timestamp NULL DEFAULT NULL,
  `sdi_attempt` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Numero di tentativi di invio al SDI',
  `imported_from_ae` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_uuid_unique` (`uuid`),
  KEY `invoices_company_id_foreign` (`company_id`),
  KEY `invoices_client_id_foreign` (`client_id`),
  KEY `invoices_numbering_id_foreign` (`numbering_id`),
  KEY `invoices_sdi_status_index` (`sdi_status`),
  KEY `invoices_payment_method_id_foreign` (`payment_method_id`),
  KEY `invoices_original_invoice_id_foreign` (`original_invoice_id`),
  KEY `invoices_sdi_status_received_at_index` (`sdi_status`,`sdi_received_at`),
  KEY `invoices_legal_storage_status_index` (`legal_storage_status`),
  KEY `invoices_legal_storage_completed_at_index` (`legal_storage_completed_at`),
  KEY `invoices_notification_type_index` (`notification_type`),
  KEY `invoices_sdi_identificativo_index` (`sdi_identificativo`),
  KEY `invoices_sdi_data_consegna_index` (`sdi_data_consegna`),
  CONSTRAINT `invoices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_numbering_id_foreign` FOREIGN KEY (`numbering_id`) REFERENCES `invoice_numberings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_original_invoice_id_foreign` FOREIGN KEY (`original_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices_passive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices_passive` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `document_type` varchar(10) NOT NULL DEFAULT 'TD01',
  `original_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `issue_date` date NOT NULL,
  `data_accoglienza_file` date DEFAULT NULL,
  `fiscal_year` year(4) NOT NULL,
  `withholding_tax` tinyint(1) NOT NULL DEFAULT 0,
  `inps_contribution` tinyint(1) NOT NULL DEFAULT 0,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `vat` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `global_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `header_notes` text DEFAULT NULL,
  `footer_notes` text DEFAULT NULL,
  `contact_info` text DEFAULT NULL,
  `pdf_path` varchar(250) DEFAULT NULL,
  `pdf_url` varchar(255) DEFAULT NULL,
  `xml_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`xml_payload`)),
  `sdi_uuid` char(36) DEFAULT NULL,
  `sdi_filename` varchar(255) DEFAULT NULL,
  `sdi_status` varchar(255) NOT NULL DEFAULT 'received',
  `sdi_error` varchar(255) DEFAULT NULL,
  `sdi_error_description` text DEFAULT NULL,
  `sdi_received_at` timestamp NULL DEFAULT NULL,
  `sdi_processed_at` timestamp NULL DEFAULT NULL,
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `imported_from_callback` tinyint(1) NOT NULL DEFAULT 1,
  `has_attachments` tinyint(1) NOT NULL DEFAULT 0,
  `attachments_count` int(11) NOT NULL DEFAULT 0,
  `primary_attachment_path` varchar(255) DEFAULT NULL,
  `primary_attachment_filename` varchar(255) DEFAULT NULL,
  `attachments_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments_summary`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_passive_uuid_unique` (`uuid`),
  UNIQUE KEY `invoices_passive_sdi_uuid_unique` (`sdi_uuid`),
  KEY `invoices_passive_supplier_id_foreign` (`supplier_id`),
  KEY `invoices_passive_original_invoice_id_foreign` (`original_invoice_id`),
  KEY `invoices_passive_payment_method_id_foreign` (`payment_method_id`),
  KEY `invoices_passive_company_id_supplier_id_index` (`company_id`,`supplier_id`),
  KEY `invoices_passive_issue_date_index` (`issue_date`),
  KEY `invoices_passive_sdi_status_index` (`sdi_status`),
  KEY `invoices_passive_is_processed_is_paid_index` (`is_processed`,`is_paid`),
  KEY `invoices_passive_has_attachments_index` (`has_attachments`),
  KEY `invoices_passive_attachments_count_index` (`attachments_count`),
  CONSTRAINT `invoices_passive_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_passive_original_invoice_id_foreign` FOREIGN KEY (`original_invoice_id`) REFERENCES `invoices_passive` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_passive_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_passive_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meta_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meta_domains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `source_url` varchar(255) DEFAULT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_domains_domain_unique` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meta_pivas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meta_pivas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cap` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `piva` varchar(255) NOT NULL,
  `sdi` varchar(255) DEFAULT NULL,
  `pec` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_pivas_piva_unique` (`piva`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `type` varchar(200) NOT NULL,
  `iban` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sdi_code` varchar(4) NOT NULL DEFAULT 'MP05',
  PRIMARY KEY (`id`),
  KEY `payment_methods_company_id_foreign` (`company_id`),
  CONSTRAINT `payment_methods_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `stripe_price_id` varchar(255) NOT NULL,
  `unit_amount` int(11) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'eur',
  `interval` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prices_stripe_price_id_unique` (`stripe_price_id`),
  KEY `prices_product_id_foreign` (`product_id`),
  CONSTRAINT `prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stripe_account_id` bigint(20) unsigned NOT NULL,
  `stripe_product_id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_stripe_product_id_unique` (`stripe_product_id`),
  KEY `products_stripe_account_id_foreign` (`stripe_account_id`),
  CONSTRAINT `products_stripe_account_id_foreign` FOREIGN KEY (`stripe_account_id`) REFERENCES `stripe_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `step` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `project_type` varchar(100) DEFAULT NULL,
  `registered` tinyint(1) NOT NULL DEFAULT 0,
  `contacted` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `piva` varchar(255) DEFAULT NULL,
  `company_name` varchar(250) DEFAULT NULL,
  `company_address` varchar(250) DEFAULT NULL,
  `company_cf` varchar(250) DEFAULT NULL,
  `cf` varchar(20) DEFAULT NULL,
  `residenza` varchar(250) DEFAULT NULL,
  `indirizzo` varchar(250) DEFAULT NULL,
  `provincia` varchar(50) DEFAULT NULL,
  `cap` varchar(5) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place_code` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `document_front` varchar(250) DEFAULT NULL,
  `document_back` varchar(250) DEFAULT NULL,
  `step_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `utm_source` varchar(100) DEFAULT NULL,
  `utm_medium` varchar(100) DEFAULT NULL,
  `utm_campaign` varchar(100) DEFAULT NULL,
  `utm_content` varchar(100) DEFAULT NULL,
  `ab_variant` varchar(100) DEFAULT NULL,
  `page_time` int(10) unsigned DEFAULT NULL,
  `scroll_time` int(10) unsigned DEFAULT NULL,
  `scroll_bounce` int(10) unsigned DEFAULT NULL,
  `mouse_movement` int(10) unsigned DEFAULT NULL,
  `form_time_fullname` int(10) unsigned DEFAULT NULL,
  `form_time_email` int(10) unsigned DEFAULT NULL,
  `form_time_phone` int(10) unsigned DEFAULT NULL,
  `form_autofill_fullname` tinyint(1) DEFAULT NULL,
  `form_autofill_email` tinyint(1) DEFAULT NULL,
  `form_autofill_phone` tinyint(1) DEFAULT NULL,
  `section_time_fatture_e_pagamenti` int(10) unsigned NOT NULL DEFAULT 0,
  `section_time_flussi_di_lavoro` int(10) unsigned NOT NULL DEFAULT 0,
  `section_time_tasse_e_scadenze` int(10) unsigned NOT NULL DEFAULT 0,
  `section_time_il_ai_automazioni_intelligenti` int(10) unsigned NOT NULL DEFAULT 0,
  `section_time_il_nostro_team_e_qui_per_te` int(10) unsigned NOT NULL DEFAULT 0,
  `section_time_con_noi_essere_freelance` int(10) unsigned NOT NULL DEFAULT 0,
  `section_time_newo_e_pensato_per_farti_crescere` int(10) unsigned NOT NULL DEFAULT 0,
  `section_time_newo_e_gia_la_scelta` int(10) unsigned NOT NULL DEFAULT 0,
  `behavior_profile` varchar(255) DEFAULT NULL,
  `behavior_score` int(11) DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registrations_uuid_unique` (`uuid`),
  KEY `registrations_user_id_foreign` (`user_id`),
  CONSTRAINT `registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stripe_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stripe_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `stripe_user_id` varchar(255) NOT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `access_token` varchar(255) NOT NULL,
  `refresh_token` varchar(255) DEFAULT NULL,
  `default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `invoice_numbering_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stripe_accounts_company_id_foreign` (`company_id`),
  KEY `stripe_accounts_invoice_numbering_id_foreign` (`invoice_numbering_id`),
  CONSTRAINT `stripe_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stripe_accounts_invoice_numbering_id_foreign` FOREIGN KEY (`invoice_numbering_id`) REFERENCES `invoice_numberings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price_monthly` decimal(8,2) NOT NULL DEFAULT 0.00,
  `price_yearly` decimal(8,2) DEFAULT NULL,
  `max_users` int(11) DEFAULT NULL,
  `max_storage_gb` int(11) DEFAULT NULL,
  `max_invoices` int(11) DEFAULT NULL,
  `max_clients` int(11) DEFAULT NULL,
  `max_documents` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_plans_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `stripe_account_id` bigint(20) unsigned DEFAULT NULL,
  `stripe_subscription_id` varchar(255) NOT NULL,
  `price_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `start_date` timestamp NULL DEFAULT NULL,
  `current_period_end` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `quantity` int(10) unsigned DEFAULT NULL,
  `unit_amount` int(10) unsigned DEFAULT NULL,
  `subtotal_amount` int(10) unsigned DEFAULT NULL,
  `discount_amount` int(10) unsigned DEFAULT NULL,
  `final_amount` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_stripe_subscription_id_unique` (`stripe_subscription_id`),
  KEY `subscriptions_client_id_foreign` (`client_id`),
  KEY `subscriptions_price_id_foreign` (`price_id`),
  KEY `subscriptions_company_id_foreign` (`company_id`),
  KEY `subscriptions_stripe_account_id_foreign` (`stripe_account_id`),
  CONSTRAINT `subscriptions_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_price_id_foreign` FOREIGN KEY (`price_id`) REFERENCES `prices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscriptions_stripe_account_id_foreign` FOREIGN KEY (`stripe_account_id`) REFERENCES `stripe_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `taxes` (
  `id` char(36) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `f24_id` char(36) DEFAULT NULL,
  `section_type` enum('erario','inps','imu','altri') DEFAULT NULL,
  `tax_year` int(11) NOT NULL,
  `payment_year` int(11) NOT NULL,
  `tax_type` enum('IMPOSTA_SOSTITUTIVA_SALDO','IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO','IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO','IMPOSTA_SOSTITUTIVA_CREDITO','INPS_SALDO','INPS_PRIMO_ACCONTO','INPS_SECONDO_ACCONTO','INPS_TERZO_ACCONTO','INPS_QUARTO_ACCONTO','INPS_CREDITO','INPS_FISSI_SALDO','INPS_FISSI_PRIMO_ACCONTO','INPS_FISSI_SECONDO_ACCONTO','INPS_FISSI_TERZO_ACCONTO','INPS_FISSI_QUARTO_ACCONTO','INPS_PERCENTUALI_SALDO','INPS_PERCENTUALI_PRIMO_ACCONTO','INPS_PERCENTUALI_SECONDO_ACCONTO','SANZIONI','INTERESSI','DIRITTO_ANNUALE_CCIAA') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `tax_code` varchar(10) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `payment_status` enum('PENDING','PAID','OVERDUE','CANCELLED','CREDIT') NOT NULL DEFAULT 'PENDING',
  `f24_url` varchar(500) DEFAULT NULL,
  `f24_generated_at` timestamp NULL DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_manual` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica se la tassa è stata caricata manualmente (true) o calcolata automaticamente (false)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_year` (`company_id`,`tax_year`,`payment_year`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_f24_id` (`f24_id`),
  KEY `idx_section_type` (`section_type`),
  KEY `idx_is_manual` (`is_manual`),
  CONSTRAINT `taxes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `taxes_f24_id_foreign` FOREIGN KEY (`f24_id`) REFERENCES `f24s` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stripe_account_id` bigint(20) unsigned NOT NULL,
  `stripe_charge_id` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'eur',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `subscription_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_stripe_charge_id_unique` (`stripe_charge_id`),
  KEY `transactions_stripe_account_id_foreign` (`stripe_account_id`),
  KEY `transactions_subscription_id_foreign` (`subscription_id`),
  CONSTRAINT `transactions_stripe_account_id_foreign` FOREIGN KEY (`stripe_account_id`) REFERENCES `stripe_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `invitation_token` varchar(255) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `current_team_id` bigint(20) unsigned DEFAULT NULL,
  `profile_photo_path` varchar(2048) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_company_id` bigint(20) unsigned DEFAULT NULL,
  `current_email_account_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_current_company_id_foreign` (`current_company_id`),
  CONSTRAINT `users_current_company_id_foreign` FOREIGN KEY (`current_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_04_08_185856_add_two_factor_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_04_08_185905_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_04_08_191828_create_subscription_plans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_04_08_192140_create_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_04_08_193704_create_company_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_04_08_193845_create_company_operational_addresses_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_04_08_193946_add_current_company_id_to_users_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_04_08_234206_create_ateco_codes_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_04_09_170203_create_clients_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_04_09_170316_create_invoices_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_04_09_171120_add_client_id_to_invoices_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_04_09_181137_create_invoice_numberings_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_04_09_185005_add_last_invoice_year_to_invoice_numberings',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_04_09_200141_create_invoice_items_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_04_09_200607_create_bank_accounts_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_04_09_200608_update_invoices_table_add_fields',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_04_09_211840_add_fields_to_clients_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_04_09_212001_create_contacts_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_04_09_214817_add_default_notes_to_invoice_numberings_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_04_10_153215_create_api_tokens_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_04_10_160213_add_invitation_token_to_users_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_04_15_212535_create_leads_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_04_17_225535_create_email_accounts_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_04_20_184746_create_registrations_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_04_20_224721_add_signed_at_to_registrations',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_04_20_230329_add_step_to_registrations_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_04_20_230536_add_step_history_to_registrations_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_04_22_000410_add_tracking_fields_to_registrations_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_04_22_005408_add_behavior_fields_to_registrations',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_04_25_163429_create_stripe_accounts_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_04_27_213703_create_clients_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_04_27_213703_create_products_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_04_27_213704_create_prices_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_04_27_213704_create_subscriptions_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_04_27_213704_create_transactions_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_04_27_214611_add_subscription_id_to_transactions_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_04_28_175518_drop_client_duplicate_columns_from_invoices_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_04_28_183712_add_fiscale_fields_to_companies_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_04_28_211447_add_sdi_code_to_payment_methods',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_04_29_114348_add_rea_to_companies_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_04_29_121917_add_legal_street_and_legal_number_to_companies_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_04_29_133406_add_sdi_attempt_to_invoices_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_04_29_164552_create_invoice_payment_schedules_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_04_29_170801_add_document_type_to_invoices_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_04_29_171906_add_default_payment_method_id_to_invoice_numberings',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_04_29_174521_add_uuid_and_pdf_url_to_invoices_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_04_29_194823_add_payment_method_id_to_invoices_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_04_30_134016_add_openapi_id_to_companies_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_04_30_134629_remove_rea_from_companies_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_04_30_150449_add_callbacks_to_companies_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_04_30_165131_update_contacts_nullable_name_surname',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_05_05_185117_add_primary_to_contacts_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_05_05_192510_create_invoice_payments_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_05_05_231620_add_logo_base64_square_to_invoice_numberings_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_05_05_235825_update_logo_column_on_invoice_numberings_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_05_06_004244_add_fiscal_fields_to_companies_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_05_06_164510_add_company_id_to_subscriptions_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_05_07_135116_add_billing_fields_to_subscriptions_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_05_07_145713_remove_email_and_phone_from_clients_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_05_07_150709_add_logo_url_to_clients_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_05_07_212252_add_meta_domains_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_05_08_011234_add_invoice_numbering_id_to_stripe_accounts_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_07_09_134844_create_meta_pivas_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_07_09_153139_create_fiscoapi_sessions_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_07_09_171519_add_post_login_executed_to_fiscoapi_sessions_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_07_10_160605_add_domain_to_clients_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_07_10_160619_add_domain_to_meta_pivas_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_07_10_182428_add_ae_fields_to_invoices_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_07_10_224853_remove_unique_constraint_from_invoice_number',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_07_11_134620_add_type_to_invoice_templates_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_07_11_134623_remove_templateid_and_currentnumber_from_invoice_numberings_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_07_11_170256_add_original_invoice_id_to_invoices_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_07_11_181920_add_contact_info_to_invoice_numberings_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_07_11_181938_add_contact_info_to_invoices_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_01_03_140000_add_payment_method_id_to_invoice_payments_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_07_24_143413_add_payment_method_id_to_invoice_payments_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_08_13_114427_add_account_name_to_stripe_accounts_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_08_13_114500_remove_unique_constraint_from_stripe_accounts_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_08_13_130913_remove_unique_constraint_from_stripe_accounts_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_08_13_131500_add_stripe_account_id_to_subscriptions_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2025_08_13_132656_add_stripe_account_id_to_subscriptions_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_08_14_111517_create_invoices_passive_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_08_14_112131_create_invoice_passive_items_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2025_08_14_112142_create_invoice_passive_payments_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2025_08_14_122201_create_invoice_passive_attachments_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_08_14_123116_add_attachment_fields_to_invoices_passive_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_08_21_162448_add_agevolazione_inps_to_companies_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_08_21_163504_create_taxes_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_08_21_163559_add_regime_fiscale_to_companies_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_08_21_172459_add_inps_terzo_quarto_acconto_to_taxes_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2025_08_21_175344_replace_gestione_separata_with_inps_type_in_companies_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_08_21_180614_remove_unique_tax_record_constraint_from_taxes_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_08_21_202757_remove_total_revenue_from_companies_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_08_21_213819_add_new_inps_tax_types_to_taxes_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_08_22_104731_add_sanctions_and_interests_to_tax_types',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_08_22_154639_add_cciaa_diritto_annuale_to_tax_types',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_08_25_111306_create_inps_parameters_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2025_08_25_130358_add_detailed_inps_parameters_to_inps_parameters_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2025_08_25_131832_update_inps_aliquote_precision',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2025_08_27_110121_create_f24s_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2025_08_27_110141_add_f24_id_to_taxes_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2025_08_27_110150_add_receipt_fields_to_f24s_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2025_08_27_175347_update_f24s_payment_status_enum',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2025_08_27_180353_add_payment_reference_to_f24s_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2025_08_28_131414_add_is_manual_to_taxes_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2025_08_29_074405_add_sdi_status_documentation_to_invoices_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2025_08_29_074559_add_legal_storage_status_to_invoices_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2025_08_29_075113_add_customer_notification_fields_to_invoices_table',40);
