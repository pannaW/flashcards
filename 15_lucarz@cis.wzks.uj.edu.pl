-- MySQL dump 10.13  Distrib 5.5.55, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: local
-- ------------------------------------------------------
-- Server version	5.5.55-0ubuntu0.14.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `flashcards`
--

DROP TABLE IF EXISTS `flashcards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `flashcards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(45) NOT NULL,
  `definition` varchar(250) NOT NULL,
  `sets_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_flashcards_1_idx` (`sets_id`),
  CONSTRAINT `fk_flashcards_1` FOREIGN KEY (`sets_id`) REFERENCES `sets` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flashcards`
--

LOCK TABLES `flashcards` WRITE;
/*!40000 ALTER TABLE `flashcards` DISABLE KEYS */;
INSERT INTO `flashcards` VALUES (1,'Mutter','mother',5),(2,'Vater','fatherek',5),(3,'tienda','shop',1),(4,'ropa','ubrania',1),(5,'barrio','neighbourhood',6),(6,'hija','dother',6),(7,'hijo','son',6);
/*!40000 ALTER TABLE `flashcards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'ROLE_ADMIN'),(2,'ROLE_USER');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `set_has_tag`
--

DROP TABLE IF EXISTS `set_has_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `set_has_tag` (
  `sets_id` int(11) DEFAULT NULL,
  `tags_id` int(11) DEFAULT NULL,
  KEY `fk_sets_has_tags_1_idx` (`sets_id`),
  KEY `fk_sets_has_tags_2_idx` (`tags_id`),
  CONSTRAINT `fk_sets_has_tags_1` FOREIGN KEY (`sets_id`) REFERENCES `sets` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_sets_has_tags_2` FOREIGN KEY (`tags_id`) REFERENCES `tags` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `set_has_tag`
--

LOCK TABLES `set_has_tag` WRITE;
/*!40000 ALTER TABLE `set_has_tag` DISABLE KEYS */;
INSERT INTO `set_has_tag` VALUES (1,12),(6,2);
/*!40000 ALTER TABLE `set_has_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sets`
--

DROP TABLE IF EXISTS `sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `public` tinyint(1) DEFAULT NULL,
  `modified_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_sets_1_idx` (`users_id`),
  CONSTRAINT `fk_sets_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sets`
--

LOCK TABLES `sets` WRITE;
/*!40000 ALTER TABLE `sets` DISABLE KEYS */;
INSERT INTO `sets` VALUES (1,2,1,'2017-06-28 17:37:57','2017-06-26 23:19:44','Spanish'),(3,3,0,'2017-06-27 15:17:53','2017-06-27 15:01:46','German'),(4,3,1,'2017-06-27 16:39:02','2017-06-27 15:13:07','Japanease'),(5,2,0,'2017-06-28 17:38:08','2017-06-28 17:38:08','German'),(6,4,1,'2017-06-28 21:44:13','2017-06-28 21:44:13','Spanish');
/*!40000 ALTER TABLE `sets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `si_bookmarks`
--

DROP TABLE IF EXISTS `si_bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `si_bookmarks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `modified_at` datetime NOT NULL,
  `title` varchar(128) NOT NULL,
  `url` varchar(128) NOT NULL,
  `is_public` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_bookmarks_1` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `si_bookmarks`
--

LOCK TABLES `si_bookmarks` WRITE;
/*!40000 ALTER TABLE `si_bookmarks` DISABLE KEYS */;
INSERT INTO `si_bookmarks` VALUES (1,'0000-00-00 00:00:00','0000-00-00 00:00:00','PHP manual','http://php.net',0),(2,'0000-00-00 00:00:00','0000-00-00 00:00:00','Twig','http://twig.sensiolags.org',0);
/*!40000 ALTER TABLE `si_bookmarks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `si_bookmarks_tags`
--

DROP TABLE IF EXISTS `si_bookmarks_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `si_bookmarks_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bookmark_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_bookmarks_tags_1` (`bookmark_id`,`tag_id`),
  KEY `FK_bookmarks_tags_1` (`bookmark_id`),
  KEY `FK_bookmarks_tags_2` (`tag_id`),
  CONSTRAINT `FK_bookmarks_tags_1` FOREIGN KEY (`bookmark_id`) REFERENCES `si_bookmarks` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_bookmarks_tags_2` FOREIGN KEY (`tag_id`) REFERENCES `si_tags` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `si_bookmarks_tags`
--

LOCK TABLES `si_bookmarks_tags` WRITE;
/*!40000 ALTER TABLE `si_bookmarks_tags` DISABLE KEYS */;
INSERT INTO `si_bookmarks_tags` VALUES (1,1,1),(2,1,2),(7,2,1),(3,2,3),(4,2,4),(5,2,5),(6,2,6);
/*!40000 ALTER TABLE `si_bookmarks_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `si_tags`
--

DROP TABLE IF EXISTS `si_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `si_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_tags_1` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `si_tags`
--

LOCK TABLES `si_tags` WRITE;
/*!40000 ALTER TABLE `si_tags` DISABLE KEYS */;
INSERT INTO `si_tags` VALUES (10,'inny'),(2,'manual'),(12,'new'),(1,'PHP_edited'),(6,'Silex'),(4,'templates'),(7,'test'),(3,'tools'),(5,'Twig_edited');
/*!40000 ALTER TABLE `si_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,'anything'),(2,'clothes'),(4,'food'),(5,'verbs'),(6,'random'),(7,'nouns'),(8,'rando'),(9,'body'),(10,'hours'),(11,'days'),(12,'shopping');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(45) NOT NULL,
  `password` varchar(128) NOT NULL,
  `roles_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_UNIQUE` (`login`),
  KEY `fk_users_1_idx` (`roles_id`),
  CONSTRAINT `fk_users_1` FOREIGN KEY (`roles_id`) REFERENCES `roles` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'TestAdmin','$2y$13$HKEDPaxx5CJKCqkcGyqISO.chAi3CavHHMe66YmPLCjFZ5GsLfuNe',1),(2,'TestUser','$2y$13$B0ivUhGZ6fth4FLYMcICt.mgq4MKi8JqdaTYVncrDqgUIzUw6C1Ou',2),(3,'TestAdmin2','$2y$13$8lWcDELLYzDG34Hu2jnDoOsWI5ktKC1CfFJYmS1gGJKudxwnRutD.',1),(4,'TestUser2','$2y$13$rNFtoK9oXy2LM1eHVaCMnePWfutoy.J3m2XiACNKmYrnimSobzyGC',2);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_data`
--

DROP TABLE IF EXISTS `users_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `surname` varchar(45) NOT NULL,
  `email` varchar(45) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  KEY `fk_users_data_1_idx` (`users_id`),
  CONSTRAINT `fk_users_data_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_data`
--

LOCK TABLES `users_data` WRITE;
/*!40000 ALTER TABLE `users_data` DISABLE KEYS */;
INSERT INTO `users_data` VALUES (1,'User','Userowski','user@example.com',2),(2,'Tester','Testowicz','test@example.com',3),(3,'User','Userowski','user2@example.com',4);
/*!40000 ALTER TABLE `users_data` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-06-28 23:25:10
