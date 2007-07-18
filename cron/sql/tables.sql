-- SQL Dump
-- Host: localhost
-- Server version: 5.0.22
-- PHP Version: 5.1.4-0.1
-- 
-- Database: `phpqagcov`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `local_builds`
-- 

DROP TABLE IF EXISTS `local_builds`;
CREATE TABLE `local_builds` (
`build_id` int(11) NOT NULL auto_increment,
`version_id` int(11) NOT NULL,
`build_datetime` datetime NOT NULL,
`build_numerrors` int(11) NOT NULL,
`build_numwarnings` int(11),
`build_numfailures` int(11),
`build_numleaks` int(11),
`build_percent_code_coverage` float,
`build_os_info` tinytext collate latin1_general_ci NOT NULL,
`build_compiler_info` tinytext collate latin1_general_ci NOT NULL,
PRIMARY KEY  (`build_id`),
KEY `version_id` (`version_id`),
KEY `build_datetime` (`build_datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Store local build statistics for graph building';

-- --------------------------------------------------------

-- 
-- Table structure for table `remote_builds`
-- 

DROP TABLE IF EXISTS `remote_builds`;
CREATE TABLE `remote_builds` (
`user_id` int(11) NOT NULL auto_increment,
`user_name` varchar(20) collate latin1_general_ci NOT NULL,
`user_pass` varchar(32) collate latin1_general_ci NOT NULL,
`user_email` varchar(50) collate latin1_general_ci,
`last_build_xml` longtext collate latin1_general_ci,
`last_user_os` varchar(100) collate latin1_general_ci,
`last_user_os_version` varchar(50) collate latin1_general_ci,
PRIMARY KEY  (`user_id`),
KEY `user_name` (`user_name`),
KEY `last_user_os` (`last_user_os`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Store general build information submitted from users';

-- --------------------------------------------------------

-- 
-- Table structure for table `versions`
-- 

DROP TABLE IF EXISTS `versions`;
CREATE TABLE `versions` (
`version_id` tinyint(4) NOT NULL auto_increment,
`version_name` varchar(30) collate latin1_general_ci NOT NULL,
`version_last_build_time` int(11) NOT NULL,
`version_last_attempted_build_date` datetime NOT NULL,
`version_last_successful_build_date` datetime NOT NULL,
PRIMARY KEY  (`version_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Store the PHP versions accepted and local build information';

