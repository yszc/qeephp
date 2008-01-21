-- MySQL Administrator dump 1.4
--
-- ------------------------------------------------------
-- Server version	5.0.45


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


--
-- Create schema test
--

CREATE DATABASE IF NOT EXISTS test;
USE test;

--
-- Definition of table `test`.`rx_posts`
--

DROP TABLE IF EXISTS `test`.`rx_posts`;
CREATE TABLE  `test`.`rx_posts` (
  `post_id` int(11) NOT NULL auto_increment,
  `title` varchar(300) NOT NULL,
  `body` text NOT NULL,
  `created` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `hint` int(11) NOT NULL default '0',
  PRIMARY KEY  (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `test`.`rx_posts`
--

/*!40000 ALTER TABLE `rx_posts` DISABLE KEYS */;
LOCK TABLES `rx_posts` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `rx_posts` ENABLE KEYS */;


--
-- Definition of table `test`.`rx_posts_seq`
--

DROP TABLE IF EXISTS `test`.`rx_posts_seq`;
CREATE TABLE  `test`.`rx_posts_seq` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `test`.`rx_posts_seq`
--

/*!40000 ALTER TABLE `rx_posts_seq` DISABLE KEYS */;
LOCK TABLES `rx_posts_seq` WRITE;
INSERT INTO `test`.`rx_posts_seq` VALUES  (17);
UNLOCK TABLES;
/*!40000 ALTER TABLE `rx_posts_seq` ENABLE KEYS */;


--
-- Definition of table `test`.`rx_users`
--

DROP TABLE IF EXISTS `test`.`rx_users`;
CREATE TABLE  `test`.`rx_users` (
  `user_id` char(8) NOT NULL,
  `username` varchar(32) NOT NULL,
  `password` varchar(64) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `test`.`rx_users`
--

/*!40000 ALTER TABLE `rx_users` DISABLE KEYS */;
LOCK TABLES `rx_users` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `rx_users` ENABLE KEYS */;


--
-- Definition of table `test`.`test_seq`
--

DROP TABLE IF EXISTS `test`.`test_seq`;
CREATE TABLE  `test`.`test_seq` (
  `id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `test`.`test_seq`
--

/*!40000 ALTER TABLE `test_seq` DISABLE KEYS */;
LOCK TABLES `test_seq` WRITE;
INSERT INTO `test`.`test_seq` VALUES  (43);
UNLOCK TABLES;
/*!40000 ALTER TABLE `test_seq` ENABLE KEYS */;




/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
