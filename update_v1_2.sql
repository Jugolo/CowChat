ALTER TABLE `%prefix%chat_user` ADD `email` varchar(255) NOT NULL AFTER `password`;
ALTER TABLE `%prefix%chat_user` ADD `status` varchar(1) NOT NULL AFTER `email`;

CREATE TABLE IF NOT EXISTS `%prefix%chat_updater` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dir` varchar(255) NOT NULL,
  `version` varchar(255) NOT NULL,
  `last_check` int(11) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `repo` varchar(255) NOT NULL,
  PRIMARY KEY(`id`)
  ) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `%prefix%chat_error` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `errno` int(11) NOT NULL,
  `errstr` varchar(255) NOT NULL,
  `errfile` varchar(255) NOT NULL,
  `errline` int(11) NOT NULL,
  `seen` int(1) NOT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=MyISAM;
