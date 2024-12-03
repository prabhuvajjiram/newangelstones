-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: angelstones_quotes_new
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
-- Table structure for table `base_products`
--

DROP TABLE IF EXISTS `base_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `base_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(10) NOT NULL,
  `model` varchar(10) NOT NULL,
  `size_inches` int(11) NOT NULL,
  `length_inches` decimal(10,2) NOT NULL,
  `breadth_inches` decimal(10,2) NOT NULL,
  `is_premium` tinyint(1) DEFAULT 0,
  `description` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `base_products`
--

LOCK TABLES `base_products` WRITE;
/*!40000 ALTER TABLE `base_products` DISABLE KEYS */;
INSERT INTO `base_products` VALUES (1,'BASE-8-P1','P1',8,0.00,0.00,0,NULL,57.72,'2024-11-28 21:57:04'),(2,'BASE-8-PM','P/M',8,0.00,0.00,0,NULL,64.38,'2024-11-28 21:57:04'),(3,'BASE-6-P1','P1',6,0.00,0.00,0,NULL,44.44,'2024-11-28 21:57:04'),(4,'BASE-6-PM','P/M',6,0.00,0.00,0,NULL,48.84,'2024-11-28 21:57:04'),(5,'BASE-10-P1','P1',10,0.00,0.00,0,NULL,68.82,'2024-11-28 21:57:04'),(6,'BASE-10-PM','P/M',10,0.00,0.00,0,NULL,82.14,'2024-11-28 21:57:04');
/*!40000 ALTER TABLE `base_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_results`
--

DROP TABLE IF EXISTS `campaign_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `metric_name` varchar(50) NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `date_recorded` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  CONSTRAINT `campaign_results_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_results`
--

LOCK TABLES `campaign_results` WRITE;
/*!40000 ALTER TABLE `campaign_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('email','sms','social','print','other') NOT NULL,
  `status` enum('draft','scheduled','active','completed','cancelled') DEFAULT 'draft',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `target_audience` text DEFAULT NULL,
  `success_metrics` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaigns`
--

LOCK TABLES `campaigns` WRITE;
/*!40000 ALTER TABLE `campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commission_rates`
--

DROP TABLE IF EXISTS `commission_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commission_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rate_name` varchar(100) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commission_rates`
--

LOCK TABLES `commission_rates` WRITE;
/*!40000 ALTER TABLE `commission_rates` DISABLE KEYS */;
INSERT INTO `commission_rates` VALUES (1,'Standard',10.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(2,'Premium',15.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(3,'Special',20.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(4,'No Commision',0.00,'2024-11-28 21:57:03','2024-11-28 21:57:03');
/*!40000 ALTER TABLE `commission_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_communications`
--

DROP TABLE IF EXISTS `customer_communications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('email','phone','meeting','other') NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `customer_communications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `customer_communications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_communications`
--

LOCK TABLES `customer_communications` WRITE;
/*!40000 ALTER TABLE `customer_communications` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_communications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_documents`
--

DROP TABLE IF EXISTS `customer_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `quote_id` int(11) DEFAULT NULL,
  `document_type` enum('quote','contract','design','invoice','other') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `quote_id` (`quote_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `customer_documents_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `customer_documents_ibfk_2` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`),
  CONSTRAINT `customer_documents_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_documents`
--

LOCK TABLES `customer_documents` WRITE;
/*!40000 ALTER TABLE `customer_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_notes`
--

DROP TABLE IF EXISTS `customer_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `note_type` enum('general','follow_up','quote','payment') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `customer_notes_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `customer_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_notes`
--

LOCK TABLES `customer_notes` WRITE;
/*!40000 ALTER TABLE `customer_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_preferences`
--

DROP TABLE IF EXISTS `customer_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `preference_key` varchar(50) NOT NULL,
  `preference_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer_preference` (`customer_id`,`preference_key`),
  CONSTRAINT `customer_preferences_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_preferences`
--

LOCK TABLES `customer_preferences` WRITE;
/*!40000 ALTER TABLE `customer_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `lead_score` int(11) DEFAULT 0,
  `lead_source_id` int(11) DEFAULT NULL,
  `last_campaign_id` int(11) DEFAULT NULL,
  `preferred_contact_method` enum('email','phone','sms','mail') DEFAULT 'email',
  `budget_range` varchar(50) DEFAULT NULL,
  `decision_timeframe` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive','potential','converted') DEFAULT 'potential',
  `total_quotes` int(11) DEFAULT 0,
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `last_contact_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lead_source_id` (`lead_source_id`),
  KEY `last_campaign_id` (`last_campaign_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`lead_source_id`) REFERENCES `lead_sources` (`id`),
  CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`last_campaign_id`) REFERENCES `campaigns` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'Deepa Anbalagan','da@theangelstones.com','6129994558','5540 Centerview Dr','Raleigh','North Carolina','27606','',45,NULL,NULL,'email',NULL,NULL,'potential',0,0.00,NULL,'2024-11-28 21:57:42','2024-12-01 00:45:31');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `template_type` enum('quote','follow_up','welcome','general') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
INSERT INTO `email_templates` VALUES (1,'Quote Follow-up','Following up on your recent quote','Dear {customer_name},\n\nThank you for your interest in our products. I wanted to follow up regarding the quote ({quote_number}) we prepared for you on {quote_date}.\n\nPlease let me know if you have any questions or if you would like to proceed with the order.\n\nBest regards,\n{user_name}','follow_up','2024-11-28 21:57:03','2024-11-28 21:57:03'),(2,'New Quote','Your Quote from Angel Stones','Dear {customer_name},\n\nThank you for your interest in our products. Please find attached your quote ({quote_number}).\n\nIf you have any questions, please don\'t hesitate to contact us.\n\nBest regards,\n{user_name}','quote','2024-11-28 21:57:03','2024-11-28 21:57:03');
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `follow_ups`
--

DROP TABLE IF EXISTS `follow_ups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `follow_ups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `follow_up_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `quote_id` (`quote_id`),
  KEY `customer_id` (`customer_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `follow_ups_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `follow_ups_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `follow_ups_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `follow_ups`
--

LOCK TABLES `follow_ups` WRITE;
/*!40000 ALTER TABLE `follow_ups` DISABLE KEYS */;
/*!40000 ALTER TABLE `follow_ups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_sources`
--

DROP TABLE IF EXISTS `lead_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_sources`
--

LOCK TABLES `lead_sources` WRITE;
/*!40000 ALTER TABLE `lead_sources` DISABLE KEYS */;
INSERT INTO `lead_sources` VALUES (1,'Website','Leads from company website',1,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(2,'Referral','Customer referrals',1,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(3,'Google Ads','Google advertising campaigns',1,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(4,'Facebook','Facebook social media',1,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(5,'Instagram','Instagram social media',1,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(6,'Trade Show','Trade show contacts',1,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(7,'Direct Mail','Direct mail campaigns',1,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(8,'Cold Call','Cold calling campaigns',1,'2024-11-28 21:57:03','2024-11-28 21:57:03');
/*!40000 ALTER TABLE `lead_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marker_products`
--

DROP TABLE IF EXISTS `marker_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marker_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(10) NOT NULL,
  `model` varchar(10) NOT NULL,
  `square_feet` decimal(4,1) NOT NULL,
  `length_inches` decimal(10,2) NOT NULL,
  `breadth_inches` decimal(10,2) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `thickness_inches` decimal(10,2) NOT NULL DEFAULT 4.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marker_products`
--

LOCK TABLES `marker_products` WRITE;
/*!40000 ALTER TABLE `marker_products` DISABLE KEYS */;
INSERT INTO `marker_products` VALUES (1,'MAR-3-P1','P1',3.0,0.00,0.00,NULL,26.64,'2024-11-28 21:57:04',4.00),(2,'MAR-4-P1','P1',4.0,0.00,0.00,NULL,32.19,'2024-11-28 21:57:04',4.00);
/*!40000 ALTER TABLE `marker_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_components`
--

DROP TABLE IF EXISTS `price_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price_components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `component_name` varchar(100) NOT NULL,
  `base_rate` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_components`
--

LOCK TABLES `price_components` WRITE;
/*!40000 ALTER TABLE `price_components` DISABLE KEYS */;
INSERT INTO `price_components` VALUES (1,'Width Polish',10.00,'Base rate for width polishing','2024-11-28 21:57:04','2024-11-28 21:57:04'),(2,'Edge Polish',15.00,'Base rate for edge polishing','2024-11-28 21:57:04','2024-11-28 21:57:04'),(3,'Beveling',20.00,'Base rate for beveling','2024-11-28 21:57:04','2024-11-28 21:57:04'),(4,'Lamination',25.00,'Base rate for lamination','2024-11-28 21:57:04','2024-11-28 21:57:04'),(5,'Engraving',30.00,'Base rate for engraving','2024-11-28 21:57:04','2024-11-28 21:57:04');
/*!40000 ALTER TABLE `price_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quote_items`
--

DROP TABLE IF EXISTS `quote_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quote_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `product_type` varchar(50) NOT NULL,
  `model` varchar(20) NOT NULL,
  `size` varchar(20) NOT NULL,
  `color_id` int(11) NOT NULL,
  `length` decimal(10,2) NOT NULL,
  `breadth` decimal(10,2) NOT NULL,
  `sqft` decimal(10,2) NOT NULL,
  `cubic_feet` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `quote_id` (`quote_id`),
  KEY `color_id` (`color_id`),
  CONSTRAINT `quote_items_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `quote_items_ibfk_2` FOREIGN KEY (`color_id`) REFERENCES `stone_color_rates` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quote_items`
--

LOCK TABLES `quote_items` WRITE;
/*!40000 ALTER TABLE `quote_items` DISABLE KEYS */;
INSERT INTO `quote_items` VALUES (12,12,'sertop','P5','6.00',1,24.00,20.00,3.33,1.67,10,46.62,466.20,10.00,'2024-11-30 16:36:27'),(14,14,'sertop','P3','6.00',1,20.00,20.00,2.78,1.39,10,129.50,1295.00,0.00,'2024-11-30 21:09:38'),(18,18,'sertop','P5','6.00',1,24.00,20.00,3.33,1.67,10,155.40,1554.00,0.00,'2024-11-30 21:40:41'),(19,19,'base','P/M','6.00',1,32.00,10.00,2.22,1.11,10,108.53,1085.33,0.00,'2024-11-30 21:50:01'),(20,19,'sertop','P5','6.00',1,24.00,20.00,3.33,1.67,10,155.40,1554.00,0.00,'2024-11-30 21:50:01');
/*!40000 ALTER TABLE `quote_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quote_status_history`
--

DROP TABLE IF EXISTS `quote_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quote_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `quote_id` (`quote_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `quote_status_history_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `quote_status_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quote_status_history`
--

LOCK TABLES `quote_status_history` WRITE;
/*!40000 ALTER TABLE `quote_status_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `quote_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotes`
--

DROP TABLE IF EXISTS `quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_number` varchar(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `valid_until` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_number` (`quote_number`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `quotes_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotes`
--

LOCK TABLES `quotes` WRITE;
/*!40000 ALTER TABLE `quotes` DISABLE KEYS */;
INSERT INTO `quotes` VALUES (12,'AS-2024-00001',1,NULL,512.82,10.00,46.62,'pending',NULL,'2024-11-30 16:36:27','2024-11-30 16:36:27'),(14,'Q202411304711',1,NULL,12950.00,0.00,0.00,'pending','2024-12-30','2024-11-30 21:09:38','2024-11-30 21:09:38'),(18,'Q202411303105',1,NULL,1709.40,10.00,155.40,'pending','2024-12-30','2024-11-30 21:40:41','2024-11-30 21:40:41'),(19,'Q202411301427',1,NULL,2903.27,10.00,263.93,'pending','2024-12-30','2024-11-30 21:50:01','2024-11-30 21:50:01');
/*!40000 ALTER TABLE `quotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reminder_settings`
--

DROP TABLE IF EXISTS `reminder_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reminder_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reminder_type` enum('quote_follow_up','task_due','campaign_start','customer_birthday') NOT NULL,
  `days_before` int(11) NOT NULL DEFAULT 1,
  `is_email` tinyint(1) DEFAULT 1,
  `is_notification` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reminder_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reminder_settings`
--

LOCK TABLES `reminder_settings` WRITE;
/*!40000 ALTER TABLE `reminder_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `reminder_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sertop_products`
--

DROP TABLE IF EXISTS `sertop_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sertop_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(100) NOT NULL,
  `size_inches` decimal(10,2) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sertop_products`
--

LOCK TABLES `sertop_products` WRITE;
/*!40000 ALTER TABLE `sertop_products` DISABLE KEYS */;
INSERT INTO `sertop_products` VALUES (1,'P1',8.00,57.72,'2024-11-28 21:57:04','2024-11-28 21:57:04'),(2,'P2',8.00,57.72,'2024-11-28 21:57:04','2024-11-28 21:57:04'),(3,'P3',8.00,61.05,'2024-11-28 21:57:04','2024-11-28 21:57:04'),(4,'P4',8.00,61.05,'2024-11-28 21:57:04','2024-11-28 21:57:04'),(5,'P5',8.00,61.05,'2024-11-28 21:57:04','2024-11-28 21:57:04'),(6,'P1',6.00,44.40,'2024-11-28 21:57:04','2024-11-28 21:57:04'),(7,'P2',6.00,44.40,'2024-11-28 21:57:04','2024-11-28 21:57:04'),(8,'P3',6.00,46.62,'2024-11-28 21:57:04','2024-11-28 21:57:04'),(9,'P4',6.00,46.62,'2024-11-28 21:57:04','2024-11-28 21:57:04'),(10,'P5',6.00,46.62,'2024-11-28 21:57:04','2024-11-28 21:57:04');
/*!40000 ALTER TABLE `sertop_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slant_products`
--

DROP TABLE IF EXISTS `slant_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slant_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(10) NOT NULL,
  `model` varchar(10) NOT NULL,
  `length_inches` decimal(10,2) NOT NULL,
  `breadth_inches` decimal(10,2) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `size_inches` int(11) DEFAULT 16,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slant_products`
--

LOCK TABLES `slant_products` WRITE;
/*!40000 ALTER TABLE `slant_products` DISABLE KEYS */;
INSERT INTO `slant_products` VALUES (1,'SLANT-P2','P2',0.00,0.00,NULL,73.26,'2024-11-28 21:57:04',16),(2,'SLANT-P3','P3',0.00,0.00,NULL,77.77,'2024-11-28 21:57:04',16),(3,'SLANT-P4','P4',0.00,0.00,NULL,81.03,'2024-11-28 21:57:04',16),(4,'SLANT-P5','P5',0.00,0.00,NULL,83.89,'2024-11-28 21:57:04',16);
/*!40000 ALTER TABLE `slant_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stone_color_rates`
--

DROP TABLE IF EXISTS `stone_color_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stone_color_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `color_name` varchar(100) NOT NULL,
  `price_increase_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stone_color_rates`
--

LOCK TABLES `stone_color_rates` WRITE;
/*!40000 ALTER TABLE `stone_color_rates` DISABLE KEYS */;
INSERT INTO `stone_color_rates` VALUES (1,'Black',0.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(2,'Coffee Brown',7.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(3,'Star Galaxy Black',40.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(4,'Bahama Blue',0.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(5,'NH Red',20.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(6,'Cats Eye',20.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(7,'Brown Wood',40.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(8,'SF Impala',65.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(9,'Blue Pearl',100.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(10,'Emeral Pearl',100.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(11,'rainforest Green',45.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(12,'Brazil Gold',35.00,'2024-11-28 21:57:03','2024-11-28 21:57:03'),(13,'Grey',0.00,'2024-11-28 21:57:03','2024-11-28 21:57:03');
/*!40000 ALTER TABLE `stone_color_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL DEFAULT 1,
  `customer_id` int(11) DEFAULT NULL,
  `quote_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tasks_customer` (`customer_id`),
  KEY `fk_tasks_quote` (`quote_id`),
  KEY `fk_tasks_user` (`user_id`),
  KEY `fk_tasks_creator` (`created_by`),
  CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_tasks_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tasks_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tasks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
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
  `email` varchar(100) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$vs3H7J.kjRb4H36xZP.QU.YAjhAPQJV0zW2PCTQFOpxvuAH2.v.UG','admin@example.com','admin','2024-11-28 21:57:03',NULL,1);
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

-- Dump completed on 2024-11-30 19:56:51
