-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: task_manager
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tbl_calendar_events`
--

DROP TABLE IF EXISTS `tbl_calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_calendar_events` (
  `event_id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_title` varchar(200) NOT NULL,
  `event_description` text,
  `event_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `event_type` varchar(50) DEFAULT 'event',
  `task_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_start_time` (`start_time`),
  KEY `idx_end_time` (`end_time`),
  KEY `idx_date_range` (`event_date`,`end_date`),
  CONSTRAINT `tbl_calendar_events_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tbl_tasks` (`task_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_calendar_events`
--

LOCK TABLES `tbl_calendar_events` WRITE;
/*!40000 ALTER TABLE `tbl_calendar_events` DISABLE KEYS */;
INSERT INTO `tbl_calendar_events` VALUES (6,'Ojii','','2025-07-05',NULL,'02:24:00',NULL,'softkit',NULL,'2025-07-04 19:24:39','2025-07-04 19:26:30');
/*!40000 ALTER TABLE `tbl_calendar_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_event_types`
--

DROP TABLE IF EXISTS `tbl_event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_event_types` (
  `event_type_id` int NOT NULL AUTO_INCREMENT,
  `type_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_color` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#007bff',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_type_id`),
  UNIQUE KEY `type_code` (`type_code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_event_types`
--

LOCK TABLES `tbl_event_types` WRITE;
/*!40000 ALTER TABLE `tbl_event_types` DISABLE KEYS */;
INSERT INTO `tbl_event_types` VALUES (1,'event','General Event','#3fb950',1,7,'2025-07-04 19:01:41','2025-07-04 19:31:42'),(2,'task','Task Deadline','#388bfd',1,5,'2025-07-04 19:01:41','2025-07-04 19:23:08'),(3,'meeting','Meeting','#ffff00',1,4,'2025-07-04 19:01:41','2025-07-04 19:22:59'),(4,'reminder','Reminder','#a5a5f0',1,6,'2025-07-04 19:01:41','2025-07-04 19:21:07'),(9,'office','Office Work','#ff0000',1,1,'2025-07-04 19:13:25','2025-07-04 19:25:41'),(10,'softkit','SoftkIT Work','#26debe',1,2,'2025-07-04 19:16:25','2025-07-04 19:22:38'),(11,'code','Code Task','#ff7b00',1,3,'2025-07-04 19:22:17','2025-07-04 19:22:17');
/*!40000 ALTER TABLE `tbl_event_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_lists`
--

DROP TABLE IF EXISTS `tbl_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_lists` (
  `list_id` int unsigned NOT NULL AUTO_INCREMENT,
  `list_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `list_description` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`list_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_lists`
--

LOCK TABLES `tbl_lists` WRITE;
/*!40000 ALTER TABLE `tbl_lists` DISABLE KEYS */;
INSERT INTO `tbl_lists` VALUES (17,'Personal','Personal tasks'),(18,'Minigh',''),(19,'Ojii',''),(20,'Oshin',''),(21,'Lehaq',''),(22,'SoftkIT',''),(23,'Epic Gadget','');
/*!40000 ALTER TABLE `tbl_lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_tasks`
--

DROP TABLE IF EXISTS `tbl_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_tasks` (
  `task_id` int unsigned NOT NULL AUTO_INCREMENT,
  `task_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `task_description` text COLLATE utf8mb4_general_ci NOT NULL,
  `list_id` int NOT NULL,
  `priority` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `deadline` date NOT NULL,
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_tasks`
--

LOCK TABLES `tbl_tasks` WRITE;
/*!40000 ALTER TABLE `tbl_tasks` DISABLE KEYS */;
INSERT INTO `tbl_tasks` VALUES (32,'Motor Cycle Battery','',23,'High','2025-07-25');
/*!40000 ALTER TABLE `tbl_tasks` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-06 16:05:24
