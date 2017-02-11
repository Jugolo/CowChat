ALTER TABLE `%prefix%chat_user` ADD ip VARCHAR(255) AFTER avatar;

CREATE TABLE IF NOT EXISTS `%prefix%chat_plugin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dir` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=MyISAM;
