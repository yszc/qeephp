--- test: qdbo, qtable

DROP TABLE IF EXISTS `q_posts`;
CREATE TABLE IF NOT EXISTS `q_posts` (
  `post_id` int(11) NOT NULL auto_increment,
  `title` varchar(300) NOT NULL,
  `body` text NOT NULL,
  `created` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `hint` int(11) NOT NULL default '0',
  PRIMARY KEY  (`post_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--- test: qtablelink
