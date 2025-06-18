-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
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
  `event_time` time DEFAULT NULL,
  `event_type` enum('event','task','meeting','reminder') DEFAULT 'event',
  `task_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_task_id` (`task_id`),
  CONSTRAINT `tbl_calendar_events_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tbl_tasks` (`task_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_calendar_events`
--

LOCK TABLES `tbl_calendar_events` WRITE;
/*!40000 ALTER TABLE `tbl_calendar_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_calendar_events` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_tasks`
--

LOCK TABLES `tbl_tasks` WRITE;
/*!40000 ALTER TABLE `tbl_tasks` DISABLE KEYS */;
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

-- Dump completed on 2025-06-18 15:19:14
