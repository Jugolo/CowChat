CREATE TABLE IF NOT EXISTS `%prefix%chat_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `isBot` tinyint(1) NOT NULL,
  `time` datetime NOT NULL,
  `message` text NOT NULL,
  `isMsg` int(1) NOT NULL,
  `msgTo` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `%prefix%chat_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `mode` varchar(255) NOT NULL,
  `lastActiv` datetime NOT NULL,
  `isInAktiv` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `%prefix%chat_name`(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `isPriv` tinyint(1) NOT NULL,
  `title` text NOT NULL,
  `members` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `%prefix%chat_ignore`(
`id` int(11) NOT NULL AUTO_INCREMENT,
`uid` int(11) NOT NULL,
`ignore` int(11) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `%prefix%chat_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `nick` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `%prefix%chat_smylie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) NOT NULL,
  `url` text NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `%prefix%chat_plugin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dir` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=MyISAM;

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

INSERT INTO `%prefix%chat_name` (`id`,`name`,`isPriv`,`title`) VALUES('1','Bot','".No."','Channel to connect system and user togeter');
