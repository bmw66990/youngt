-- phpMyAdmin SQL Dump
-- version 3.3.7
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2014 年 08 月 21 日 16:48
-- 服务器版本: 5.1.69
-- PHP 版本: 5.2.17p1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `youngt`
--

-- --------------------------------------------------------

--
-- 表的结构 `comment`
--

CREATE TABLE IF NOT EXISTS `comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '序号',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `team_id` int(11) NOT NULL COMMENT '项目id',
  `order_id` int(11) NOT NULL COMMENT '订单id',
  `content` text COMMENT '评论内容',
  `partner_id` int(11) NOT NULL COMMENT '商家id',
  `create_time` varchar(20) DEFAULT NULL COMMENT '评论时间',
  `isuser` enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '判断商家还是用户',
  `is_comment` enum('Y','N') NOT NULL DEFAULT 'N' COMMENT '是否评价',
  `cate_id` int(11) NOT NULL COMMENT '分类id',
  `comment_detail` varchar(255) DEFAULT NULL COMMENT '评论详情',
  `comment_num` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cate_id` (`cate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- 转存表中的数据 `comment`
--





 

insert into comment (user_id,team_id,order_id,partner_id,content,create_time)select user_id,team_id,id,partner_id,comment_content,comment_time from `order` where comment_content <>''

UPDATE comment SET is_comment='Y' where content <>''

UPDATE comment SET cate_id= (SELECT group_id FROM   team WHERE  team.id=comment.team_id)

insert into comment (user_id,team_id,order_id,partner_id,content,create_time)select user_id,team_id,id,partner_id,comment_content,comment_time from `order` where state='pay' or rstate='berefund'

truncate table comment 