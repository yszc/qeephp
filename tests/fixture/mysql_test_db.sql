CREATE DATABASE IF NOT EXISTS test;
USE test;

CREATE TABLE  `test`.`rx_posts` (
  `post_id` int(11) NOT NULL auto_increment,
  `title` varchar(300) NOT NULL,
  `body` text NOT NULL,
  `created` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `hint` int(11) NOT NULL default '0',
  PRIMARY KEY  (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  `test`.`rx_users` (
  `user_id` char(8) NOT NULL,
  `username` varchar(32) NOT NULL,
  `password` varchar(64) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  `test`.`posts` (
  `post_id` int(11) NOT NULL auto_increment,
  `author_id` int(11) NOT NULL,
  `title` varchar(300) NOT NULL,
  `body` longtext NOT NULL,
  `created` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `exists_copy` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  `test`.`comments` (
  `comment_id` int(11) NOT NULL auto_increment,
  `post_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `body` longtext NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY  (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  `test`.`tags` (
  `tag_id` int(11) NOT NULL auto_increment,
  `tagname` varchar(60) NOT NULL,
  PRIMARY KEY  (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  `test`.`post_has_tags` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY  (`post_id`, `tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  `test`.`authors` (
  `author_id` int(11) NOT NULL auto_increment,
  `realname` varchar(80) NOT NULL,
  PRIMARY KEY  (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
