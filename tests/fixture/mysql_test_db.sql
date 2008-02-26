-- phpMyAdmin SQL Dump
-- version 2.11.4
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2008 年 02 月 26 日 08:35
-- 服务器版本: 5.0.45
-- PHP 版本: 5.2.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- 数据库: `test`
--

-- --------------------------------------------------------

--
-- 表的结构 `q_authors`
--

CREATE TABLE `q_authors` (
  `author_id` int(11) NOT NULL auto_increment,
  `name` varchar(40) NOT NULL,
  `contents_count` int(11) NOT NULL,
  `comments_count` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY  (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `q_books`
--

CREATE TABLE `q_books` (
  `book_code` char(8) NOT NULL,
  `title` varchar(240) NOT NULL,
  `intro` text NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY  (`book_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `q_books_has_authors`
--

CREATE TABLE `q_books_has_authors` (
  `book_code` char(8) NOT NULL,
  `author_id` int(11) NOT NULL,
  `remark` text NOT NULL,
  PRIMARY KEY  (`book_code`,`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `q_comments`
--

CREATE TABLE `q_comments` (
  `comment_id` int(11) NOT NULL auto_increment,
  `author_id` int(11) NOT NULL,
  `author_name` varchar(40) NOT NULL,
  `content_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY  (`comment_id`),
  KEY `author_id` (`author_id`),
  KEY `content_id` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `q_contents`
--

CREATE TABLE `q_contents` (
  `content_id` int(11) NOT NULL auto_increment,
  `author_id` int(11) NOT NULL,
  `title` varchar(240) NOT NULL,
  `comments_count` int(11) NOT NULL,
  `marks_avg` float NOT NULL,
  `created` int(11) NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`content_id`),
  KEY `author_id` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `q_contents_has_tags`
--

CREATE TABLE `q_contents_has_tags` (
  `content_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY  (`content_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `q_marks`
--

CREATE TABLE `q_marks` (
  `content_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `score` smallint(6) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY  (`content_id`,`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `q_posts`
--

CREATE TABLE `q_posts` (
  `post_id` int(11) NOT NULL auto_increment,
  `title` varchar(300) NOT NULL,
  `body` text NOT NULL,
  `created` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `hint` int(11) NOT NULL default '0',
  PRIMARY KEY  (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `q_tags`
--

CREATE TABLE `q_tags` (
  `tag_id` int(11) NOT NULL auto_increment,
  `name` varchar(40) NOT NULL,
  PRIMARY KEY  (`tag_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
