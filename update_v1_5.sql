ALTER TABLE `%prefix%chat_user` ADD `email` varchar(255) NOT NULL AFTER `password`;
ALTER TABLE `%prefix%chat_user` ADD `status` varchar(1) NOT NULL AFTER `email`;
